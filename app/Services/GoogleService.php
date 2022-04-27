<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Log;
use Google\Client;
use Google\Service\Gmail;
use App\Models\Services\GoogleReportService;

/**
 * Class GoogleService.
 */
class GoogleService
{
    /**
     * @var Account $account
     */
    private $account;

    public static function resolve(Account $account): GoogleService
    {
        $googleService = app(self::class);
        $googleService->account = $account;

        return $googleService;
    }

    public function getAdsenseReport(): string
    {
        $account = Account::query()->where('id', 1)->first();

        Log::info('start gmail fetch - ' . date('Y-m-d'));

        $client = $this->getClient();
        $service = new Gmail($client);

        $optParams = [];
        $optParams['maxResults'] = 5; // Return Only 5 Messages
        $optParams['labelIds'] = 'INBOX'; // Only show messages in Inbox
        $optParams['q'] = 'from:noreply@lookermail.com'; // Only show messages in Inbox
        $messages = $service->users_messages->listUsersMessages('me',$optParams);
        $list = $messages->getMessages();
        $messageId = $list[0]->getId(); // Grab first Message

        $optParamsGet = [];
        $optParamsGet['format'] = 'full'; // Display message in payload
        $message = $service->users_messages->get('me',$messageId,$optParamsGet);
        $parts = $message->getPayload()->getParts();

        $body = $parts[1]['body'];
        $filename = $parts[1]['filename'];
        $attachmentId = $body->attachmentId;

        if ($attachmentId == null)
            return "success";

        $attachment = $service->users_messages_attachments->get('me',$messageId,$attachmentId);
        $attachmentData = $attachment->getData();
        $sanitizedData = strtr($attachmentData,'-_', '+/');
        $decodedData = base64_decode($sanitizedData);

        $str = preg_replace('~(,(?=[^"]*"(?:[^"]*"[^"]*")*[^"]*$)|")~', '', $decodedData);
        $row = explode("\n", $str);

        for ($i=1; $i<count($row); $i++) {
            if (!empty(trim($row[$i]))) {
                $data = explode(",", $row[$i]);
                
                $r = str_replace('$', '', $data[10]);

                if ($data[6] > 0) {
                    $cpc = (is_numeric($r) ? round($r/ $data[6], 2) : 0);
                } else {
                    $cpc = 0;
                }

                $reportData = [
                    'update_date' => $data[1].' '.$data[2],
                    'client_id' => $data[3],
                    'platform' => $data[4],
                    'channel' => $data[5],
                    'clicks' => $data[6],
                    'clicks_spam' => $data[7],
                    'coverage' => $data[8],
                    'cpc' => '$'.$cpc,
                    'net_revenue' => $r,
                    'ctr' => $data[11],
                    'impressions' => $data[12],
                    'impressions_spam' => $data[13],
                    'matched_query' => $data[14],
                    'queries' => $data[15],
                    'queries_spam' => $data[16],
                    'rpm' => $data[17]
                ];

                GoogleReportService::create($account, (array) $reportData);
            }
        }

        Log::info('done gmail fetch - ' . date('Y-m-d'));

        return 'Success';
    }

    private function getClient()
    {
        $client = new Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes(Gmail::GMAIL_READONLY);
        $client->setAuthConfig(\storage_path('app/credentials1.json'));
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $tokenPath = \storage_path('app/public/token1.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }
}
