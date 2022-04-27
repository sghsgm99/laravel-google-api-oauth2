<?php

namespace App\Models\Services;

use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V8\GoogleAdsClient;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V8\ResourceNames;
use Google\Ads\GoogleAds\V8\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V8\Resources\Campaign;
use Google\Ads\GoogleAds\V8\Services\CampaignOperation;
use Google\Ads\GoogleAds\V8\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V8\Enums\CriterionTypeEnum\CriterionType;
use Google\Ads\GoogleAds\V8\Enums\KeywordMatchTypeEnum\KeywordMatchType;
use Google\Ads\GoogleAds\V8\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\Lib\V8\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V8\GoogleAdsException;
use Google\Ads\GoogleAds\V8\Common\AdTextAsset;
use Google\Ads\GoogleAds\V8\Common\ResponsiveSearchAdInfo;
use Google\Ads\GoogleAds\V8\Enums\AdGroupAdStatusEnum\AdGroupAdStatus;
use Google\Ads\GoogleAds\V8\Enums\ServedAssetFieldTypeEnum\ServedAssetFieldType;
use Google\Ads\GoogleAds\V8\Resources\Ad;
use Google\Ads\GoogleAds\V8\Resources\AdGroupAd;
use Google\Ads\GoogleAds\V8\Services\AdGroupAdOperation;
use Google\Ads\GoogleAds\V8\Enums\KeywordPlanNetworkEnum\KeywordPlanNetwork;
use Google\Ads\GoogleAds\V8\Services\GenerateKeywordIdeaResult;
use Google\Ads\GoogleAds\V8\Services\KeywordAndUrlSeed;
use Google\Ads\GoogleAds\V8\Services\KeywordSeed;
use Google\Ads\GoogleAds\V8\Services\UrlSeed;
use Google\Ads\GoogleAds\V8\Common\ManualCpc;
use Google\Ads\GoogleAds\V8\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType;
use Google\Ads\GoogleAds\V8\Enums\BudgetDeliveryMethodEnum\BudgetDeliveryMethod;
use Google\Ads\GoogleAds\V8\Resources\Campaign\NetworkSettings;
use Google\Ads\GoogleAds\V8\Resources\CampaignBudget;
use Google\Ads\GoogleAds\V8\Services\CampaignBudgetOperation;
use Google\Ads\GoogleAds\V8\Enums\AdGroupStatusEnum\AdGroupStatus;
use Google\Ads\GoogleAds\V8\Enums\AdGroupTypeEnum\AdGroupType;
use Google\Ads\GoogleAds\V8\Resources\AdGroup;
use Google\Ads\GoogleAds\V8\Services\AdGroupOperation;
use Google\Ads\GoogleAds\V8\Common\KeywordInfo;
use Google\Ads\GoogleAds\V8\Enums\AdGroupCriterionStatusEnum\AdGroupCriterionStatus;
use Google\Ads\GoogleAds\V8\Resources\AdGroupCriterion;
use Google\Ads\GoogleAds\V8\Services\AdGroupCriterionOperation;
use Google\ApiCore\ApiException;
use Illuminate\Support\Str;
use DateTime;
use App\Models\Services\AdTemplateService;
use App\Models\KeyIntl;

class GoogleService extends ModelService
{
    private $kt_apikey = "4e56bd539deb313beaf95dd806b929a8d761bcfa";
    private $sem_apikey = "cda4ec39d887169e147e0ced5dcbfba8";
    private $mg_apikey = "71c1dcf1b4373520d414513fcb58f1e0fc695394c71c43e1736e090152635a8f";

    private const COMPETITION_HIGH = 3;
    private const LANG_US_ID = 1000;
    private const CUSTOMER_ID = 9641267633;
    private const LOC_IDS = array(2840);
    private const CN_LIMIT = 100;

    private $googleAdsClient;

