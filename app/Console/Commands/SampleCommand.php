<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SampleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:sample';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sample command, it creates logs';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('Command run successful ' . date('Y-m-d H:i:s'));

        $this->info('Command run successful.');

        return CommandAlias::SUCCESS;
    }
}
