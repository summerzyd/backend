<?php
namespace App\Console\Commands;

use App\Components\Helper\LogHelper;
use App\Models\DeliveryLog;
use App\Models\ExpenseLog;
use Illuminate\Support\Facades\DB;

class TempRepairDeliveryLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * 2016/09/28修改搜狗数据
     * @var string
     */
    protected $signature = 'temp_repair_delivery_log';

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
        $sql = '';
        $insert = [
            'campaignid' => '1514',
            'zoneid' => '126',
            'cb' => rand(10000000, 99999999),
            'price' => '2',
            'price_gift' => '0',
            'actiontime' => '2016-10-19 00:00:00',
            'af_income' => '1.2',
            'source_log_type' => 'down',
        ];
        $fields = array_keys($insert);
        foreach ($insert as $k => $v) {
            $values[] = "'{$v}'";
        }
        for ($i=0; $i < 100; $i++) {
            $sql.= 'INSERT INTO up_delivery_log('
                . implode(',', $fields)
                . ') VALUES('
                . implode(',', $values)
                . ');' ."\n";
        }
        LogHelper::info($sql);

    }
}


