<?php

namespace Jundayw\LaravelFirewall\Support;

use Illuminate\Config\Repository as Config;
use Illuminate\Cache\Repository as Cache;

class IpList
{
    private Config    $config;
    private Cache     $cache;
    private IpAddress $ipAddress;
    private Countries $countries;

    public function __construct(
        Config    $config,
        Cache     $cache,
        IpAddress $ipAddress,
        Countries $countries
    )
    {
        $this->config    = $config;
        $this->cache     = $cache;
        $this->ipAddress = $ipAddress;
        $this->countries = $countries;
    }

    /**
     * Tell in which list (black/white) an IP address is.
     *
     * @param string|null $ip
     * @return bool|string
     */
    public function whichList(string $ip = null)
    {
        return ($has = $this->find($ip)) ?? $has;
    }

    /**
     * 查询IP是否在黑/白列表
     *
     * @param string|null $ip
     * @return mixed
     */
    private function find(string $ip = null)
    {
        return $this->cache->remember($this->makeHashedKey($ip), $this->config->get('ip_address_cache_expire_time'), function() use ($ip) {
            foreach ($this->getIps() as $ips) {
                $type      = $ips['type'];
                $ipAddress = $ips['ip_address'];
                if ($ipAddress == $ip) {
                    return $type;
                }
                if ($this->config->get('allow_ip_range') && $this->ipAddress->validRange($ip, $ipAddress)) {
                    return $type;
                }
                if ($this->config->get('allow_country_range') && $ipAddress == $this->countries->getCountryFromIp($ip)) {
                    return $type;
                }
            }
            return null;
        });
    }

    /**
     * 获取所有黑/白名单列表
     *
     * @return array
     */
    public function getIps()
    {
        return $this->cache->remember($this->makeHashedKey('AllIP'), $this->config->get('ip_list_cache_expire_time'), function() {
            $blacklist = storage_path('firewall/blacklist.txt');
            if (app('files')->exists($blacklist)) {
                $blacklist = file($blacklist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            } else {
                $blacklist = [];
            }
            $blacklist = array_merge($this->config->get('blacklist'), $blacklist);
            $blacklist = array_map(function($ips) {
                $ips['type'] = 'blacklist';
                return $ips;
            }, $this->getIpsFormat($blacklist));
            $whitelist = storage_path('firewall/whitelist.txt');
            if (app('files')->exists($whitelist)) {
                $whitelist = file($whitelist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            } else {
                $whitelist = [];
            }
            $whitelist = array_merge($this->config->get('whitelist'), $whitelist);
            $whitelist = array_map(function($ips) {
                $ips['type'] = 'whitelist';
                return $ips;
            }, $this->getIpsFormat($whitelist));
            return array_merge_recursive($blacklist, $whitelist);
        });
    }

    /**
     * 获取所有黑/白名单列表并将IP地址转为数组
     *
     * @param array $ips
     * @return array|array[]
     */
    private function getIpsFormat(array $ips = [])
    {
        return array_map(function($ip) {
            return ['ip_address' => $ip];
        }, $this->getIpsMultiple($ips));
    }

    /**
     * 获取所有黑/白名单列表并将黑白单列重复列合并
     *
     * @param array $ips
     * @return array|string[]
     */
    private function getIpsMultiple(array $ips = [])
    {
        $list = [];
        foreach ($ips as $ip) {
            $list = array_merge($list, $this->getIpsFromAnything($ip));
        }
        return $list;
    }

    /**
     * 获取所有黑/白名单列表并解析国家、域名、文件做响应的解析工作
     *
     * @param string $ip
     * @return array|string[]
     */
    private function getIpsFromAnything(string $ip)
    {
        if (str($ip)->startsWith('country:')) {
            return [$ip];
        }

        $ip = $this->ipAddress->hostToIp($ip);
        if ($this->ipAddress->ipV4Valid($ip)) {
            return [$ip];
        }

        return $this->readFile($ip);
    }

    /**
     * 递归处理文件列表
     *
     * @param string $file
     * @return array|string[]
     */
    private function readFile(string $file)
    {
        if (app('files')->exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return $this->getIpsMultiple($lines);
        }
        return [];
    }

    /**
     * Make a hashed key.
     *
     * @param string $field
     * @return string
     */
    public function makeHashedKey(string $field)
    {
        return hash(
            'sha256',
            sprintf('%s::%s', static::class, $field)
        );
    }
}
