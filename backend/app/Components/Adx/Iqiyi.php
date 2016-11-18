<?php

namespace App\Components\Adx;

use App\Components\Helper\HttpClientHelper;
use App\Components\Helper\LogHelper;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignVideo;
use App\Models\Client;
use App\Models\IQiyiClientManager;
use App\Models\IQiyiMaterialManager;
use App\Components\Config;

class Iqiyi extends BaseAdx
{
    protected $dspid;
    protected $token;
    protected $url_advertiser_upload;
    protected $url_advertiser_status_single;
    protected $url_advertiser_status_multi;
    protected $url_ad_upload;
    protected $url_ad_status;
    protected $bannerId;
    protected $material_type = 1;//1图片，2视频
    public function __construct()
    {
        $this->token = Config::get('biddingos.adx.iqiyi.token');
        $this->url_advertiser_upload = Config::get('biddingos.adx.iqiyi.advertiser_upload');
        $this->url_advertiser_status_single = Config::get('biddingos.adx.iqiyi.advertiser_status_single');
        $this->url_advertiser_status_multi = Config::get('biddingos.adx.iqiyi.advertiser_status_multi');
        $this->url_ad_upload = Config::get('biddingos.adx.iqiyi.ad_upload');
        $this->url_ad_status = Config::get('biddingos.adx.iqiyi.ad_status');
    }

    /**
     * 上传Adx广告主和素材 0:未达到提交的条件 1：上传失败 2：上传成功
     *
     * @return array {"code":0, "msg":"广告主的资质未上传"}|{"code":2, "msg":"m_id为1001"}
     */
    public function upload($bannerId)
    {
        $this->bannerId = $bannerId;
        $banner = Banner::find($this->bannerId);
        $campaign = $banner->campaign;
        if ($campaign->ad_type == Campaign::AD_TYPE_VIDEO) {
            $this->material_type = 2;
        }
        //判断是否已经上传符合尺寸的素材
        $ret = $this->getMaterialImages();
        if (count($ret) <= 0) {
            $ad_size = $this->getSize($campaign->ad_type);
            if (empty($ad_size)) {
                return ["code" => 0, "msg" => "该Adx不支持该类型广告"];
            }
            $ad_size = implode(',', $ad_size);
            return ["code" => 0, "msg" => "投放到该Adx需要{$ad_size}的素材，请上传"];
        }

        // 上传广告主
        return $this->uploadAdvertiser();

    }

    /**
     * 查询Adx广告主和素材状态 0:未上传 1：审核中 2：审核通过 3:拒绝
     *
     * @return array {"code":3, "msg":"广告素材规格不对"}
     */
    public function status($bannerId)
    {
        $this->bannerId = $bannerId;
        $banner = Banner::find($this->bannerId);
        $campaign = $banner->campaign;
        $client = $campaign->client;

        //判断广告主状态
        $clientMgr = IQiyiClientManager::find($client->clientid);
        if ($clientMgr) {
            if ($clientMgr->status==IQiyiClientManager::STATUS_PENDING_AUDIT) {
                //check advertiser's status
                $temp_arr = $this->adverStatus();
                if ($temp_arr["msg"]==="广告主审核中") {
                    $temp_arr["msg"] = "广告主{$client->clientid}审核中";
                }
                return $temp_arr;
            } elseif ($clientMgr->status==IQiyiClientManager::STATUS_REJECT) {
                return ["code" =>3, "msg" => "广告主{$client->clientid}被拒绝"];
            }
        } else {
            return ["code" =>0, "msg" => "广告主{$client->clientid}未上传"];
        }

        // 上传待提交的广告素材
        $res = $this->uploadAdMaterial();
        if ($res['code']==0) {//没有待提交的素材，可以查询状态
            // check material's status
            $res =  $this->checkAdxStatusAndUpdate();
            if ($res['code']==0) {//没有审核中的素材，则判断是否审核通过
                $ret = $this->getImage(IQiyiMaterialManager::STATUS_ADOPT);
                if (count($ret)>0) {
                    return ["code" =>2, "msg" => "审核通过"];
                } else {
                    return ["code" =>0, "msg" => "素材未上传"];
                }
            } else {
                return $res;
            }
        } else {
            return $res;
        }
    }

