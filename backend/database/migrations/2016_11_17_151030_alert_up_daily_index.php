<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use App\Components\Helper\LogHelper;

class AlertUpDailyIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //

        $dataBase = Config::get('database.connections.mysql.database');
        $sql = "SELECT table_name
          FROM  information_schema.tables
          WHERE table_schema= '{$dataBase}'
          AND table_type='base table'
          AND TABLE_name LIKE 'up_data_hourly_daily_2%'";
        $res = DB::select($sql);
        foreach ($res as $val) {
            $sql ="
ALTER TABLE {$val->table_name} DROP INDEX up_data_hourly_daily_date_ad_id_zone_id_unique;
CREATE unique INDEX up_data_hourly_daily_date_ad_id_zone_id_unique ON {$val->table_name} (`date`,`campaign_id`,`ad_id`,`zone_id`, `affiliateid`);
";
            $sql = <<<SQL
$sql
SQL;
            DB::getPdo()->exec($sql);

        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
