<?php

namespace Jundayw\LaravelFirewall;

use Illuminate\Log\Logger;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Jundayw\LaravelFirewall\Repositories\Repository;

class AttackBlocker
{
    private Firewall   $firewall;
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return Firewall
     */
    public function getFirewall()
    {
        return $this->firewall;
    }

    /**
     * @param Firewall $firewall
     * @return AttackBlocker
     */
    public function setFirewall($firewall)
    {
        $this->firewall = $firewall;
        return $this;
    }

    /**
     * 是否可疑访问
     *
     * @param string|null $ip
     * @return bool
     */
    public function isBlockAttacks(string $ip = null)
    {
        // 可疑访问黑名单
        if ($this->isBlacklisted($ip)) {
            return true;
        }

        // 防火墙黑名单
        if ($this->repository->getConfig()->get('blocker_firewall_blacklist') && $this->firewall->isBlacklisted($ip)) {
            return true;
        }

        // 防火墙白名单
        if ($this->repository->getConfig()->get('blocker_firewall_whitelist') && $this->firewall->isWhitelisted($ip)) {
            return false;
        }

        // 检查是否达到可疑访问判定标准
        if ($this->isAttacking($ip)) {
            $this->makeBlackList($ip);
            $this->makeBlackerDurations($ip);
            return true;
        }

        return false;
    }

    /**
     * 可疑访问黑名单
     *
     * @param string|null $ip
     * @return bool
     */
    private function isBlacklisted(string $ip = null)
    {
        $blacklist = function($ip) {
            if (!$this->repository->getConfig()->get('blocker_blacklist.enabled')) {
                return false;
            }
            if (app('files')->exists($file = $this->repository->getConfig()->get('blocker_blacklist.path'))) {
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                return in_array($ip, $lines);
            }
            return false;
        };

        $durations = function($ip) {
            if (!$this->repository->getConfig()->get('blocker_durations.enabled')) {
                return false;
            }
            if ($this->repository->getCache()->has($this->makeHashedKey("{$ip}.duration"))) {
                return true;
            }
            return false;
        };

        return $blacklist($ip) || $durations($ip);
    }

    /**
     * 是否达到判定标准
     *
     * @param string|null $ip
     * @return bool
     */
    private function isAttacking(string $ip = null)
    {
        return $this->checkIPBlockAttack($ip) || $this->checkCountryBlockAttack($ip);
    }

    /**
     * 验证IP请求数是否达到判定标准
     *
     * @param string|null $ip
     * @return bool
     */
    private function checkIPBlockAttack(string $ip = null)
    {
        if (!$this->repository->getConfig()->get('rate_limiter.ip.enabled')) {
            return false;
        }

        return $this->historyBlockAttack($ip, 'ip');
    }

    /**
     * 验证国家请求数是否达到判定标准
     *
     * @param string|null $ip
     * @return bool
     */
    private function checkCountryBlockAttack(string $ip = null)
    {
        if (!$this->repository->getConfig()->get('rate_limiter.country.enabled')) {
            return false;
        }

        if (is_null($country = $this->repository->getCountries()->getCountryFromIp($ip))) {
            return false;
        }

        return $this->historyBlockAttack($country, 'country');
    }

    /**
     * 累计(IP/COUNTRY)请求数是否符合规定时间内规定请求数总和
     *
     * @param string $key
     * @param string $type
     * @return bool
     */
    private function historyBlockAttack(string $key, string $type)
    {
        $requestKeyTime     = $this->makeHashedKey("{$key}.time");
        $requestKeyCount    = $this->makeHashedKey("{$key}.count");
        $limiterTime        = $this->repository->getConfig()->get("rate_limiter.{$type}.limiter_time");
        $limiterMaxRequests = $this->repository->getConfig()->get("rate_limiter.{$type}.limiter_max_requests");
        if ($this->repository->getCache()->has($requestKeyCount)) {
            $this->repository->getCache()->increment($requestKeyCount);
        } else {
            $this->repository->getCache()->put(
                $requestKeyCount,
                1,
                $limiterTime
            );
            $this->repository->getCache()->put(
                $requestKeyTime,
                time(),
                $limiterTime
            );
        }
        if ($this->repository->getCache()->get($requestKeyCount) > $limiterMaxRequests) {
            $this->makeLogging($key, $type);
            return true;
        }
        return false;
    }

    /**
     * 可疑访问日志记录
     *
     * @param string|null $key
     * @param string|null $type
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Contracts\Container\CircularDependencyException
     */
    private function makeLogging(string $key = null, string $type = null)
    {
        if (!$this->repository->getConfig()->get('blocker_logging.enabled')) {
            return false;
        }

        $requestCount = $this->repository->getCache()->get($this->makeHashedKey("{$key}.count"));
        $requestTime  = $this->repository->getCache()->get($this->makeHashedKey("{$key}.time"));
        $info         = [
            'type' => $type,
            'ip_address' => $key,
            'remote_address' => request()->server('REMOTE_ADDR'),
            'request_count' => $requestCount,
            'request_first_time' => date('Y-m-d H:i:s', $requestTime),
            'request_last_time' => date('Y-m-d H:i:s'),
            'user_agent' => request()->server('HTTP_USER_AGENT'),
            'request_method' => request()->server('REQUEST_METHOD'),
            'http_host' => request()->server('HTTP_HOST'),
            'query_string' => request()->server('QUERY_STRING'),
        ];

        $channel = $this->repository->getConfig()->get('blocker_logging.channel');

        app('log')->build($channel)->info(json_encode($info, JSON_UNESCAPED_UNICODE));

        return true;
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

    /**
     * 可疑访问加入黑名单
     *
     * @param string|null $ip
     * @return bool
     */
    private function makeBlackList(string $ip = null)
    {
        if (!$this->repository->getConfig()->get('blocker_blacklist.enabled')) {
            return false;
        }

        $file = $this->repository->getConfig()->get('blocker_blacklist.path');

        if (is_null($file)) {
            return false;
        }

        if (!app('files')->isDirectory($path = app('files')->dirname($file))) {
            app('files')->makeDirectory($path);
        }

        app('files')->append($file, $ip . PHP_EOL);

        return true;
    }

    private function makeBlackerDurations(string $ip = null)
    {
        if (!$this->repository->getConfig()->get('blocker_durations.enabled')) {
            return false;
        }

        $duration = $this->repository->getConfig()->get('blocker_durations.duration');
        $duration = intval($duration);

        $this->repository->getCache()->put(
            $this->makeHashedKey("{$ip}.duration"),
            $ip,
            $duration
        );

        return true;
    }
}