    private function insertClientsInfo()
    {
        //查询投放到爱奇艺的广告主信息
        $row = \DB::table('clients AS c')
        ->leftJoin('campaigns AS cpn', 'c.clientid', '=', 'cpn.clientid')
        ->leftJoin('banners AS b', 'b.campaignid', '=', 'cpn.campaignid')
        ->select('c.clientname', 'c.clientid')
        ->where('c.clients_status', Client::STATUS_ENABLE)
        ->where('b.bannerid', $this->bannerId)
        ->distinct()
        ->first();

        //检测广告主是否存在
        $client = IQiyiClientManager::where('clientid', $row->clientid)
        ->first();
        if ($client) {
            //广告主被替换时，更新名字并更改状态为待提交
            if ($row->clientname != $client->clientname) {
                IQiyiClientManager::where('clientid', $row->clientid)->update([
                'clientname' => $row->clientname,
                'status' => IQiyiClientManager::STATUS_PENDING_SUBMISSION,
                'upload_op' => 'update'
                        ]);
                        LogHelper::info('update ' . $row->clientid .
                                ' name from ' . $client->clientname .
                                ' to ' . $row->clientname);
            }
        } else {
            //新增广告主，插入到广告主管理表
            $client = new IQiyiClientManager();
            $client->clientid = $row->clientid;
            $client->clientname = $row->clientname;
            $client->status = $client::STATUS_PENDING_SUBMISSION;
            $client->upload_op = 'create';
            $client->save();
            LogHelper::info('add advertiser clientid ' . $row->clientid . ' name ' . $row->clientname);
        }
    }

    private function getClients($status = IQiyiClientManager::STATUS_PENDING_SUBMISSION)
    {
        $res = \DB::table('clients AS c')
        ->leftJoin('campaigns AS cpn', 'c.clientid', '=', 'cpn.clientid')
        ->leftJoin('banners AS b', 'b.campaignid', '=', 'cpn.campaignid')
        ->leftJoin('iqiyi_client_manager AS icm', 'c.clientid', '=', 'icm.clientid')
        ->select('c.clientname', 'c.clientid', 'icm.upload_op')
        ->where('c.clients_status', Client::STATUS_ENABLE)
        ->where('b.bannerid', $this->bannerId)
        ->where('icm.status', $status)
        ->distinct()
        ->first();
        return $res;
    }

    // 上传广告主
    private function uploadAdvertiser()
    {
        $banner = Banner::find($this->bannerId);
        $campaign = $banner->campaign;
        $client = $campaign->client;
        
        $clientId = $client->clientid;

        //判断是否已经审核通过
        $clientMgr = IQiyiClientManager::find($client->clientid);
        if ($clientMgr) {
            if ($clientMgr->status == IQiyiClientManager::STATUS_ADOPT) {
                return ["code" => 2, "msg" => "广告主" . $clientId . "已上传"];
            }
        }
        //广告主未提交，审核中，审核不通过
        //广告主上传前，插入或需要上传的广告主信息
        $this->insertClientsInfo();
        //获取 待提交 的广告主
        $row = $this->getClients();
        if (empty($row)) {
            return ["code" => 2, "msg" => "广告主" . $clientId . "审核中"];
        }
        $name = urlencode($row->clientname);
        $client = [
            "ad_id: {$row->clientid}",//广告主ID
            "name: {$name}",
            //'industry' => '',//可为空
            "op: {$row->upload_op}",// update or create
            "dsp_token: {$this->token}",
            //'file_name' => ''
        ];

        $res = $this->post2iqiyi($this->url_advertiser_upload, "", $client);

        if (!isset($res['code']) || $res['code'] != 0) { //上传失败
            $code = !isset($res['code']) ? 9999 : $res['code'];
            LogHelper::info("upload advertiser id : {$row->clientid} fail");
            return ["code" => 1, "msg" => "发送广告主{$row->clientid}失败"];

        } else { //上传成功
            //广告主上传成功后，修改广告主管理状态为待审核
            IQiyiClientManager::where('clientid', $row->clientid)->update([
                'status' => IQiyiClientManager::STATUS_PENDING_AUDIT
            ]);
            LogHelper::info("upload advertiser id : {$row->clientid} success");
            return ["code" => 2, "msg" => "广告主{$row->clientid}上传成功"];
        }
    }

