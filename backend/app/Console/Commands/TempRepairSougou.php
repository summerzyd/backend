<?php
namespace App\Console\Commands;

use App\Components\Helper\LogHelper;
use App\Models\DeliveryLog;
use App\Models\ExpenseLog;
use Illuminate\Support\Facades\DB;

class TempRepairSougou extends Command
{
    /**
     * The name and signature of the console command.
     *
     * 2016/09/28修改搜狗数据
     * @var string
     */
    protected $signature = 'temp_repair_sougou';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description repair sougou data';

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
        //猎鹰浏览器 13号
        for ($i=0; $i < 269; $i++) {
            $model = new  ExpenseLog();
            $model->campaignid = 1415;
            $model->price = 1;
            $model->price_gift = 0;
            $model->actiontime = '2016-10-13 00:00:00';
            $model->af_income = 0.8;
            $model->zoneid = 783;
            $model->source =  'down';
            $model->cb =  rand(10000000, 99999999);
            $ret = $model->save();
            if (!$ret) {
                LogHelper::error("Update campaign :1415");
            }
            $deliveryLog = new  DeliveryLog();
            $deliveryLog->campaignid = 1415;
            $deliveryLog->price = 1;
            $deliveryLog->actiontime = '2016-10-13 00:00:00';
            $deliveryLog->af_income = 0.8;
            $deliveryLog->zoneid = 783;
            $deliveryLog->source =  'down';
            $deliveryLog->cb =  rand(10000000, 99999999);
            $ret = $deliveryLog->save();
            if (!$ret) {
                LogHelper::error("Update campaign :1415");
            }
            $this->updateBalance(1415, 1);
        }
        //欢乐书库 13号
        for ($i=0; $i < 39; $i++) {
            $model = new  ExpenseLog();
            $model->campaignid = 1602;
            $model->price = 1.8;
            $model->price_gift = 0;
            $model->actiontime = '2016-10-13 00:00:00';
            $model->af_income = 1.2;
            $model->zoneid = 783;
            $model->source =  'down';
            $model->cb =  rand(10000000, 99999999);
            $ret = $model->save();
            if (!$ret) {
                LogHelper::error("Update campaign :1602");
            }
            $deliveryLog = new  DeliveryLog();
            $deliveryLog->campaignid = 1602;
            $deliveryLog->price = 1.8;
            $deliveryLog->actiontime = '2016-10-13 00:00:00';
            $deliveryLog->af_income = 0.8;
            $deliveryLog->zoneid = 783;
            $deliveryLog->source =  'down';
            $deliveryLog->cb =  rand(10000000, 99999999);
            $ret = $deliveryLog->save();
            if (!$ret) {
                LogHelper::error("Update campaign :1602");
            }
            $this->updateBalance(1602, 1.8);
        }
        //乐享动 13号
        for ($i=0; $i < 23; $i++) {
            $model = new  ExpenseLog();
            $model->campaignid = 298;
            $model->price = 2.5;
            $model->price_gift = 0;
            $model->actiontime = '2016-10-13 00:00:00';
            $model->af_income = 1;
            $model->zoneid = 783;
            $model->source =  'down';
            $model->cb =  rand(10000000, 99999999);
            $ret = $model->save();
            if (!$ret) {
                LogHelper::error("Update campaign :298");
            }
            $deliveryLog = new  DeliveryLog();
            $deliveryLog->campaignid = 298;
            $deliveryLog->price = 2.5;
            $deliveryLog->actiontime = '2016-10-13 00:00:00';
            $deliveryLog->af_income = 1;
            $deliveryLog->zoneid = 783;
            $deliveryLog->source =  'down';
            $deliveryLog->cb =  rand(10000000, 99999999);
            $ret = $deliveryLog->save();
            if (!$ret) {
                LogHelper::error("Update campaign :298");
            }
            $this->updateBalance(298, 2.5);
        }
        //宜人理财 13号
        for ($i=0; $i < 50; $i++) {
            $model = new  ExpenseLog();
            $model->campaignid = 585;
            $model->price = 6;
            $model->price_gift = 0;
            $model->actiontime = '2016-10-13 00:00:00';
            $model->af_income = 1.2;
            $model->zoneid = 783;
            $model->source =  'down';
            $model->cb =  rand(10000000, 99999999);
            $ret = $model->save();
            if (!$ret) {
                LogHelper::error("Update campaign :585");
            }
            $deliveryLog = new  DeliveryLog();
            $deliveryLog->campaignid = 585;
            $deliveryLog->price = 6;
            $deliveryLog->actiontime = '2016-10-13 00:00:00';
            $deliveryLog->af_income = 1.2;
            $deliveryLog->zoneid = 783;
            $deliveryLog->source =  'down';
            $deliveryLog->cb =  rand(10000000, 99999999);
            $ret = $deliveryLog->save();
            if (!$ret) {
                LogHelper::error("Update campaign :585");
            }
            $this->updateBalance(585, 6);
        }
        //零钱夺宝 13号
        for ($i=0; $i < 104; $i++) {
            $model = new  ExpenseLog();
            $model->campaignid = 196;
            $model->price = 2;
            $model->price_gift = 0;
            $model->actiontime = '2016-10-13 00:00:00';
            $model->af_income = 1.4;
            $model->zoneid = 783;
            $model->source =  'down';
            $model->cb =  rand(10000000, 99999999);
            $ret = $model->save();
            if (!$ret) {
                LogHelper::error("Update campaign :196");
            }
            $deliveryLog = new  DeliveryLog();
            $deliveryLog->campaignid = 196;
            $deliveryLog->price = 2;
            $deliveryLog->actiontime = '2016-10-13 00:00:00';
            $deliveryLog->af_income = 1.4;
            $deliveryLog->zoneid = 783;
            $deliveryLog->source =  'down';
            $deliveryLog->cb =  rand(10000000, 99999999);
            $ret = $deliveryLog->save();
            if (!$ret) {
                LogHelper::error("Update campaign :196");
            }
            $this->updateBalance(196, 2);
        }
        //平安好医生
        for ($i=0; $i < 15; $i++) {
            $model = new  ExpenseLog();
            $model->campaignid = 1505;
            $model->price = 1.2;
            $model->actiontime = '2016-10-13 00:00:00';
            $model->af_income = 0.8;
            $model->zoneid = 783;
            $model->source =  'down';
            $model->cb =  rand(10000000, 99999999);
            $ret = $model->save();
            if (!$ret) {
                LogHelper::error("Update campaign :1505");
            }
            $deliveryLog = new  DeliveryLog();
            $deliveryLog->campaignid = 1505;
            $deliveryLog->price = 1.2;
            $deliveryLog->actiontime = '2016-10-13 00:00:00';
            $deliveryLog->af_income = 0.8;
            $deliveryLog->zoneid = 783;
            $deliveryLog->source =  'down';
            $deliveryLog->cb =  rand(10000000, 99999999);
            $ret = $deliveryLog->save();
            if (!$ret) {
                LogHelper::error("Update campaign :1505");
            }
            $this->updateBalance(1505, 1.2);
        }
        //东方头条
        for ($i=0; $i < 101; $i++) {
            $model = new  ExpenseLog();
            $model->campaignid = 1450;
            $model->price = 1;
            $model->actiontime = '2016-10-13 00:00:00';
            $model->af_income = 0.8;
            $model->zoneid = 783;
            $model->source =  'down';
            $model->cb =  rand(10000000, 99999999);
            $ret = $model->save();
            if (!$ret) {
                LogHelper::error("Update campaign :1505");
            }
            $deliveryLog = new  DeliveryLog();
            $deliveryLog->campaignid = 1450;
            $deliveryLog->price = 1;
            $deliveryLog->actiontime = '2016-10-13 00:00:00';
            $deliveryLog->af_income = 0.8;
            $deliveryLog->zoneid = 783;
            $deliveryLog->source =  'down';
            $deliveryLog->cb =  rand(10000000, 99999999);
            $ret = $deliveryLog->save();
            if (!$ret) {
                LogHelper::error("Update campaign :1450");
            }
            $this->updateBalance(1450, 1);
        }

        $sql = "DELETE from up_data_hourly_daily_client where campaign_id in 
        (1415,1602,298,585,1505,1450,196) and date = '2016-10-13' and zone_id = 783";
        $update = DB::update($sql);
        if (!$update) {
            $this->notice('delete  client '. $sql);
        }
    }
    private function updateBalance($campaignid, $balance)
    {
        $sql = <<<SQL
                                    UPDATE up_balances
                                    SET
                                        balance = balance-{$balance}
                                    WHERE
                                        account_id=(
                                            SELECT account_id
                                            FROM up_campaigns c
                                            JOIN `up_clients` cli ON (c.clientid = cli.clientid)
                                            WHERE campaignid = '{$campaignid}'
                                        )
SQL;
        $query = DB::statement($sql);
    }
}
