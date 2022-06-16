<?php

namespace Jundayw\LaravelFirewall;

use Illuminate\Config\Repository as Config;
use Illuminate\Support\ServiceProvider;
use Jundayw\LaravelFirewall\Commands\FirewallBlacklistCommand;
use Jundayw\LaravelFirewall\Commands\FirewallBlockAttackCommand;
use Jundayw\LaravelFirewall\Commands\FirewallCommand;
use Jundayw\LaravelFirewall\Commands\FirewallWhitelistCommand;
use Jundayw\LaravelFirewall\MaxMind\Country;
use Jundayw\LaravelFirewall\Middleware\FirewallBlacklist;
use Jundayw\LaravelFirewall\Middleware\FirewallBlockAttacks;
use Jundayw\LaravelFirewall\Middleware\FirewallWhitelist;
use Jundayw\LaravelFirewall\Repositories\Repository;
use Jundayw\LaravelFirewall\Support\Countries;
use Jundayw\LaravelFirewall\Support\IpAddress;
use Jundayw\LaravelFirewall\Support\IpList;

class FirewallServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/firewall.php', 'firewall');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/firewall.php' => config_path('firewall.php'),
            ], 'firewall-config');
            $this->publishes([
                __DIR__ . '/../resources/GeoLite2-Country.mmdb' => storage_path('firewall/GeoLite2-Country.mmdb'),
            ], 'firewall-country');
        }

        $this->registerCache();
        $this->registerConfig();
        $this->registerRepository();
        $this->registerAttackBlocker();
        $this->registerFirewall();
        $this->registerCountries();
        $this->registerIpAddress();
        $this->registerIpList();
        $this->registerGeoIp();
        $this->addMiddlewareAlias('firewall.black', FirewallBlacklist::class);
        $this->addMiddlewareAlias('firewall.white', FirewallWhitelist::class);
        $this->addMiddlewareAlias('firewall.attacks', FirewallBlockAttacks::class);

        $this->commands([
            FirewallCommand::class,
            FirewallBlacklistCommand::class,
            FirewallWhitelistCommand::class,
            FirewallBlockAttackCommand::class,
        ]);
    }

    /**
     * Register the Cache driver used by Firewall.
     *
     * @return void
     */
    private function registerCache()
    {
        $this->app->singleton('firewall.cache', function() {
            $name = $this->app->make('firewall.config')->get('cache');
            return $this->app->make('cache')->store($name);
        });
    }

    /**
     * Register the config used by Firewall.
     *
     * @return void
     */
    private function registerConfig()
    {
        $this->app->singleton('firewall.config', function() {
            return new Config($this->app->make('config')->get('firewall', []));
        });
    }

    /**
     * Register the Repository used by Firewall.
     *
     * @return void
     */
    private function registerRepository()
    {
        $this->app->singleton('firewall.repository', function($app) {
            return new Repository(
                $app['firewall.cache'],
                $app['firewall.config'],
                $app['firewall.ip.address'],
                $app['firewall.ip.list'],
                $app['firewall.countries'],
                $this->app
            );
        });
    }

    /**
     * Register the attack blocker.
     *
     * @return void
     */
    private function registerAttackBlocker()
    {
        $this->app->singleton('firewall.attack.blocker', function($app) {
            return new AttackBlocker($app['firewall.repository']);
        });
    }

    /**
     * Register the firewall.
     *
     * @return void
     */
    private function registerFirewall()
    {
        $this->app->singleton('firewall', function($app) {
            $this->firewall = new Firewall(
                $app['firewall.repository'],
                $app['request'],
                $attackBlocker = $app['firewall.attack.blocker']
            );

            $attackBlocker->setFirewall($this->firewall);

            return $this->firewall->setIp();
        });
    }

    /**
     * Register the countries repository.
     *
     * @return void
     */
    private function registerCountries()
    {
        $this->app->singleton('firewall.countries', function($app) {
            return new Countries(
                $app['firewall.config'],
                $app['firewall.geoip']
            );
        });
    }

    /**
     * Register the ip address repository.
     *
     * @return void
     */
    private function registerIpAddress()
    {
        $this->app->singleton('firewall.ip.address', function() {
            return new IpAddress();
        });
    }

    /**
     * Register the ip list repository.
     *
     * @return void
     */
    private function registerIpList()
    {
        $this->app->singleton('firewall.ip.list', function($app) {
            return new IpList(
                $app['firewall.config'],
                $app['firewall.cache'],
                $app['firewall.ip.address'],
                $app['firewall.countries']
            );
        });
    }

    /**
     * Register the GeoIP.
     *
     * @return void
     */
    private function registerGeoIp()
    {
        $this->app->singleton('firewall.geoip', function() {
            $filename = $this->app->make('firewall.config')->get('geoip_database_path');
            return new Country($filename);
        });
    }

    /**
     * Register the middleware used by Firewall.
     *
     * @param $name
     * @param $class
     * @return mixed
     */
    protected function addMiddlewareAlias($name, $class)
    {
        $router = $this->app['router'];

        if (method_exists($router, 'aliasMiddleware')) {
            return $router->aliasMiddleware($name, $class);
        }

        return $router->middleware($name, $class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
