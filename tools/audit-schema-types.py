#!/usr/bin/env python3
"""Compare the schema the migrations declare against the live production schema.

    python3 tools/audit-schema-types.py

Reads every `database/migrations/*.php` (up-phase only) and the recorded live
schema in `database/schema/production_column_types.txt`, then reports three
classes of divergence.

Comparison is at the level of the type *family*, deliberately. That is where
breakage lives: a column that is `boolean` in production while the model compares
it to 'pending' raises "operator does not exist: boolean = text" at runtime,
whereas varchar(255) vs text, or numeric(10,2) vs numeric, breaks nothing.
Reporting the cosmetic cases too would bury the ones that matter.

Refreshing the production fixture (Supabase SQL editor or the connector):

    SELECT table_name || '|' || string_agg(
             column_name || ':' || CASE
               WHEN data_type IN ('character varying','character','text') THEN 's'
               WHEN data_type LIKE 'timestamp%' OR data_type = 'date'
                 OR data_type LIKE 'time%' THEN 't'
               WHEN data_type IN ('numeric','integer','bigint','smallint',
                 'double precision','real') THEN 'n'
               WHEN data_type = 'boolean' THEN 'b'
               WHEN data_type = 'uuid' THEN 'u'
               WHEN data_type IN ('json','jsonb') THEN 'j'
               WHEN data_type = 'bytea' THEN 'y'
               WHEN data_type = 'inet' THEN 'i'
               ELSE 'X:' || data_type END
             || CASE WHEN is_nullable = 'NO' THEN '!' ELSE '' END,
             ',' ORDER BY ordinal_position)
    FROM information_schema.columns
    WHERE table_schema = 'public'
    GROUP BY table_name ORDER BY table_name;

Known blind spots, so a clean run is not over-read as proof:
  * Only Blueprint calls are understood. A type produced by a raw
    `DB::statement()` — `audit_logs.ip_address`, narrowed to inet on Postgres —
    is invisible here and shows up as a false mismatch.
  * Adding a Blueprint method to the tree without adding it to SIMPLE/MULTI
    silently drops columns. The completeness check is to extract every distinct
    method used under database/migrations and diff it against the maps below;
    that is how `double` (46 columns, every price field), `bigIncrements` and
    `id()` were found missing.
"""
"""Parse database/migrations/*.php into the Postgres schema they should produce.

Only the type *family* is derived, because that is where breakage lives: a column
that is `boolean` in production while the model compares it to 'pending' raises
"operator does not exist: boolean = text" at runtime, whereas varchar(255) vs text
or numeric(10,2) vs numeric are cosmetic. Nullability, precision and defaults are
deliberately ignored here so the report keeps its signal.
"""
import io, re, glob, os, json

FAMILY = {
    'varchar':'string','char':'string','text':'string','bpchar':'string',
    'date':'temporal','timestamp':'temporal','timestamptz':'temporal','time':'temporal','timetz':'temporal',
    'numeric':'numeric','integer':'numeric','bigint':'numeric','smallint':'numeric','float8':'numeric','real':'numeric',
    'boolean':'bool','uuid':'uuid','json':'json','jsonb':'json','bytea':'binary','inet':'net',
}

def fam(t):
    t = re.sub(r'\(.*\)', '', t).strip().lower()
    return FAMILY.get(t, t)

