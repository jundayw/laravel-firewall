<?php

return [

    /*
     * Enable / disable firewall
     */
    'firewall' => env('FIREWALL_ENABLED', true),

    /*
     * Whitelisted and blacklisted IP addresses, ranges, countries, files and/or files of files
     *
     *   '127.0.0.1',
     *   '192.168.17.0/24',
     *   '127.0.0.1/255.255.255.255',
     *   '10.0.0.1-10.0.0.255',
     *   '172.17.*.*',
     *   'country:us',
     *   'host:baidu.com',
     *   storage_path('firewall/ips.txt'), // a file with IPs, one per line
     *
     * Examples of IP address, hosts, country codes and CIDRs
     */
    'blacklist' => [
        storage_path('firewall/blacklist.txt'),
    ],
    'whitelist' => [
        storage_path('firewall/whitelist.txt'),
    ],

    /*
     * Search by range allow you to store ranges of addresses in
     * your black and whitelist:
     *
     *   192.168.17.0/24 or
     *   127.0.0.1/255.255.255.255 or
     *   10.0.0.1-10.0.0.255 or
     *   172.17.*.*
     *
     * Note that range searches may be slow and waste memory, this is why
     * it is disabled by default.
     */
    'allow_ip_range' => true,

    /*
     * Search by country range allow you to store country ids in your
     * your black and whitelist:
     *
     *   php artisan firewall:whitelist country:cn
     *   php artisan firewall:blacklist country:us
     */
    'allow_country_range' => false,

    /*
     * GeoIp2 database path.
     * default path storage_path('firewall/GeoLite2-Country.mmdb').
     */
    'geoip_database_path' => null,

    /*
     * Cache Stores
     *    "apc",
     *    "array",
     *    "database",
     *    "file",
     *    "memcached",
     *    "redis",
     *    "dynamodb",
     *    "octane",
     *    "null"
     * Supported drivers
     */
    'cache' => null,

    /*
     * How long should we keep IP addresses in cache?
     *
     * This is a general client IP addresses cache. When the user hits your system his/her IP address
     * is searched and cached for the desired time. Finding an IP address contained in a CIDR
     * range (172.17.0.0/24, for instance) can be a "slow", caching it improves performance.
     */
    'ip_address_cache_expire_time' => 60,

    /*
     * How long should we keep lists of IP addresses in cache?
     *
     * This is the list cache. Database lists can take some time to load and process,
     * caching it, if you are not making frequent changes to your lists, may improve firewall speed a lot.
     */
    'ip_list_cache_expire_time' => 24 * 3600,

    /*
     * Block suspicious attacks
     */
    'attack_blocker' => true,

    /*
     * Anchor firewall blacklist
     * The premise is that the firewall blacklist is enabled
     */
    'blocker_firewall_blacklist' => false,

    /*
     * Anchor firewall whitelist
     * The premise is that the firewall whitelist is enabled
     */
    'blocker_firewall_whitelist' => false,

    /*
     * Request rate limiter
     */
    'rate_limiter' => [
        'ip' => [
            'enabled' => true,
            'limiter_max_requests' => 50, // Maximum number of requests per IP
            'limiter_time' => 1 * 60, // units/seconds
        ],
        'country' => [
            'enabled' => false,
            'limiter_max_requests' => 3000, // Maximum number of requests per country
            'limiter_time' => 2 * 60, // units/seconds
        ],
    ],

    /*
     * Only one of blocker_blacklist or blocker_times is valid at the same time,
     * and they are enabled at the same time,
     * blocker_blacklist has a higher priority
     */
    'blocker_blacklist' => [
        'enabled' => false,
        'path' => storage_path('firewall/blocker_blacklist.txt'),
    ],
    'blocker_durations' => [
        'enabled' => true,
        'duration' => 5 * 60,
    ],

    /*
     * Blocking logging
     */
    'blocker_logging' => [
        'enabled' => true,
        'channel' => [
            'driver' => 'daily',
            'path' => storage_path('logs/firewall.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
    ],
];
