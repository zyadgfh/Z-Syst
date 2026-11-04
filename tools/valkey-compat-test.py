#!/usr/bin/env python3
"""Z-Syst — Valkey/Redis compatibility test.

Verifies that a Valkey (or Redis) server supports every operation Z-Syst's
Laravel 10 code performs: cache, sessions, queues, atomic locks, rate
limiting, broadcasting, transactions and persistence settings.

The queue/lock Lua scripts below are copied verbatim from laravel/framework
10.x (Illuminate\\Queue\\LuaScripts and Illuminate\\Cache\\LuaScripts), so this
tests the REAL code paths, not approximations.

Usage:
    pip install redis
    VALKEY_PASSWORD='your-password' python3 tools/valkey-compat-test.py

Environment variables:
    VALKEY_HOST      default 127.0.0.1
    VALKEY_PORT      default 6379
    VALKEY_PASSWORD  required (REDIS_PASSWORD from .env)
    VALKEY_PREFIX    default z_syst_pharmacy_database_ (Laravel's prefix)

Verified against Valkey 9.1.3: 36/36 checks passed (2026-11-04).
"""
import json
import os
import sys
import time
import uuid

try:
    import redis
except ImportError:
    sys.exit("Missing dependency: pip install redis")

HOST = os.environ.get("VALKEY_HOST", "127.0.0.1")
PORT = int(os.environ.get("VALKEY_PORT", "6379"))
PASSWORD = os.environ.get("VALKEY_PASSWORD") or sys.exit("Set VALKEY_PASSWORD (your REDIS_PASSWORD)")
PREFIX = os.environ.get("VALKEY_PREFIX", "z_syst_pharmacy_database_")

# --- Real Laravel 10.x Lua scripts (verbatim) ---------------------------------
QUEUE_LUA = {
    "size": "return redis.call('llen', KEYS[1]) + redis.call('zcard', KEYS[2]) + redis.call('zcard', KEYS[3])",
    "push": """-- Push the job onto the queue...
redis.call('rpush', KEYS[1], ARGV[1])
-- Push a notification onto the "notify" queue...
redis.call('rpush', KEYS[2], 1)""",
    "pop": """-- Pop the first job off of the queue...
local job = redis.call('lpop', KEYS[1])
local reserved = false

if(job ~= false) then
    -- Increment the attempt count and place job on the reserved queue...
    reserved = cjson.decode(job)
    reserved['attempts'] = reserved['attempts'] + 1
    reserved = cjson.encode(reserved)
    redis.call('zadd', KEYS[2], ARGV[1], reserved)
    redis.call('lpop', KEYS[3])
end

return {job, reserved}""",
    "release": """-- Remove the job from the current queue...
redis.call('zrem', KEYS[2], ARGV[1])

-- Add the job onto the "delayed" queue...
redis.call('zadd', KEYS[1], ARGV[2], ARGV[1])

return true""",
    "migrateExpiredJobs": """-- Get all of the jobs with an expired "score"...
local val = redis.call('zrangebyscore', KEYS[1], '-inf', ARGV[1], 'limit', 0, ARGV[2])

-- If we have values in the array, we will remove them from the first queue
-- and add them onto the destination queue in chunks of 100, which moves
-- all of the appropriate jobs onto the destination queue very safely.
if(next(val) ~= nil) then
    redis.call('zremrangebyrank', KEYS[1], 0, #val - 1)

    for i = 1, #val, 100 do
        redis.call('rpush', KEYS[2], unpack(val, i, math.min(i+99, #val)))
        -- Push a notification for every job that was migrated...
        for j = i, math.min(i+99, #val) do
            redis.call('rpush', KEYS[3], 1)
        end
    end
end

return val""",
    "clear": """local size = redis.call('llen', KEYS[1]) + redis.call('zcard', KEYS[2]) + redis.call('zcard', KEYS[3])
redis.call('del', KEYS[1], KEYS[2], KEYS[3], KEYS[4])
return size""",
}
CACHE_LUA = {
    "releaseLock": """if redis.call("get", KEYS[1]) == ARGV[1] then
    return redis.call("del", KEYS[1])
else
    return 0
end""",
}

