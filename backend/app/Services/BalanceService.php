<?php
namespace App\Services;

use App\Components\Config;
use App\Components\Formatter;
use App\Models\Account;
use App\Models\Balance;
use App\Models\Campaign;
use App\Models\OperationLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\BalanceLog;
use App\Components\Helper\LogHelper;
use JMS\Serializer\Tests\Fixtures\Publisher;
use App\Models\Recharge;

class BalanceService
{
    /**
     * 取操作描述
     * @param $payType
     * @param null $balanceType
     * @return string
     */
    public static function getActionLabel($payType, $balanceType = null)
    {
        if ($balanceType !== null) {
            $type = BalanceLog::getBalanceType($balanceType);
            if (!is_array($type)) {
                return $type;
            }

        }
        return BalanceLog::getPayTypeLabel($payType);
    }

    /**
     * 取地址
     * @param $prov 省
     * @param $city 市
     * @param $dist 地区
     * @param $addr 地址
     * @return string
     */
    public static function getAddress($prov, $city, $dist, $addr)
    {
        return ($prov).' '.($city == 'null' ? '' : $city).' '.($dist == 'null' ? '' : $dist).' '.$addr;
    }


    /**
     * 取发票详细
     * @param $id
     * @return array
     */
    public static function getInvoice($id)
    {
        $rows = Invoice::find($id)->balancelogs()->select('create_time', 'amount', 'pay_type')->get();

        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'create_time' => $row->create_time->toDateTimeString(),
                'amount' => $row->amount,
                'pay_type_label' => self::getActionLabel($row->pay_type),
                'invoice_id' => $id,
                'balance_log_id' => $row->pivot->balance_log_id
            ];
        }
        return $result;
    }

    /**
     * 代理商余额
     * @param $pageNo
     * @param $pageSize
     * @param $type
     * @return array
     */
    public static function getBrokerBalance($pageNo, $pageSize, $type)
    {
        if ($type == 'recharge') {
            //充值账户
            $payType = [
                BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
                BalanceLog::PAY_TYPE_OFFLINE_RECHARGE,
                BalanceLog::PAY_TYPE_GOLD_BROKER_TO_ADVERTISER,
                BalanceLog::PAY_TYPE_GOLD_ADVERTISER_TO_BROKER
            ];
            //记录类型
            $balanceType = [BalanceLog::BALANCE_TYPE_GOLD_ACCOUNT];

            $type = [
                BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
                BalanceLog::PAY_TYPE_OFFLINE_RECHARGE,
                BalanceLog::PAY_TYPE_GOLD_ADVERTISER_TO_BROKER
            ];
        } else {
            $payType = [
                BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
                BalanceLog::PAY_TYPE_OFFLINE_RECHARGE,
                BalanceLog::PAY_TYPE_PRESENT_GOLD,
                BalanceLog::PAY_TYPE_GIVE_ADVERTISER_TO_BROKER,
                BalanceLog::PAY_TYPE_GIVE_BROKER_TO_ADVERTISER
            ];
            //记录类型
            $balanceType = [
                BalanceLog::BALANCE_TYPE_GIVE_ACCOUNT,
                BalanceLog::BALANCE_TYPE_GIFT_PROMOTION,
            ];

            $type = [
                BalanceLog::PAY_TYPE_ONLINE_RECHARGE,
                BalanceLog::PAY_TYPE_OFFLINE_RECHARGE,
                BalanceLog::PAY_TYPE_PRESENT_GOLD,
                BalanceLog::PAY_TYPE_GIVE_ADVERTISER_TO_BROKER,
            ];
        }

        $adAccountId = Auth::user()->account->account_id;
        $agencyId = Auth::user()->account->broker->agency->agencyid;
        $select = DB::table('balance_log')
            ->leftjoin('users', 'users.user_id', '=', 'balance_log.operator_userid')
            ->where('balance_log.media_id', '=', $agencyId)
            ->where('balance_log.target_acountid', '=', $adAccountId)
            ->whereIn('balance_log.pay_type', $payType)
            ->whereIn('balance_log.balance_type', $balanceType)
            ->orderBy('balance_log.create_time', 'desc')
            ->select(
                'balance_log.create_time AS day_time',
                'balance_log.pay_type AS type',
                'balance_log.amount AS money',
                'users.contact_name',
                'balance_log.comment'
            );

        $count = $select->count();
        $offset = (intval($pageNo) - 1) * intval($pageSize);
        $rows = $select->skip($offset)->take($pageSize)->get();

        //将结果转换成数组
        $rows = json_decode(json_encode($rows), true);
        $incomeTotal = 0;
        $payTotal = 0;
        foreach ($rows as $item) {
            if (in_array($item['type'], $type)) {
                $incomeTotal += floatval($item['money']);
            } else {
                $payTotal += floatval($item['money']);
            }
        }
        // 分页
        $result = [];
        foreach ($rows as $item) {
            if (floatval($item['money']) > 0) {
                $item['money'] = '+' . $item['money'];
            }
            $item['type_label'] = BalanceLog::getPayTypeLabel($item['type']);
            $result[] = $item;
        }
        return [[
            'income_total' => '+' . Formatter::asDecimal($incomeTotal),
            'pay_total' => Formatter::asDecimal($payTotal)
        ], [
            'count' => $count,
            'pageSize' => $pageSize,
            'pageNo' => $pageNo,
        ], $result];
    }
    
    /**
     * 根据up_$type_log的计费
     * @param string $type click/down
     * @param array $log 日志
     * @param int $price 价格
     * @param int $balance 余额
     * @param int $gift 赠送金额
     * @param int $accountid 账户id
     * @return array
     */
    public static function conversion($type, $log, $balance, $gift, $accountId)
    {
        DB::beginTransaction();
        
        $logid = $type . 'id';
        $price = sprintf('%.2f', $log['price']);
        $giftPrice = self::getGiftPrice($accountId, $price);
        $log = array_merge($log, $giftPrice);

        if (!self::addDelivery($type, $log)) {//将即将扣费的up_click_log/up_down_log数据插入up_delivery_log
            $last_query = DB::getQueryLog();
            LogHelper::error('insert into delivery error! msg:' . json_encode(array_pop($last_query)));
            DB::rollback();
            return false;
        }
        if (!DB::update('CALL account_cost_price(?,?)', array($accountId, $price))) {//扣费
            $last_query = DB::getQueryLog();
            LogHelper::error(sprintf(
                'CALL account_cost_price(%d, %.2f)',
                $accountId,
                $price
            ) . ' error! msg:' . json_encode(array_pop($last_query)));
            DB::rollback();
            return false;
        }
        LogHelper::notice(
            $logid
            . ':'
            . $log[$logid]
            . '#'
            . sprintf('CALL account_cost_price(%d, %.2f)', $accountId, $price)
            . ' ok!'
        );
        
        //修改up_click_log/up_down_log的status=0，表示已扣费
        if (!DB::update("UPDATE up_{$type}_log SET status=0 WHERE {$logid}='{$log[$logid]}'")) {
            $last_query = DB::getQueryLog();
            LogHelper::error('update up_' . $type . '_log error! msg:' . json_encode(array_pop($last_query)));
            DB::rollback();
            return false;
        }
        DB::commit();
        return true;
    }
    
    /**
     * 添加up_delivery_log记录
     * @param string $type 日志类型,click/down
     * @param array $data
     * @return boolean
     */
    public static function addDelivery($type, $data)
    {
        //插入up_delivery_log
        $data['source_log_type'] = $type;//表示扣费类型是CPD还是CPC
        $data['source_log_id'] = $data[$type.'id'];//up_click_log/up_down_log的自增id
        $data['status'] = 0;//已扣费
        unset($data[$type.'id'], $data['source_clickid'], $data['source_downid']);
        $keys = array_keys($data);
        foreach ($data as &$v) {
            $v = addslashes($v);
        }
        
        $fields = implode('`,`', $keys);//up_click_log/up_down_log字段名
        $values = implode(',', array_fill(0, count($keys), '?'));//要插入字段对应的值
        
        //插入up_click_log/up_down_log的sql
        $sql = 'INSERT INTO up_delivery_log(`'. $fields .'`)VALUES ('. $values .')';
        
        return DB::insert($sql, array_values($data));
    }
    
    /**
     * 根据campaignid 获取对应的balance信息
     * @param int $id
     * @return array
     */
    public static function getBalanceByCampaignid($campaignId)
    {
        return DB::table('balances AS bal')
            ->join('clients AS cl', 'cl.account_id', '=', 'bal.account_id')
            ->join('campaigns AS c', 'c.clientid', '=', 'cl.clientid')
            ->where('c.campaignid', $campaignId)
            ->select('bal.balance', 'bal.gift', 'cl.clientname', 'cl.account_id')
            ->get();
    }
    
    /**
     * 获取广告赠送金消费和媒体赠送金结算
     * @param $accountId
     * @param $price
     * @return array
     */
    public static function getGiftPrice($accountId, $price)
    {
        // 默认不使用赠送金
        $arr = ['price_gift' => 0];
        // 获取是否需要使用赠送金
        $obj = DB::table('balances')
            ->where('account_id', $accountId)
            ->where('gift', '>=', '0')
            ->where('balance', '<', $price)
            ->first();
        if ($obj) {
            if ($obj->balance >= 0) {
                //【余额有钱】
                if (($obj->balance + $obj->gift) <= $price) {
                    //余额钱不够，加上赠送金 也不够支付，使用【全部】赠送金
                    $arr['price_gift'] = $obj->gift;
                } elseif (($obj->balance + $obj->gift) > $price) {
                    //余额钱不够，加上部分赠送金够，使用【部分】赠送金
                    $arr['price_gift'] = $price - $obj->balance;
                }
            } else {
                //【余额没有钱、欠费】
                if ($obj->gift >= $price) {
                    // 赠送金足够支付本次消费，使用【部分】赠送金
                    $arr['price_gift'] = $price;
                } elseif ($obj->gift < $price) {
                    // 赠送金不足够支付本次消费，使用【全部】赠送金
                    $arr['price_gift'] = $obj->gift;
                }
            }
        }
        return $arr;
    }

    /**
     * 更新余额
     * @param $accountId
     * @param $balance
     * @param $agencyId
     * @return bool
     */
    public static function updateBalance($accountId, $balance, $agencyId)
    {
        $cur_balance = Balance::find($accountId);
        $save = DB::update("INSERT INTO up_balances(account_id,balance) VALUES(?, ?)
                                ON DUPLICATE KEY UPDATE balance = balance + ?;", array($accountId, $balance, $balance));
        $operator_accountid = Auth::user()->account->account_id;
        $operator_userid = Auth::user()->user_id;
        $create_time = date('Y-m-d H:i:s');
        $save_log = DB::insert(
            "insert into up_balance_log(media_id, operator_accountid,
                            operator_userid,target_acountid, amount, pay_type, balance, balance_type,
                            comment, create_time) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$agencyId,
                $operator_accountid,
                $operator_userid,
                $accountId,
                $balance,
                1,
                $cur_balance->balance + $cur_balance->gift,
                0,
                '',
                $create_time]
        );

        $save_agency = DB::update("INSERT INTO up_balances(account_id,balance) VALUES(?,?) ON
                            DUPLICATE KEY UPDATE balance = balance + ?;", [$operator_accountid, $balance, $balance]);

        if (!$save || !$save_log || !$save_agency) {
            return false;
        } else {
            //启动因余额不足被暂停的广告计划
            self::restartCampaign($accountId, $balance, 6002);
            return true;
        }
    }
    
    
    public static function updateGift($gift)
    {
        //更新
        $balance = Balance::find($gift->target_accountid);
        if (!empty($balance)) {
            $data = [
                'gift' => $balance->gift + $gift->amount
            ];
            $balance_gift = $balance->balance + $balance->gift;
            $balanceResult = Balance::where('account_id', $gift->target_accountid)->update($data);
        } else {
            $data = [
                'account_id' => $gift->target_accountid,
                'balance' => 0,
                'gift' => $gift->amount
            ];
            $balance_gift = 0;
            $balanceResult = Balance::create($data);
        }
        
        if ($balanceResult) {
            $agencyId = Auth::user()->agencyid;
            $accountId = Auth::user()->account->account_id;
        
            //给操作的账户也加上赠送金
            $save_agency = DB::update("INSERT INTO up_balances(account_id, gift) VALUES(?,?) ON
                            DUPLICATE KEY UPDATE gift = gift + ?;", [$accountId, $gift->amount, $gift->amount]);
        
            $logData = [
                'media_id' => $agencyId,
                'operator_accountid' => $gift->account_id,
                'operator_userid' => $gift->user_id,
                'target_acountid' => $gift->target_accountid,
                'pay_type' => BalanceLog::PAY_TYPE_PRESENT_GOLD,
                'amount' => $gift->amount,
                'balance' => $balance_gift,
                'balance_type' => BalanceLog::BALANCE_TYPE_GIFT_PROMOTION,
                'comment' => $gift->gift_info,
                'create_time' => date("Y-m-d H:i:s")
            ];
            $logResult = BalanceLog::create($logData);
            if ($logResult) {
                //启动因余额不足而被暂停的广告
                self::restartCampaign($gift->target_accountid, $gift->amount, 6003);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     * @param $accountId
     * @param $balance
     * @param $code
     * @return bool
     *
     * 启动因余额不足被暂停的广告
     */
    public static function restartCampaign($accountId, $balance, $code)
    {
        //以上账户更新成功
        $accountInfo = Account::find($accountId);
        if (Account::TYPE_BROKER == $accountInfo->account_type) {
            return true;
        }

        //判断账户余额；
        $balanceObj = $accountInfo->balance;
        $balanceTotal = ($balanceObj->balance + $balanceObj->gift);
        $balanceTotals = intval($balanceTotal);

        //重启因广告主余额不足暂停的广告计划；
        if (($balanceTotals > 0)) {
            $clientId = $accountInfo->client->clientid;
            $campaignIds = CampaignService::recoverActive(Campaign::PAUSE_STATUS_BALANCE_NOT_ENOUGH, $clientId, false);
            if (!empty($campaignIds)) {
                foreach ($campaignIds as $k => $campaignId) {
                    self::saveCampaignLog(
                        [
                            'clientid' => $clientId,
                            'balance' => $balance,
                            'code' => $code,
                            'campaignId' => $campaignId
                        ]
                    );
                }
            }
            return true;
        }
    }
    
    
    /**
     * 返回一条符合要求的记录
     * @param integer $id
     * @return array
     */
    public static function getOneRow($id)
    {
        $row = Recharge::find($id);
        return $row;
    }
    
    
    /**
     *
     * @param integer $id
     * @param string $field
     * @param number $affiliateid
     * @return boolean
     */
    public static function rechargeApproved($id, $field, $affiliateid = 0)
    {
        return DB::transaction(function () use ($id, $field, $affiliateid) {
            $row = Recharge::find($id);
            if (empty($row)) {
                return false;
            }
            //判断是否是当前操作的平台账户信息
            if ($row->agencyid != Auth::user()->agencyid) {
                return false;
            }
            
            $accountId = $row->target_accountid;
            $agencyId = $row->agencyid;
            $balance = $row->amount;
            if ($balance < 0) {
                return false;
            }
            $prefix = DB::getTablePrefix();
            //检查欲加推广金的广告主代理商账户是否是自己的，不是则不能增加--housead
            if (0 < $affiliateid) {
                $checkRow = Recharge::leftJoin(
                    DB::raw("(SELECT  affiliateid, account_id FROM up_clients
                    UNION
                    SELECT affiliateid,account_id FROM up_brokers)
                     AS {$prefix}c"),
                    'recharge.target_accountid',
                    '=',
                    'c.account_id'
                )
                ->where('c.affiliateid', $affiliateid)
                ->where('recharge.id', $id)
                ->first();
                if (empty($checkRow)) {
                    return false;
                }
            }
        
            DB::beginTransaction();
            $row->$field = Recharge::STATUS_APPROVED;
            if ($row->save()) {
                $ret = self::updateBalance($accountId, $balance, $agencyId);
                if ($ret === false) {
                    DB::rollBack();
                    return false;
                }
                DB::commit();
                return true;
            } else {
                DB::rollBack();
                return false;
            }
        });
    }

    /**
     * 驳回充值申请
     * @param $id
     * @param $content
     * @return int
     */
    public static function saveCharge($id, $content)
    {
        if (empty($content)) {
            return 5300;
        }
        //更新状态
        $save = DB::table("recharge")
            ->where("id", $id)
            ->update(
                ["status" => 3, "comment" => $content]
            );
        if (!$save) {
            return 5001;
        }

        return true;
    }

    private static function saveCampaignLog($params)
    {
        $clientid = $params['clientid'];
        $balance = $params['balance'];
        $code = $params['code'];
        $campaignId = $params['campaignId'];
        //写一条审计日志
        $clientRow = CampaignService::getClientInfoByClientID($clientid);
        $clientName = !empty($clientRow) ? $clientRow['clientname'] : '';
        $message = CampaignService::formatWaring(
            $code,
            [
                $clientName,
                sprintf("%.2f", $balance)
            ]
        );
        OperationLog::store([
            'category' => OperationLog::CATEGORY_CAMPAIGN,
            'type' => OperationLog::TYPE_SYSTEM,
            'operator' => Config::get('error')[6000],
            'target_id' => $campaignId,
            'message' => $message,
        ]);
    }
}
