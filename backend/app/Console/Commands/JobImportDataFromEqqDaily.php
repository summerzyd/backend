<?php
namespace App\Console\Commands;

use App\Models\ManualDeliveryData;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputArgument;
use App\Components\Eqq\Eqq;
use App\Models\Campaign;
use App\Models\OperationClient;

class JobImportDataFromEqqDaily extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_import_data_from_eqq_daily {subDate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从广点通同步某日人工投放数据';

    protected $eqq = null;// Eqq对象
    protected $errors = array();// 存储错误信息的数组
    
    const REPAIR_TYPE_NEED_INCREASE_REVENUE = 2;// 少录了广点通统计数据，需从广告主账户扣款
    const REPAIR_TYPE_NEED_DECREASE_REVENUE = 1;// 多录了广点通统计数据，需向广告主账户补款
    const REPAIR_TYPE_NEED_FIRST_IMPORT = 0;// 首次录入广点通统计数据

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $subDate = $this->argument('subDate');
        $this->eqq = new Eqq($subDate);
        $login = $this->eqq->login();
        $loginCnt = 0;
        while (! $login) { // 尝试登录3次
            if ($loginCnt > 2) { // 3次登录失败，退出脚本
                $data = array();
                $data['subject'] = '【广点通数据录入】尝试登录' . $loginCnt . '次';
                $data['msg']['content'] = '尝试登录' . $loginCnt . '次失败:' . $this->eqq->errorInfo();
                $this->eqq->sendMail($data);
                return false;
            }
            
            $login = $this->eqq->login();
            if (! $login) {
                $this->error('@'
                    . date('Y-m-d H:i:s')
                    . '---relogin '
                    . ++ $loginCnt .
                    ' failed...msg: '
                    . $this->eqq->errorInfo());
            }
        }
        $totalPage = 0;
        $confPage = 0;
        $getAddListFailedCnt = 0;
        do {
            $res = $this->eqq->getAdList(isset($confPage) ? $confPage + 1 : 1); //获取广告列表
            
            if ($res === false) {
                $getAddListFailedCnt ++;
                if ($getAddListFailedCnt <= 3) {
                    $this->error('get add list error(' . $getAddListFailedCnt . '): 请求e.qq.com服务器失败');
                    continue;
                }
                
                $data = array();
                $data['subject'] = '【广点通数据录入】请求e.qq.com服务器失败';
                $data['msg']['content'] = $this->eqq->errorInfo();
                $this->eqq->sendMail($data);
                return false;
            }
            $addList = json_decode($res, true);
            
            if (isset($addList) && $addList['ret'] > 0) {// 获取广告列表失败
                $getAddListFailedCnt ++;
                if ($getAddListFailedCnt <= 3) {
                    $this->error('get add list error(' . $getAddListFailedCnt . '): ' . $addList['msg']);
                    continue;
                }
                
                if ($addList['msg'] === '请先登录' || $addList['msg'] === '登录态验证失败') {
                    // 广点通获取登录态失败，则记日志、发告警邮件
                    $this->error('get login status error: ' . $addList['msg']);
                    
                    $data = array();
                    $data['subject'] = '【广点通数据录入】获取登录态失败';
                    $data['msg']['content'] = '登录获取登录态失败:' . $addList['msg'];
                    $this->eqq->sendMail($data);
                    return false;
                } else { // 拉取广告列表失败，发告警
                    $this->error('get adlist error: ' . $addList['msg']);
                    
                    $data = array();
                    $data['subject'] = '【广点通数据录入】获取广点通广告列表失败';
                    $data['msg']['content'] = '获取广点通广告列表失败:' . $addList['msg'];
                    $this->eqq->sendMail($data);
                    return false;
                }
            }
            
            $conf = $addList['data']['conf'];
            $confPage = $conf['page'];
            $confPageSize = $conf['pagesize'];
            $confTotalNum = $conf['totalnum'];
            $totalPage = ceil($confTotalNum / $confPageSize);
            
            foreach ($addList['data']['list'] as $_adList) { // 遍历广告
                $_getAddDetailFailedCnt = 0;
                $names = explode('-', $_adList['ordername']);
                $campaignid = $clientName = array_shift($names); // 第一个是广告主名称 or campaignid
                $appName = implode('-', $names); // 第二个开始是广告名称
                
                do {
                    $res = $this->eqq->getAdDetail($_adList['orderid']); // 获取单个广告统计详情
                    
                    $adDetail = json_decode($res, true);
                    if (! $adDetail) { // 返回数据格式错误
                        $_getAddDetailFailedCnt ++;
                        if ($_getAddDetailFailedCnt < 3) {
                            $this->error('get add('
                                . $_adList['ordername']
                                . ') detail error('
                                . $_getAddDetailFailedCnt
                                . '): 请求e.qq.com服务器失败');
                            continue;
                        }
                        
                        $this->error('get add('
                            . $_adList['ordername']
                            . ') detail error('
                            . $_getAddDetailFailedCnt
                            . '): 请求e.qq.com服务器失败');
                        
                        break;
                    }
                    
                    if (isset($adDetail) && $adDetail['ret'] > 0) { // 获取广告统计数据失败，发告警
                        $_getAddDetailFailedCnt ++;
                        if ($_getAddDetailFailedCnt < 3) {
                            $this->error('get add('
                                . $_adList['ordername']
                                . ') detail error('
                                . $_getAddDetailFailedCnt
                                . '): ' . $adDetail['msg']);
                            continue;
                        }
                        
                        $this->error('get ordername('
                            . $_adList['ordername']
                            . ') adgroupdetail error: '
                            . $adDetail['msg']);
                        $this->addError('根据ordername('
                            . $_adList['ordername']
                            . ')获取广点通广告数据统计失败:' . $adDetail['msg']);
                        
                        break;
                    }
                } while (! $adDetail || $adDetail['ret'] > 0);
                
                if (! $adDetail || $adDetail['ret'] > 0) {
                    continue;
                }
                
                $adInfo = $this->getAdInfo($campaignid, $appName); // 广告id+广告名称=》从ADN查询广告信息
                if ($adInfo === false) {
                    continue;
                }
                
                $statistics = [
                    'views' => 0,
                    'clicks' => 0,
                    'conversions' => 0,
                    'expense' => 0,
                ];
                foreach ($adDetail['data']['list'] as $k => $_adDetail) { // 遍历广告统计数据，准备录入
                    $statistics['views'] += intval($_adDetail['viewcount']); // 展示量
                    $statistics['clicks'] += intval($_adDetail['validclickcount']); // 点击量
                    $statistics['conversions'] += floatval($_adDetail['download']); // 下载量
                    $statistics['expense'] += floatval($_adDetail['cost']); // 支出
                }
                $statistics['expense'] = $statistics['expense'] / 100;
                // 人工投放数据导入up_manual_deliverydata
                $this->addManualData($clientName, $appName, $statistics, $adInfo);
                unset($clientName, $appName, $adInfo, $res, $adDetail, $addList);
            }
        } while ($confPage <= $totalPage);
        