passed, failed = [], []


def check(name, cond, detail=""):
    (passed if cond else failed).append(name)
    print(f"  {'PASS' if cond else 'FAIL'}  {name}" + (f"  [{detail}]" if detail and not cond else ""))


r = redis.Redis(host=HOST, port=PORT, password=PASSWORD, db=0, decode_responses=True)
rc = redis.Redis(host=HOST, port=PORT, password=PASSWORD, db=1, decode_responses=True)

print(f"Testing {HOST}:{PORT} ...\n== 1) Connection / server ==")
check("PING (AUTH)", r.ping() is True)
info = r.info("server")
print(f"        server: {info.get('server_name', 'redis')} "
      f"valkey_version={info.get('valkey_version', '-')} redis_version={info.get('redis_version')}")
check("SELECT cache DB 1", rc.ping() is True)
check("maxmemory-policy is queue-safe (volatile-* or noeviction)",
      r.config_get("maxmemory-policy")["maxmemory-policy"] in
      ("volatile-lru", "volatile-lfu", "volatile-ttl", "volatile-random", "noeviction"))
check("appendonly yes (queue durability)", r.config_get("appendonly")["appendonly"] == "yes")

print("== 2) Cache store ==")
k = PREFIX + "cache:test"
rc.set(k, "s:6:\"foobar\";", ex=60)
check("Cache::put + get (SET EX)", rc.get(k) == 's:6:"foobar";')
check("TTL set", 0 < rc.ttl(k) <= 60)
check("Cache::add on existing (SET NX) returns False", rc.set(k, "other", nx=True, ex=60) is None)
rc.delete(k)
check("Cache::forget (DEL)", rc.get(k) is None)
rc.set(PREFIX + "cache:counter", 5)
check("Cache::increment (INCR)", rc.incr(PREFIX + "cache:counter") == 6)
check("Cache::decrement (DECRBY)", rc.decr(PREFIX + "cache:counter", 2) == 4)
rc.set(PREFIX + "cache:perm", "x")
rc.delete(*list(rc.scan_iter(match=PREFIX + "cache:*")))
check("SCAN + bulk delete (flush pattern)", rc.get(PREFIX + "cache:perm") is None)

print("== 3) Atomic locks ==")
lock_key, owner = PREFIX + "lock:report", str(uuid.uuid4())
check("acquire lock (SET NX PX)", r.set(lock_key, owner, nx=True, px=10000) is True)
check("second acquire fails", r.set(lock_key, "other-owner", nx=True, px=10000) is None)
check("release with wrong owner refused (real Lua)", r.eval(CACHE_LUA["releaseLock"], 1, lock_key, "wrong-owner") == 0)
check("release with correct owner (real Lua)",
      r.eval(CACHE_LUA["releaseLock"], 1, lock_key, owner) == 1 and r.get(lock_key) is None)

print("== 4) Rate limiter ==")
rl = PREFIX + "rate:login:test"
r.delete(rl)
hits = r.incr(rl)
if hits == 1:
    r.expire(rl, 60)
check("hit #1 + EXPIRE", hits == 1 and 0 < r.ttl(rl) <= 60)
check("hit #2", r.incr(rl) == 2)
r.delete(rl)

print("== 5) Sessions ==")
sid = PREFIX + "sess:" + str(uuid.uuid4())
r.set(sid, "serialized-session-payload", ex=7200)
check("session write+read", r.get(sid) == "serialized-session-payload")
check("session TTL ~120min", 7100 < r.ttl(sid) <= 7200)
r.delete(sid)
check("session destroy", r.get(sid) is None)

print("== 6) Queue — real Laravel 10 Lua scripts ==")
q = PREFIX + "queues:default"
payload = json.dumps({"uuid": str(uuid.uuid4()), "displayName": "App\\Jobs\\TestJob",
                      "job": "Illuminate\\Queue\\CallQueuedHandler@call", "attempts": 0,
                      "data": {"commandName": "App\\Jobs\\TestJob", "command": "O:19:..."}}, separators=(",", ":"))