    public function __construct()
    {
        $this->googleAdsClient = (new GoogleAdsClientBuilder())
            ->fromFile(storage_path('app/google_ads_php.ini'))
            ->withOAuth2Credential((new OAuth2TokenBuilder())
                ->fromFile(storage_path('app/google_ads_php.ini'))
                ->build())
            ->build();
    }

    private function getSemRushKeywords($kw, $v)
    {
        $keyword =  preg_replace('~\s~', '%20', $kw);
        $filter = '%2B|Co|Eq|1|%2B|Cp|Gt|' . $v;

        $url = 'https://api.semrush.com/?type=phrase_related&key=' . $this->sem_apikey . '&phrase=' . $keyword . '&export_columns=Ph,Nq,Cp,Co&database=us&display_filter=' . $filter;
        $res = file_get_contents($url);

        $list = explode("\n", $res);

        $kw_arr = [];
        for ($j = 1; $j < count($list); $j++) {
            $sem_v = explode(";", $list[$j]);
            if ($sem_v[0] != "")
                array_push($kw_arr, $sem_v[0]);
        }

        return $kw_arr;
    }

    private function getKeywordToolKeywords($kw, $v)
    {
        $url = 'https://api.keywordtool.io/v2/search/suggestions/google';

        $params = [
            'apikey' => $this->kt_apikey,
            'keyword' => $kw,
            'country' => 'US',
            'language' => 'en',
            'type' => 'suggestions',
            'exclude' => [],
            'metrics' => true,
            'metrics_location' => [
                2840
            ],
            'metrics_language' => [
                'en'
            ],
            'metrics_network' => 'googlesearchnetwork',
            'metrics_currency' => 'USD',
            'output' => 'json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($output, TRUE);

        $kw_arr = [];
        if (!empty($res["results"])) {
            foreach ($res["results"] as $key => $value) {
                foreach ($value as $cvalue) {
                    if ($cvalue["cmp"] == 1 && $cvalue["cpc"] >= $v && $cvalue["string"] != "") {
                        array_push($kw_arr, $cvalue["string"]);
                    }
                }
            }
        }

        return $kw_arr;
    }

    private function getTwinwordKeywords($kw, $v)
    {
        $keyword =  preg_replace('~\s~', '%20', $kw);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://twinword-keyword-suggestion-v1.p.rapidapi.com/suggest/?phrase=" . $keyword,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "x-rapidapi-host: twinword-keyword-suggestion-v1.p.rapidapi.com",
                "x-rapidapi-key: 6f8a6f8269mshf3d960cbd916ca5p1f68d2jsnee79565763ae"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $json_response = json_decode($response);

        $kw_arr = [];
        if ($err) {
        } else {
            if (!empty($json_response->keywords)) {
                foreach ($json_response->keywords as $key => $value) {
                    if ($value->cpc >= $v && $value->{'paid competition'} == 1 && $key != "") {
                        array_push($kw_arr, $key);
                    }
                }
            }
        }

        return $kw_arr;
    }

    private function getMangoolsKeywords($kw, $v)
    {
        $keyword =  preg_replace('~\s~', '%20', $kw);

        $ch = curl_init('https://api.mangools.com/v3/kwfinder/related-keywords?kw=' . $keyword . '&location_id=2840&language_id=1000');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'x-access-token: 71c1dcf1b4373520d414513fcb58f1e0fc695394c71c43e1736e090152635a8f'
            )
        );

        $result = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($result, TRUE);

        $kw_arr = [];
        if (!empty($res["keywords"])) {
            foreach ($res["keywords"] as $cvalue) {
                if ($cvalue["seo"] == 100 && $cvalue["cpc"] >= $v && $cvalue["kw"] != "") {
                    array_push($kw_arr, $cvalue["kw"]);
                }
            }
        }

