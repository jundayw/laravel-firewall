# Installation

## Compatible with

- Laravel 5.5+

## Installing

```
composer require jundayw/laravel-firewall
```

这个包使用包 Auto-Discovery's 特性，并且应该在您通过 Composer 安装它后立即可用。
并且预置三个中间件，方便直接调用：

```
protected $routeMiddleware = [
    ...
    'firewall.black' => \Jundayw\LaravelFirewall\Middleware\FirewallBlacklist::class,
    'firewall.white' => \Jundayw\LaravelFirewall\Middleware\FirewallWhitelist::class,
    'firewall.attacks' => \Jundayw\LaravelFirewall\Middleware\FirewallBlockAttacks::class,
];
```

然后您可以在路由中使用它们：

```
Route::group(['middleware' => 'firewall.attacks'], function () {
    Route::get('/', 'HomeController@index');
});
```

发布配置文件：

```
php artisan vendor:publish --tag=firewall-config
```

发布IP数据库文件：

```
php artisan vendor:publish --tag=firewall-country
```

> 如果需要，您可以手动更新 `GeoLite2-Country.mmdb` 数据库文件。[MaxMind-DB](https://github.com/maxmind/MaxMind-DB)

# Purpose

这是一个软防火墙包。
它的目的是帮助人们防止未经授权访问IP地址的路由。它能够跟踪IP、国家和主机（动态IP），同时允许白名单IP访问整个站点。
它现在还能够检测和阻止来自单个IP或整个国家的攻击（太多请求）。

这个软件包可以防止一些麻烦，帮助你阻止对你的应用程序的访问，
但不能取代防火墙和设备，对于网络级别的攻击，你仍然需要一个真正的防火墙。

# Features

- 通过黑名单和白名单控制访问。
- 检测并阻止来自IP地址或国家/地区的应用程序攻击
- 使用中间件完成，因此您可以保护/取消保护路由组
- 所有功能都可用于主机、IP地址、IP地址范围和整个国家
- 每次请求增加不到10ms

# Concepts

Firewall 包含 `Blacklist` 及 `Whitelist` ， AttackBlocker 包含 `rate_limiter`。

## Blacklist

这些列表中的所有IP地址将无法访问由黑名单筛选器筛选的路由。

## Whitelist

这些IP地址、范围或国家可以

- 访问黑名单路由，即使它们在黑名单IP地址范围内
- 访问“允许白名单”筛选路由

## AttackBlocker

- 可锚定 `Blacklist` ，共享黑名单限制范围
- 可锚定 `Whitelist` ，共享白名单限制范围
- 可限定 `rate_limiter` 中 `IP` 访问频率
- 可限定 `rate_limiter` 中 `country` 访问频率

# IPs lists

IPs（黑白）列表可以存储在数组、文件和数据库中。
因此，要测试防火墙配置，可以发布配置文件并编辑blacklist或whitelist数组：

```php
'blacklist' => array(
    '127.0.0.1',
    '192.168.17.0/24',
    '127.0.0.1/255.255.255.255',
    '10.0.0.1-10.0.0.255',
    '172.17.*.*',
    'country:br',
    'host:www.baidu.com',
    '/usr/bin/firewall/blacklisted.txt',
),
```

文件（例如/usr/bin/firewall/blacklisted.txt）每行必须包含一个IP、范围或文件名，
而且，它将递归地搜索文件，因此，如果需要，您可以文件中包含一个文件：

```
127.0.0.2
10.0.0.0-10.0.0.100
/tmp/blacklist.txt
```

# Artisan Commands

```
php artisan firewall --list
+------------+-----------+-----------+
| ip_address | whitelist | blacklist |
+------------+-----------+-----------+
| 127.0.0.1  |           |     x     |
| 127.0.0.2  |           |     x     |
| country:us |     x     |           |
+------------+-----------+-----------+

php artisan firewall --reload

php artisan firewall:blacklist -l
php artisan firewall:blacklist -f
php artisan firewall:blacklist -d=country:cn
php artisan firewall:blacklist -a=127.0.0.1

php artisan firewall:whitelist --list
php artisan firewall:whitelist --flush
php artisan firewall:whitelist --delete=country:cn
php artisan firewall:whitelist --append=host:www.baidu.com

php artisan firewall:attack -l
php artisan firewall:attack -f
php artisan firewall:attack -d=country:cn
php artisan firewall:attack -a=10.0.0.0
```

# Facade

```
Firewall::isBlacklisted('127.0.0.1');
Firewall::isWhitelisted('127.0.0.1');
Firewall::isBlockAttacks('127.0.0.1');
```
