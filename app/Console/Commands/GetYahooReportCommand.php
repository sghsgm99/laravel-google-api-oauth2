<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\YahooService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GetYahooReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:get_yahoo_report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Yahoo Report from 3rd party API';

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

        YahooService::resolveAMG($account)->getTypeReport();

        YahooService::resolveAMG($account)->getSourceReport();

        YahooService::resolveDDC($account)->getDDCReport();

        $this->info('Retrieving reports from Yahoo successful.');

        return CommandAlias::SUCCESS;
    }
}
