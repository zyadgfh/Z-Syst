#!/usr/bin/env python3
"""
Supabase Web Bridge — a tiny browser UI for connecting to a Supabase
PostgreSQL database from this sandbox.

Why it exists: outbound HTTPS to supabase.com is blocked in this
environment, but raw TCP to Supabase's Postgres endpoints (direct host /
pooler on 5432/6543) works. This bridge connects server-side with psycopg2
and exposes a small web UI through the sandbox live preview.

Credentials are kept in memory only — never written to disk or logged.
"""

import json
import threading
import os
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

import psycopg2
import psycopg2.extras

HERE = os.path.dirname(os.path.abspath(__file__))
REPO_ROOT = os.path.abspath(os.path.join(HERE, '..', '..'))
UPDATE_SQL_PATH = os.path.join(REPO_ROOT, 'supabase', 'update_2026_10_01.sql')

PORT = int(os.environ.get('BRIDGE_PORT', '8000'))

# ---------------------------------------------------------------------------
# In-memory connection holder (single shared connection, guarded by a lock)
# ---------------------------------------------------------------------------
class ConnHolder:
    def __init__(self):
        self.lock = threading.Lock()
        self.conn = None
        self.info = {}

    def connect(self, host, port, user, password, database, readonly):
        with self.lock:
            self.close_locked()
            conn = psycopg2.connect(
                host=host,
                port=int(port),
                user=user,
                password=password,
                dbname=database,
                connect_timeout=15,
                sslmode='require',
            )
            conn.autocommit = True
            cur = conn.cursor()
            cur.execute("SET statement_timeout = '30s'")
            if readonly:
                cur.execute('SET default_transaction_read_only = on')
            cur.execute('SELECT version(), current_user, current_database()')
            version, cur_user, cur_db = cur.fetchone()
            cur.close()
            self.conn = conn
            self.info = {
                'host': host,
                'port': int(port),
                'user': cur_user,
                'database': cur_db,
                'version': version,
                'readonly': bool(readonly),
            }
            return self.info

    def close_locked(self):
        if self.conn is not None:
            try:
                self.conn.close()
            except Exception:
                pass
            self.conn = None
            self.info = {}

    def close(self):
        with self.lock:
            self.close_locked()

    def run(self, fn):
        with self.lock:
            if self.conn is None or self.conn.closed:
                raise RuntimeError('not_connected')
            return fn(self.conn)


HOLDER = ConnHolder()

# ---------------------------------------------------------------------------
# DB operations
# ---------------------------------------------------------------------------
MAX_ROWS = 500


def op_tables(conn):
    cur = conn.cursor()
    cur.execute("""
        SELECT c.relname AS table_name,
               GREATEST(c.reltuples::bigint, 0) AS approx_rows
        FROM pg_class c
        JOIN pg_namespace n ON n.oid = c.relnamespace
        WHERE n.nspname = 'public' AND c.relkind = 'r'
        ORDER BY c.relname
    """)
    rows = cur.fetchall()
    cur.close()
    return [{'table': r[0], 'approx_rows': int(r[1])} for r in rows]


def op_describe(conn, table):
    cur = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)
    cur.execute(
        """
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = %s
        ORDER BY ordinal_position
        """,
        (table,),
    )
    cols = [dict(r) for r in cur.fetchall()]
    cur.execute(
        'SELECT * FROM public."{}" LIMIT 50'.format(table.replace('"', '""'))
    )
    data_cols = [d[0] for d in cur.description] if cur.description else []
    data_rows = [list(r) for r in cur.fetchall()]
    cur.close()
    return {'columns': cols, 'data_columns': data_cols, 'data_rows': data_rows}


def _sanitize_cell(v):
    if v is None or isinstance(v, (int, float, bool)):
        return v
    s = str(v)
    return s if len(s) <= 2000 else s[:2000] + '…'


def op_query(conn, sql):
    cur = conn.cursor()
    cur.execute(sql)
    if cur.description is not None:
        cols = [d[0] for d in cur.description]
        rows = [[_sanitize_cell(c) for c in r] for r in cur.fetchmany(MAX_ROWS)]
        truncated = cur.fetchone() is not None
        cur.close()
        return {'kind': 'rows', 'columns': cols, 'rows': rows, 'truncated': truncated}
    rowcount = cur.rowcount
    cur.close()
    return {'kind': 'status', 'rowcount': rowcount}


