<?php

namespace Jundayw\LaravelFirewall\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;

class FirewallWhitelistCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall:whitelist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Laravel Firewall Whitelist.';

    private $filesystem;
    private $whitelist;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = app('files');
        $this->whitelist  = storage_path('firewall/whitelist.txt');
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        if ($this->option('list')) {
            $this->list();
        } elseif ($this->option('flush')) {
            $this->flush();
        } elseif ($ip = $this->option('append')) {
            $this->append($ip);
        } elseif ($ip = $this->option('delete')) {
            $this->delete($ip);
        } else {
            return $this->error("Not enough arguments");
        }
    }

    private function list()
    {
        $ips  = app('firewall.ip.list')->getIps();
        $rows = [];
        foreach ($ips as $ip) {
            if ($ip['type'] == 'blacklist') {
                continue;
            }
            $rows[] = [
                $ip['ip_address'],
                $ip['type'] == 'whitelist' ? '    x    ' : '',
            ];
        }
        $this->table(['ip_address', 'whitelist'], $rows);
    }

    private function flush()
    {
        if (!$this->filesystem->exists($this->whitelist)) {
            return $this->error("file [{$this->whitelist}] is't exists");
        }

        $this->filesystem->put($this->whitelist, '');

        $this->info('flush file successfully.');
        $this->info($this->whitelist);
    }

    private function append(string $ip)
    {
        if (!$this->filesystem->exists($this->whitelist)) {
            return $this->error("file [{$this->whitelist}] is't exists");
        }

        $this->filesystem->prepend($this->whitelist, $ip . PHP_EOL);

        $this->info('add IP address successfully.');
        $this->info($this->whitelist);
    }

    private function delete(string $ip)
    {
        if (!$this->filesystem->exists($this->whitelist)) {
            return $this->error("file [{$this->whitelist}] is't exists");
        }

        $lines = file($this->whitelist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->filesystem->put($this->whitelist, '');
        foreach ($lines as $line) {
            if ($line == $ip) {
                continue;
            }
            $this->filesystem->append($this->whitelist, $line . PHP_EOL);
        }

        $this->info('add IP address successfully.');
        $this->info($this->whitelist);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['list', 'l', InputOption::VALUE_NONE, 'List white IP address.'],
            ['flush', 'f', InputOption::VALUE_NONE, 'flush dynamic blacklist.'],
            ['append', 'a', InputOption::VALUE_REQUIRED, 'The IP address to be added.'],
            ['delete', 'd', InputOption::VALUE_REQUIRED, 'The IP address to be deleted.'],
        ];
    }
}
