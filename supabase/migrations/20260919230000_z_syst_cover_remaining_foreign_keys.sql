-- Z-Syst production performance hardening: cover remaining foreign keys.
-- Creates a simple btree index for each FK that is not already covered by
-- an existing index whose leading columns match the FK columns.

do $$
declare
  fk record;
  idx_name text;
  covered boolean;
begin
  for fk in
    select
      n.nspname as schema_name,
      c.relname as table_name,
      con.conname as constraint_name,
      con.conrelid as table_oid,
      con.conkey as fk_cols
    from pg_constraint con
    join pg_class c on c.oid = con.conrelid
    join pg_namespace n on n.oid = c.relnamespace
    where con.contype = 'f'
      and n.nspname = 'public'
      and c.relkind = 'r'
  loop
    select exists (
      select 1
      from pg_index i
      where i.indrelid = fk.table_oid
        and i.indisvalid
        and i.indisready
        and (
          select array_agg(x order by ordinality)
          from unnest(i.indkey::int[]) with ordinality as u(x, ordinality)
          where ordinality <= array_length(fk.fk_cols, 1)
        ) = fk.fk_cols::int[]
    ) into covered;

    if not covered then
      idx_name := left('idx_' || fk.table_name || '_fk_' || md5(fk.constraint_name), 63);
      execute format(
        'create index if not exists %I on %I.%I using btree (%s)',
        idx_name,
        fk.schema_name,
        fk.table_name,
        (
          select string_agg(format('%I', a.attname), ', ' order by k.ord)
          from unnest(fk.fk_cols) with ordinality as k(attnum, ord)
          join pg_attribute a on a.attrelid = fk.table_oid and a.attnum = k.attnum
        )
      );
    end if;
  end loop;
end $$;
