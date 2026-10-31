#!/usr/bin/env python3
"""Generate Eloquent models from database/supabase_schema.txt (live Supabase schema).

Source of truth: the actual Postgres schema of project atvttthrqsybjjwrbiyl.
Re-run after any migration:  python3 tools/generate-models.py
"""
import os
import re

HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(HERE, "..", "database", "supabase_schema.txt")
OUT = os.path.join(HERE, "..", "app", "Models")


def studly(s):
    return "".join(p.capitalize() for p in re.split(r"[_\-]", s) if p)


def singular(t):
    if t.endswith("ies"):
        return t[:-3] + "y"
    if t.endswith(("sses", "ches", "shes", "xes")):
        return t[:-2]
    if t.endswith("s") and not t.endswith("ss"):
        return t[:-1]
    return t


def model_name(t):
    return studly(singular(t))


CASTS = {
    "int8": "integer", "int4": "integer", "int2": "integer",
    "numeric": "decimal:2", "float8": "float", "float4": "float",
    "bool": "boolean", "jsonb": "array", "json": "array",
    "timestamptz": "datetime", "timestamp": "datetime", "date": "date",
}

HASMANY = {"businesses", "sales", "purchases", "products", "parties", "warehouses", "branches"}
MONEY_HINT = re.compile(r"(amount|price|total|due|balance|cost|discount|tax|profit|rate|limit|value|period_|threshold|score|demand|stock_level)")
COUNT_HINT = re.compile(r"^(quantity|quantities|qty|points|count|usage_count|refill_number|print_count|days|sort_order|trial_days|lead_time_days|alert_frequency_hours|min_stock_for_fefo|referral_count|prescription_count|total_checks|passed|failed)$")

schema = {}
with open(SRC, encoding="utf-8") as fh:
    for line in fh:
        line = line.strip()
        if not line:
            continue
        table, cols = line.split(":", 1)
        pairs = []
        for chunk in cols.split(","):
            chunk = chunk.strip()
            if not chunk:
                continue
            name, typ = chunk.rsplit(" ", 1)
            pairs.append((name.strip(), typ.strip()))
        schema[table.strip()] = pairs


def casts_for(cols):
    casts = {}
    for name, typ in cols:
        if name in ("id", "created_at", "updated_at", "deleted_at", "business_id", "branch_id"):
            continue
        cast = CASTS.get(typ)
        if not cast:
            continue
        if cast == "decimal:2" and (COUNT_HINT.match(name) or not MONEY_HINT.search(name)):
            cast = "decimal:4"
        casts[name] = cast
    return casts


MODEL_TO_TABLE = {model_name(t): t for t in schema}

USER_FK = {
    "user_id", "created_by", "updated_by", "printed_by", "opened_by", "closed_by",
    "actor_user_id", "approved_by", "manager_user_id", "dispensed_by",
    "performed_by", "generated_by", "medical_rep_id",
}


def belongs_to(table, cols):
    """Map *_id columns to models. AppUser is the auth identity (uuid FKs)."""
    rels = {}
    for name, _typ in cols:
        if not name.endswith("_id") or name == table + "_id":
            continue
        if name in USER_FK:
            rels[name] = "AppUser"
            continue
        candidate = studly(singular(name[:-3]))
        if candidate in MODEL_TO_TABLE:
            rels[name] = candidate
            continue
        candidate_s = candidate + "s"
        if candidate_s in MODEL_TO_TABLE:
            rels[name] = candidate_s
    return rels


os.makedirs(OUT, exist_ok=True)
written = []

for table in sorted(schema):
    cols = schema[table]
    names = [n for n, _ in cols]
    types = dict(cols)
    cls = model_name(table)
    is_user = table == "app_users"

    imports = []
    if is_user:
        imports += ["use Illuminate\\Foundation\\Auth\\User as Authenticatable;",
                    "use Illuminate\\Notifications\\Notifiable;"]
    else:
        imports += ["use Illuminate\\Database\\Eloquent\\Model;"]
    soft = "deleted_at" in names
    if soft:
        imports.append("use Illuminate\\Database\\Eloquent\\SoftDeletes;")

    fillable = [n for n in names if n not in ("id", "created_at", "updated_at", "deleted_at")]
    casts = casts_for(cols)
    rels = belongs_to(table, cols)

    out = ["<?php", "", "namespace App\\Models;", ""] + imports
    if rels:
        out.append("use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;")
    if table in HASMANY:
        out.append("use Illuminate\\Database\\Eloquent\\Relations\\HasMany;")
    out += ["", "/**",
            f" * {cls} - table `{table}` (Supabase Postgres).",
            " * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.",
            " */",
            f"class {cls} extends {'Authenticatable' if is_user else 'Model'}", "{"]
    if soft:
        out += ["    use SoftDeletes;", ""]
    if is_user:
        out += ["    use Notifiable;", ""]
    out += [f"    protected $table = '{table}';", ""]

    if is_user:
        out += ["    protected $keyType = 'string';", "", "    public $incrementing = false;", ""]
    elif types[names[0]] != "int8":
        out += [f"    protected $primaryKey = '{names[0]}';",
                "    protected $keyType = 'string';", "", "    public $incrementing = false;", ""]

    if "created_at" not in names and "updated_at" not in names:
        out += ["    public $timestamps = false;", ""]
    elif "created_at" in names and "updated_at" not in names:
        out += ["    const UPDATED_AT = null;", ""]

    out += ["    protected $fillable = ["]
    out += [f"        '{f}'," for f in fillable]
    out += ["    ];", ""]

    if casts:
        out += ["    protected $casts = ["]
        out += [f"        '{k}' => '{v}'," for k, v in casts.items()]
        out += ["    ];", ""]

    if table == "insurance_companies":
        out += ["    protected $hidden = ['api_credentials'];", ""]

    for col, rel in rels.items():
        method = rel[0].lower() + rel[1:]
        out += [f"    public function {method}(): BelongsTo", "    {",
                f"        return $this->belongsTo({rel}::class, '{col}');", "    }", ""]

    if table in HASMANY:
        fk_name = singular(table) + "_id"
        children = sorted({t for t, cs in schema.items() if fk_name in [n for n, _ in cs]})
        for child_table in children:
            child = model_name(child_table)
            method = child[0].lower() + child[1:]
            if not method.endswith("s"):
                method += "s"
            out += [f"    public function {method}(): HasMany", "    {",
                    f"        return $this->hasMany({child}::class, '{fk_name}');", "    }", ""]

    while out and out[-1] == "":
        out.pop()
    out += ["}", ""]

    with open(os.path.join(OUT, cls + ".php"), "w", encoding="utf-8") as fh:
        fh.write("\n".join(out))
    written.append((cls, table, len(cols), len(rels)))

print(f"generated {len(written)} models -> {os.path.abspath(OUT)}")
for cls, table, ncols, nrels in written:
    print(f"  {cls:34s} <- {table:30s} cols={ncols:3d} rels={nrels}")
