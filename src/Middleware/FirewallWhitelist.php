<?php

namespace Jundayw\LaravelFirewall\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jundayw\LaravelFirewall\Exceptions\FirewallWhitelistException;

class FirewallWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws FirewallWhitelistException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $firewall = app()->make('firewall');
        $firewall = $firewall->isWhitelisted($ipAddress = $firewall->getIp());
        if ($firewall) {
            throw new FirewallWhitelistException($ipAddress, "FirewallWhitelist:{$ipAddress}");
        }
        return $next($request);
    }
}