# Blueprint method -> (pg type, is_multi_column)
SIMPLE = {
    'id':'bigint','foreignId':'bigint','foreignUuid':'uuid','uuid':'uuid','ulid':'char(26)',
    'unsignedBigInteger':'bigint','bigInteger':'bigint','integer':'integer','int':'integer',
    'smallInteger':'smallint','tinyInteger':'smallint','mediumInteger':'integer',
    'boolean':'boolean','string':'varchar(255)','char':'char(255)','text':'text',
    'mediumText':'text','longText':'text','json':'json','jsonb':'jsonb',
    'decimal':'numeric(8,2)','date':'date','dateTime':'timestamp','timestamp':'timestamp',
    'timestampTz':'timestamptz','time':'time','timeTz':'timetz','binary':'bytea',
    'ipAddress':'varchar(45)','macAddress':'varchar(17)','enum':'varchar(255)',
    'year':'integer','unsignedDecimal':'numeric(8,2)','unsignedInteger':'integer',
    'unsignedTinyInteger':'smallint','unsignedSmallInteger':'smallint',
    'rememberToken':'varchar(100)',
    # Laravel maps both to `double precision` on Postgres; production stores
    # these as unbounded `numeric`, which is the same family.
    'double':'float8','float':'float8',
    'bigIncrements':'bigint','increments':'integer','tinyIncrements':'smallint',
}
# methods that create several columns at once
MULTI = {
    'timestamps': [('created_at','timestamptz'), ('updated_at','timestamptz')],
    'nullableTimestamps': [('created_at','timestamptz'), ('updated_at','timestamptz')],
    'softDeletes': [('deleted_at','timestamptz')],
    'softDeletesTz': [('deleted_at','timestamptz')],
    'timestampsTz': [('created_at','timestamptz'), ('updated_at','timestamptz')],
    'datetimes': [('created_at','timestamptz'), ('updated_at','timestamptz')],
}

def blocks(src, kind):
    """Yield (table, body) for Schema::create / Schema::table with brace matching."""
    out = []
    for m in re.finditer(r"Schema::%s\(\s*'([a-zA-Z_0-9.]+)'\s*,\s*function\s*\(\s*Blueprint\s+\$(\w+)\s*\)\s*(?:use\s*\([^)]*\)\s*)?\{" % kind, src):
        name, var = m.group(1), m.group(2)
        i = m.end() - 1
        depth = 0
        for j in range(i, len(src)):
            if src[j] == '{': depth += 1
            elif src[j] == '}':
                depth -= 1
                if depth == 0:
                    out.append((name, src[i:j], var))
                    break
    return out

def columns_from(body, var):
    cols = {}
    pat = re.compile(r"\$" + re.escape(var) + r"->(\w+)\(\s*(?:'([^']*)')?")
    for m in pat.finditer(body):
        meth, name = m.group(1), m.group(2)
        # the whole chained call, to see ->nullable() and an explicit length
        rest = body[m.end():]
        nxt = rest.find('$' + var + '->')
        chain = rest[:nxt if nxt >= 0 else len(rest)]
        if meth in MULTI:
            for cn, ct in MULTI[meth]:
                cols[cn] = ct
            continue
        if meth in ('morphs', 'nullableMorphs', 'foreignMorphs'):
            if name:
                cols[name + '_type'] = 'varchar(255)'
                cols[name + '_id'] = 'bigint'
            continue
        if meth in ('uuidMorphs', 'nullableUuidMorphs'):
            if name:
                cols[name + '_type'] = 'varchar(255)'
                cols[name + '_id'] = 'uuid'
            continue
        if meth in ('dropColumn', 'dropIfExists', 'dropForeign', 'dropIndex', 'dropUnique',
                    'dropConstrainedForeignId', 'index', 'unique', 'primary', 'foreign',
                    'comment', 'after', 'rename', 'renameColumn', 'renameIndex'):
            if meth == 'dropColumn' and name:
                cols.pop(name, None)
            if meth == 'dropConstrainedForeignId' and name:
                cols.pop(name, None)
            continue
        if meth not in SIMPLE:
            continue
        if not name:
            # $table->id() takes no argument but still creates the primary key.
            if meth == 'id':
                cols['id'] = 'bigint'
            continue
        t = SIMPLE[meth]
        # explicit length/precision arguments
        argm = re.match(r"\s*,\s*(\d+)", rest)
        if meth in ('string', 'char') and argm:
            t = 'varchar(%s)' % argm.group(1) if meth == 'string' else 'char(%s)' % argm.group(1)
        if meth in ('decimal', 'unsignedDecimal'):
            pm = re.match(r"\s*,\s*(\d+)\s*,\s*(\d+)", rest)
            t = 'numeric(%s,%s)' % (pm.group(1), pm.group(2)) if pm else 'numeric(8,2)'
        cols[name] = t
    return cols

