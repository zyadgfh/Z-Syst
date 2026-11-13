<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

/**
 * Decide which proxy headers this application believes.
 *
 * Why not `'*'`
 * -------------
 * The deployment is Render → nginx → php-fpm, so the socket peer is always the
 * platform's router and never the real client. Two consequences:
 *
 *   • Trusting nothing (the previous `protected $proxies;` = null) means
 *     `$request->ip()` is the proxy's address for every visitor, so
 *     `throttle:10,1` on /api/v1/sign-in is effectively one shared bucket for
 *     the whole internet: a single attacker exhausting it locks out every user,
 *     and audit_logs.ip_address records the proxy for everyone.
 *   • Trusting `'*'` fixes that but hands the client control of
 *     X-Forwarded-For: the platform *appends* the real peer, so with every hop
 *     trusted Symfony resolves the client IP from the leftmost — attacker
 *     supplied — entry. That is a rate-limit bypass and an audit-log forgery.
 *
 * Trusting only private/loopback ranges gets both halves right: when the peer
 * is the platform's internal router the forwarded chain is believed, and the
 * rightmost entry that is not itself a trusted proxy is the real client — an
 * address an internet client cannot forge, because to be believed it would have
 * to arrive from inside the network. A request that reaches the container
 * directly from a public IP trusts nothing at all.
 *
 * Override with TRUSTED_PROXIES (comma separated) if the platform ever routes
 * through a public range; `REMOTE_ADDR` is accepted as a value and means "trust
 * whoever opened this connection".
 */
class TrustProxies extends Middleware
{
    /**
     * Private (RFC1918), loopback and link-local ranges only — see above.
     */
    protected $proxies = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.1',
        '::1',
    ];

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Allow an environment override without touching the defaults above.
     *
     * @return array<int, string>|string|null
     */
    protected function proxies(): array|string|null
    {
        $alwaysTrust = static::$alwaysTrustProxies;

        if ($alwaysTrust) {
            return $alwaysTrust;
        }

        $fromEnv = env('TRUSTED_PROXIES');

        return is_string($fromEnv) && $fromEnv !== '' ? $fromEnv : $this->proxies;
    }
}