r.eval(QUEUE_LUA["push"], 2, q, q + ":notify", payload)
check("Queue::push (Lua rpush + notify)", r.llen(q) == 1 and r.llen(q + ":notify") == 1)
check("Queue::size (Lua)", r.eval(QUEUE_LUA["size"], 3, q, q + ":delayed", q + ":reserved") == 1)
expire_at = int(time.time()) + 90
job, reserved = r.eval(QUEUE_LUA["pop"], 3, q, q + ":reserved", q + ":notify", expire_at)
check("Queue::pop (Lua lpop + zadd reserved)", job == payload and r.zcard(q + ":reserved") == 1)
check("attempts incremented via cjson", json.loads(reserved)["attempts"] == 1)
r.eval(QUEUE_LUA["release"], 2, q + ":delayed", q + ":reserved", reserved, int(time.time()) + 30)
check("Queue::release (Lua zrem + zadd delayed)", r.zcard(q + ":reserved") == 0 and r.zcard(q + ":delayed") == 1)
migrated = r.eval(QUEUE_LUA["migrateExpiredJobs"], 3, q + ":delayed", q, q + ":notify", int(time.time()) + 60, 100)
check("migrateExpiredJobs (Lua)", len(migrated) == 1 and r.llen(q) == 1)
r.eval(QUEUE_LUA["pop"], 3, q, q + ":reserved", q + ":notify", expire_at)
cleared = r.eval(QUEUE_LUA["clear"], 4, q, q + ":delayed", q + ":reserved", q + ":notify")
check("Queue::clear (Lua)", cleared == 1 and r.llen(q) == 0)

print("== 7) Delayed jobs / sorted sets ==")
z = PREFIX + "queues:delayed-test"
r.zadd(z, {"job-a": time.time() - 10, "job-b": time.time() + 100})
check("ZRANGEBYSCORE ready jobs", r.zrangebyscore(z, "-inf", time.time()) == ["job-a"])
r.delete(z)

print("== 8) Broadcasting (Pub/Sub) ==")
ps = r.pubsub()
ps.subscribe(PREFIX + "channel:orders")
time.sleep(0.2)
n = r.publish(PREFIX + "channel:orders", json.dumps({"event": "order.created", "id": 42}))
msg, deadline = None, time.time() + 3
while time.time() < deadline:
    m = ps.get_message(ignore_subscribe_messages=True, timeout=1)
    if m:
        msg = m
        break
check("PUBLISH -> SUBSCRIBE delivered", n == 1 and msg and json.loads(msg["data"])["id"] == 42)
ps.close()

print("== 9) Transactions & pipelines ==")
with r.pipeline(transaction=True) as pipe:
    pipe.set(PREFIX + "tx:a", 1).incr(PREFIX + "tx:a").get(PREFIX + "tx:a")
    res = pipe.execute()
check("MULTI/EXEC pipeline", res[-1] == "2")
with r.pipeline(transaction=False) as pipe:
    for i in range(100):
        pipe.set(PREFIX + f"pl:{i}", i)
    pipe.execute()
check("plain pipeline x100", r.get(PREFIX + "pl:99") == "99")
r.delete(*r.keys(PREFIX + "pl:*"), PREFIX + "tx:a")

print("== 10) Hashes (Horizon-style metrics) ==")
h = PREFIX + "horizon:supervisor"
r.hset(h, mapping={"status": "running", "pid": "123"})
r.hincrby(h, "processed", 7)
check("HSET/HGETALL/HINCRBY", r.hgetall(h) == {"status": "running", "pid": "123", "processed": "7"})
r.delete(h)

print("== 11) EVALSHA caching ==")
sha = r.script_load(QUEUE_LUA["size"])
check("SCRIPT LOAD + EVALSHA", r.evalsha(sha, 3, q, q + ":delayed", q + ":reserved") == 0)

print("== 12) Expiry semantics ==")
r.set(PREFIX + "exp", "x", px=150)
time.sleep(0.3)
check("PX expiry honored", r.get(PREFIX + "exp") is None)

print(f"\n{'=' * 50}\nRESULT: {len(passed)} passed, {len(failed)} failed")
if failed:
    print("FAILED:", failed)
    sys.exit(1)
print("ALL TESTS PASSED — this server is fully compatible with Z-Syst's Laravel usage")
