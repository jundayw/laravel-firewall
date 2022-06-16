<?php

namespace Jundayw\LaravelFirewall\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;

class FirewallBlockAttackCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'firewall:attack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Laravel Firewall BlockAttack list.';

    private $filesystem;
    private $blacklist;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = app('files');
        $this->blacklist  = storage_path('firewall/blocker_blacklist.txt');
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
        $ips  = file($this->blacklist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $rows = [];
        foreach ($ips as $ip) {
            $rows[] = [$ip];
        }
        $this->table(['ip_address'], $rows);
    }

    private function flush()
    {
        if (!$this->filesystem->exists($this->blacklist)) {
            return $this->error("file [{$this->blacklist}] is't exists");
        }

        $this->filesystem->put($this->blacklist, '');

        $this->info('flush file successfully.');
        $this->info($this->blacklist);
    }

    private function append(string $ip)
    {
        if (!$this->filesystem->exists($this->blacklist)) {
            return $this->error("file [{$this->blacklist}] is't exists");
        }

        $this->filesystem->prepend($this->blacklist, $ip . PHP_EOL);

        $this->info('add IP address successfully.');
        $this->info($this->blacklist);
    }

    private function delete(string $ip)
    {
        if (!$this->filesystem->exists($this->blacklist)) {
            return $this->error("file [{$this->blacklist}] is't exists");
        }

        $lines = file($this->blacklist, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->filesystem->put($this->blacklist, '');
        foreach ($lines as $line) {
            if ($line == $ip) {
                continue;
            }
            $this->filesystem->append($this->blacklist, $line . PHP_EOL);
        }

        $this->info('add IP address successfully.');
        $this->info($this->blacklist);
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
