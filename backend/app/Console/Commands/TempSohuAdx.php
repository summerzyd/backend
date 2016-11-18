<?php
namespace App\Console\Commands;

use App\Components\Sohu\SohuEncrypt;
use App\Models\Banner;
use App\Components\Config;
use App\Models\Campaign;
use App\Models\SoHuClientManager;
use App\Models\SoHuMaterialManager;

class TempSohuAdx extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_sohu_adx {--cmd=} {--value=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sohu adx command';

    protected $afid;
    protected $base_url = 'http://api.ad.sohu.com/';
    protected $auth_consumer_key;
    protected $auth_consumer_secret;
    protected $price_key;
    protected $v3_downloadcgi_uri;
    protected $v3_downloadendcgi_uri;
    protected $v3_clickcgi_uri;
    protected $v3_impressioncgi_uri;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->afid = Config::get('biddingos.sohu.afid');
        $this->auth_consumer_key = Config::get('biddingos.sohu.auth_consumer_key');
        $this->auth_consumer_secret = Config::get('biddingos.sohu.auth_consumer_secret');
        $this->price_key = Config::get('biddingos.sohu.price_key');
        $this->v3_downloadcgi_uri = Config::get('biddingos.sohu.v3_downloadcgi_uri');
        $this->v3_downloadendcgi_uri = Config::get('biddingos.sohu.v3_downloadendcgi_uri');
        $this->v3_clickcgi_uri = Config::get('biddingos.sohu.v3_clickcgi_uri');
        $this->v3_impressioncgi_uri = Config::get('biddingos.sohu.v3_impressioncgi_uri');
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
        $ret = "invalid cmd: " . $cmd;
        switch ($cmd) {
            case "upload":
                $ret = $this->upload();
                break;
            case "status":
                $ret = $this->status();
                break;
            case "delete-m"://删除素材
                $str = $this->option('value');
                if (!$str || $str == 'all') {
                    $ret = $this->deletaAllMaterial();
                } else {
                    $ret = $this->materialDelete($str);
                }
                break;
            case "decode"://价格解密，对应win_price参数值
                $str = $this->option('value');
                $ret = SohuEncrypt::decode($this->price_key, $str);
                break;
//                 $ret = $this->customerList();
//                 $ret = $this->customerCreate();
//                 $ret = $this->customerUpdate();
//                 $ret = $this->materialList();
//                 $ret = $this->materialCreate();
//                 $ret = $this->materialDelete();
            default:
                break;
        }
        $this->notice($ret);
    }
    
    public function upload()
    {
        $this->checkClient();
        $this->checkMaterial();
    }
    
    public function status()
    {
        $ret = $this->customerList();
        if ($ret !== false) {
            $fill = SoHuClientManager::getColumns();
            foreach ($ret as $c) {
                switch (intval($c['tv_status'])) {
                    case 0:
                        $this->notice($c['customer_name'] . " waiting audit.");
                        break;
                    case 1:
                        $this->notice($c['customer_name'] . " audit sucess.");
                        break;
                    case 2:
                        $this->notice($c['customer_name'] . " audit reject.");
                        break;
                }
                $client = \DB::table('sohu_client_manager')->where('customer_key', $c['customer_key'])->first();
                if ($client) {
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
        $ret = $this->materialList();
        if ($ret !== false) {
            $fill = SoHuMaterialManager::getColumns();
            foreach ($ret as $m) {
                switch (intval($m['status'])) {
                    case 0:
                        $this->notice($m['file_source'] . " waiting audit.");
                        break;
                    case 1:
                        $this->notice($m['file_source'] . " audit sucess.");
                        break;
                    case 2:
                        $this->notice($m['file_source'] . " audit reject.");
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
        }
        
    }
    
    /**
     * 检查广告主是否与sohu对接
     */
    public function checkClient()
    {
        $clients = \DB::table('banners as b')
            ->leftJoin('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->leftJoin('clients as cl', 'c.clientid', '=', 'cl.clientid')
            ->leftJoin('sohu_client_manager as s_cl', 'cl.clientid', '=', 's_cl.clientid')
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('b.affiliateid', $this->afid)
            ->whereNull('s_cl.clientid')
            ->select('cl.clientid', 'cl.clientname', 'cl.website', 'cl.contact')
            ->distinct()
            ->get();
        foreach ($clients as $c) {
            $ret = $this->customerCreate([
                /*
                 * @todo 广告主的信息与搜狐要求上传的信息匹配
                 */
                'customer_name' => $c->clientname,
                'customer_website' => $c->website,
            ]);
            if ($ret !== false) {
                $this->notice('Create Client Success! ' . $ret);
                \DB::table('sohu_client_manager')->insert([
                    'clientid' => $c->clientid,
                    'customer_key' => $ret,
                    'customer_name' => $c->clientname,
                ]);
            } else {
                $this->error('Create Client Error!');
            }
        }
    }
    
    /**
     * 检查素材是否与sohu对接
     */
    public function checkMaterial()
    {
        $materials = \DB::table('banners as b')
            ->leftJoin('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->leftJoin('products as p', 'c.product_id', '=', 'p.id')
            ->leftJoin('clients as cl', 'c.clientid', '=', 'cl.clientid')
            ->leftJoin('sohu_client_manager as s_cl', 'cl.clientid', '=', 's_cl.clientid')
            ->leftJoin('campaigns_images as ci', 'ci.campaignid', '=', 'b.campaignid')
            ->leftJoin('sohu_material_manager as s_m', 's_m.campaign_image_id', '=', 'ci.id')
            ->where('b.status', Banner::STATUS_PUT_IN)
            ->where('c.status', Campaign::STATUS_DELIVERING)
            ->where('b.affiliateid', $this->afid)
            ->whereNull('s_m.campaign_image_id')
            ->whereNotNull('ci.id')
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
        foreach ($materials as $m) {
            if ($m->customer_key) {
                $args = [
                    /*
                     * @todo sohu素材信息匹配
                     */
                    'customer_key' => $m->customer_key,
                    'material_name' => $m->campaignid . '-' . $m->width . '-' . $m->height,
                    'file_source' => $m->url,
                    'imp' => json_encode([$this->v3_impressioncgi_uri . '?%%DISPLAY%%&win_price=%%WINPRICE%%']),
                    'click_monitor' => $this->v3_clickcgi_uri . '?%%CLICK%%&win_price=%%WINPRICE%%',
                    'gotourl' => $m->link_url,
                    'advertising_type' => '102100',
                    'submit_to' => 2, //1：搜狐门户；2：搜狐视频
                    'delivery_type' => 1, //1：RTB；2：PDB；3：PMP；4：Preferred Deal
                ];
                $ret = $this->materialCreate($args);
                if ($ret !== false) {
                    $this->notice('Create Material Success! ' . $ret);
                    \DB::table('sohu_material_manager')->insert(array_merge($args, ['campaign_image_id' => $m->id]));
                } else {
                    $this->error('Create Material Error! ' . $m->url);
                }
            } else {
                $this->error("Client {$m->clientname} didn't Created! Cann't create material!");
            }
        }
    }
    
    public function deletaAllMaterial()
    {
        $list = $this->materialList();
        if ($list !== false) {
            foreach ($list as $m) {
                $this->notice('Detele material: ' . $m['file_source']);
                $this->materialDelete($m['file_source']);
            }
        }
    }
    /**
     * 循环遍历获取所有广告主信息
     */
    public function customerList($args = null)
    {
        $only = true;
        if (!$args) {
            $args = [
                'perpage' => 50,
                'page' => 1
            ];
            $only = false;
        }
        $url = $this->base_url . 'exchange/customer/list';
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);
        $content = $this->checkResp($encrypt->setUrl($url)->setParams($args)->get());
        if ($content !== false) {
            if ($only) {
                return $content['items'];
            } else {
                $items = $content['items'];
                $loop = ceil($content['count'] / 50);
                for ($i = 2; $i <= $loop; $i++) {
                    $tmp = $this->customerList([
                        'perpage' => 50,
                        'page' => $i,
                    ]);
                    $items = array_merge($items, $tmp);
                }
                return $items;
            }
        }
        return $content;
    }
    
    public function customerCreate($args = null)
    {
//         $args = [
//             'customer_name' => '测试广告主3',        //广告主名称，不可重复
//             'customer_website' => '',     //广告主官方网站地址
//             'company_address' => '',      //公司地址
//             'capital' => '',              //公司注册资金
//             'reg_address' => '',          //公司注册地区
//             'contact' => '',              //公司联系人
//             'phone_number' => '',         //公司联系电话
//             'publish_category' => '',     //经营/发布产品类别
//             'oganization_code' => '',     //组织机构证号
//             'oganization_license' => '@', //组织机构扫描件（小于3MB）
//             'business_license' => '@',    //营业执照照片（小于3MB）
//             'legalperson_identity' => '@',//法人代表身份证照片（小于3MB）
//             'tax_cert' => '@',            //税务登记证（小于3MB）
//             'taxreg_cert' => '@',         //完税证照片（小于3MB）
//             'ext_license' => '@',         //扩展资质文件，仅限zip格式文件（小于3MB）
//             'deadline' => '',             //期望最晚审核时间，格式示例：2015-12-02
//         ];
        $url = $this->base_url . 'exchange/customer/create';
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);
    
        return $this->checkResp($encrypt->setUrl($url)->setParams($args)->post());
    }
    
    public function customerUpdate($args = null)
    {
//         $args = [
//             'customer_key' => '00977532a51d52e069e41cb725cfa534',
//             'customer_name' => '测试广告主2',        //广告主名称，不可重复
//             'customer_website' => 'http://dsp.biddingos.com',        //广告主官方网站地址
//             'company_address' => '',      //公司地址
//             'capital' => '',              //公司注册资金
//             'reg_address' => '',          //公司注册地区
//             'contact' => '',              //公司联系人
//             'phone_number' => '',         //公司联系电话
//             'publish_category' => '',     //经营/发布产品类别
//             'oganization_code' => '',     //组织机构证号
//             'oganization_license' => '@', //组织机构扫描件（小于3MB）
//             'business_license' => '@',    //营业执照照片（小于3MB）
//             'legalperson_identity' => '@',//法人代表身份证照片（小于3MB）
//             'tax_cert' => '@',            //税务登记证（小于3MB）
//             'taxreg_cert' => '@',         //完税证照片（小于3MB）
//             'ext_license' => '@',         //扩展资质文件，仅限zip格式文件（小于3MB）
//             'deadline' => '',             //期望最晚审核时间，格式示例：2015-12-02
//         ];
        $url = $this->base_url . 'exchange/customer/update';
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);
    
        return $this->checkResp($encrypt->setUrl($url)->setParams($args)->post());
    }
    
    public function materialList($args = null)
    {
        $only = true;
        if (!$args) {
            $args = [
                'perpage' => 50,
                'page' => 1
            ];
            $only = false;
        }
        $url = $this->base_url . 'exchange/material/list';
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);
        
        $content = $this->checkResp($encrypt->setUrl($url)->setParams($args)->get());
        if ($content !== false) {
            if ($only) {
                return $content['items'];
            } else {
                $items = $content['items'];
                $loop = ceil($content['count'] / 50);
                for ($i = 2; $i <= $loop; $i++) {
                    $tmp = $this->materialList([
                        'perpage' => 50,
                        'page' => $i,
                    ]);
                    $items = array_merge($items, $tmp);
                }
                return $items;
            }
        }
        return $content;
    }
    
    public function materialCreate($args = null)
    {
//         $args =[
//             'customer_key' => '055a06e4ca1a8b97a20865c8414d71c0',
//             'material_name' => '测试素材1',
//             'file_source' => 'http://dsp.biddingos.com/bos-front/web/site/domain/default/images/bid_logo.png',
//             'imp' => ['http://dsp.biddingos.com'],
//             'click_monitor' => 'http://dsp.biddingos.com',
//             'gotourl' => 'http://dsp.biddingos.com',
//             'advertising_type' => '102100',
//             'submit_to' => 2,
//             'delivery_type' => 1,
//             'campaign_id' => '',
//             'expire' => '',
//             'imp_sendtag' => [],
//             'clk_sendtag' => '',
//             'material_type' => '',
//             'template' => '',
//             'main_attr' => '',
//             'slave' => '',
//         ];
        $url = $this->base_url . 'exchange/material/create';
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);
    
        return $this->checkResp($encrypt->setUrl($url)->setParams($args)->post());
    }
    
    public function materialDelete($file_source)
    {
        $args = [
            'file_source' => $file_source,
        ];
        
        $url = $this->base_url . 'exchange/material/delete';
        $encrypt = new SohuEncrypt($this->auth_consumer_key, $this->auth_consumer_secret);
    
        return $this->checkResp($encrypt->setUrl($url)->setParams($args)->post());
    }
    
    public function checkResp($ret)
    {
//         $this->notice($ret);
        $ret = json_decode($ret, true);
        if (!empty($ret['status']) && $ret['status']) {
            return $ret['content'];
        } else {
            $this->error('Error! ' . json_encode($ret));
            return false;
        }
    }
    
    public function fillTable($table, $cols)
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
