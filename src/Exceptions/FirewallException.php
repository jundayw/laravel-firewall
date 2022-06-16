<?php

namespace Jundayw\LaravelFirewall\Exceptions;

use Exception;

class FirewallException extends Exception
{
    private string $ip;

    public function __construct(string $ip, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }
}