        // 发送处理出错的广告告警邮件
        $data = array();
        $data['subject'] = '【广点通数据录入】录入广点通人工投放数据错误';
        $data['msg']['content'] = $this->getError();
        $this->eqq->sendMail($data);
    }

    /**
     *
     * @param string $clientName 广告主名称
     * @param string $appName 广告名称
     * @param object $_adDetail 广告统计信息，从广点通获取
     * @param object $adInfo 广告信息，从adn数据库查询
     * @param int $delay 数据延迟$delay秒
     * @return boolean
     */
    protected function addManualData($clientName, $appName, $statistics, $adInfo)
    {
        //保存人工投放数据
        $param = array();
        $param['date']         = $this->eqq->getDate();
        $param['data_type']    = $adInfo['revenue_type'] == Campaign::REVENUE_TYPE_CPA ? 'A2D-AF' : 'D2D';
        $param['affiliate_id'] = $adInfo['affiliateid'];
        $param['zone_id']      = $adInfo['zoneid'];
        $param['banner_id']    = $adInfo['bannerid'];
        $param['campaign_id']  = $adInfo['campaignid'];
        $param['views']        =  $statistics['views'];
        $param['clicks']       = $adInfo['revenue_type']==Campaign::REVENUE_TYPE_CPC ? $statistics['clicks'] : 0;
        $param['conversions']  = $adInfo['revenue_type']==Campaign::REVENUE_TYPE_CPD ? $statistics['conversions'] : 0;
        $param['expense']      = $statistics['expense'] * $adInfo['income_rate'] / 100;
        $param['flag']  = self::REPAIR_TYPE_NEED_FIRST_IMPORT;
        $param['update_time'] = date('Y-m-d H:i:s');
        $param['repair_type'] = self::REPAIR_TYPE_NEED_FIRST_IMPORT;

        $delivery = DB::table('manual_deliverydata')
            ->where('date', $param['date'])
            ->where('affiliate_id', $param['affiliate_id'])
            ->where('banner_id', $param['banner_id'])
            ->where('campaign_id', $param['campaign_id'])
            ->select('views', 'clicks', 'conversions', 'flag', 'id', 'zone_id')
            ->first();

        if (count($delivery) > 0) {
            $param['flag'] = $delivery->flag;
            $type = $adInfo['revenue_type'] == Campaign::REVENUE_TYPE_CPC ? 'clicks' : 'conversions';
            
            if ($delivery->$type == $param[$type]) {//认为广点通数据无更新，无需变更
                //数据无更新
                $this->notice('广告主('
                    . $clientName
                    .'), 广告('
                    . $appName
                    .') @'
                    .$this->eqq->getDate()
                    .' no data to update,jump to next ad...');
                return true;
                //小于广点通数据，认为adn少录了数据，repair_type=2
            } elseif ($delivery->$type < $param[$type]) {
                if ($delivery->$type == 0) {
                    //首次录入
                    $param['repair_type'] = self::REPAIR_TYPE_NEED_FIRST_IMPORT;
                } else {
                    //数据少录，需扣款
                    $param['repair_type'] = self::REPAIR_TYPE_NEED_INCREASE_REVENUE;
                }
            } else {//大于广点通数据，认为adn多录了数据，repair_type=1
                //数据多录，需补款
                $param['repair_type'] = self::REPAIR_TYPE_NEED_DECREASE_REVENUE;
            }
            //flag=0时，视为首次录入
            if ($delivery->flag == self::REPAIR_TYPE_NEED_FIRST_IMPORT) {
                $param['repair_type'] = self::REPAIR_TYPE_NEED_FIRST_IMPORT;
            }
            
            //如果数据有多个广告位，则是试投
            if ($delivery->zone_id != $this->eqq->getZoneID()) {
                $oldDelivery = DB::table('manual_deliverydata')
                    ->where('date', $param['date'])
                    ->where('affiliate_id', $param['affiliate_id'])
                    ->where('banner_id', $param['banner_id'])
                    ->where('campaign_id', $param['campaign_id'])
                    ->select(DB::raw('sum(clicks) as clicks, sum(conversions) as conversions,
                    sum(revenues) as revenues, sum(expense) as expense'))
                    ->get();
                $oldDelivery = isset($oldDelivery[0]) ? $oldDelivery[0] : null;
                if (!$oldDelivery) {
                    return false;
                }
                $clicksSub = $param['clicks'] - $oldDelivery->clicks;
                $conversionsSub = $param['conversions'] - $oldDelivery->conversions;
                $expenseSub = $param['expense'] - $oldDelivery->expense;

                // 差额数据补到当天的78位置上
                $yesterdayDelivery = ManualDeliveryData::where('date', $param['date'])
                    ->where('zone_id', $this->eqq->getZoneID())
                    ->where('banner_id', $param['banner_id'])
                    ->where('campaign_id', $param['campaign_id'])
                    ->first();

                if ($yesterdayDelivery) {
                    $yesterdayDelivery->clicks += $clicksSub;
                    $yesterdayDelivery->conversions += $conversionsSub;
                    $yesterdayDelivery->expense += $expenseSub;
                    $yesterdayDelivery->flag = ManualDeliveryData::FLAG_ASSIGNED;
                    $yesterdayDelivery->repair_type = $conversionsSub > 0
                        ? self::REPAIR_TYPE_NEED_INCREASE_REVENUE : self::REPAIR_TYPE_NEED_DECREASE_REVENUE;
                    $yesterdayDelivery->repair_status = 0;
                    $yesterdayDelivery->save();
                } else {
                    // 如果是负数就不分发
                    if ($conversionsSub > 0) {
                        $modelExist = ManualDeliveryData::find($delivery->id);
                        $model = $modelExist->replicate();
                        $model->zone_id = $this->eqq->getZoneID();
                        $model->clicks = $clicksSub;
                        $model->conversions = $conversionsSub;
                        $model->expense = $expenseSub;
                        $model->revenues = 0;
                        $model->views = 0;
                        $model->flag = ManualDeliveryData::FLAG_UNTREATED;
                        $model->repair_type = self::REPAIR_TYPE_NEED_FIRST_IMPORT;
                        $model->repair_status = 0;
                        $model->save();
                    }
                }
                $this->notice('repair success: ' . json_encode($param));

                return true;
            }

            //如果数据已经被审计，则重新修复
            if ($this->isAudit($param['campaign_id'], $param['date'])) {
                $param['flag'] = ManualDeliveryData::FLAG_ASSIGNED;
                $param['repair_type'] = 1;
                $param['repair_status'] = 0;
            }
        }
    
        //组装SQL
        $fields = array_keys($param);
        $values = [];
        foreach ($param as $k => $v) {
            $values[] = "'{$v}'";
        }
        $status = DB::update('INSERT INTO up_manual_deliverydata('
            . implode(',', $fields)
            . ') VALUES('
            . implode(',', $values)
            . ') ON DUPLICATE KEY UPDATE views='.$param['views']
            . ',clicks='.$param['clicks']
            . ',conversions='.$param['conversions']
            . ',expense='.$param['expense']
            . ',repair_type='.$param['repair_type']
            . ",update_time='{$param['update_time']}'"
            . ",repair_status=0"
            . ",flag='{$param['flag']}'");
    
        if (!$status) {
            $sql = DB::getQueryLog();
            $message = 'insert into up_manual_deliverydata error! sql:'. print_r(array_pop($sql), true);
            $this->error($message . '@line ' . __LINE__);
            $this->addError('广告主('
                . $clientName
                .'), 广告('
                . $appName
                .')@'
                .$this->eqq->getDate()
                .', import error:'
                .$message);
    
            return false;
        }

        $this->notice('广告主('.$clientName.'), 广告('.$appName.') @'.$this->eqq->getDate().' import success!');
        return true;
    }
    
    
    /**
     * 根据广告id+广告名称=>查询广告信息
     * @param int $campaignid 广告id，设定里面头部的id
     * @param string $appName 广告名称
     * @return boolean|array
     */
    protected function getAdInfo($campaignid, $appName)
    {
        $adInfo = DB::table('banners as b')
            ->join('zones as z', 'z.affiliateid', '=', 'b.affiliateid')
            ->join('affiliates as af', 'af.affiliateid', '=', 'b.affiliateid')
            ->join('campaigns as c', 'c.campaignid', '=', 'b.campaignid')
            ->join('appinfos as a', 'a.app_id', '=', 'c.campaignname')
            ->join('clients as cl', 'cl.clientid', '=', 'c.clientid')
            ->where('af.mode', $this->eqq->getDeliveryMode())
            ->where('z.type', '!=', $this->eqq->getZoneType())
            ->where('b.affiliateid', $this->eqq->getEqqAffiliateId())
            ->where('z.zoneid', $this->eqq->getZoneID())
            ->where('c.campaignid', $campaignid)
            ->select(
                'b.affiliateid',
                'b.bannerid',
                'b.campaignid',
                'af.income_rate',
                'c.revenue_type',
                'z.zoneid',
                'a.app_show_name',
                'cl.clientname'
            )
            ->get();
        if (count($adInfo)===0) {
            $this->error('campaignid('.$campaignid.'), 广告('.$appName.'), 未ADN匹配到投放中的广告!');
            $this->addError('campaignid('.$campaignid.'), 广告('.$appName.'), 未ADN匹配到投放中的广告!');
            return false;
        } elseif (count($adInfo)>1) {
            $this->error('campaignid('
                .$campaignid
                .'), 广告('. $appName .'), 匹配到多个广告! '
                .print_r($adInfo, true));
            $this->addError('campaignid('.$campaignid.'), 广告('.$appName.'), 在ADN匹配到多个广告');
            return false;
        }
        return (array)$adInfo[0];
    }
    
    private function isAudit($id, $date)
    {
        $oc = OperationClient::where('campaign_id', $id)->where('date', $date)->select('issue')->first();
        if ($oc && $oc->issue == OperationClient::ISSUE_APPROVAL) {
            return true;
        }
        return false;
    }
    
    /**
     * 检查此广告是否已人工处理了
     * @param string $date
     * @param integer $zone_id
     * @param integer $affiliateid
     * @param integer $campaignid
     * @param integer $bannerid
     */
    /*private function getDeliveryData($date, $zone_id, $affiliateid, $campaignid, $bannerid)
    {
        $row = ManualDeliveryData::where('affiliate_id', $affiliateid)
              ->where('zone_id', $zone_id)
              ->where('date', $date)
              ->where('campaign_id', $campaignid)
              ->where('banner_id', $bannerid)
              ->select('is_manual')
              ->first();
        return $row;
    }*/
    
    
    /**
     * 添加错误信息
     * @param string $error
     */
    protected function addError($error)
    {
        $this->errors[] = $error;
    }
    
    /**
     * 返回错误信息的数组
     * @return array:
     */
    protected function getError()
    {
        return $this->errors;
    }
    
    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array(
                'subDate',
                InputArgument::REQUIRED
            )
        );
    }
}