    /**
     * 获取某广告类型的规格, 0（应用市场）1（Banner纯图片）2 (Feeds) 3(插屏半屏)
     * 4(插屏全屏) 5(banner文字链) 71(appstore) 81(其他)
     *
     * @param
     * @return array e.g ["500*600", "320*50"]
     */
    public function getSize($adType)
    {
        $data = [
            Campaign::AD_TYPE_BANNER_IMG => ['180*150', '480*70'],
            Campaign::AD_TYPE_HALF_SCREEN => ['600*500'],
            Campaign::AD_TYPE_FULL_SCREEN => ['1280*720']
        ];
        if ($adType !== null) {
            return isset($data[$adType]) ? $data[$adType] : [];
        } else {
            return $data;
        }
    }

    /**
     * 请求爱奇艺接口
     * @param $url
     * @param $post_data
     * @return bool
     */
    private function post2iqiyi($url, $post_data, $post_header)
    {
        $result = HttpClientHelper::call($url, $post_data, $post_header);

        LogHelper::info('iqiyi : ' . $url . " : " . json_encode($post_header));
        LogHelper::info('iqiyi return: ' . $result);

        $res = json_decode($result, true);
        return $res;
    }
    
    //获取符合尺寸的素材
    private function getMaterialImages()
    {
        $material_tb = $this->material_type == 1 ? "campaigns_images" : "campaigns_video";
        $s1 = $this->getSizeForSqlV1();
        $s2 = $this->getSizeForSqlV2();
        $prefix = \DB::getTablePrefix();
        $select = \DB::table("{$material_tb} AS i")
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->select('i.url', 'i.id')
            ->where('b.bannerid', $this->bannerId)
            ->where('c.status', Campaign::STATUS_DELIVERING);

        if ($this->material_type == 1) {
            $ret = $select->whereRaw("{$prefix}i.width / {$prefix}i.height IN ({$s1})")
                ->whereIn('i.width', $s2)
                ->get();
        } else {
            $ret = $select->where('i.status', CampaignVideo::STATUS_USING)->get();
        }
        return $ret;
    }

    private function getSizeForSqlV1()
    {
        if ($this->material_type == 1) {
            return "600 / 500,1280 / 720,180 / 150,480 / 70";
        } else {
            return "640 / 480";
        }
    }

    private function getSizeForSqlV2()
    {
        if ($this->material_type == 1) {
            return [600, 1280, 180, 480];
        } else {
            return [640];
        }
    }

    private function insertMaterialImages()
    {
        //查询投放到爱奇艺广告素材
        $ret = $this->getMaterialImages();

        foreach ($ret as $row) {
            //检测素材是否存在
            $material = IQiyiMaterialManager::where('id', $row->id)
                ->where('type', $this->material_type)
                ->first();
            if ($material) {
                //素材被替换时，更新URL并更改状态为待提交
                if ($row->url != $material->url) {
                    IQiyiMaterialManager::where('id', $row->id)
                        ->update([
                        'url' => $row->url,
                        'status' => IQiyiMaterialManager::STATUS_PENDING_SUBMISSION,
                        ]);
                    LogHelper::info('update ' . $row->id . ' url from ' . $material->url . ' to ' . $row->url);
                }
            } else {
                //新增素材，插入到素材管理表
                $material = new IQiyiMaterialManager();
                $material->id = $row->id;
                $material->url = $row->url;
                $material->startdate = '2012-08-30';
                $material->enddate = '2020-08-30';
                $material->status = IQiyiMaterialManager::STATUS_PENDING_SUBMISSION;
                $material->save();

                LogHelper::info('add meaterial id ' . $row->id . ' url ' . $row->url);
            }
        }
    }

