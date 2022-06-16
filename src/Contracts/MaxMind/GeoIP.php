<?php

namespace Jundayw\LaravelFirewall\Contracts\MaxMind;

interface GeoIP
{
    public function getReader();

    public function get(string $ipAddress, string $method = null, ...$arguments);
}
