<?php
namespace App\Console\Commands;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\HttpClientHelper;
use App\Components\Helper\LogHelper;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignImage;
use App\Models\Client;
use App\Models\YoukuClientManager;
use App\Models\YoukuMaterialManager;
use App\Components\Config;
use Illuminate\Support\Facades\DB;

class TempYoukuAdx extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_youku_adx {--cmd=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'youku adx command';

    protected $afid;
    protected $dspid;
    protected $token;
    protected $url_upload;
    protected $url_status;
    //protected $url_upload_advertiser;
    //protected $url_get_advertiser;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->afid = Config::get('biddingos.adx.youku.afid');
        $this->dspid = Config::get('biddingos.adx.youku.dspid');
        $this->token = Config::get('biddingos.adx.youku.token');
        $this->url_upload = Config::get('biddingos.adx.youku.prefix_url') . "/upload";
        $this->url_status = Config::get('biddingos.adx.youku.prefix_url') . "/status";
        //$this->url_upload_advertiser = Config::get('biddingos.adx.youku.prefix_url') . "/uploadadvertiser";
        //$this->$url_get_advertiser = Config::get('biddingos.adx.youku.prefix_url') . "/getadvertiser";
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cmd = $this->option('cmd');
        switch ($cmd) {
            case "upload":
                $this->upload();
                break;
            case "status":
                $this->status();
                break;
            default:
                echo "invalid cmd: " . $cmd;
        }
    }

    private function insertMaterialImages()
    {
        //查询投放到优酷广告素材
        $prefix = \DB::getTablePrefix();
        $ret = \DB::table('campaigns_images AS i')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->select('i.url', 'i.id')
            ->where('b.affiliateid', $this->afid)
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->whereRaw("{$prefix}i.width / {$prefix}i.height IN (
                    300 / 100,
                    300 / 250,
                    300 / 50,
                    310 / 82,
                    337 / 110,
                    400 / 300,
                    600 / 500,
                    610 / 100,
                    640 / 100,
                    640 / 90
                )")
            ->whereIn('i.width', [
                300,
                310,
                337,
                400,
                600,
                610,
                640
            ])->get();

        foreach ($ret as $row) {
            //检测素材是否存在
            $material = YoukuMaterialManager::where('id', $row->id)
                ->first();
            if ($material) {
                //素材被替换时，更新URL并更改状态为待提交
                if ($row->url != $material->url) {
                    YoukuMaterialManager::where('id', $row->id)->update([
                        'url' => $row->url,
                        'status' => YoukuMaterialManager::STATUS_PENDING_SUBMISSION,
                    ]);
                    $this->notice('update ' . $row->id . ' url from ' . $material->url . ' to ' . $row->url);
                }
            } else {
                //新增素材，插入到素材管理表
                $material = new YoukuMaterialManager();
                $material->id = $row->id;
                $material->url = $row->url;
                $material->startdate = '2012-08-30';
                $material->enddate = '2020-08-30';
                $material->status = YoukuMaterialManager::STATUS_PENDING_SUBMISSION;
                $material->save();

                $this->notice('add meaterial id ' . $row->id . ' url ' . $row->url);
            }
        }
    }
    /**
     * 获取素材信息
     * @param int $status
     * @return array|static[]
     */
    public function getImage($status = YoukuMaterialManager::STATUS_PENDING_SUBMISSION)
    {
        $prefix = \DB::getTablePrefix();
        $ret = \DB::table('campaigns_images AS i')
            ->leftJoin('youku_material_manager AS ym', 'ym.id', '=', 'i.id')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('products AS p', 'p.id', '=', 'c.product_id')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->leftJoin('clients AS cli', 'cli.clientid', '=', 'c.clientid')
            ->select('i.url', 'p.link_url', 'cli.clientname', 'ym.id', 'ym.startdate', 'ym.enddate', 'c.campaignid')
            ->where('b.affiliateid', $this->afid)
            ->where('cli.clients_status', Client::STATUS_ENABLE)
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('ym.status', $status)
            ->whereRaw("{$prefix}i.width / {$prefix}i.height IN (
                    300 / 100,
                    300 / 250,
                    300 / 50,
                    310 / 82,
                    337 / 110,
                    400 / 300,
                    600 / 500,
                    610 / 100,
                    640 / 100,
                    640 / 90
                )")
            ->whereIn('i.width', [
                300,
                310,
                337,
                400,
                600,
                610,
                640
            ])->get();

//        $ret = DB::select('
//            SELECT
//                i.url,
//                p.link_url,
//                cli.clientname
//            FROM
//                up_campaigns_images AS i
//            LEFT JOIN up_youku_material_manager AS ym ON ym.id = i.id
//            LEFT JOIN up_campaigns AS c ON c.campaignid = i.campaignid
//            LEFT JOIN up_products AS p ON p.id = c.product_id
//            LEFT JOIN up_banners as b on b.campaignid = i.campaignid
//            LEFT JOIN up_clients as cli on cli.clientid = c.clientid
//            WHERE
//                b.affiliateid = ' . $this->afid . '
//                and cli.clients_status = 1
//                and b.`status` = 0
//                and c.`status` = 0
//                AND i.width / i.height IN (
//                    300 / 100,
//                    300 / 250,
//                    300 / 50,
//                    310 / 82,
//                    337 / 110,
//                    400 / 300,
//                    600 / 500,
//                    610 / 100,
//                    640 / 100,
//                    640 / 90
//                ) AND i.width IN (
//                    300,
//                    310,
//                    337,
//                    400,
//                    600,
//                    610,
//                    640
//                )
//        ');

        return $ret;
    }

    /**
     * 请求优酷接口
     * @param $url
     * @param $post_data
     * @return bool
     */
    public function post2youku($url, $post_data)
    {
        $post_data["dspid"] = $this->dspid;
        $post_data["token"] = $this->token;
        $post_data = json_encode($post_data);

        $result = HttpClientHelper::call($url, $post_data, ['Content-Type' => "application/json"]);

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
     * 若campaignid属于视频，则替换成视频地址
     * @param $campainid campaign id
     * @param $url 图片地址
     */
    private function checkVideoAd($campainid, $url)
    {
        $prefix = \DB::getTablePrefix();
        $ret = \DB::table('campaigns_video')
        ->select('url')
        ->where('campaignid', $campainid)
        ->get();
        if (!empty($ret) && count($ret)>0) {
            foreach ($ret as $row) {
                return $row->url;
            }
        }
        return $url;
    }

    /**
     * 上传素材
     */
    public function upload()
    {
        //素材上传前，插入需要上传的素材
        $this->insertMaterialImages();

        //获取待上传素材信息
        $ret = $this->getImage();
        $image_list = [];
        $image = [];
        foreach ($ret as $row) {
            $image['url'] = $this->checkVideoAd($row->campaignid, $row->url);
            $image['landingpage'] = $row->link_url;
            $image['advertiser'] = $row->clientname;
            $image['startdate'] = $row->startdate;
            $image['enddate'] = $row->enddate;
            $image_list[] = $image;
        }

        $post_data = [];
        $post_data["material"] = $image_list;

        $res = $this->post2youku($this->url_upload, $post_data);
        if ($res === false) {
            $this->info("upload fail");
        } else {
            $ids = ArrayHelper::classColumn($ret, 'id');
            //上传素材ID写入日志
            $this->notice('upload material id:' . implode(',', $ids));
            if (count($res['message']) > 0) {
                $campaignImages = CampaignImage::whereIn('url', array_values($res['message']))
                    ->select('id', 'url')
                    ->get();
                //上传素材不成功素材，标记为系统错误，并填写原因
                foreach ($res['message'] as $k => $v) {
                    foreach ($v as $url) {
                        //记录上传失败URL
                        $this->notice('fail upload material url:' . $url);
                        foreach ($campaignImages as $item) {
                            if ($url == $item->url) {
                                YoukuMaterialManager::where('id', $item->id)->update([
                                    'status' => YoukuMaterialManager::STATUS_SYSTEM_ERROR,
                                    'reason' => Config::get('biddingos.youku_fail_reason.' . $k)
                                ]);
                                //删除上传失败ID
                                unset($ids[array_search($item->id, $ids)]);
                            }
                        }
                    }
                }
            }

            //素材上传成功后，修改素材管理素材状态为待审核
            YoukuMaterialManager::whereIn('id', $ids)->update([
                'status' => YoukuMaterialManager::STATUS_PENDING_AUDIT,
            ]);

            $this->info("upload success");
        }
    }


    /**
     * 查询素材状态
     */
    public function status()
    {
        $ret = $this->getImage(YoukuMaterialManager::STATUS_PENDING_AUDIT);
        $image_list = [];
        foreach ($ret as $row) {
            $image_list[] = $this->checkVideoAd($row->campaignid, $row->url);
        }

        $post_data = [];
        $post_data["materialurl"] = $image_list;

        $res = $this->post2youku($this->url_status, $post_data);
        if ($res === false) {
            $this->info("fail");
        } else {
            if ($res['message']['total'] > 0) {
                //获取素材审核状态，修改本地素材管理状态
                foreach ($res['message']['records'] as $item) {
                    foreach ($ret as $rItem) {
                        $new_url = $this->checkVideoAd($rItem->campaignid, $rItem->url);
                        if ($item['url'] == $new_url) {
                            $status = $this->getMaterialStatus($item['result']);
                            YoukuMaterialManager::where('id', $rItem->id)->update([
                                'status' => $status,
                                'reason' => $item['reason'],
                            ]);
                            $this->notice('update ' . $rItem->id . ' url ' . $item['url'] . 'to status ' . $status);
                        }
                    }
                }
            }
            $this->info("success");
        }
    }

    /**
     * 上传广告主信息
     */
    public function uploadAdvertiser()
    {
        $ret = $this->getClients();
        $client_list = [];
        $client = [];
        foreach ($ret as $row) {
            $client['name'] = $row->clientname;
            $client['brand'] = $row->brand;
            $client['firstindustry'] = $row->firstindustry;
            $client['secondindustry'] = $row->secondindustry;
            $qualification = json_decode($row->qualifications, true);
            foreach ($qualification as $item) {
                if (empty($item['operation'])) {
                    continue;
                }
                $client['qualifications'][] = $item;
            }
            $client_list[] = $client;
        }

        $post_data = [];
        $post_data["advertiser"] = $client_list;
        $res = $this->post2youku($this->url_upload_advertiser, $post_data);
        if ($res === false) {
            $this->info("upload advertiser fail");
        } else {
            $this->info('upload advertiser success');
        }

    }

    private function getClients($status = YoukuClientManager::STATUS_PENDING_SUBMISSION)
    {
        $res = \DB::table('clients AS c')
            ->leftJoin('youku_client_manager AS ycm', 'c.clientid', '=' . 'ycm.clientid')
            ->select('c.clientname', 'ycm.brand', 'c.qualifications', 'ycm.firstindustry', 'ycm.secondindustry')
            ->where('c.clients_status', Client::STATUS_ENABLE)
            ->where('ycm.status', $status)
            ->get();
        return $res;
    }


    /**
     * 状态转换
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
