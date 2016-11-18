<?php
namespace App\Components\Adx;

use App\Components\Config;
use App\Components\Helper\LogHelper;
use App\Components\Sohu\SohuEncrypt;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\SoHuClientManager;
use App\Models\SoHuMaterialManager;

class Sohu extends BaseAdx
{
    protected $base_url = 'http://api.ad.sohu.com/';
    protected $auth_consumer_key;
    protected $auth_consumer_secret;
    protected $price_key;
    protected $v3_downloadcgi_uri;
    protected $v3_downloadendcgi_uri;
    protected $v3_clickcgi_uri;
    protected $v3_impressioncgi_uri;

    public function __construct()
    {
        $this->auth_consumer_key = Config::get('biddingos.sohu.auth_consumer_key');
        $this->auth_consumer_secret = Config::get('biddingos.sohu.auth_consumer_secret');
        $this->price_key = Config::get('biddingos.sohu.price_key');
        $this->v3_downloadcgi_uri = Config::get('biddingos.sohu.v3_downloadcgi_uri');
        $this->v3_downloadendcgi_uri = Config::get('biddingos.sohu.v3_downloadendcgi_uri');
        $this->v3_clickcgi_uri = Config::get('biddingos.sohu.v3_clickcgi_uri');
        $this->v3_impressioncgi_uri = Config::get('biddingos.sohu.v3_impressioncgi_uri');
    }

    public function upload($bannerId)
    {
        //判断是否已经上传符合尺寸的素材
        $ret = $this->getMaterialImages($bannerId);
        if (count($ret) <= 0) {
            $banner = Banner::find($bannerId);
            $campaign = $banner->campaign;
            $ad_size = $this->getSize($campaign->ad_type);
            if (empty($ad_size)) {
                return ["code" => 0, "msg" => "该Adx不支持该类型广告"];
            }
            $ad_size = implode(',', $ad_size);
            return ["code" => 0, "msg" => "投放到该Adx需要{$ad_size}的素材，请上传"];
        }

        //上传广告主
        $res = $this->uploadAdvertiser($bannerId);
        if ($res["code"] == 0) {
            return $res;
        }

        return ["code" => 2, "msg" => '上传广告主成功,审核中'];
    }

    public function status($bannerId)
    {
        $banner = Banner::find($bannerId);
        $campaign = $banner->campaign;
        $client = $campaign->client;

        //判断广告主是否已经上传
        $clientMgr = SoHuClientManager::where('clientid', $client->clientid)->first();
        if (!$clientMgr) {
            //上传广告主
            $res = $this->uploadAdvertiser($bannerId);
            if ($res["code"] == 0) {
                return $res;
            }
        }

        // 上传待提交的广告素材
        $res = $this->uploadAdMaterial($bannerId);
        if ($res['code'] != 2) {
            return $res;
        }
        $res = $this->checkAdxStatusAndUpdate($bannerId);
        if ($res['code'] != 2) {
            return $res;
        }

        return ['code' => 2, 'msg' => '审核通过'];
    }

