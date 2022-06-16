<?php

namespace Jundayw\LaravelFirewall\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class FirewallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Laravel Firewall.';

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        if ($this->option('list')) {
            $this->list();
        } elseif ($this->option('reload')) {
            $this->reload();
        } else {
            return $this->error("Not enough arguments");
        }
    }

    private function list()
    {
        $ips  = app('firewall.ip.list')->getIps();
        $rows = [];
        foreach ($ips as $ip) {
            $rows[] = [
                $ip['ip_address'],
                $ip['type'] == 'whitelist' ? '    x    ' : '',
                $ip['type'] == 'blacklist' ? '    x    ' : '',
            ];
        }
        $this->table(['ip_address', 'whitelist', 'blacklist'], $rows);
    }

    private function reload()
    {
        $ipList = app('firewall.ip.list');
        app('firewall.cache')->forget($ipList->makeHashedKey('AllIP'));
        $ipList->getIps();

        $this->info('reload successfully.');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['list', 'l', InputOption::VALUE_NONE, 'List all IP address, white and blacklisted.'],
            ['reload', 'r', InputOption::VALUE_NONE, 'Clear Cache, Reload all IP address, white and blacklisted.'],
        ];
    }
}