    /**
     * 获取素材信息
     *
     * @param int $status
     * @return array|static[]
     */
    private function getImage($status = IQiyiMaterialManager::STATUS_PENDING_SUBMISSION)
    {
        $prefix = \DB::getTablePrefix();
        $ret = \DB::table('campaigns_images AS i')
            ->leftJoin('iqiyi_material_manager AS im', 'im.id', '=', 'i.id')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('products AS p', 'p.id', '=', 'c.product_id')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->leftJoin('clients AS cli', 'cli.clientid', '=', 'c.clientid')
            ->select(
                'i.id',
                'i.width',
                'i.height',
                'i.url',
                'p.link_url',
                'cli.clientid',
                'im.m_id',
                'im.startdate',
                'im.enddate'
            )
            ->where('b.bannerid', $this->bannerId)
            ->where('cli.clients_status', Client::STATUS_ENABLE)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('im.status', $status)
            ->whereRaw("{$prefix}i.width / {$prefix}i.height IN (
                    600 / 500,
                    1280 / 720,
                    180 / 150,
                    480 / 70
                )")
            ->whereIn('i.width', [
                600,
                1280,
                180,
                480
            ])->get();

        return $ret;
    }

    /**
     * 上传素材
     */
    private function uploadAdMaterial()
    {
        //素材上传前，插入需要上传的素材
        $this->insertMaterialImages();

        //获取待上传素材信息
        $ret = $this->getImage();
        if (count($ret)<=0) {
            return ["code" => 0, "msg" => ''];
        }

        $is_all_ok = true;
        $arr_fail = [];
        foreach ($ret as $item) {
            $f = explode('/', $item->url);
            $adType = $this->getAdxIqiyiAdtype($item->width, $item->height);
            $link_url = urlencode($item->link_url);
            $file_name = urlencode($f[count($f) - 1]);
            $header = [
                "click_url: {$link_url}",
                "ad_id: {$item->clientid}",//广告主id
                "video_id: {$item->id}",
                "ad_type: {$adType}",
                "file_name: {$file_name}",
                "platform: 2", //2为移动端
                "end_date: {$item->enddate}",
                "dsp_token: {$this->token}",
            ];

            if ($adType == 4) {
                array_push($header, "duration: 120");
            }

            //网络请求
            $res = $this->postPicToIqiyiByUrl($this->url_ad_upload, $item->url, $header);

            if (!isset($res['code']) || $res['code'] != 0) { //上传失败
                $code = !isset($res['code']) ? 9999 : $res['code'];
                //IQiyiMaterialManager::where('id', $row->id)->update([
                //'status' => IQiyiMaterialManager::STATUS_SYSTEM_ERROR,
                //'reason' => Config::get('biddingos.iqiyi_fail_reasion_material.' . $code)
                //]);
                LogHelper::info("upload iqiyi img id : {$item->id} fail code:" . $res['code']);
                $is_all_ok = false;
                array_push($arr_fail, $item->url);

            } else { //上传成功
                //素材上传成功后，修改素材管理素材状态为待审核
                IQiyiMaterialManager::where('id', $item->id)->update([
                    'status' => IQiyiMaterialManager::STATUS_PENDING_AUDIT,
                    'm_id' => $res['m_id']
                ]);
                LogHelper::info("upload iqiyi img id : {$item->id} success");
            }
        }

        $msg = '';
        if (!$is_all_ok) {
            $msg = implode(",", $arr_fail) . "上传失败\n";
        }

        //读出上传成功的素材
        $ret = $this->getAdMaterialUploadedSuccessfully();
        $upload_mid = [];
        foreach ($ret as $row) {
            $upload_mid[] = $row->m_id;
        }
        if (count($upload_mid)>0) {
            $msg = "请告知媒体素材的mid:" . implode(",", $upload_mid) . "审核";
        }
        return ["code" => 1, "msg" => $msg];

    }

    //获取成功上传的素材
    private function getAdMaterialUploadedSuccessfully()
    {
        $prefix = \DB::getTablePrefix();
        $ret = \DB::table('campaigns_images AS i')
          ->leftJoin('iqiyi_material_manager AS im', 'im.id', '=', 'i.id')
          ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
          ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
          ->select(
              'i.id',
              'i.url',
              'im.m_id'
          )
          ->where('b.bannerid', $this->bannerId)
          ->whereIn(
              'im.status',
              [IQiyiMaterialManager::STATUS_UPLOAD_DEAL,
              IQiyiMaterialManager::STATUS_PENDING_AUDIT]
          )
          ->where('im.m_id', '<>', '')
          ->get();
        return $ret;
    }