    /**
     * 获取上传素材
     * @param $bannerId
     * @return array|static[]
     */
    private function getMaterialImages($bannerId)
    {
        $prefix = \DB::getTablePrefix();
        $result = \DB::table('campaigns_images AS i')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->select('i.url', 'i.id')
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('b.bannerid', $bannerId)
            ->whereRaw("{$prefix}i.width / {$prefix}i.height IN (
                640 / 360
            )")->whereIn('i.width', [
                640,
            ])->get();
        return $result;
    }

    /**
     * 获取广告素材尺寸
     * @param $adType
     * @return array|null
     */
    public function getSize($adType)
    {
        $data = [
            Campaign::AD_TYPE_FULL_SCREEN => ['640*360']
        ];
        if ($adType !== null) {
            return isset($data[$adType]) ? $data[$adType] : [];
        } else {
            return $data;
        }
    }

    /**
     * 检查广告主，素材审核状态
     * @param $bannerId
     * @return array
     */
    private function checkAdxStatusAndUpdate($bannerId)
    {
        $banner = Banner::find($bannerId);
        //查询广告主是否审核
        $res = $this->customerList($banner->campaign->client);
        if ($res['code'] !== 2) {
            return $res;
        }

        //查询素材是否审核
        $ret = $this->materialList($banner->campaign->client);
        if ($ret === false) {
            return ['code' => 0, 'msg' => "没有审核中的素材 : " . $bannerId];
        }
        $fill = SoHuMaterialManager::getColumns();
        $msg = [];
        $IS_STATUS_AUDITING = false;
        foreach ($ret as $m) {
            switch (intval($m['status'])) {
                case SoHuMaterialManager::STATUS_AUDITING:
                    $IS_STATUS_AUDITING = true;
                    LogHelper::info($m['file_source'] . " waiting audit.");
                    break;
                case SoHuMaterialManager::STATUS_SUCCESS:
                    LogHelper::info($m['file_source'] . " audit sucess.");
                    break;
                case SoHuMaterialManager::STATUS_REJECT:
                    $msg[] = $m['audit_info'];
                    LogHelper::info($m['file_source'] . " audit reject.");
                    break;
            }
            $material = \DB::table('sohu_material_manager')->where('file_source', $m['file_source'])->first();
            if ($material) {
                \DB::table('sohu_material_manager')
                    ->where('file_source', $m['file_source'])
                    ->update($this->fillTable($m, $fill));
            } else {
                \DB::table('sohu_material_manager')
                    ->where('file_source', $m['file_source'])
                    ->insert($this->fillTable($m, $fill));
            }
        }
        if ($IS_STATUS_AUDITING) {
            return ['code' => 1, 'msg' => '审核中'];
        }
        if (count($msg) > 0) {
            return ['code' => 3, 'msg' => implode(',', $msg)];
        }
        return ['code' => 2, 'msg' => '审核通过'];
    }

    /**
     * 查看广告主是否审核
     * @param $client
     * @return array
     */
    private function customerList($client)
    {
        $soHuClientManager = SoHuClientManager::where('clientid', $client->clientid)->first();
        if (!$soHuClientManager) {
            return ['code' => 0, 'msg' => '广告主未上传'];
        }
        $args = [
            'perpage' => 50,
            'page' => 1,
            'customer_key' => $soHuClientManager->customer_key,
        ];
        $url = $this->base_url . 'exchange/customer/list';
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);
        $ret = json_decode($encrypt->setUrl($url)->setParams($args)->get(), true);
        $res = [];
        if (!empty($ret['status'])) {
            $items = $ret['content']['items'];

            $fill = SoHuClientManager::getColumns();
            foreach ($items as $c) {
                switch (intval($c['tv_status'])) {
                    case SoHuClientManager::TV_STATUS_AUDITING:
                        $res = ['code' => 1, 'msg' => '审核中'];
                        LogHelper::info($c['customer_name'] . " waiting audit.");
                        break;
                    case SoHuClientManager::TV_STATUS_SUCCESS:
                        $res = ['code' => 2, 'msg' => '审核通过'];
                        LogHelper::info($c['customer_name'] . " audit sucess.");
                        break;
                    case SoHuClientManager::TV_STATUS_REJECT:
                        $res = ['code' => 1, 'msg' => $c['tv_audit_info']];
                        LogHelper::info($c['customer_name'] . " audit reject.");
                        break;
                }
                $count = SoHuClientManager::where('customer_key', $c['customer_key'])->count();
                if ($count > 0) {
                    \DB::table('sohu_client_manager')
                        ->where('customer_key', $c['customer_key'])
                        ->update($this->fillTable($c, $fill));
                } else {
                    \DB::table('sohu_client_manager')
                        ->where('customer_key', $c['customer_key'])
                        ->insert($this->fillTable($c, $fill));
                }
            }
        }
        return $res;
    }

    /**
     * 获取素材列表
     * @param $client
     * @return array|bool
     */
    private function materialList($client)
    {
        $soHuClientManager = SoHuClientManager::where('clientid', $client->clientid)->first();
        if (!$soHuClientManager) {
            return ['code' => 0, 'msg' => '广告主未上传'];
        }
        $args = [
            'perpage' => 50,
            'page' => 1,
            'customer_key' => $soHuClientManager->customer_key,
        ];
        $url = $this->base_url . 'exchange/material/list';
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);
        $ret = json_decode($encrypt->setUrl($url)->setParams($args)->get(), true);
        if (!empty($ret['status'])) {
            $items = $ret['content']['items'];
            $loop = ceil($ret['content']['count'] / 50);
            for ($i = 2; $i <= $loop; $i++) {
                $tmp = $this->materialList([
                    'perpage' => 50,
                    'page' => $i,
                ]);
                $items = array_merge($items, $tmp);
            }
            return $items;
        }
        return false;
    }

    /**
     * 上传广告主信息
     * @param $bannerId
     * @return array
     */
    private function uploadAdvertiser($bannerId)
    {
        $banner = Banner::find($bannerId);
        $campaign = $banner->campaign;
        $client = $campaign->client;

        //检查广告主信息
        $ret = $this->checkAdvertiserCouldUpload($client);
        if ($ret !== true) {
            return $ret;
        }
        //广告主的信息与搜狐要求上传的信息匹配
        $soHuClientManager = SoHuClientManager::where('clientid', $client->clientid)
            ->first();
        //组装需要传输的信息
        $data = [
            'customer_name' => $client->clientname,
            'customer_website' => $client->website,
            'company_address' => $client->address,
            'contact' => $client->contact,
        ];
        if ($soHuClientManager) {
            if ($soHuClientManager->tv_status == SoHuClientManager::TV_STATUS_SUCCESS) {
                return ["code" => 2, "msg" => "广告主已经上传"];
            } else {
                $data['customer_key'] = $soHuClientManager->customer_key;
                $ret = $this->customerUpdate($data);
                if (empty($ret['status'])) {
                    LogHelper::info("upload advertiser fail");
                    return ["code" => 0, "msg" => "发送广告主失败"];
                }
            }
        } else {
            $ret = $this->customerCreate($data);
            if (empty($ret['status'])) {
                LogHelper::info("upload advertiser fail");
                return ["code" => 0, "msg" => "发送广告主失败"];
            } else {
                LogHelper::info('Create Client Success! ' . json_encode($ret));
                $soHuClientManager = new SoHuClientManager();
                $soHuClientManager->clientid = $client->clientid;
                $soHuClientManager->customer_key = $ret['content'];
                $soHuClientManager->customer_name = $client->clientname;
                $soHuClientManager->save();
            }
        }
        return ["code" => 2, "msg" => "广告主已经上传"];
    }

    /**
     * 检测广告主是否符合上传要求
     * @param $client
     * @return array|bool
     */
    private function checkAdvertiserCouldUpload($client)
    {
        if (empty($client->address)) {
            return ['code' => 0, 'msg' => '无法提交，请检查广告主地址是否已填写'];
        }
        if (empty($client->website)) {
            return ['code' => 0, 'msg' => '无法提交，请检查广告主官网地址是否已填写'];
        }
        if (empty($client->qualifications)) {
            return ['code' => 0, 'msg' => '无法提交，请检查广告主资质是否已上传'];
        }
        $ret = json_decode($client->qualifications, true);
        if (empty($ret['business_license']['image'])) {
            return ['code' => 0, 'msg' => '无法提交，请检查营业执照图片是否已上传'];
        }
        if (empty($ret['network_business_license']['image'])) {
            return ['code' => 0, 'msg' => '无法提交，请检查网络文化经营许可证图片是否已上传'];
        }
        return true;
    }

    /**
     * 新建客户
     * @param null $args
     * @return mixed
     */
    private function customerCreate($args = null)
    {
        $url = $this->base_url . 'exchange/customer/create';
        return $this->postUrl($url, $args);
    }

    /**
     * 更新客户信息
     * @param null $args
     * @return mixed
     */
    private function customerUpdate($args = null)
    {
        $url = $this->base_url . 'exchange/customer/update';
        return $this->postUrl($url, $args);
    }

    private function postUrl($url, $args)
    {
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);
        return json_decode($encrypt->setUrl($url)->setParams($args)->post(), true);
    }

    private function uploadAdMaterial($bannerId)
    {
        $prefix = \DB::getTablePrefix();
        $materials = \DB::table('banners as b')
            ->leftJoin('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->leftJoin('products as p', 'c.product_id', '=', 'p.id')
            ->leftJoin('clients as cl', 'c.clientid', '=', 'cl.clientid')
            ->leftJoin('sohu_client_manager as s_cl', 'cl.clientid', '=', 's_cl.clientid')
            ->leftJoin('campaigns_images as ci', 'ci.campaignid', '=', 'b.campaignid')
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('b.bannerid', $bannerId)
            ->whereRaw("{$prefix}ci.width / {$prefix}ci.height IN (
                640 / 360
            )")->whereIn('ci.width', [
                640,
            ])
            ->select(
                'ci.id',
                'ci.campaignid',
                'ci.url',
                'ci.width',
                'ci.height',
                's_cl.customer_key',
                'cl.clientid',
                'cl.clientname',
                'p.link_url'
            )
            ->distinct()
            ->get();
        $msg = [];
        $ids = [];
        foreach ($materials as $item) {
            if ($item->customer_key) {
                $args = [
                    /*
                     * @todo sohu素材信息匹配
                     */
                    'customer_key' => $item->customer_key,
                    'material_name' => $item->campaignid . '-' . $item->width . '-' . $item->height,
                    'file_source' => $item->url,
                    'imp' => json_encode([$this->v3_impressioncgi_uri . '?%%DISPLAY%%&win_price=%%WINPRICE%%']),
                    'click_monitor' => $this->v3_clickcgi_uri . '?%%CLICK%%&win_price=%%WINPRICE%%',
                    'gotourl' => $item->link_url,
                    'advertising_type' => '102100',
                    'submit_to' => 2, //1：搜狐门户；2：搜狐视频
                    'delivery_type' => 1, //1：RTB；2：PDB；3：PMP；4：Preferred Deal
                ];
                $soHuMaterialManager = SoHuMaterialManager::where('campaign_image_id', $item->id)->first();
                if (!$soHuMaterialManager) {
                    $ret = $this->materialCreate($args);
                    $ids[] = $item->id;
                    if (!empty($ret['status'])) {
                        LogHelper::info('Create Material Success! ' . json_encode($ret));
                        \DB::table('sohu_material_manager')
                            ->insert(array_merge($args, ['campaign_image_id' => $item->id]));
                    } else {
                        LogHelper::info('Create Material Error! ' . json_encode($ret));
                        $msg[] = 'id ' . $item->id . '的素材上传失败';
                    }
                }
            } else {
                LogHelper::info("Client {$item->clientname} didn't Created! Cann't create material!");
            }
        }
        if (count($msg) > 0) {
            return ['code' => 1, 'msg' => implode(',', $msg)];
        }
        return ['code' => 2, 'msg' => implode(',', $ids) . '已上传'];
    }

    /**
     * 上传素材
     * @param null $args
     * @return mixed
     */
    public function materialCreate($args = null)
    {
        $url = $this->base_url . 'exchange/material/create';
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);

        return json_decode($encrypt->setUrl($url)->setParams($args)->post(), true);
    }

    /**
     * 填充数据
     * @param $table
     * @param $cols
     * @return array
     */
    private function fillTable($table, $cols)
    {
        $ret = [];
        foreach ($cols as $c) {
            if (isset($table[$c])) {
                if (is_array($table[$c])) {
                    $table[$c] = json_encode($table[$c]);
                }
                $ret[$c] = $table[$c];
            }
        }
        return $ret;
    }
}