        return $kw_arr;
    }

    private function getSemRushCPCBatch($kw)
    {
        $tmp = preg_replace('~\s~', '%20', $kw);
        $keywords = substr($tmp, 0, -1);

        $url = 'https://api.semrush.com/?type=phrase_these&key=' . $this->sem_apikey . '&phrase=' . $keywords . '&export_columns=Ph,Nq,Cp,Co,Nr,Td&database=us';
        $res = file_get_contents($url);

        return explode("\n", $res);
    }

    private function getKeywordHighAPIs($keyword_arr, $lvalue)
    {
        $customerId = self::CUSTOMER_ID;
        $languageId = self::LANG_US_ID;
        $locationIds = self::LOC_IDS;
        $pageUrl = null;

        $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();
        $kw_arr = array_slice($keyword_arr, 0, 5);

        $keywordList = [
            'data' => [],
            'meta' => [
                'total' => 0
            ]
        ];

        $requestOptionalArgs = [];
        if (empty($kw_arr)) {
            $requestOptionalArgs['urlSeed'] = new UrlSeed(['url' => $pageUrl]);
        } elseif (is_null($pageUrl)) {
            $requestOptionalArgs['keywordSeed'] = new KeywordSeed(['keywords' => $kw_arr]);
        } else {
            $requestOptionalArgs['keywordAndUrlSeed'] =
                new KeywordAndUrlSeed(['url' => $pageUrl, 'keywords' => $kw_arr]);
        }

        $geoTargetConstants = array_map(function ($locationId) {
            return ResourceNames::forGeoTargetConstant($locationId);
        }, $locationIds);

        $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas(
            [
                'language' => ResourceNames::forLanguageConstant($languageId),
                'customerId' => $customerId,
                'geoTargetConstants' => $geoTargetConstants,
                'keywordPlanNetwork' => KeywordPlanNetwork::GOOGLE_SEARCH_AND_PARTNERS
            ] + $requestOptionalArgs
        );

        foreach ($response->iterateAllElements() as $result) {
            $lowrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getLowTopOfPageBidMicros());
            $comp = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getCompetition());
            $lowrange = round($lowrange / 1000000, 2);

            if ($comp > self::COMPETITION_HIGH && $lowrange >= $lvalue) {
                $avg = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getAvgMonthlySearches());
                $highrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getHighTopOfPageBidMicros());
                $highrange = round($highrange / 1000000, 2);
                $keyword = $result->getText();

                $keywordList['data'][] = [
                    'keyword' => $keyword,
                    'avgmonth' => $avg,
                    'lowrange' => '$' . $lowrange,
                    'highrange' => '$' . $highrange,
                    'compet' => 'HIGH'
                ];

                $keywordList['meta']['total']++;
            }
        }

        return $keywordList;
    }

    public function getKeywordHigh(string $kw, int $lvalue)
    {
        $customerId = self::CUSTOMER_ID;
        $languageId = self::LANG_US_ID;
        $locationIds = self::LOC_IDS;
        $pageUrl = null;

        $keywordList = [
            'data' => [],
            'meta' => [
                'total' => 0
            ]
        ];

        $kw_arr = [$kw];

        $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();

        $requestOptionalArgs = [];
        if (empty($kw_arr)) {
            $requestOptionalArgs['urlSeed'] = new UrlSeed(['url' => $pageUrl]);
        } elseif (is_null($pageUrl)) {
            $requestOptionalArgs['keywordSeed'] = new KeywordSeed(['keywords' => $kw_arr]);
        } else {
            $requestOptionalArgs['keywordAndUrlSeed'] =
                new KeywordAndUrlSeed(['url' => $pageUrl, 'keywords' => $kw_arr]);
        }

        $geoTargetConstants = array_map(function ($locationId) {
            return ResourceNames::forGeoTargetConstant($locationId);
        }, $locationIds);

        $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas(
            [
                'language' => ResourceNames::forLanguageConstant($languageId),
                'customerId' => $customerId,
                'geoTargetConstants' => $geoTargetConstants,
                'keywordPlanNetwork' => KeywordPlanNetwork::GOOGLE_SEARCH_AND_PARTNERS
            ] + $requestOptionalArgs
        );

        foreach ($response->iterateAllElements() as $result) {
            $lowrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getLowTopOfPageBidMicros());
            $comp = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getCompetition());
            $lowrange = round($lowrange / 1000000, 2);

            if (($comp > self::COMPETITION_HIGH) && ($lowrange >= $lvalue)) {
                $avg = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getAvgMonthlySearches());
                $highrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getHighTopOfPageBidMicros());
                $highrange = round($highrange / 1000000, 2);
                $keyword = $result->getText();

                $keywordList['data'][] = [
                    'keyword' => $keyword,
                    'avgmonth' => $avg,
                    'lowrange' => '$' . $lowrange,
                    'highrange' => '$' . $highrange,
                    'compet' => 'HIGH'
                ];

                $keywordList['meta']['total']++;
            }
        }

        if ($keywordList['meta']['total'] == 0) {
            $kw_arr = [];
            $sem_kws = $this->getSemRushKeywords($kw, $lvalue);
            $kt_kws = $this->getKeywordToolKeywords($kw, $lvalue);
            $tw_kws = $this->getTwinwordKeywords($kw, $lvalue);
            $mg_kws = $this->getMangoolsKeywords($kw, $lvalue);
            $kw_arr = array_unique(array_merge($sem_kws, $kt_kws));
            $kw_arr = array_unique(array_merge($kw_arr, $tw_kws));
            $kw_arr = array_unique(array_merge($kw_arr, $mg_kws));

            if (!empty($kw_arr))
                return $this->getKeywordHighAPIs($kw_arr, $lvalue);
        }

        return $keywordList;
    }

    public function getCampaignNames(string $kw, int $lvalue)
    {
        return [
            'cname_arr' => [
                ['value'=>1, 'text' => 'INTL111'],
                ['value'=>21, 'text' => 'INTL222']
            ],
            'kw_arr' => [
                'aaa','bbb'
            ],
            'intl_arr' => ['111', '222'],
        ];



        $customerId = self::CUSTOMER_ID;
        $languageId = self::LANG_US_ID;
        $locationIds = self::LOC_IDS;
        $pageUrl = null;
        $kw_arr = [$kw];

        $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();

        $requestOptionalArgs = [];
        if (empty($kw_arr)) {
            $requestOptionalArgs['urlSeed'] = new UrlSeed(['url' => $pageUrl]);
        } elseif (is_null($pageUrl)) {
            $requestOptionalArgs['keywordSeed'] = new KeywordSeed(['keywords' => $kw_arr]);
        } else {
            $requestOptionalArgs['keywordAndUrlSeed'] =
                new KeywordAndUrlSeed(['url' => $pageUrl, 'keywords' => $kw_arr]);
        }

        $geoTargetConstants = array_map(function ($locationId) {
            return ResourceNames::forGeoTargetConstant($locationId);
        }, $locationIds);

        $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas(
            [
                'language' => ResourceNames::forLanguageConstant($languageId),
                'customerId' => $customerId,
                'geoTargetConstants' => $geoTargetConstants,
                'keywordPlanNetwork' => KeywordPlanNetwork::GOOGLE_SEARCH_AND_PARTNERS
            ] + $requestOptionalArgs
        );

        $res_result = [
            'cname_arr' => [],
            'kw_arr' => [],
            'intl_arr' => [],
        ];
        $cnt = 0;

        foreach ($response->iterateAllElements() as $result) {
            $lowrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getLowTopOfPageBidMicros());
            $comp = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getCompetition());
            $lowrange = round($lowrange / 1000000, 2);

            if (($comp > self::COMPETITION_HIGH) && ($lowrange >= $lvalue)) {
                $keyword = $result->getText();

                $q_res = KeyIntl::where('keyword', $keyword)->first();

                if (empty($q_res)) {
                    $mycams = KeyIntl::all();

                    $tmp_arr = [];
                    foreach ($mycams as $item) {
                        $tmp_arr[] = $item['intl'];
                    }

                    do {
                        $n = mt_rand(1, 1900);
                    } while (in_array($n, $tmp_arr));

                    $res_result['cname_arr'][] = [
                        'value' => $cnt++,
                        'text' => 'INTL' . $n . ' ' . $keyword . ' ($' . $lowrange . ')'
                    ];
                    $res_result['kw_arr'][] = $keyword;
                    $res_result['intl_arr'][] = $n;
                }

                if ($cnt > self::CN_LIMIT)
                    return $res_result;
            }
        }

        return $res_result;
    }

    public function getKeywordLow(int $currentPage, int $perPage, string $kw, int $lvalue)
    {
        $customerId = self::CUSTOMER_ID;
        $languageId = self::LANG_US_ID;
        $locationIds = self::LOC_IDS;
        $pageUrl = null;

        $from_page = ($currentPage - 1) * $perPage - 1;
        $to_page = $currentPage * $perPage;

        $kw_arr = [$kw];

        $keywordList = [
            'data' => [],
            'meta' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => 0
            ]
        ];

        $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();

        $requestOptionalArgs = [];
        if (empty($kw_arr)) {
            $requestOptionalArgs['urlSeed'] = new UrlSeed(['url' => $pageUrl]);
        } elseif (is_null($pageUrl)) {
            $requestOptionalArgs['keywordSeed'] = new KeywordSeed(['keywords' => $kw_arr]);
        } else {
            $requestOptionalArgs['keywordAndUrlSeed'] =
                new KeywordAndUrlSeed(['url' => $pageUrl, 'keywords' => $kw_arr]);
        }

        $geoTargetConstants = array_map(function ($locationId) {
            return ResourceNames::forGeoTargetConstant($locationId);
        }, $locationIds);

        $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas(
            [
                'language' => ResourceNames::forLanguageConstant($languageId),
                'customerId' => $customerId,
                'geoTargetConstants' => $geoTargetConstants,
                'keywordPlanNetwork' => KeywordPlanNetwork::GOOGLE_SEARCH_AND_PARTNERS
            ] + $requestOptionalArgs
        );

        $cnt = 0;
        $sem_kw = "";

        foreach ($response->iterateAllElements() as $result) {
            $avg = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getAvgMonthlySearches());
            $lowrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getLowTopOfPageBidMicros());
            $comp = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getCompetition());
            $lowrange = round($lowrange / 1000000, 2);

            if ($comp > 3 && $lowrange < $lvalue && $avg > 1000) {
                if (($cnt > $from_page) && ($cnt < $to_page)) {
                    $highrange = (is_null($result->getKeywordIdeaMetrics()) ? 0 : $result->getKeywordIdeaMetrics()->getHighTopOfPageBidMicros());
                    $highrange = round($highrange / 1000000, 2);
                    $keyword = $result->getText();

                    $keywordList['data'][] = [
                        'keyword' => $keyword,
                        'avgmonth' => $avg,
                        'lowrange' => '$' . $lowrange,
                        'highrange' => '$' . $highrange,
                        'compet' => 'HIGH',
                        'sem_cpc' => '-',
                        'action' => $keyword
                    ];

                    $sem_kw .= $keyword . ";";
                }
                $cnt++;
            }
        }

        $keywordList['meta']['total'] = $cnt;

        if ($cnt > 0) {
            $sem_arr = $this->getSemRushCPCBatch($sem_kw);

            for ($i = 0; $i < count($keywordList['data']); $i++) {
                for ($j = 1; $j < count($sem_arr); $j++) {
                    $sem_v = explode(";", $sem_arr[$j]);

                    if ($keywordList['data'][$i]["keyword"] == $sem_v[0]) {
                        $keywordList['data'][$i]["sem_cpc"] = $sem_v[2];
                    }
                }
            }
        }

        return $keywordList;
    }

    public function createCampaignAdscreateCampaignAds(
        $kws,
        $intls,
        $budget,
        $keyword,
        $net_search,
        $net_display,
        $gn_options,
        $bid,
        $ek_list,
        $pk_list,
        $bk_list,
        $finalURL,
        $path1,
        $path2,
        $headlines,
        $descriptions
    ) {
        try {
            $campaignId_arr = self::addCampaign($kws, $intls, $budget, $keyword, $net_search, $net_display);
            $adGroupId_arr = self::addAdGroup($gn_options, $bid, $campaignId_arr);
            self::addKeywords($ek_list, $pk_list, $bk_list, $adGroupId_arr[0]);
            self::addResponsiveSearchAd($finalURL, $path1, $path2, $headlines, $descriptions, $adGroupId_arr);

            return ResponseService::success('Submitted successfully.');
        } catch (GoogleAdsException $googleAdsException) {
            printf(
                "Request with ID '%s' has failed.%sGoogle Ads failure details:%s",
                $googleAdsException->getRequestId(),
                PHP_EOL,
                PHP_EOL
            );
            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                /** @var GoogleAdsError $error */
                printf(
                    "\t%s: %s%s",
                    $error->getErrorCode()->getErrorCode(),
                    $error->getMessage(),
                    PHP_EOL
                );
            }
            exit(1);
        } catch (ApiException $apiException) {
            printf(
                "ApiException was thrown with message '%s'.%s",
                $apiException->getMessage(),
                PHP_EOL
            );
            exit(1);
        }
    }

    private static function addCampaign($kws, $intls, $budget, $keyword, $net_search, $net_display)
    {
        $customerId = self::CUSTOMER_ID;

        $budgetResourceName = self::addCampaignBudget($customerId, $budget);

        $networkSettings = new NetworkSettings([
            'target_google_search' => true,
            'target_search_network' => ($net_search ? true : false),
            'target_content_network' => ($net_display ? true : false),
            'target_partner_search_network' => false
        ]);

        $campaignOperations = [];
        for ($i = 0; $i < count($kws); $i++) {
            $kw = $kws[$i];
            $intl = $intls[$i];
            $cname = 'INTL' . $intl . ' ' . $kw;

            $campaign = new Campaign([
                'name' => $cname,
                'advertising_channel_type' => AdvertisingChannelType::SEARCH,
                'status' => CampaignStatus::PAUSED,
                'manual_cpc' => new ManualCpc(),
                'campaign_budget' => $budgetResourceName,
                'network_settings' => $networkSettings,
                'start_date' => date('Ymd', strtotime('+1 day')),
                'end_date' => date('Ymd', strtotime('+1 month'))
            ]);

            $campaignOperation = new CampaignOperation();
            $campaignOperation->setCreate($campaign);
            $campaignOperations[] = $campaignOperation;

            AdTemplateService::create($keyword, $kw, $intl);
        }

        $campaignServiceClient = $this->googleAdsClient->getCampaignServiceClient();
        $response = $campaignServiceClient->mutateCampaigns($customerId, $campaignOperations);

        $campaignId_arr = [];

        foreach ($response->getResults() as $addedCampaign) {
            $campaignId_arr[] = explode("/", $addedCampaign->getResourceName())[3];
        }

        return $campaignId_arr;
    }

    private static function addCampaignBudget(int $customerId, $v)
    {
        $budget = new CampaignBudget([
            'name' => 'budget #' . self::getPrintableDatetime(),
            'delivery_method' => BudgetDeliveryMethod::STANDARD,
            'amount_micros' => $v * 1000000
            //'explicitly_shared' => false
        ]);

        $campaignBudgetOperation = new CampaignBudgetOperation();
        $campaignBudgetOperation->setCreate($budget);

        $campaignBudgetServiceClient = $this->googleAdsClient->getCampaignBudgetServiceClient();
        $response = $campaignBudgetServiceClient->mutateCampaignBudgets(
            $customerId,
            [$campaignBudgetOperation]
        );

        $addedBudget = $response->getResults()[0];

        return $addedBudget->getResourceName();
    }

    private static function addAdGroup($gn_options, $bid, $campaignId_arr)
    {
        $customerId = self::CUSTOMER_ID;

        $operations = [];

        for ($i = 0; $i < count($campaignId_arr); $i++) {
            $grpname = $gn_options[$i];
            $campaignId = $campaignId_arr[$i];

            $campaignResourceName = ResourceNames::forCampaign($customerId, $campaignId);

            $adGroup = new AdGroup([
                'name' => $grpname,
                'campaign' => $campaignResourceName,
                'status' => AdGroupStatus::ENABLED,
                'type' => AdGroupType::SEARCH_STANDARD,
                'cpc_bid_micros' => $bid * 1000000
            ]);

            $adGroupOperation = new AdGroupOperation();
            $adGroupOperation->setCreate($adGroup);
            $operations[] = $adGroupOperation;
        }

        $adGroupServiceClient = $this->googleAdsClient->getAdGroupServiceClient();
        $response = $adGroupServiceClient->mutateAdGroups(
            $customerId,
            $operations
        );

        $adGroupId_arr = [];

        foreach ($response->getResults() as $addedAdGroup) {
            $adGroupId_arr[] = explode("/", $addedAdGroup->getResourceName())[3];
        }

        return $adGroupId_arr;
    }

    private static function addKeywords($ek_list, $pk_list, $bk_list, int $adGroupId)
    {
        if (!empty($ek_list) && $ek_list != "") {
            $kw_arry = explode("\n", $ek_list);

            $adGroupCriterionOperations = [];
            for ($i = 0; $i < count($kw_arry); $i++) {

                if (!empty(trim($kw_arry[$i]))) {
                    $keywordInfo = new KeywordInfo([
                        'text' => $kw_arry[$i],
                        'match_type' => KeywordMatchType::EXACT
                    ]);

                    $adGroupCriterion = new AdGroupCriterion([
                        'ad_group' => ResourceNames::forAdGroup($customerId, $adGroupId),
                        'status' => AdGroupCriterionStatus::ENABLED,
                        'keyword' => $keywordInfo
                    ]);

                    $adGroupCriterionOperation = new AdGroupCriterionOperation();
                    $adGroupCriterionOperation->setCreate($adGroupCriterion);
                    $adGroupCriterionOperations[] = $adGroupCriterionOperation;
                }
            }

            $adGroupCriterionServiceClient = $this->googleAdsClient->getAdGroupCriterionServiceClient();
            $response = $adGroupCriterionServiceClient->mutateAdGroupCriteria(
                $customerId,
                $adGroupCriterionOperations
            );
        }

        if (!empty($pk_list) && $pk_list != "") {
            $kw_arry = explode("\n", $pk_list);

            $adGroupCriterionOperations = [];
            for ($i = 0; $i < count($kw_arry); $i++) {

                if (!empty(trim($kw_arry[$i]))) {
                    $keywordInfo = new KeywordInfo([
                        'text' => $kw_arry[$i],
                        'match_type' => KeywordMatchType::PHRASE
                    ]);

                    $adGroupCriterion = new AdGroupCriterion([
                        'ad_group' => ResourceNames::forAdGroup($customerId, $adGroupId),
                        'status' => AdGroupCriterionStatus::ENABLED,
                        'keyword' => $keywordInfo
                    ]);

                    $adGroupCriterionOperation = new AdGroupCriterionOperation();
                    $adGroupCriterionOperation->setCreate($adGroupCriterion);
                    $adGroupCriterionOperations[] = $adGroupCriterionOperation;
                }
            }

            $adGroupCriterionServiceClient = $this->googleAdsClient->getAdGroupCriterionServiceClient();
            $response = $adGroupCriterionServiceClient->mutateAdGroupCriteria(
                $customerId,
                $adGroupCriterionOperations
            );
        }

        if (!empty($bk_list) && $bk_list != "") {
            $kw_arry = explode("\n", $bk_list);

            $adGroupCriterionOperations = [];
            for ($i = 0; $i < count($kw_arry); $i++) {

                if (!empty(trim($kw_arry[$i]))) {
                    $keywordInfo = new KeywordInfo([
                        'text' => $kw_arry[$i],
                        'match_type' => KeywordMatchType::BROAD
                    ]);

                    $adGroupCriterion = new AdGroupCriterion([
                        'ad_group' => ResourceNames::forAdGroup($customerId, $adGroupId),
                        'status' => AdGroupCriterionStatus::ENABLED,
                        'keyword' => $keywordInfo
                    ]);

                    $adGroupCriterionOperation = new AdGroupCriterionOperation();
                    $adGroupCriterionOperation->setCreate($adGroupCriterion);
                    $adGroupCriterionOperations[] = $adGroupCriterionOperation;
                }
            }

            $adGroupCriterionServiceClient = $this->googleAdsClient->getAdGroupCriterionServiceClient();
            $response = $adGroupCriterionServiceClient->mutateAdGroupCriteria(
                $customerId,
                $adGroupCriterionOperations
            );
        }

        return "ok";
    }

    private static function addResponsiveSearchAd($finalURL, $path1, $path2, $head, $desc, $adGroupId_arr)
    {
        $headlines = [];
        $descriptions = [];

        $hl_arry = explode("\n", $head);
        $cnt = (count($hl_arry) > 15) ? 15 : count($hl_arry);
        array_push($headlines, self::createAdTextAsset(
            $hl_arry[0],
            ServedAssetFieldType::HEADLINE_1
        ));
        for ($i = 1; $i < $cnt; $i++) {
            $hl = self::createAdTextAsset($hl_arry[$i]);
            array_push($headlines, $hl);
        }

        $desc_arry = explode("\n", $desc);
        $cnt = (count($desc_arry) > 4) ? 4 : count($desc_arry);
        for ($i = 0; $i < $cnt; $i++) {
            $de = self::createAdTextAsset($desc_arry[$i]);
            array_push($descriptions, $de);
        }

        $ad = new Ad([
            'responsive_search_ad' => new ResponsiveSearchAdInfo([
                'headlines' => $headlines,
                'descriptions' => $descriptions,
                'path1' => $path1,
                'path2' => $path2
            ]),
            'final_urls' => [$finalURL]
        ]);

        $adGroupAdOperations = [];

        for ($i = 0; $i < count($adGroupId_arr); $i++) {
            $adGroupId = $adGroupId_arr[$i];

            $adGroupAd = new AdGroupAd([
                'ad_group' => ResourceNames::forAdGroup($customerId, $adGroupId),
                'status' => AdGroupAdStatus::PAUSED,
                'ad' => $ad
            ]);

            $adGroupAdOperation = new AdGroupAdOperation();
            $adGroupAdOperation->setCreate($adGroupAd);
            $adGroupAdOperations[] = $adGroupAdOperation;
        }

        $adGroupAdServiceClient = $this->googleAdsClient->getAdGroupAdServiceClient();
        $response = $adGroupAdServiceClient->mutateAdGroupAds($customerId, $adGroupAdOperations);

        $ad_arr = [];

        foreach ($response->getResults() as $ads) {
            $ad_arr = $ads->getResourceName();
        }

        return $ad_arr;
    }

    private static function createAdTextAsset(string $text, int $pinField = null): AdTextAsset
    {
        $adTextAsset = new AdTextAsset(['text' => $text]);
        if (!is_null($pinField)) {
            $adTextAsset->setPinnedField($pinField);
        }
        return $adTextAsset;
    }

    private static function getPrintableDatetime(): string
    {
        return (new DateTime())->format("Y-m-d\TH:i:s.vP");
    }
}
