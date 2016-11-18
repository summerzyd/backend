<?php
namespace App\Console\Commands;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\HttpClientHelper;
use App\Components\Helper\LogHelper;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignImage;
use App\Models\Client;
use App\Models\IQiyiClientManager;
use App\Models\IQiyiMaterialManager;
use App\Components\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TempIqiyiAdx extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_iqiyi_adx {--cmd=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'iqiyi adx command';

    protected $afid;
    protected $dspid;
    protected $token;
    protected $url_advertiser_upload;
    protected $url_advertiser_status_single;
    protected $url_advertiser_status_multi;
    protected $url_ad_upload;
    protected $url_ad_status;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->afid = Config::get('biddingos.adx.iqiyi.afid');
        $this->token = Config::get('biddingos.adx.iqiyi.token');
        $this->url_advertiser_upload = Config::get('biddingos.adx.iqiyi.advertiser_upload');
        $this->url_advertiser_status_single = Config::get('biddingos.adx.iqiyi.advertiser_status_single');
        $this->url_advertiser_status_multi = Config::get('biddingos.adx.iqiyi.advertiser_status_multi');
        $this->url_ad_upload = Config::get('biddingos.adx.iqiyi.ad_upload');
        $this->url_ad_status = Config::get('biddingos.adx.iqiyi.ad_status');
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
            case "adver_upload":
                $this->uploadAdvertiser();
                break;
            case "adver_status":
                $this->adverStatus();
                break;
            case "ad_upload":
                $this->adUpload();
                break;
            case "ad_status":
                $this->adStatus();
                break;
            default:
                echo "invalid cmd: " . $cmd;
        }
    }
    
    private function insertMaterialImages()
    {
        //查询投放到爱奇艺广告素材
        $prefix = \DB::getTablePrefix();
        $ret = \DB::table('campaigns_images AS i')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->select('i.url', 'i.id')
            ->where('b.affiliateid', $this->afid)
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->where('c.status', Campaign::STATUS_DELIVERING)
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

        foreach ($ret as $row) {
            //检测素材是否存在
            $material = IQiyiMaterialManager::where('id', $row->id)
                ->first();
            if ($material) {
                //素材被替换时，更新URL并更改状态为待提交
                if ($row->url != $material->url) {
                    IQiyiMaterialManager::where('id', $row->id)->update([
                        'url' => $row->url,
                        'status' => IQiyiMaterialManager::STATUS_PENDING_SUBMISSION,
                    ]);
                    $this->notice('update ' . $row->id . ' url from ' . $material->url . ' to ' . $row->url);
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

                $this->notice('add meaterial id ' . $row->id . ' url ' . $row->url);
            }
        }
    }
    /**
     * 获取素材信息
     * @param int $status
     * @return array|static[]
     */
    public function getImage($status = IQiyiMaterialManager::STATUS_PENDING_SUBMISSION)
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
            ->where('b.affiliateid', $this->afid)
            ->where('cli.clients_status', Client::STATUS_ENABLE)
            ->where('b.status', Banner::STATUS_PUT_IN)
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
     * 请求爱奇艺接口
     * @param $url
     * @param $header
     * @return bool
     */
    public function getFromIqiyi($url, $params, $header)
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
    
    public function postPicToIqiyiByUrl($url, $src_pic_url, $header)
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
        
        $res = json_decode($result, true);
        return $res;
    }

    /**
     * 请求爱奇艺接口
     * @param $url
     * @param $post_data
     * @return bool
     */
    public function post2iqiyi($url, $post_data, $post_header)
    {
        $result = HttpClientHelper::call($url, $post_data, $post_header);

        LogHelper::info('iqiyi : ' . $url . " : " . json_encode($post_header));
        LogHelper::info('iqiyi return: ' . $result);

        $res = json_decode($result, true);
        return $res;
    }
    
    /**
     * 映射图片素材为adx的素材类型
     * @param $width
     * @param $height
     */
    public function getAdxIqiyiAdtype($width, $height)
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
     * 上传素材
     */
    public function adUpload()
    {
        //素材上传前，插入需要上传的素材
        $this->insertMaterialImages();

        //获取待上传素材信息
        $ret = $this->getImage();
        
        $ok_ids = [];
        foreach ($ret as $row) {
            
            $f = explode('/', $row->url);
            $adtype = $this->getAdxIqiyiAdtype($row->width, $row->height);
            $link_url = urlencode($row->link_url);
            $file_name = urlencode($f[count($f)-1]);
            $header = [
            "click_url: {$link_url}",
            "ad_id: {$row->clientid}",//广告主id
            "video_id: {$row->id}",
            "ad_type: {$adtype}",
            "file_name: {$file_name}",
            "platform: 2", //2为移动端
            "end_date: {$row->enddate}",
            "dsp_token: {$this->token}",
            ];
            
            if ($adtype==4) {
                array_push($header, "duration: 120");
            }
            
            //网络请求
            $res = $this->postPicToIqiyiByUrl($this->url_ad_upload, $row->url, $header);

            if (!isset($res['code']) || $res['code'] != 0) { //上传失败
                $code = !isset($res['code']) ? 9999: $res['code'];
//                 IQiyiMaterialManager::where('id', $row->id)->update([
//                 'status' => IQiyiMaterialManager::STATUS_SYSTEM_ERROR,
//                 'reason' => Config::get('biddingos.iqiyi_fail_reasion_material.' . $code)
//                 ]);
                $this->info("upload img id : {$row->id} fail");
           
            } else { //上传成功
                
                //素材上传成功后，修改素材管理素材状态为待审核
                IQiyiMaterialManager::where('id', $row->id)->update([
                    'status' => IQiyiMaterialManager::STATUS_PENDING_AUDIT,
                    'm_id' => $res['m_id']
                ]);
                $this->info("upload img id : {$row->id} success");
            }
        }
        
        $this->info("upload done");
        
    }


    /**
     * 查询素材状态
     */
    public function adStatus()
    {
        //找到待审核的素材
        $ret = $this->getImage(IQiyiMaterialManager::STATUS_PENDING_AUDIT);
        $batch = [];
        foreach ($ret as $row) {
            if (!empty($row->m_id)) {
                $batch[] = $row->m_id;
            }
        }
        $batch = implode(',', $batch);
        $res = $this->getFromIqiyi($this->url_ad_status, ['batch'=>$batch], null);
        if ($res === false) {
            $this->info("fail");
        } else {
            if (is_array($res['results']) && count($res['results']) > 0) {
                //获取素材审核状态，修改本地素材管理状态
                foreach ($res['results'] as $item) {
                    $status = $this->getMaterialStatus($item['status']);
                    IQiyiMaterialManager::where('m_id', $item['m_id'])->update([
                        'status' => $status,
                        'reason' =>  (isset($item['reason'])?$item['reason']:""),
                        'tv_id' =>  (isset($item['tv_id'])?$item['tv_id']:"")
                    ]);
                    $this->notice('update ' . json_encode($item));
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
        //广告主上传前，插入需要上传的广告主信息
        $this->insertClientsInfo();
        
        $ret = $this->getClients();
        foreach ($ret as $row) {
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
                $code = !isset($res['code']) ? 9999: $res['code'];
//              IQiyiClientManager::where('clientid', $row->clientid)->update([
//              'status' => IQiyiClientManager::STATUS_SYSTEM_ERROR,
//              'reason' => Config::get('biddingos.iqiyi_fail_reasion_client.' . $code)
//              ]);
                $this->info("upload advertiser id : {$row->clientid} fail");

            } else { //上传成功
                //广告主上传成功后，修改广告主管理状态为待审核
                IQiyiClientManager::where('clientid', $row->clientid)->update([
                'status' => IQiyiClientManager::STATUS_PENDING_AUDIT
                ]);
                $this->info("upload advertiser id : {$row->clientid} success");
            }
        }
    }

    /**
     * 查询广告主状态
     */
    public function adverStatus()
    {
        $ret = $this->getClients(IQiyiClientManager::STATUS_PENDING_AUDIT);
        $batch = [];
        foreach ($ret as $row) {
            array_push($batch, $row->clientid);
        }
        $batch = implode(',', $batch);
        $res = $this->getFromIqiyi($this->url_advertiser_status_multi, ['batch'=>$batch], null);
        if ($res === false) {
            $this->info("check iqiyi advertiser fail");
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
                    $this->notice('update ' . json_encode($item));
                }
            }
            $this->info("success");
        }
    }
    
    private function insertClientsInfo()
    {
        //查询投放到爱奇艺的广告主信息
        $res = \DB::table('clients AS c')
        ->leftJoin('campaigns AS cpn', 'c.clientid', '=', 'cpn.clientid')
        ->leftJoin('banners AS b', 'b.campaignid', '=', 'cpn.campaignid')
        ->select('c.clientname', 'c.clientid')
        ->where('c.clients_status', Client::STATUS_ENABLE)
        ->where('b.affiliateid', $this->afid)
        ->distinct()
        ->get();
    
        foreach ($res as $row) {
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
                    $this->notice('update ' . $row->clientid .
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
    
                $this->notice('add advertiser clientid ' . $row->clientid . ' name ' . $row->clientname);
            }
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
        ->where('b.affiliateid', $this->afid)
        ->where('icm.status', $status)
        ->distinct()
        ->get();
        return $res;
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
