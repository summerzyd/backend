<?php
namespace App\Console\Commands;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\HttpClientHelper;
use App\Components\Helper\LogHelper;
use App\Models\Banner;
use App\Models\Campaign;
use App\Models\CampaignImage;
use App\Models\Client;
use App\Models\ChinaMobileClientManager;
use App\Models\ChinaMobileMaterialManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TempChinaMobileAdx extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_china_mobile_adx {--cmd=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'china mobile adx command';

    protected $afid;
    protected $dspid;
    protected $token;
    protected $url_advertiser_upload;
    protected $url_advertiser_status;
    protected $url_ad_upload;
    protected $url_ad_status;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        
        $this->afid = Config::get('biddingos.adx.chinamobile.afid');
        $this->dspid = Config::get('biddingos.adx.chinamobile.dspid');
        $this->token = Config::get('biddingos.adx.chinamobile.token');
        $this->url_advertiser_upload = Config::get('biddingos.adx.chinamobile.advertiser_upload');
        $this->url_advertiser_status = Config::get('biddingos.adx.chinamobile.advertiser_status');
        $this->url_ad_upload = Config::get('biddingos.adx.chinamobile.ad_upload');
        $this->url_ad_status = Config::get('biddingos.adx.chinamobile.ad_status');
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


    /**
     * 上传广告主信息
     */
    public function uploadAdvertiser()
    {
        //广告主上传前，插入需要上传的广告主信息
        $this->insertClientsInfo();
        $this->notice("insertClientsInfo success");
        $ret = $this->getClients();
        $this->notice("getClients success");
        
        print_r($ret);
        foreach ($ret as $row) {
            $this->notice("clientname = ".$row->clientname." client id = ".$row->clientid);
            $name = $row->clientname;
            $id = $this->getId();
            $qualifications = $row->qualifications;
            $qual = json_decode($qualifications);

            $t = [];
            array_push($t, array('attachurl'=>"http://dsp.biddingos.com"));
            $tm = [];
            array_push($tm, array('qualifyid'=>$row->clientid,'qualifyattach'=>$t));
            $tmp = [];
            array_push($tmp, array('adverid'=>$row->clientid,
                                    'advername'=>$name,
                                    'website'=>"http://dsp.biddingos.com",
                                    'vocate'=>$row->industry,
                                    'opertype'=>$row->upload_op,
                                    'qualify'=>$tm
                                    ));
            $client = array('id'=>$id,'dspid'=>$this->dspid,'token'=>$this->token,'adver'=>$tmp);
            $this->info('chinamobile : ' . $this->url_advertiser_upload . " : " . json_encode($client));

            $head[] = "Content-type: application/json";
            $res = $this->post2chinamobile($this->url_advertiser_upload, json_encode($client), $head);
            //$this->info(json_encode($res));
            if (!isset($res['id']) || $res['id'] != $id || !isset($res['data']) || !is_array($res['data'])) {
                $this->info("upload advertiser id : {$row->clientid} fail param error");
            } else {
                //更新广告主状态
                if (is_array($res['data']) && count($res['data']) == 1) {
                    //获取广告主审核状态，修改本地广告主管理状态
                    foreach ($res['data'] as $item) {
                        if ($item['id'] == $row->clientid
                                && ($item['code'] == 0 || $item['code'] == 2007)) { //上传成功
                            //广告主上传成功后，修改广告主管理状态为待审核
                            ChinaMobileClientManager::where('clientid', $row->clientid)->update([
                            'status' => ChinaMobileClientManager::STATUS_PENDING_AUDIT
                            ]);
                            $this->info("upload advertiser id : {$row->clientid} success");
                        } else { //上传失败
                            $this->info("upload advertiser id : {$row->clientid} fail codeid = ".$item['code']);
                        }
                    }
                    $this->info("upload advertiser id : {$row->clientid} success");
                } else {
                    $this->info("upload advertiser id : {$row->clientid} fail  count num error");
                }
            }
              
        }
    }


    private function insertClientsInfo()
    {
        //查询投放到移动的广告主信息
        $res = \DB::table('clients AS c')
        ->leftJoin('campaigns AS cpn', 'c.clientid', '=', 'cpn.clientid')
        ->leftJoin('banners AS b', 'b.campaignid', '=', 'cpn.campaignid')
        ->select('c.clientname', 'c.clientid', 'c.industry')
        ->where('c.clients_status', Client::STATUS_ENABLE)
        ->where('b.status', Banner::STATUS_PUT_IN)
        ->where('b.affiliateid', $this->afid)
        ->distinct()
        ->get();
        print_r($res);
        foreach ($res as $row) {
            //检测广告主是否存在
            $client = ChinaMobileClientManager::where('clientid', $row->clientid)
            ->first();
            if ($client) {
                //广告主被替换时，更新名字并更改状态为待提交
                if ($row->clientname != $client->clientname) {
                    ChinaMobileClientManager::where('clientid', $row->clientid)->update([
                    'clientname' => $row->clientname,
                    'status' => ChinaMobileClientManager::STATUS_PENDING_SUBMISSION,
                    'upload_op' => 2
                    ]);
                    $this->notice('update ' . $row->clientid .
                            ' name from ' . $client->clientname .
                            ' to ' . $row->clientname);
                }
            } else {
                //新增广告主，插入到广告主管理表
                $client = new ChinaMobileClientManager();
                $client->clientid = $row->clientid;
                $client->clientname = $row->clientname;
                $client->status = $client::STATUS_PENDING_SUBMISSION;
                $client->upload_op = 1;
                $client->industry = "1,4";
                $client->save();
    
                $this->notice('add advertiser clientid ' . $row->clientid . ' name ' . $row->clientname);
            }
        }
    }



    /**
     * 请求中国移动接口
     * @param $url
     * @param $post_data
     * @return bool
     */
    public function post2chinamobile($url, $post_data, $post_header)
    {
        $result = HttpClientHelper::call($url, $post_data, $post_header);

        $this->info('chinamobile : ' . $url . " : " . json_encode($post_data));
        $this->info('chinamobile return: ' . $result);
        LogHelper::info('chinamobile : ' . $url . " : " . json_encode($post_header));
        LogHelper::info('chinamobile return: ' . $result);

        $res = json_decode($result, true);
        return $res;
    }




    /**
     * 查询广告主状态
     */
    public function adverStatus()
    {
        $ret = $this->getClients(ChinaMobileClientManager::STATUS_PENDING_AUDIT);
        foreach ($ret as $row) {
            $id = $this->getId();
            $post_data = array("id"=>$id,
                                "dspid"=>$this->dspid,
                                "token"=>$this->token,
                                "date"=>'',
                                "adverid"=>$row->clientid
                                );
            $post_header[] = "Content-type: application/json";
            $res = $this->post2chinamobile($this->url_advertiser_status, json_encode($post_data), $post_header);

            if (!isset($res['id']) || $res['id'] != $id || !isset($res['data']) || !is_array($res['data'])) {
                $this->info("get adverStatus id : {$row->clientid} fail");
            } else {
                //更新广告主状态
                if (is_array($res['data']) && count($res['data']) == 1) {
                //获取广告主审核状态，修改本地广告主管理状态
                    foreach ($res['data'] as $item) {
                        if ($item['adverid'] == $row->clientid) {
                            $status = $this->getStatus($item['status']);
                            ChinaMobileClientManager::where('clientid', $item['adverid'])->update([
                                'status' => $status,
                                'memo' => (isset($item['memo'])?$item['memo']:"")
                                ]);
                            $this->notice('update ' . json_encode($item));
                        } else {
                            $this->info("get adverStatus id : {$row->clientid} fail");
                        }
                    }
                } else {
                    $this->info("get adverStatus id : {$row->clientid} fail");
                }
            }
        }
    }


    private function getClients($status = ChinaMobileClientManager::STATUS_PENDING_SUBMISSION)
    {
        $res = \DB::table('clients AS c')
        ->leftJoin('campaigns AS cpn', 'c.clientid', '=', 'cpn.clientid')
        ->leftJoin('banners AS b', 'b.campaignid', '=', 'cpn.campaignid')
        ->leftJoin('china_mobile_client_manager AS cmcm', 'c.clientid', '=', 'cmcm.clientid')
        ->select('c.clientname', 'c.clientid', 'cmcm.upload_op', 'c.website', 'c.qualifications', 'cmcm.industry')
        ->where('c.clients_status', Client::STATUS_ENABLE)
        ->where('b.status', Banner::STATUS_PUT_IN)
        ->where('b.affiliateid', $this->afid)
        ->where('cmcm.status', $status)
        ->distinct()
        ->get();
        return $res;
    }

    /**
     * 状态转换
     * @param $statusName
     * @return int
     */
    private function getStatus($statusName)
    {
        switch ($statusName) {
            case 5: //异常
                $status = ChinaMobileClientManager::STATUS_ABNORMAL;
                break;
            case 44: //已删除的
                $status = ChinaMobileClientManager::STATUS_DELETED;
                break;
            case 3: //审核未通过
                $status = ChinaMobileClientManager::STATUS_REJECT;
                break;
            case 4: //黑名单
                $status = ChinaMobileClientManager::STATUS_BLACK_LIST;
                break;
            case 2: //审核通过，可以使用
                $status = ChinaMobileClientManager::STATUS_ADOPT;
                break;
            case 1: //待审核
            default:
                $status = ChinaMobileClientManager::STATUS_PENDING_AUDIT;
                break;
        }
        return $status;
    }


    /**
     * 获取id号
     * @param
     * @return string
     */
    public function getId()
    {
        $num = rand(1000000, 9999999);
        return md5($num);
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
            $id = $this->getId();
            $adtype = $this->getAdxChinaMobileAdtype($row->width, $row->height);
            $link_url = $row->link_url;
            $url = $row->url;

            $s = [];
            $tm = [];
            array_push($tm, array("creativeid"=>$row->id,
                                    "dsporderid"=>$row->campaignid,
                                    "adverid"=>$row->clientid,
                                    "qualify"=>$s,
                                    "opertype"=>$row->upload_op,
                                    "style"=>$row->type,
                                    "destinationurl"=>$link_url,
                                    "creativeurl"=>$url,
                                    "creativestyle"=>$adtype,
                                    "system"=>'',
                                    "drivice"=>''));

            $post_data = array("id"=>$id,"dspid"=>$this->dspid,"token"=>$this->token,"creative"=>$tm);
            
            $this->info('chinamobile : ' . $this->url_ad_upload . " : " . json_encode($post_data));

            $head[] = "Content-type: application/json";
            $res = $this->post2chinamobile($this->url_ad_upload, json_encode($post_data), $head);

            if (!isset($res['id']) || $res['id'] != $id
                    || !isset($res['data']) || !is_array($res['data'])) { //上传失败
                
                $this->info("upload img id : {$row->id} fail");
           
            } else { //上传成功
                
                if (is_array($res['data']) && count($res['data']) == 1) {
                    foreach ($res['data'] as $item) {
                        if ($item['id'] == $row->id && $item['code'] == 0) { //上传成功
                            //素材上传成功后，修改素材管理素材状态为待审核
                            ChinaMobileMaterialManager::where('id', $row->id)->update([
                                'status' => ChinaMobileMaterialManager::STATUS_PENDING_AUDIT
                            ]);
                            $this->info("upload img id : {$row->id} success");
                        } else {
                            $this->info("upload img id : {$row->id} fail");
                        }
                    }
                } else {
                    $this->info("upload img id : {$row->id} fail");
                }
            }
        }
        
        $this->info("upload done");
        
    }


    private function insertMaterialImages()
    {
        //查询投放到中的移动广告素材
        $prefix = \DB::getTablePrefix();
        $ret = \DB::table('campaigns_images AS i')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->select('i.url', 'i.id')
            ->where('b.affiliateid', $this->afid)
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->whereRaw("{$prefix}i.width / {$prefix}i.height IN (
                    800 / 120,
                    600 / 500,
                    640 / 270,
                    960 / 640,
                    600 / 300,
                    480 / 800,
                    640 / 960,
                    720 / 1280,
                    768 / 1024,
                    728 / 90,
                    468 / 60,
                    1024 / 768,
                    1280 / 800,
                    1366 / 768
                )")
            ->whereIn('i.width', [
                800,
                600,
                640,
                960,
                600,
                480,
                640,
                720,
                768,
                728,
                468,
                1024,
                1280,
                1366
            ])->get();

        print_r($ret);
        foreach ($ret as $row) {
            //检测素材是否存在
            $material = ChinaMobileMaterialManager::where('id', $row->id)
                ->first();
            if ($material) {
                //素材被替换时，更新URL并更改状态为待提交
                if ($row->url != $material->url) {
                    ChinaMobileMaterialManager::where('id', $row->id)->update([
                        'url' => $row->url,
                        'status' => ChinaMobileMaterialManager::STATUS_PENDING_SUBMISSION,
                        'upload_op' => 2
                    ]);
                    $this->notice('update ' . $row->id . ' url from ' . $material->url . ' to ' . $row->url);
                }
            } else {
                //新增素材，插入到素材管理表
                $material = new ChinaMobileMaterialManager();
                $material->id = $row->id;
                $material->url = $row->url;
                $material->upload_op = 1;
                $material->startdate = '2012-08-30';
                $material->enddate = '2020-08-30';
                $material->status = ChinaMobileMaterialManager::STATUS_PENDING_SUBMISSION;
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
    public function getImage($status = ChinaMobileMaterialManager::STATUS_PENDING_SUBMISSION)
    {
        $prefix = \DB::getTablePrefix();
        $ret = \DB::table('campaigns_images AS i')
            ->leftJoin('china_mobile_material_manager AS cmmm', 'cmmm.id', '=', 'i.id')
            ->leftJoin('campaigns AS c', 'c.campaignid', '=', 'i.campaignid')
            ->leftJoin('products AS p', 'p.id', '=', 'c.product_id')
            ->leftJoin('banners AS b', 'b.campaignid', '=', 'i.campaignid')
            ->leftJoin('clients AS cli', 'cli.clientid', '=', 'c.clientid')
            ->select(
                'i.id',
                'i.campaignid',
                'cli.clientid',
                'cmmm.upload_op',
                'i.type',
                'i.url',
                'p.link_url',
                'b.download_url',
                'i.width',
                'i.height'
            )
            ->where('b.affiliateid', $this->afid)
            ->where('cli.clients_status', Client::STATUS_ENABLE)
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('cmmm.status', $status)
            //测试逻辑
            ->whereRaw("{$prefix}i.width / {$prefix}i.height IN (
                    800 / 120,
                    600 / 500,
                    640 / 270,
                    960 / 640,
                    600 / 300,
                    480 / 800,
                    640 / 960,
                    720 / 1280,
                    768 / 1024,
                    728 / 90,
                    468 / 60,
                    1024 / 768,
                    1280 / 800,
                    1366 / 768
                )")
            ->whereIn('i.width', [
                800,
                600,
                640,
                960,
                600,
                480,
                640,
                720,
                768,
                728,
                468,
                1024,
                1280,
                1366
            ])->get();

        return $ret;
    }

    /**
     * 映射图片素材为adx的素材类型
     * @param $width
     * @param $height
     */
    public function getAdxChinaMobileAdtype($width, $height)
    {

        if ($width==800 && $height==120) {
            return 1;
        } elseif ($width==600 && $height==500) {
            return 2;
        } elseif ($width==640 && $height==270) {
            return 3;
        } elseif ($width==960 && $height==640) {
            return 4;
        } elseif ($width==600 && $height==300) {
            return 5;
        } elseif ($width==480 && $height==800) {
            return 6;
        } elseif ($width==640 && $height==960) {
            return 7;
        } elseif ($width==720 && $height==1280) {
            return 8;
        } elseif ($width==768 && $height==1024) {
            return 9;
        } elseif ($width==728 && $height==90) {
            return 10;
        } elseif ($width==468 && $height==60) {
            return 11;
        } elseif ($width==1024 && $height==768) {
            return 12;
        } elseif ($width==1280 && $height==800) {
            return 13;
        } elseif ($width==1366 && $height==768) {
            return 14;
        }
        return 0;
    }


    /**
     * 查询素材状态
     */
    public function adStatus()
    {
        //找到待审核的素材
        $ret = $this->getImage(ChinaMobileMaterialManager::STATUS_PENDING_AUDIT);
        print_r($ret);
        foreach ($ret as $row) {

            $id = $this->getId();
            $post_data = array("id"=>$id,
                                "dspid"=>$this->dspid,
                                "token"=>$this->token,
                                "date"=>'',
                                "adverid"=>$row->clientid
                                );
            $post_header[] = "Content-type: application/json";
            $res = $this->post2chinamobile($this->url_ad_status, json_encode($post_data), $post_header);
            if (!isset($res['id']) || $res['id'] != $id ||
                    !isset($res['data']) || !is_array($res['data'])) { //查询失败

                $this->info("get adverStatus id : {$row->id} fail");

            } else {
                if (is_array($res['data']) && count($res['data']) == 1) {
                    foreach ($res['data'] as $item) {
                        if ($item['creativeid'] == $row->id) {
                            $status = $this->getStatus($item['status']);
                            ChinaMobileMaterialManager::where('id', $row->id)->update([
                                    'status' => $status,
                                    'memo' =>  (isset($item['memo'])?$item['memo']:"")
                            ]);
                            $this->notice('update ' . json_encode($item));
                        } else {
                            $this->info("upload adverStatus id : {$row->id} fail");
                        }
                    }
                } else {
                    $this->info("upload adverStatus id : {$row->id} fail");
                }

            }
        }
    }
}
