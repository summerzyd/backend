<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = <<<SQL
        CREATE TABLE `up_setting` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `agencyid` mediumint(9) NOT NULL DEFAULT 0,
            `parent_id` int(11) NOT NULL DEFAULT 0,
            `code` VARCHAR(32) NOT NULL,
            `type` VARCHAR(32) NOT NULL,
            `store_range` VARCHAR(255) NOT NULL DEFAULT '',
            `store_dir` VARCHAR(255) NOT NULL DEFAULT '',
            `value` TEXT,
            `sort_order` int(10) NOT NULL DEFAULT 50,
            `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
            `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
SQL;
        DB::getPdo()->exec($sql);
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
