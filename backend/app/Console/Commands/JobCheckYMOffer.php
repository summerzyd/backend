<?php
namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CampaignService;
use App\Components\Config;

class JobCheckYMOffer extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_check_ym_offer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check YeahMobi offer';

    protected $api_id;
    protected $api_token;
    protected $refresh_time;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->api_id = Config::get('biddingos.ym.api_id');
        $this->api_token = md5(Config::get('biddingos.ym.api_token'));
        $this->refresh_time = Config::get('biddingos.ym.refresh_time');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $offerList = $this->getOfferList();
        // 按国家维度遍历
        foreach ($offerList as $offer) {
            $offerId = $offer->offer_id;
            $cid = $offer->campaign_id;
            // 处理配置文件的campaign
            $tmp = $this->getOffer([$offerId]);
            $offerDetail = null;
            if (sizeof($tmp) > 0) {
                $offerDetail = $tmp[0];
            }
            
            $campaign = Campaign::find($cid);
            if ($campaign) {
                $this->notice("Campaigh {$cid} status={$campaign->status} pause_status={$campaign->pause_status}");
                // 如果查不到对应的广告或限额为0且当前广告在投放中，则暂停，
                if (!$offerDetail || $offerDetail['remaining_daily_cap'] == 0) {
                    if ($campaign->status == Campaign::STATUS_DELIVERING) {
                        CampaignService::modifyStatus($cid, Campaign::STATUS_SUSPENDED, [
                            'approve_comment' => '达到Yeahmobi API限额暂停',
                            'pause_status' => Campaign::PAUSE_STATUS_PLATFORM
                        ]);
                        $this->notice('Campaigh ' . $cid . ' 达到Yeahmobi API限额暂停');
                    }
                } else {
                    $this->notice('Offer ' . $offerId . ' remaining_daily_cap=' . $offerDetail['remaining_daily_cap']);
                    // 如果当前是暂停状态且是平台暂停的则恢复投放
                    if ($campaign->status == Campaign::STATUS_SUSPENDED
                        && $campaign->pause_status == Campaign::PAUSE_STATUS_PLATFORM) {
                            CampaignService::modifyStatus($cid, Campaign::STATUS_DELIVERING);
                        $this->notice('Campaigh ' . $cid . ' 恢复投放');
                    }
                }
            }
        }
    }

    private function getOfferList()
    {
        $offerList = \DB::table('ym_material_manager')
            ->select('offer_id', 'campaign_id')
            ->where('status', 1)
            ->get();
        return $offerList;
    }
    
    private function getOffer($ids, $q = null)
    {
        $this->notice('Begin to get Offer ' . implode(',', $ids));
        $url = 'http://sync.yeahmobi.com/sync/offer/get';
        $offers = [];
        $size = sizeof($ids);
        $pageSize = 30;
        $totalPage = ceil($size / $pageSize);
        for ($i = 1; $i <= $totalPage; $i ++) {
            $tmpIds = array_slice($ids, $pageSize * ($i - 1), $pageSize);
            $args = [
                'api_id' => $this->api_id,
                'api_token' => $this->api_token,
                'limit' => $pageSize,
                'page' => 1
            ];
            foreach ($tmpIds as $idx => $id) {
                $args['filters[id][$in][' . $idx . ']'] = $id;
            }
            $data = $this->checkResult($this->httpGet($url . '?' . $this->encodeArgs($args)));
            if ($data) {
                foreach ($data['data'] as $k => $v) {
                    // 如果传入关键字，则只取与关键字相关的内容
                    if (!$q || stripos($v['appname'], $q) !== false) {
                        $offers[] = $v;
                    }
                }
            }
        }
        return $offers;
    }

    private function getOfferIdsByCountryIds($ids)
    {
        $this->notice('Begin to get OfferId ' . implode(',', $ids));
        $url = 'http://sync.yeahmobi.com/sync/offer/getOfferIdsByCountryIds';
        $args = [
            'api_id' => $this->api_id,
            'api_token' => $this->api_token,
            'limit' => 100,
            'page' => 1
        ];
        foreach ($ids as $idx => $id) {
            $args["ids[$idx]"] = $id;
        }
        $data = $this->checkResult($this->httpGet($url . '?' . $this->encodeArgs($args)));
        if ($data !== false) {
            $totalPage = $data['totalpage'];
            $data = $data['data'];
            for ($i = 2; $i <= $totalPage; $i ++) {
                $args['page'] = $i;
                $tmp = $this->checkResult($this->httpGet($url . '?' . $this->encodeArgs($args)));
                if ($tmp) {
                    $data = array_merge($data, $tmp['data']);
                }
            }
        }
        return $data;
    }

    private function getCountryID($codes = [])
    {
        $this->notice('Begin to get CountryID ' . implode(',', $codes));
        $url = 'http://sync.yeahmobi.com/sync/Country/getAll';
        $id = [];
        $args = [
            'api_id' => $this->api_id,
            'api_token' => $this->api_token
        ];
        $ret = $this->httpGet($url . '?' . $this->encodeArgs($args));
        $countryList = $this->checkResult($ret);
        if ($countryList) {
            if (sizeof($codes) > 0) {
                foreach ($countryList['data'] as $v) {
                    if (in_array($v['code'], $codes)) {
                        $id[$v['code']] = $v['id'];
                    }
                }
            } else {
                $id = $countryList['data'];
            }
        }
        return $id;
    }

    private function checkResult($ret)
    {
        $data = json_decode($ret, true);
        if (isset($data['flag']) && $data['flag'] == 'success') {
            return $data['data'];
        } else {
            $this->error('Check result Fail! ' . $ret);
            return false;
        }
    }

    private function encodeArgs($args)
    {
        $tmp = [];
        foreach ($args as $k => $v) {
            $tmp[] = urlencode($k) . '=' . urlencode($v);
        }
        return implode('&', $tmp);
    }

    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        
        return $output;
    }
}