def op_apply_update(conn):
    with open(UPDATE_SQL_PATH, 'r', encoding='utf-8') as f:
        script = f.read()
    del conn.notices[:]
    cur = conn.cursor()
    cur.execute(script)
    # the script ends with a read-only verification SELECT (RLS policies)
    result = None
    if cur.description is not None:
        cols = [d[0] for d in cur.description]
        rows = [[_sanitize_cell(c) for c in r] for r in cur.fetchmany(MAX_ROWS)]
        result = {'columns': cols, 'rows': rows}
    cur.close()
    notices = [n.strip() for n in conn.notices]
    return {'notices': notices, 'last_result': result}


def op_read_update_script():
    with open(UPDATE_SQL_PATH, 'r', encoding='utf-8') as f:
        return f.read()


# ---------------------------------------------------------------------------
# HTTP layer
# ---------------------------------------------------------------------------
INDEX_HTML = open(os.path.join(HERE, 'index.html'), 'r', encoding='utf-8').read()


class Handler(BaseHTTPRequestHandler):
    server_version = 'SupabaseBridge/1.0'

    def log_message(self, fmt, *args):  # keep logs quiet / no credential leaks
        pass

    def _json(self, payload, status=200):
        body = json.dumps(payload, ensure_ascii=False, default=str).encode('utf-8')
        self.send_response(status)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_header('Content-Length', str(len(body)))
        self.send_header('Cache-Control', 'no-store')
        self.end_headers()
        self.wfile.write(body)

    def _read_body(self):
        length = int(self.headers.get('Content-Length') or 0)
        if length <= 0:
            return {}
        raw = self.rfile.read(length)
        try:
            return json.loads(raw.decode('utf-8'))
        except Exception:
            return {}

    def do_GET(self):
        if self.path == '/' or self.path == '/index.html':
            body = INDEX_HTML.encode('utf-8')
            self.send_response(200)
            self.send_header('Content-Type', 'text/html; charset=utf-8')
            self.send_header('Content-Length', str(len(body)))
            self.end_headers()
            self.wfile.write(body)
        elif self.path == '/api/status':
            info = HOLDER.info if HOLDER.conn is not None else None
            self._json({'connected': info is not None, 'info': info})
        elif self.path == '/api/tables':
            self._guarded(op_tables)
        elif self.path.startswith('/api/describe?table='):
            table = self.path.split('?table=', 1)[1]
            table = json.loads('"' + table.replace('"', '\\"') + '"') if table.startswith('%') else table
            from urllib.parse import unquote
            table = unquote(table)
            self._guarded(op_describe, table)
        elif self.path == '/api/update-script':
            try:
                self._json({'ok': True, 'script': op_read_update_script()})
            except Exception as e:
                self._json({'ok': False, 'error': str(e)}, 500)
        else:
            self._json({'ok': False, 'error': 'not found'}, 404)

    def do_POST(self):
        if self.path == '/api/connect':
            b = self._read_body()
            required = ('host', 'port', 'user', 'password', 'database')
            missing = [k for k in required if not str(b.get(k, '')).strip()]
            if missing:
                self._json({'ok': False, 'error': 'حقول ناقصة: ' + ', '.join(missing)}, 400)
                return
            try:
                info = HOLDER.connect(
                    b['host'].strip(),
                    b['port'],
                    b['user'].strip(),
                    b['password'],
                    b['database'].strip(),
                    bool(b.get('readonly', False)),
                )
                self._json({'ok': True, 'info': info})
            except psycopg2.OperationalError as e:
                self._json({'ok': False, 'error': str(e)}, 400)
            except Exception as e:
                self._json({'ok': False, 'error': str(e)}, 500)
        elif self.path == '/api/disconnect':
            HOLDER.close()
            self._json({'ok': True})
        elif self.path == '/api/query':
            b = self._read_body()
            sql = (b.get('sql') or '').strip()
            if not sql:
                self._json({'ok': False, 'error': 'لا يوجد استعلام'}, 400)
                return
            self._guarded(op_query, sql)
        elif self.path == '/api/apply-update':
            self._guarded(op_apply_update)
        else:
            self._json({'ok': False, 'error': 'not found'}, 404)

    def _guarded(self, fn, *args):
        try:
            result = HOLDER.run(lambda conn: fn(conn, *args))
            self._json({'ok': True, 'result': result})
        except RuntimeError as e:
            if str(e) == 'not_connected':
                self._json({'ok': False, 'error': 'غير متصل — اتصل أولًا'}, 400)
            else:
                self._json({'ok': False, 'error': str(e)}, 500)
        except psycopg2.Error as e:
            self._json({'ok': False, 'error': str(e).strip()}, 400)
        except Exception as e:
            self._json({'ok': False, 'error': str(e)}, 500)


def main():
    server = ThreadingHTTPServer(('0.0.0.0', PORT), Handler)
    print(f'Supabase Web Bridge listening on 0.0.0.0:{PORT}', flush=True)
    server.serve_forever()


if __name__ == '__main__':
    main()