def build():
    expected = {}
    for f in sorted(glob.glob('database/migrations/*.php')):
        src = io.open(f, encoding='utf-8').read()
        # only up(): cut the file at "public function down"
        up_end = src.find('public function down')
        up = src[:up_end] if up_end > 0 else src
        for name, body, var in blocks(up, 'create'):
            expected.setdefault(name, {}).update(columns_from(body, var))
        for name, body, var in blocks(up, 'table'):
            expected.setdefault(name, {}).update(columns_from(body, var))
    return expected

# ---------------------------------------------------------------------------
# Divergences that are understood and accepted, with the reason. Anything not
# listed here is a finding that needs a decision.
# ---------------------------------------------------------------------------
ACCEPTED_MISMATCHES = {
    # Declared as varchar by Blueprint, then narrowed to inet on Postgres by a
    # raw DB::statement in the same migration. The parser cannot see raw SQL.
    'audit_logs.ip_address': 'narrowed to inet by DB::statement in the migration',
}

ACCEPTED_MIGRATION_ONLY = {
    # Declared by a migration, absent from production, and written by no code
    # path. A fresh deploy simply gains an unused nullable column.
    'auto_order_suggestions.enabled': 'declared but unused; AutoOrderRule owns the live column',
    'auto_order_suggestions.max_stock_level': 'declared but unused; AutoOrderRule owns the live column',
    'auto_order_suggestions.min_stock_level': 'declared but unused; AutoOrderRule owns the live column',
    'goods_received_notes.verified_by': 'declared but never written',
    'prescriptions.reviewed_by': 'declared but never written',
    'purchase_orders.approved_by': 'declared but never written',
    'purchase_orders.rejected_by': 'declared but never written',
    'traceability_logs.user_id': 'declared but never written',
}

ACCEPTED_PRODUCTION_ONLY = {
    # Legacy columns from an earlier design of the same table. Verified against
    # every model: none is in $fillable/$casts and none is referenced by the
    # query builder, so a fresh deploy that lacks them loses nothing.
    'approval_workflows.description': 'legacy design; ApprovalWorkflow::$fillable does not include it',
    'approval_workflows.is_active': 'legacy design; ApprovalWorkflow::$fillable does not include it',
    'approval_workflows.name': 'legacy design; ApprovalWorkflow::$fillable does not include it',
    'approval_workflows.workflow_type': 'legacy design; superseded by approval_workflows.type',
    'approval_workflows.steps': 'shadowed by the steps() HasMany relation; unreachable',
    'banners.link': 'not fillable, not queried',
    'banners.sort_order': 'not fillable, not queried',
    'business_categories.icon': 'not fillable, not queried',
    'gateways.code': 'not fillable, not queried',
    'insurance_coverages.coverage_name': 'not fillable, not queried',
    'inventory_turnover_reports.data': 'superseded by the typed report_* columns',
    'inventory_turnover_reports.report_date': 'superseded by period_start/period_end',
    'languages.code': 'not fillable, not queried',
    'plans.billing_cycle': 'not fillable, not queried',
    'plans.currency': 'not fillable, not queried',
    'plans.slug': 'not fillable, not queried',
    'plans.sort_order': 'not fillable, not queried',
    'prediction_settings.key': 'superseded by the typed prediction_* columns',
    'prediction_settings.value': 'superseded by the typed prediction_* columns',
    'product_inventory_analysis.analysis': 'superseded by the typed analysis columns',
    'receipt_settings.footer': 'superseded by receipt_footer',
    'receipt_settings.header': 'superseded by receipt_header',
    'receipt_settings.show_logo': 'not fillable, not queried',
    'receipts.total': 'not fillable, not queried',
    'sales_forecasts.forecast': 'superseded by the typed forecast_* columns',
    'stock_audit_details.counted_quantity': 'not fillable; physical_quantity is the live column',
    'stock_reconciliations.notes': 'not fillable, not queried',
    'stock_reconciliations.status': 'not fillable, not queried',
    'supplier_performance.notes': 'not fillable, not queried',
    'supplier_performance.rating': 'not fillable; supplier_ratings.rating is the queried column',
    'supplier_ratings.comment': 'not fillable; review is the live column',
    'suppliers.name': 'superseded by suppliers.company_name, which is what $fillable declares',
    'suppliers.status': 'superseded by suppliers.is_active, which is what $fillable declares',
    'tenant_payment_settings.status': 'superseded by tenant_payment_settings.is_active',
}

