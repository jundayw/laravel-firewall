<?php

namespace Jundayw\LaravelFirewall\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Jundayw\LaravelFirewall\Firewall as LaravelFirewall;

/**
 * @method static string|null getIp(string $ip = null)
 * @method static LaravelFirewall setIp(string $ip = null)
 * @method static bool|null whichList(string $ip = null)
 * @method static bool isBlacklisted(string $ip = null)
 * @method static bool isWhitelisted(string $ip = null)
 * @method static bool isBlockAttacks(string $ip = null)
 *
 * @method static void macro($name, $macro)
 * @method static void mixin($mixin, $replace = true)
 * @method static bool hasMacro($name)
 * @method static void flushMacros()
 *
 * @see LaravelFirewall
 * @see Macroable
 */
class Firewall extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'firewall';
    }
}
