<?php

namespace Jundayw\LaravelFirewall\MaxMind;

class Country extends GeoIP
{
    protected $filename = __DIR__ . '/../../resources/GeoLite2-Country.mmdb';

    public function __construct(string $filename = null, array $locales = ['en'])
    {
        parent::__construct($filename ?? $this->filename, $locales ?? $this->locales);
    }

    public function get(string $ipAddress, ...$arguments)
    {
        return parent::get($ipAddress, 'country', 'raw', ...$arguments);
    }
}