FIXTURE = 'database/schema/production_column_types.txt'
SKIP_TABLES = {'migrations'}
REPO_ONLY_TABLES = {'password_resets', 'password_reset_tokens', 'users'}


# The fixture records families as single letters; the parser spells them out.
# Normalising both sides to the spelled-out form is what makes the comparison
# meaningful -- comparing 'string' against 's' flags every column in the schema.
LETTER_TO_FAMILY = {
    's': 'string', 't': 'temporal', 'n': 'numeric', 'b': 'bool',
    'u': 'uuid', 'j': 'json', 'y': 'binary', 'i': 'net',
}


def read_production(path):
    prod = {}
    for line in io.open(path, encoding='utf-8'):
        line = line.strip()
        if not line or '|' not in line or line.startswith('#'):
            continue
        table, cols = line.split('|', 1)
        prod[table] = {}
        for entry in cols.split(','):
            if not entry:
                continue
            name, spec = entry.split(':')
            spec = spec.rstrip('!')
            prod[table][name] = LETTER_TO_FAMILY.get(spec, '?' + spec)
    return prod


def report(expected, prod):
    hard, mig_only, prod_only, table_gaps = [], [], [], []

    for table in sorted(set(expected) | set(prod)):
        if table in SKIP_TABLES:
            continue
        exp, live = expected.get(table), prod.get(table)
        if exp and not live:
            if table not in REPO_ONLY_TABLES:
                table_gaps.append(table + ' (declared by a migration, absent from production)')
            continue
        if live and not exp:
            table_gaps.append(table + ' (in production, created by no migration)')
            continue
        for col in sorted(set(exp) | set(live)):
            key = table + '.' + col
            et, pt = exp.get(col), live.get(col)
            if et and not pt:
                mig_only.append((key, et))
            elif pt and not et:
                prod_only.append((key, pt))
            elif fam(et) != pt:
                hard.append((key, et, pt))

    def section(title, rows, accepted, render):
        novel = [r for r in rows if r[0] not in accepted]
        print('=' * 78)
        print('%s: %d new, %d accepted' % (title, len(novel), len(rows) - len(novel)))
        for row in novel:
            print('    ' + render(row))
        for row in rows:
            if row[0] in accepted:
                print('    [accepted] %s -- %s' % (row[0], accepted[row[0]]))
        return novel

    a = section('HARD TYPE MISMATCHES (different families)', hard, ACCEPTED_MISMATCHES,
                lambda r: '%s: migrations=%s (%s)  production=%s' % (r[0], r[1], fam(r[1]), r[2]))
    b = section('COLUMNS DECLARED BY A MIGRATION, ABSENT FROM PRODUCTION', mig_only,
                ACCEPTED_MIGRATION_ONLY, lambda r: '%s = %s' % (r[0], r[1]))
    c = section('COLUMNS IN PRODUCTION, DECLARED BY NO MIGRATION', prod_only,
                ACCEPTED_PRODUCTION_ONLY, lambda r: '%s = %s' % (r[0], r[1]))
    print('=' * 78)
    print('TABLE-LEVEL GAPS: %d' % len(table_gaps))
    for gap in table_gaps:
        print('    ' + gap)

    return len(a) + len(b) + len(c) + len(table_gaps)


def main():
    import sys
    expected = build()
    print('migrations declare %d tables / %d columns' % (
        len(expected), sum(len(v) for v in expected.values())))
    try:
        prod = read_production(FIXTURE)
    except FileNotFoundError:
        print('missing %s -- refresh it with the query in this file\'s docstring' % FIXTURE)
        return 2
    print('production has %d tables / %d columns' % (
        len(prod), sum(len(v) for v in prod.values())))
    findings = report(expected, prod)
    print()
    print('NEW FINDINGS: %d' % findings)
    return 1 if findings else 0


if __name__ == '__main__':
    import sys
    sys.exit(main())
