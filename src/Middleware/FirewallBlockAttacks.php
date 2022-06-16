<?php

namespace Jundayw\LaravelFirewall\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jundayw\LaravelFirewall\Exceptions\FirewallBlockAttackException;

class FirewallBlockAttacks
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws FirewallBlockAttackException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $firewall = app()->make('firewall');
        $firewall = $firewall->isBlockAttacks($ipAddress = $firewall->getIp());
        if ($firewall) {
            throw new FirewallBlockAttackException($ipAddress, "FirewallBlockAttacks:{$ipAddress}");
        }
        return $next($request);
    }
}
