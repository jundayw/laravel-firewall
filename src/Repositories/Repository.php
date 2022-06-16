<?php

namespace Jundayw\LaravelFirewall\Repositories;

use Illuminate\Cache\Repository as Cache;
use Illuminate\Config\Repository as Config;
use Jundayw\LaravelFirewall\Support\IpAddress;
use Jundayw\LaravelFirewall\Support\IpList;
use Jundayw\LaravelFirewall\Support\Countries;

class Repository
{
    private Cache     $cache;
    private Config    $config;
    private IpAddress $ipAddress;
    private IpList    $ipList;
    private Countries $countries;

    public function __construct(
        Cache     $cache,
        Config    $config,
        IpAddress $ipAddress,
        IpList    $ipList,
        Countries $countries
    )
    {
        $this->cache     = $cache;
        $this->config    = $config;
        $this->ipAddress = $ipAddress;
        $this->ipList    = $ipList;
        $this->countries = $countries;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @return IpList
     */
    public function getIpList()
    {
        return $this->ipList;
    }

    /**
     * @return Countries
     */
    public function getCountries()
    {
        return $this->countries;
    }
}
