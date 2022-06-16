<?php

namespace Jundayw\LaravelFirewall\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jundayw\LaravelFirewall\Exceptions\FirewallBlacklistException;

class FirewallBlacklist
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws FirewallBlacklistException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $firewall = app()->make('firewall');
        $firewall = $firewall->isBlacklisted($ipAddress = $firewall->getIp());
        if ($firewall) {
            throw new FirewallBlacklistException($ipAddress, "FirewallBlacklist:{$ipAddress}");
        }
        return $next($request);
    }
}
