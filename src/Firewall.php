<?php

namespace Jundayw\LaravelFirewall;

use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Jundayw\LaravelFirewall\Repositories\Repository;

class Firewall
{
    use Macroable;

    private $ip;

    private $repository;
    private $request;
    private $attackBlocker;

    public function __construct(
        Repository    $repository,
        Request       $request,
        AttackBlocker $attackBlocker
    )
    {
        $this->repository    = $repository;
        $this->request       = $request;
        $this->attackBlocker = $attackBlocker;
    }

    /**
     * Get the IP address.
     *
     * @param string|null $ip
     * @return string|null
     */
    public function getIp(string $ip = null)
    {
        return $ip ?? $this->ip;
    }

    /**
     * Set the current IP address.
     *
     * @param string|null $ip
     * @return Firewall
     */
    public function setIp(string $ip = null)
    {
        $this->ip = $ip ?? $this->request->server('HTTP_CF_CONNECTING_IP') ?? $this->request->server->get('HTTP_X_FORWARDED_FOR') ?? $this->request->getClientIp();
        return $this;
    }

    /**
     * Tell in which list (black/white) an IP address is.
     *
     * @param string|null $ip
     * @return bool|string
     */
    public function whichList(string $ip = null)
    {
        return $this->repository->getIpList()->whichList($this->getIp($ip));
    }

    /**
     * Check if IP is blacklisted.
     *
     * @param string|null $ip
     * @return bool
     */
    public function isBlacklisted(string $ip = null)
    {
        if (!$this->repository->getConfig()->get('firewall')) {
            return false;
        }
        return !('whitelist' == $list = $this->whichList($ip)) && ($list == 'blacklist');
    }

    /**
     * Check if IP address is whitelisted.
     *
     * @param string|null $ip
     * @return bool
     */
    public function isWhitelisted(string $ip = null)
    {
        if (!$this->repository->getConfig()->get('firewall')) {
            return false;
        }
        return $this->whichList($ip) == 'whitelist';
    }

    /**
     * Check if the application is receiving some sort of attack.
     *
     * @param string|null $ip
     * @return bool
     */
    public function isBlockAttacks(string $ip = null)
    {
        if (!$this->repository->getConfig()->get('attack_blocker')) {
            return false;
        }
        return $this->attackBlocker->isBlockAttacks($this->getIp($ip));
    }

}
