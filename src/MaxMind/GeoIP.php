<?php

namespace Jundayw\LaravelFirewall\MaxMind;

use GeoIp2\Database\Reader;

abstract class GeoIP
{
    protected $filename;
    protected $locales = ['zh-CN', 'en'];

    public function __construct(string $filename = null, array $locales = ['en'])
    {
        $this->filename = $filename ?? $this->filename;
        $this->locales  = $locales ?? $this->locales;
    }

    public function getReader()
    {
        if (!file_exists($this->filename)) {
            return null;
        }

        return new Reader($this->filename, $this->locales);
    }

    public function get(string $ipAddress, string $method = null, ...$arguments)
    {
        if (is_null($ipAddress)) {
            return $ipAddress;
        }

        if (is_null($reader = $this->getReader())) {
            return null;
        }
        if (is_null($method)) {
            return $reader;
        }
        if (!method_exists($reader, $method)) {
            return null;
        }

        $maxmind = null;

        try {
            $maxmind = $reader->$method($ipAddress);
            foreach ($arguments as $argument) {
                if ($maxmind) {
                    $maxmind = $maxmind->$argument ?? $maxmind[$argument] ?? null;
                }
            }
        } catch (\Exception $exception) {
            $maxmind = null;
        }

        return $maxmind;
    }
}
