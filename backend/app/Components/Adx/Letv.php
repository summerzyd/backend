<?php

namespace App\Components\Adx;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\HttpClientHelper;
use App\Components\Helper\LogHelper;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignImage;
use App\Models\CampaignVideo;
use App\Models\Client;
use App\Models\AdxClientManager;

use App\Models\AdxMaterialManager;
use App\Components\Config;

class Letv extends BaseAdx
{
    protected $xname = "letv";
    protected $afid;
    protected $dspid;
    protected $token;
    protected $url_upload;
    protected $url_status;
    protected $bannerId;
    protected $material_type = 1;//1:图片 2:视频
    public function __construct()
    {
        $this->dspid = Config::get('biddingos.adx.letv.dspid');
        $this->token = Config::get('biddingos.adx.letv.token');
        $this->url_upload = Config::get('biddingos.adx.letv.ad_upload');
        $this->url_status = Config::get('biddingos.adx.letv.ad_status');
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
        $this->afid = $banner->affiliateid;
        if ($campaign->ad_type == Campaign::AD_TYPE_VIDEO) {
            $this->material_type = 2;//视频素材
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
        foreach ($ret as $row) {
            if ($this->getImgType($row->url) == '') {
                return ["code" => 0, "msg" => "请上传这些类型的素材:jpg, jpeg, png, flv, mp4"];
            }
        }

        return $this->uploadAdMaterial(true);
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
        $this->afid = $banner->affiliateid;
        if ($campaign->ad_type==91) {
            $this->material_type = 2;//视频素材
        }

        // 上传待提交的广告素材
        $res = $this->uploadAdMaterial(false);
        if ($res['code'] == 0) {//没有待提交的素材，可以查询状态
            $res = $this->checkAdxStatusAndUpdate();
            if ($res['code'] == 0) {//没有审核中的素材，则判断是否审核通过
                $ret = $this->getImage(AdxMaterialManager::STATUS_ADOPT);
                if (count($ret) > 0) {
                    return ["code" => 2, "msg" => "审核通过"];
                } else {
                    return ["code" => 0, "msg" => "素材未上传"];
                }
            } else {
                return $res;
            }
        } else {
            return $res;
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
            Campaign::AD_TYPE_BANNER_IMG => [''],
            Campaign::AD_TYPE_HALF_SCREEN => ['410*232', '620*300'],
            Campaign::AD_TYPE_FULL_SCREEN => ['1420*800'],
            Campaign::AD_TYPE_VIDEO => ['640*480']
        ];
        if ($adType !== null) {
            return isset($data[$adType]) ? $data[$adType] : [];
        } else {
            return $data;
        }
    }
    
    private function getSizeForSqlV1()
    {
        if ($this->material_type==1) {
            return "410 / 232, 620 / 300, 1420 / 800";
        } else {
            return "640 / 480";
        }
    }
    
    private function getSizeForSqlV2()
    {
        if ($this->material_type==1) {
            return [410, 620, 1420];
        } else {
            return [640];
        }
    }
    
    //获取符合尺寸的素材
    private function getMaterialImages()
    {
        $material_tb = $this->material_type == 1 ? "campaigns_images" : "campaigns_video";
        $s1 = $this->getSizeForSqlV1();
        $s2 = $this->getSizeForSqlV2();
        $prefix = \DB::getTablePrefix();
        $tmp = \DB::table("{$material_tb} AS i")
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->select('i.url', 'i.id', 'i.campaignid')
            ->where('b.bannerid', $this->bannerId)
            ->whereRaw("{$prefix}i.width / {$prefix}i.height IN ({$s1})")
            ->whereIn('i.width', $s2);
        if ($this->material_type == 1) {
            $ret = $tmp->get();
        } else {
            $ret = $tmp->where('i.status', CampaignVideo::STATUS_USING)->get();
        }
        return $ret;
    }

    private function insertMaterialImages()
    {
        // 查询投放的广告素材
        $ret = $this->getMaterialImages();

        foreach ($ret as $row) {
            // 检测素材是否存在
            $material = AdxMaterialManager::where('id', $row->id)
            ->where('affiliateid', $this->afid)
            ->where('type', $this->material_type)
            ->first();
            if ($material) {
                // 素材被替换时，更新URL并更改状态为待提交
                if ($row->url != $material->url) {
                    AdxMaterialManager::where('id', $row->id)
                    ->where('affiliateid', $this->afid)
                    ->where('type', $this->material_type)
                    ->update([
                        'url' => $row->url,
                        'status' => AdxMaterialManager::STATUS_PENDING_SUBMISSION
                    ]);
                    LogHelper::info('update ' . $row->id . ' url from ' . $material->url . ' to ' . $row->url);
                }
            } else {
                // 新增素材，插入到素材管理表
                $material = new AdxMaterialManager();
                $material->id = $row->id;
                $material->affiliateid = $this->afid;
                $material->campaignid = $row->campaignid;
                $material->type = $this->material_type;
                $material->url = $row->url;
                $material->startdate = '2012-08-30';
                $material->enddate = '2020-08-30';
                $material->status = AdxMaterialManager::STATUS_PENDING_SUBMISSION;
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
    private function getImage($status = AdxMaterialManager::STATUS_PENDING_SUBMISSION)
    {
        $material_tb = $this->material_type==1 ? "campaigns_images" : "campaigns_video";
        $s1 = $this->getSizeForSqlV1();
        $s2 = $this->getSizeForSqlV2();
        $prefix = \DB::getTablePrefix();
        $tmp = \DB::table("{$material_tb} AS i")
            ->leftJoin('adx_material_manager AS ym', 'ym.id', '=', 'i.id')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('products AS p', 'p.id', '=', 'c.product_id')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->leftJoin('clients AS cli', 'cli.clientid', '=', 'c.clientid')
            ->select(
                'i.url',
                'p.link_url',
                'cli.clientname',
                'ym.id',
                'i.height',
                'ym.startdate',
                'ym.enddate',
                'c.campaignid',
                'i.width',
                'i.duration'
            )
            ->where('b.bannerid', $this->bannerId)
            ->where('ym.status', $status)
            ->where('ym.affiliateid', $this->afid)
            ->where('ym.type', $this->material_type)
            ->whereRaw("{$prefix}i.width / {$prefix}i.height IN ($s1)")
            ->whereIn('i.width', $s2);
        
        if ($this->material_type==1) {
            $ret = $tmp->get();
        } else {
            $ret = $tmp->where('i.status', 2)->get();
        }

        return $ret;
    }

    /**
     * 请求Adx接口
     *
     * @param $url
     * @param $post_data
     * @return bool
     */
    private function post2Adx($url, $post_data)
    {
        $post_data["dspid"] = $this->dspid;
        $post_data["token"] = $this->token;
        $post_data = json_encode($post_data);

        $result = HttpClientHelper::call($url, $post_data, [
            'Content-Type' => "application/json"
        ]);

        LogHelper::info("{$this->xname} : " . $url . " : " . $post_data);
        LogHelper::info("{$this->xname} return: " . $result);

        $res = json_decode($result, true);
        if ($res['result'] != 0 && $res['result'] != 2) {
            LogHelper::info("{$this->xname} return fail: " . $result);
            return false;
        }
        return $res;
    }

    /**
     * 上传素材
     */
    private function uploadAdMaterial($from_upload)
    {
        // 素材上传前，插入需要上传的素材
        $this->insertMaterialImages();

        // 获取待上传素材信息
        $ret = $this->getImage();
        if (count($ret)<=0) {
            return ["code" => 0, "msg" => ''];
        }
        
        $image_list = [];
        $image = [];
        $upload_imgs = [];
        foreach ($ret as $row) {
            $image['url'] = $row->url;
            $image['landingpage'] = [$row->link_url];
            $image['advertiser'] = $row->clientname;
            $image['startdate'] = $row->startdate;
            $image['enddate'] = $row->enddate;
            $image['type'] = $this->getImgType($row->url);
            $image['duration'] = $this->material_type==1 ? 0 : $row->duration;
            $image['media'] = [1, 2, 3];
            $image['industy'] = 8;
            $image['display'] = $this->getDisplay($row->width);
            $image_list[] = $image;
            array_push($upload_imgs, $image['url']);
        }

        $post_data = [];
        $post_data ["ad"] = $image_list;

        $res = $this->post2Adx($this->url_upload, $post_data);
        if ($res === false) {
            LogHelper::info("upload letv fail");
            return ["code" => 1, "msg" => "发送广告素材失败"];
        } else {
            $msg = '素材';
            $ids = ArrayHelper::classColumn($ret, 'id');
            // 上传素材ID写入日志
            LogHelper::info('upload letv material id:' . implode(',', $ids));
            if (count($res ['message']) > 0) {
                $campaignImages = CampaignImage::whereIn('url', array_values($res ['message']))
                    ->select('id', 'url')
                    ->get();
                // 上传素材不成功素材，标记为系统错误，并填写原因
                foreach ($res ['message'] as $k => $v) {
                    foreach ($v as $url) {
                        // 记录上传失败URL
                        LogHelper::info('fail upload material url:' . $url);
                        foreach ($campaignImages as $item) {
                            if ($url == $item->url) {
                                $reason = $this->adx_code[$k];
                                $msg = $msg . $url . "[{$reason}],";
                                AdxMaterialManager::where('id', $item->id)
                                ->where('affiliateid', $this->afid)
                                ->where('type', $this->material_type)
                                ->update([
                                    'status' => AdxMaterialManager::STATUS_SYSTEM_ERROR,
                                    'reason' => $reason
                                ]);
                                // 删除上传失败ID
                                unset($ids [array_search($item->id, $ids)]);
                            }
                        }
                    }
                }
                $msg = $msg . $url . "上传失败";
            }

            // 素材上传成功后，修改素材管理素材状态为待审核
            AdxMaterialManager::whereIn('id', $ids)
            ->where('affiliateid', $this->afid)
            ->where('type', $this->material_type)
            ->update([
                'status' => AdxMaterialManager::STATUS_PENDING_AUDIT
            ]);

            if (count($res['message']) > 0) {
                return ["code" => 1, "msg" => $msg];
            }

            $msg = implode(",", $upload_imgs) . "已提交";
            if ($from_upload) {
                return ["code" => 2, "msg" => $msg];
            }
            return ["code" => 1, "msg" => $msg];
        }
    }
    
    
    private function getImgType($url)
    {
        if (strpos($url, '.jpeg') !== false) {
            echo 'jpg';
        }
        if (strpos($url, '.jpg') !== false) {
            echo 'jpg';
        }
        if (strpos($url, '.png') !== false) {
            echo 'jpg';
        }
        if (strpos($url, '.flv') !== false) {
            echo 'flv';
        }
        if (strpos($url, '.mp4') !== false) {
            echo 'mp4';
        }
        return '';
    }
    
    private function getDisplay($width)
    {
        if (in_array($width, [410, 620, 1420])) {
            return [6];
        }
        if (640==$width) {
            return [2, 4, 5];
        }
        return [];
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

    // 查询、更新素材状态
    private function checkAdxStatusAndUpdate()
    {
        //查询素材
        $ret = $this->getImage(AdxMaterialManager::STATUS_PENDING_AUDIT);
        $image_list = [];
        foreach ($ret as $row) {
            $image_list[] = $row->url;
        }
        if (count($image_list) <= 0) {
            return ["code" => 0, "msg" => "没有审核中的素材 : " . $this->bannerId];
        }

        $post_data = [];
        $post_data["adurl"] = $image_list;

        $res = $this->post2Adx($this->url_status, $post_data);
        if ($res === false) {
            LogHelper::info("fail to post2Adx:{$this->xname} for checking status");
            return ["code" => 1, "msg" => "fail to post2Adx:{$this->xname} for checking status"];
        } else {
            $IS_STATUS_REJECT = false;//不通过
            $IS_STATUS_ADOPT = true;//通过
            $msg = "";
            if (isset($res['message']['total']) && $res['message']['total'] > 0) {
                // 获取素材审核状态，修改本地素材管理状态
                foreach ($res['message']['records'] as $item) {
                    foreach ($ret as $rItem) {
                        $new_url = $rItem->url;
                        if ($item['url'] == $new_url) {
                            $status = $this->getMaterialStatus($item['result']);
                            AdxMaterialManager::where('id', $rItem->id)
                                ->where('affiliateid', $this->afid)
                                ->where('type', $this->material_type)
                                ->update([
                                'status' => $status,
                                'reason' => $item['reason']
                                ]);
                            LogHelper::info('update ' . $rItem->id . ' url ' . $item ['url'] . 'to status ' . $status);
                            if ($status == AdxMaterialManager::STATUS_REJECT) {
                                $IS_STATUS_REJECT = true;
                                $msg = $msg . $item['reason'] . "->" . $new_url . " ";
                            }
                            if ($status != AdxMaterialManager::STATUS_ADOPT) {
                                $IS_STATUS_ADOPT = false;
                            }
                        }
                    }
                }
            }
            if ($IS_STATUS_REJECT) {
                return ["code" => 3, "msg" => $msg];
            }
            if ($IS_STATUS_ADOPT) {
                return ["code" => 2, "msg" => "审核通过"];
            }
            return ["code" => 1, "msg" => "审核中"];
        }
    }

    /**
     * 状态转换
     *
     * @param $statusName
     * @return int
     */
    private function getMaterialStatus($statusName)
    {
        switch ($statusName) {
            case '未通过':
                $status = AdxMaterialManager::STATUS_REJECT;
                break;
            case '通过':
                $status = AdxMaterialManager::STATUS_ADOPT;
                break;
            case '待审核':
            default:
                $status = AdxMaterialManager::STATUS_PENDING_AUDIT;
                break;
        }
        return $status;
    }
    
    
    //代码解释
    protected $adx_code = [
        101 => '广告加载失败',
        102 => '必填项，不支持的文件格式，目前支持的文件格式：jpg,png,swf,flv,mp4,vpaid',
        104 => '执行插入过程异常',
        105 => '广告所属的广告主不能为空',
        106 => '广告生效时间为空或者不能解析',
        107 => '广告失效时间为空或者不是有效格式或者广告失效时间小于当前时间',
        108 => '广告尺寸不符合adx所有媒体广告位要求',
        110 => '参数中存在广告地址为空',
        111 => '第三方监测地址错误',
        112 => '同步广告物料的ad参数必须要有',
        113 => '广告物料的跳转地址landingpage参数不能为空',
        114 => '广告时长不能解析',
        115 => '广告的宽度必填且为整数类型值',
        116 => '广告的高度必填且为整数类型值',
        117 => '此广告url与adx库的已审核通过广告重复',
        118 => '广告对应的平台为整数类型值',
        119 => 'adx平台库没有此广告对应的平台值',
        120 => '广告的媒体属性不正确',
        121 => '广告主非白名单',
        122 => '广告的行业属性不正确',
        123 => '广告的广告位类型display不正确',
        201 => '上传时间不能解析',
        301 => 'adurl不能为空',
        302 => '该广告url并未同步到Adx中',
        401 => '报表查询的开始时间为空或者不能解析',
        402 => '报表查询的结束时间为空或者不能解析',
        403 => '报表查询时间跨度超过7天',
        404 => '报表查询时的结束时间早于开始时间',
    ];
}