    /**
     * 映射图片素材为adx的素材类型
     * @param $width
     * @param $height
     */
    private function getAdxIqiyiAdtype($width, $height)
    {
        //暂停 600*500
        //贴片 1280*720
        //角标 180*150
        //overlay 480*70

        //1为贴片、2为暂停、4为角标、9为overlay。
        if ($width==600 && $height==500) {
            return 2;
        } elseif ($width==1280 && $height==720) {
            return 1;
        } elseif ($width==180 && $height==150) {
            return 4;
        } elseif ($width==480 && $height==70) {
            return 9;
        }
        return 0;
    }

    /**
     * 请求爱奇艺接口
     * @param $url
     * @param $header
     * @return bool
     */
    private function getFromIqiyi($url, $params, $header)
    {
        $url = $url . '?dsp_token=' . $this->token;
        foreach ($params as $k => $v) {
            $url = $url . '&' . $k . '=' . $v;
        }
        $result = HttpClientHelper::call($url, null, $header);
    
        LogHelper::info('iqiyi : ' . $url);
        LogHelper::info('iqiyi return: ' . $result);
    
        $res = json_decode($result, true);
        if (empty($res['results'])) {
            LogHelper::info('iqiyi return fail: ' . $result);
            return false;
        }
    
        return $res;
    }
    
    private function postPicToIqiyiByUrl($url, $src_pic_url, $header)
    {
        $in=fopen($src_pic_url, 'r');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_PUT, 1);
        curl_setopt($curl, CURLOPT_INFILE, $in);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        fclose($in);
        
        LogHelper::info('iqiyi postPicToIqiyiByUrl: ' . $url . ' '
            . json_encode($header) . 'with pic url:' . $src_pic_url);
        LogHelper::info('iqiyi return: ' . $result);

