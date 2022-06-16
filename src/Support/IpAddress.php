<?php

namespace Jundayw\LaravelFirewall\Support;

class IpAddress
{
    public function ipV4Valid(string $ip)
    {
        if (realpath($ip) !== false) {
            return false;
        }

        $isIpAddress = false;
        $isRange     = false;

        try {
            $ip          = str_replace('*', '0', $ip);
            $isIpAddress = inet_pton($ip) !== false;
            $isRange     = $this->cidrToRange($ip);
            if (!$isIpAddress && !$isRange) {
                $isRange = $this->twoIpsToRange($ip);
            }
        } catch (\Exception $e) {
        }

        return $ip && ($isIpAddress || $isRange);
    }

    /**
     * method cidrToRange.
     * Returns an array of only two IPv4 addresses that have the lowest ip
     * address as the first entry. If you need to check to see if an IPv4
     * address is within range please use the IPisWithinCIDR method above.
     * Usage:
     *     CIDR::cidrToRange("127.0.0.128/25");
     * Result:
     *     array(2) {
     *       "127.0.0.128",
     *       "127.0.0.255",
     *     }
     * @param string $cidr CIDR block
     * @return Array low end of range then high end of range.
     */
    public function cidrToRange(string $cidr)
    {
        if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(\/[0-9]{1,2})?$/", $cidr)) {
            return false;
        }

        $range = [];
        $cidr  = explode('/', $cidr);

        if (count($cidr) !== 2) {
            return false;
        }

        $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int) $cidr[1]))));
        $range[1] = long2ip((ip2long($cidr[0])) + pow(2, (32 - (int) $cidr[1])) - 1);

        return $range;
    }

    /**
     * method twoIpsToRange.
     * Returns an array of only two IPv4 addresses that have the lowest ip
     * address as the first entry. If you need to check to see if an IPv4
     * Usage:
     *     CIDR::cidrToRange("127.0.0.1-127.0.0.255");
     * Result:
     *     array(2) {
     *       "127.0.0.1",
     *       "127.0.0.255",
     *     }
     * @param string $ips
     * @return Array low end of range then high end of range.
     */
    public function twoIpsToRange(string $ips)
    {
        if (!preg_match_all("/^((?:\d{1,3}\.?){4})\-((?:\d{1,3}\.?){4})$/", $ips, $matches)) {
            return false;
        }

        return [
            $matches[1][0],
            $matches[2][0],
        ];
    }

    public function hostToIp(string $ip)
    {
        if (str($ip)->startsWith($host = 'host:')) {
            return gethostbyname(str_replace($host, '', $ip));
        }
        return $ip;
    }

    public function validRange(string $ip, string $range)
    {
        // Wildcarded range
        // 192.168.1.*
        if (!str($range)->contains('-') && str($range)->contains('*')) {
            $range = str_replace('*', '0', $range) . '-' . str_replace('*', '255', $range);
        }

        // Dashed range
        //   192.168.1.1-192.168.1.100
        //   0.0.0.0-255.255.255.255
        if (count($twoIps = explode('-', $range)) == 2) {
            $ip1 = ip2long($twoIps[0]);
            $ip2 = ip2long($twoIps[1]);

            return ip2long($ip) >= $ip1 && ip2long($ip) <= $ip2;
        }

        // Masked range or fixed IP
        //   192.168.17.1/16 or
        //   127.0.0.1/255.255.255.255 or
        //   10.0.0.1
        return $this->ipv4_match_mask($ip, $range);
    }

    public function ipv4_match_mask($ip, $network)
    {
        // Determines if a network in the form of
        //   192.168.17.1/16 or
        //   127.0.0.1/255.255.255.255 or
        //   10.0.0.1
        // matches a given ip
        $ipv4_arr = explode('/', $network);

        if (count($ipv4_arr) == 1) {
            $ipv4_arr[1] = '255.255.255.255';
        }

        $network_long = ip2long($ipv4_arr[0]);

        $x         = ip2long($ipv4_arr[1]);
        $mask      = long2ip($x) == $ipv4_arr[1] ? $x : 0xffffffff << (32 - $ipv4_arr[1]);
        $ipv4_long = ip2long($ip);

        return ($ipv4_long & $mask) == ($network_long & $mask);
    }
}
