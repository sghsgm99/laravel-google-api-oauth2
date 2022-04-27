<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\BingService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GetBingReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:get_bing_report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Bing Report from 3rd party API';

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
        $account = Account::query()->where('id', 1)->first();

        if (! $account) {
            $this->info('No Account Found.');
            return CommandAlias::FAILURE;
        }

        $response = BingService::resolve($account)->getReport();

        if ($response['success']) {
            $this->info('Retrieving reports from Bing successful.');
            return CommandAlias::SUCCESS;
        }

        $this->info($response['message']);
        return CommandAlias::FAILURE;
    }
}