        $res = json_decode($result, true);
        return $res;
    }

    /**
     * 若campaignid属于视频，则替换成视频地址
     *
     * @param $campainid campaignid
     * @param $url 图片地址
     */
    private function checkVideoAd($campaignId, $url)
    {
        $ret = \DB::table('campaigns_video')->select('url')->where('campaignid', $campaignId)->get();
        if (!empty($ret) && count($ret) > 0) {
            foreach ($ret as $row) {
                return $row->url;
            }
        }
        return $url;
    }

    /**
     * 查询广告主状态
     */
    private function adverStatus()
    {
        $ret = $this->getClients(IQiyiClientManager::STATUS_PENDING_AUDIT);
        if (empty($ret)) {
            return ["code" => 1, "msg" => "广告主审核中"];
        }

        $batch = [];
        array_push($batch, $ret->clientid);
        $batch = implode(',', $batch);
        $res = $this->getFromIqiyi($this->url_advertiser_status_multi, ['batch'=>$batch], null);
        if ($res === false) {
            LogHelper::info("check iqiyi advertiser fail");
            return ["code" => 1, "msg" => "广告主查询失败，等待下一次查询"];
        } else {
            //更新广告主状态
            if (is_array($res['results']) && count($res['results']) > 0) {
                //获取广告主审核状态，修改本地广告主管理状态
                foreach ($res['results'] as $item) {
                    $status = $this->getAdvertiserStatus($item['status']);
                    IQiyiClientManager::where('clientid', $item['ad_id'])->update([
                    'status' => $status,
                    'reason' => (isset($item['reason'])?$item['reason']:"")
                    ]);
                    LogHelper::info('update ' . json_encode($item));
                    $clientId = $item['ad_id'];
                    if ($status == IQiyiClientManager::STATUS_PENDING_AUDIT) {
                        return ["code" => 1, "msg" => "广告主{$clientId}审核中"];
                    }
                    if ($status == IQiyiClientManager::STATUS_ADOPT) {
                        return ["code" => 1, "msg" => "广告主{$clientId}审核通过"];
                    }
                    if ($status == IQiyiClientManager::STATUS_REJECT) {
                        return ["code" => 3, "msg" => "广告主{$clientId}被拒绝:".$item['reason']];
                    }
                }
            }
            return ["code" => 1, "msg" => "广告主审核中"];
        }
    }
    
    /**
     * 状态转换
     * @param $statusName
     * @return int
     */
    private function getAdvertiserStatus($statusName)
    {
        switch ($statusName) {
            case 'UNPASS': //审核未通过
                $status = IQiyiClientManager::STATUS_REJECT;
                break;
            case 'PASS': //审核通过，可以使用
                $status = IQiyiClientManager::STATUS_ADOPT;
                break;
            case 'WAIT': //待审核
            default:
                $status = IQiyiClientManager::STATUS_PENDING_AUDIT;
                break;
        }
        return $status;
    }

    // 查询、更新素材状态
    private function checkAdxStatusAndUpdate()
    {
        //找到待审核的素材
        $ret = $this->getImage(IQiyiMaterialManager::STATUS_PENDING_AUDIT);
        $batch = [];
        foreach ($ret as $row) {
            if (!empty($row->m_id)) {
                $batch[] = $row->m_id;
            }
        }
        if (count($batch) <= 0) {
            return ["code" => 0, "msg" => "没有审核中的素材 : " . $this->bannerId];
        }

        $batch = implode(',', $batch);
        $res = $this->getFromIqiyi($this->url_ad_status, ['batch'=>$batch], null);
        if ($res === false) {
            LogHelper::info("fail to post2iqiyi for checking status");
            return ["code" => 1, "msg" => "fail to post2iqiyi for checking status"];
        } else {
            $IS_STATUS_REJECT = false;//不通过
            $IS_STATUS_ADOPT = true;//通过
            $msg = "";
            if (is_array($res['results']) && count($res['results']) > 0) {
                //获取素材审核状态，修改本地素材管理状态
                foreach ($res['results'] as $item) {
                    $status = $this->getMaterialStatus($item['status']);
                    IQiyiMaterialManager::where('m_id', $item['m_id'])->update([
                        'status' => $status,
                        'reason' =>  (isset($item['reason'])?$item['reason']:""),
                        'tv_id' =>  (isset($item['tv_id'])?$item['tv_id']:"")
                    ]);
                    LogHelper::info('update iqiyi ' . json_encode($item));
                    if ($status == IQiyiMaterialManager::STATUS_REJECT
                        || $status == IQiyiMaterialManager::STATUS_OFFLINE) {
                        $IS_STATUS_REJECT = true;
                        $msg = $msg . $item['reason'] . "->" . $item['m_id'] . "\n";
                    }
                    if ($status != IQiyiMaterialManager::STATUS_ADOPT) {
                        $IS_STATUS_ADOPT = false;
                    }
                }
            }

            if ($IS_STATUS_REJECT) {
                return ["code" => 3, "msg" => $msg];
            }
            if ($IS_STATUS_ADOPT) {
                return ["code" => 2, "msg" => "审核通过"];
            }
            $msg = $batch . "审核中";
            return ["code" => 1, "msg" => $msg];
        }

    }

    /**
     * 状态转换
     * @param $statusName
     * @return int
     */
    private function getMaterialStatus($statusName)
    {
        switch ($statusName) {
            case 'INIT': //上传成功，处理中
                $status = IQiyiMaterialManager::STATUS_UPLOAD_DEAL;
                break;
            case 'AUDIT_UNPASS': //审核未通过
                $status = IQiyiMaterialManager::STATUS_REJECT;
                break;
            case 'COMPLETE': //审核通过，可以使用
                $status = IQiyiMaterialManager::STATUS_ADOPT;
                break;
            case 'OFF': //投放下线
                $status = IQiyiMaterialManager::STATUS_OFFLINE;
                break;
            case 'AUDIT_WAIT': //处理成功，等待审核
            default:
                $status = IQiyiMaterialManager::STATUS_PENDING_AUDIT;
                break;
        }
        return $status;
    }
}
