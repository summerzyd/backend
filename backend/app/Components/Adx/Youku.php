<?php

namespace App\Components\Adx;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\HttpClientHelper;
use App\Components\Helper\LogHelper;
use App\Components\Youku\YoukuHelper;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignImage;
use App\Models\CampaignVideo;
use App\Models\Client;
use App\Models\YoukuClientManager;
use App\Models\YoukuMaterialManager;
use App\Components\Config;

class Youku extends BaseAdx
{
    protected $dspid;
    protected $token;
    protected $url_upload;
    protected $url_status;
    protected $bannerId;
    protected $url_upload_advertiser;
    protected $material_type = 1;//1图片，2视频

    public function __construct()
    {
        $this->dspid = Config::get('biddingos.adx.youku.dspid');
        $this->token = Config::get('biddingos.adx.youku.token');
        $this->url_upload = Config::get('biddingos.adx.youku.prefix_url') . "/upload";
        $this->url_status = Config::get('biddingos.adx.youku.prefix_url') . "/status";
        $this->url_upload_advertiser = Config::get('biddingos.adx.youku.prefix_url') . "/uploadadvertiser";
    }

    /**
     * 上传Adx广告主和素材 0:未达到提交的条件 1：上传失败 2：上传成功
     *
     * @return array {"code":0, "msg":"广告主的资质未上传"}|{"code":2, "msg":"m_id为1001"}
     */
    public function upload($bannerId)
    {
        $this->bannerId = $bannerId;
        $campaign = parent::getCampaign($bannerId);
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
        $res = $this->uploadAdvertiser();
        if ($res["code"] == 0) {
            return $res;
        }

        return ["code" => 2, "msg" => '上传广告主成功,审核中'];

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
        if ($campaign->ad_type == Campaign::AD_TYPE_VIDEO) {
            $this->material_type = 2;
        }

        //判断广告主是否已经上传
        $clientMgr = YoukuClientManager::find($client->clientid);
        if (!$clientMgr) {
            return ["code" => 0, "msg" => "广告主未上传"];
        }

        // 上传待提交的广告素材
        $res = $this->uploadAdMaterial();
        if ($res['code'] == 0) {//没有待提交的素材，可以查询状态
            $res = $this->checkAdxStatusAndUpdate();
            if ($res['code'] == 0) {//没有审核中的素材，则判断是否审核通过
                $ret = $this->getImage(YoukuMaterialManager::STATUS_ADOPT);
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

    // 判断广告主是否能够上传
    private function checkAdvertiserCouldUpload($advertiser)
    {
        $res = [];
        $res["code"] = 0;
        if (empty($advertiser["address"])) {
            $res["msg"] = "无法提交，请检查广告主地址是否已填写";
            return $res;
        }
        if (empty($advertiser["qlf"])) {
            $res["msg"] = "无法提交，请检查广告主资质是否已上传";
            return $res;
        }
        if (empty($advertiser["qlf"]["business_license"]["image"])) {
            $res ["msg"] = "无法提交，请检查营业执照图片是否已上传";
            return $res;
        }
        if (empty($advertiser["qlf"]["business_license"]["md5"])) {
            $res ["msg"] = "营业执照图片MD5为空,请重新上传";
            return $res;
        }
        if (empty($advertiser["qlf"]["network_business_license"]["image"])) {
            $res ["msg"] = "无法提交，请检查网络文化经营许可证图片是否已上传";
            return $res;
        }
        if (empty($advertiser["qlf"]["network_business_license"]["md5"])) {
            $res ["msg"] = "网络文化经营许可证图片MD5为空,请重新上传";
            return $res;
        }
        $res["code"] = 1;
        return $res;
    }

    // 上传广告主
    private function uploadAdvertiser()
    {
        $banner = Banner::find($this->bannerId);
        $campaign = $banner->campaign;
        $client = $campaign->client;
        $user = $campaign->client->account->user;

        $advertiser = [];
        $advertiser["name"] = $client->clientname;
        $advertiser["brand"] = $client->brief_name;
        $advertiser["address"] = $client->address;
        $advertiser["contacts"] = $client->contact;
        $advertiser["tel"] = $user->contact_phone;
        $advertiser["firstindustry"] = 111;
        $advertiser["secondindustry"] = 112;
        $qlf = json_decode($client->qualifications, true);
        $advertiser["qlf"] = $qlf;

        // 判断广告主是否能够上传
        $clientMgr = YoukuClientManager::find($client->clientid);
        if ($clientMgr) {
            if ($clientMgr->status == YoukuMaterialManager::STATUS_PENDING_SUBMISSION) {
                $tmp_qlf1 = ["name" => "营业执照", "url" => $qlf["business_license"]["image"],
                    "md5" => $qlf["business_license"]["md5"], "operation" => "update"];
                $tmp_qlf2 = ["name" => "网络文化经营许可证",
                    "url" => $qlf["network_business_license"]["image"],
                    "md5" => $qlf["network_business_license"]["md5"], "operation" => "update"];
                $advertiser["qualifications"] = [$tmp_qlf1, $tmp_qlf2];
                unset($advertiser["qlf"]);
                $res = $this->post2youku($this->url_upload_advertiser, ["advertiser" => $advertiser]);
                if ($res === false) {
                    LogHelper::info("upload advertiser fail");
                    return ["code" => 0, "msg" => "发送广告主失败"];
                } else {
                    $clientMgr->status = YoukuMaterialManager::STATUS_PENDING_AUDIT;
                    $clientMgr->save();
                    return ["code" => 2, "msg" => "广告主已经上传"];
                }
            }
            return ["code" => 2, "msg" => "广告主已经上传"];
        }
        $res = $this->checkAdvertiserCouldUpload($advertiser);
        if ($res["code"] == 0) {
            return $res;
        }
        unset($advertiser["qlf"]);
        $tmp_qlf1 = ["name" => "营业执照", "url" => $qlf["business_license"]["image"],
            "md5" => $qlf["business_license"]["md5"], "operation" => "add"];
        $tmp_qlf2 = ["name" => "网络文化经营许可证",
            "url" => $qlf["network_business_license"]["image"],
            "md5" => $qlf["network_business_license"]["md5"], "operation" => "add"];
        $advertiser["qualifications"] = [$tmp_qlf1, $tmp_qlf2];

        $res = $this->post2youku($this->url_upload_advertiser, ["advertiser" => $advertiser]);
        if ($res === false) {
            LogHelper::info("upload advertiser fail");
            return ["code" => 0, "msg" => "发送广告主失败"];
        } else {
            if ($res["result"] == 0) {
                $clientMgr = new YoukuClientManager();
                $clientMgr->clientid = $client->clientid;
                $clientMgr->brand = $advertiser["brand"];
                $clientMgr->firstindustry = $advertiser["firstindustry"];
                $clientMgr->secondindustry = $advertiser["secondindustry"];
                $clientMgr->status = YoukuClientManager::STATUS_PENDING_AUDIT;
                $clientMgr->type = 0;
                $clientMgr->reason = "";
                $clientMgr->save();
                return ["code" => 2, "msg" => "上传广告主成功"];
            } else {
                $msg = is_string($res["message"]) ? $res["message"] : json_encode($res["message"]);
                return ["code" => 0, "msg" => $msg];
            }
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
            Campaign::AD_TYPE_BANNER_IMG => ['640*100'],
            Campaign::AD_TYPE_HALF_SCREEN => ['600*500'],
            Campaign::AD_TYPE_FULL_SCREEN => ['640*480'],
            Campaign::AD_TYPE_VIDEO => ['640*480'],
        ];
        if ($adType !== null) {
            return isset($data[$adType]) ? $data[$adType] : [];
        } else {
            return $data;
        }
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
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('b.bannerid', $this->bannerId);
        if ($this->material_type == 1) {
            $ret = $select->whereRaw("{$prefix}i.width / {$prefix}i.height IN ({$s1})")
                ->whereIn('i.width', $s2)
                ->get();
        } else {
            $ret = $select->addSelect('i.real_name')
                ->where('i.status', CampaignVideo::STATUS_USING)
                ->get();
        }
        return $ret;
    }

    private function getSizeForSqlV1()
    {
        if ($this->material_type == 1) {
            return "600 / 500, 640 / 100, 640 / 480";
        } else {
            return "640 / 480";
        }
    }

    private function getSizeForSqlV2()
    {
        if ($this->material_type == 1) {
            return [600, 640,];
        } else {
            return [640];
        }
    }

    private function insertMaterialImages()
    {
        // 查询投放到优酷广告素材
        $ret = $this->getMaterialImages();
        foreach ($ret as $row) {
            // 检测素材是否存在
            $material = YoukuMaterialManager::where('id', $row->id)
                ->where('type', $this->material_type)
                ->first();
            if ($material) {
                // 素材被替换时，更新URL并更改状态为待提交
                if ($this->material_type == 2 &&
                    $material->upload_status != YoukuMaterialManager::UPLOAD_STATUS_LOADING
                ) {
                    if ($row->url != $material->source_url) {
                        YoukuMaterialManager::where('id', $row->id)
                            ->where('type', $this->material_type)
                            ->update(['upload_status' => YoukuMaterialManager::UPLOAD_STATUS_LOADING]);

                        $url = $this->getYoukuUrl($row->url, $row->real_name);
                        YoukuMaterialManager::where('id', $row->id)
                            ->where('type', $this->material_type)
                            ->update([
                                'url' => $url,
                                'source_url' => $row->url,
                                'status' => YoukuMaterialManager::STATUS_PENDING_SUBMISSION,
                                'upload_status' => YoukuMaterialManager::UPLOAD_STATUS_FINISH,
                            ]);
                        LogHelper::info('update ' . $row->id . ' url from ' . $material->url . ' to ' . $row->url);
                    }
                } else {
                    if ($row->url != $material->url) {
                        YoukuMaterialManager::where('id', $row->id)
                            ->where('type', $this->material_type)
                            ->update([
                                'url' => $row->url,
                                'status' => YoukuMaterialManager::STATUS_PENDING_SUBMISSION,
                            ]);
                        LogHelper::info('update ' . $row->id . ' url from ' . $material->url . ' to ' . $row->url);
                    }
                }
            } else {
                $material = new YoukuMaterialManager();
                // 新增素材，插入到素材管理表
                if ($this->material_type == 2) {
                    $url = $this->getYoukuUrl($row->url, $row->real_name);
                    $material->url = $url;
                } else {
                    $material->url = $row->url;
                }
                $material->id = $row->id;
                $material->source_url = $row->url;
                $material->startdate = '2012-08-30';
                $material->enddate = '2020-08-30';
                $material->type = $this->material_type;
                $material->status = YoukuMaterialManager::STATUS_PENDING_SUBMISSION;
                $material->upload_status = YoukuMaterialManager::UPLOAD_STATUS_FINISH;
                $material->save();
                LogHelper::info('add meaterial id ' . $row->id . ' url ' . $row->url);
            }
        }
    }

    //下载视频并上传到优酷，替换成最新的URL
    private function getYoukuUrl($url, $real_name)
    {
        $file_name = storage_path('video') . '/' . $real_name;
        file_put_contents($file_name, file_get_contents($url));
        $result = YoukuHelper::youKuUpload($file_name, $real_name);
        LogHelper::info($result);
        if ($result) {
            return 'http://v.youku.com/v_show/id_' . $result . '.html';
        }
        return '';
    }

    /**
     * 获取素材信息
     *
     * @param int $status
     * @return array|static[]
     */
    private function getImage($status = YoukuMaterialManager::STATUS_PENDING_SUBMISSION)
    {
        $material_tb = $this->material_type == 1 ? "campaigns_images" : "campaigns_video";
        $s1 = $this->getSizeForSqlV1();
        $s2 = $this->getSizeForSqlV2();
        $prefix = \DB::getTablePrefix();
        $select = \DB::table("{$material_tb} AS i")
            ->leftJoin('youku_material_manager AS ym', 'ym.id', '=', 'i.id')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('products AS p', 'p.id', '=', 'c.product_id')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->leftJoin('clients AS cli', 'cli.clientid', '=', 'c.clientid')
            ->select('ym.url', 'p.link_url', 'cli.clientname', 'ym.id', 'ym.startdate', 'ym.enddate', 'c.campaignid')
            ->where('cli.clients_status', Client::STATUS_ENABLE)
            ->where('b.bannerid', $this->bannerId)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('ym.status', $status)
            ->where('ym.type', $this->material_type);

        if ($this->material_type == 1) {
            $ret = $select->whereRaw("{$prefix}i.width / {$prefix}i.height IN ($s1)")
                ->whereIn('i.width', $s2)
                ->get();
        } else {
            $ret = $select->where('i.status', CampaignVideo::STATUS_USING)->get();
        }

        return $ret;
    }

    /**
     * 请求优酷接口
     *
     * @param $url
     * @param $post_data
     * @return bool
     */
    private function post2youku($url, $post_data)
    {
        $post_data["dspid"] = $this->dspid;
        $post_data["token"] = $this->token;
        $post_data = json_encode($post_data);

        $result = HttpClientHelper::call($url, $post_data, [
            'Content-Type' => "application/json"
        ]);

        LogHelper::info('youku : ' . $url . " : " . $post_data);
        LogHelper::info('youku return: ' . $result);

        $res = json_decode($result, true);
        if ($res['result'] != 0) {
            LogHelper::info('youku return fail: ' . $result);
            return false;
        }

        return $res;
    }

    /**
     * 上传素材
     */
    private function uploadAdMaterial()
    {
        // 素材上传前，插入需要上传的素材
        $this->insertMaterialImages();

        // 获取待上传素材信息
        $ret = $this->getImage();
        if (count($ret) <= 0) {
            return ["code" => 0, "msg" => ''];
        }

        $image_list = [];
        $image = [];
        $upload_imgs = [];
        foreach ($ret as $row) {
            $image['url'] = $row->url;
            $image['landingpage'] = $row->link_url;
            $image['advertiser'] = $row->clientname;
            $image['startdate'] = $row->startdate;
            $image['enddate'] = $row->enddate;
            $image_list[] = $image;
            array_push($upload_imgs, $image['url']);
        }

        $post_data = [];
        $post_data ["material"] = $image_list;

        $res = $this->post2youku($this->url_upload, $post_data);
        if ($res === false) {
            LogHelper::info("upload youku fail");
            return ["code" => 1, "msg" => "发送广告素材失败"];
        } else {
            $msg = '素材';
            $ids = ArrayHelper::classColumn($ret, 'id');
            // 上传素材ID写入日志
            LogHelper::info('upload youku material id:' . implode(',', $ids));
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
                                $reason = Config::get('biddingos.youku_fail_reason.' . $k);
                                $msg = $msg . $url . "[{$reason}],";
                                YoukuMaterialManager::where('id', $item->id)
                                    ->where('type', $this->material_type)
                                    ->update([
                                        'status' => YoukuMaterialManager::STATUS_SYSTEM_ERROR,
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
            YoukuMaterialManager::whereIn('id', $ids)
                ->where('type', $this->material_type)
                ->update([
                    'status' => YoukuMaterialManager::STATUS_PENDING_AUDIT
                ]);

            if (count($res['message']) > 0) {
                return ["code" => 1, "msg" => $msg];
            }

            $msg = implode(",", $upload_imgs) . "已提交";
            return ["code" => 1, "msg" => $msg];
        }
    }

    // 查询、更新素材状态
    private function checkAdxStatusAndUpdate()
    {
        //查询素材
        $ret = $this->getImage(YoukuMaterialManager::STATUS_PENDING_AUDIT);
        $image_list = [];
        foreach ($ret as $row) {
            $image_list[] = $row->url;
        }

        if (count($image_list) <= 0) {
            return ["code" => 0, "msg" => "没有审核中的素材 : " . $this->bannerId];
        }

        $post_data = [];
        $post_data["materialurl"] = $image_list;

        $res = $this->post2youku($this->url_status, $post_data);
        if ($res === false) {
            LogHelper::info("fail to post2youku for checking status");
            return ["code" => 1, "msg" => "fail to post2youku for checking status"];
        } else {
            $IS_STATUS_REJECT = false;//不通过
            $IS_STATUS_ADOPT = true;//通过
            $msg = "";
            if ($res['message']['total'] > 0) {
                // 获取素材审核状态，修改本地素材管理状态
                foreach ($res['message']['records'] as $item) {
                    foreach ($ret as $rItem) {
                        $new_url = $rItem->url;
                        if ($item['url'] == $new_url) {
                            $status = $this->getMaterialStatus($item['result']);
                            YoukuMaterialManager::where('id', $rItem->id)
                                ->where('type', $this->material_type)
                                ->update([
                                    'status' => $status,
                                    'reason' => $item['reason']
                                ]);
                            LogHelper::info('update ' . $rItem->id . ' url ' . $item ['url'] . 'to status ' . $status);
                            if ($status == YoukuMaterialManager::STATUS_REJECT) {
                                $IS_STATUS_REJECT = true;
                                $msg = $msg . $item['reason'] . "->" . $new_url . " ";
                            }
                            if ($status != YoukuMaterialManager::STATUS_ADOPT) {
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
            $msg = implode(",", $image_list) . "审核中";
            return ["code" => 1, 'msg' => $msg];
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
            case '不通过':
                $status = YoukuMaterialManager::STATUS_REJECT;
                break;
            case '通过':
                $status = YoukuMaterialManager::STATUS_ADOPT;
                break;
            case '待审核':
            default:
                $status = YoukuMaterialManager::STATUS_PENDING_AUDIT;
                break;
        }
        return $status;
    }
}
