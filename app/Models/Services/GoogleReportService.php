<?php

namespace App\Models\Services;

use App\Models\Account;
use App\Models\GoogleReport;
use Carbon\Carbon;

class GoogleReportService extends ModelService
{
    /**
     * @var GoogleReport
     */
    private $googleReport;

    public function __construct(GoogleReport $googleReport)
    {
        $this->googleReport = $googleReport;
        $this->model = $googleReport;
    }

    public static function create(
        Account $account,
        array $data = []
    )
    {
        $reported_at = ($data['update_date']) ? Carbon::parse($data['update_date']) : null;

        $google_report = GoogleReport::whereUpdatedDate($reported_at)
                ->whereClientId($data['client_id'])
                ->wherePlatform($data['platform'])
                ->whereChannel($data['channel'])
                ->first();

        if (!$google_report) {
            $report = new GoogleReport();
            $report->account_id = $account->id;
            $report->client_id = $data['client_id'];
            $report->platform = $data['platform'];
            $report->channel = $data['channel'];
            $report->reported_at = $reported_at;
            $report->data = $data;

            $report->save();
        } else {
            $google_report->reported_at = $reported_at;
            $google_report->data = $data;

            $google_report->save();
        }
    }

    public function update()
    {

    }
}
