<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpMonitorBalance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = "CREATE TABLE `up_monitor_balance` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `account_id` int(10) NOT NULL,
              `actiontime` datetime NOT NULL,
              `balance` decimal(10,2) NOT NULL DEFAULT '0.00',
              `last_balance` decimal(10,2) NOT NULL,
              `differ_balance` decimal(10,2) NOT NULL,
              `gift` decimal(10,2) NOT NULL,
              `last_gift` decimal(10,2) NOT NULL,
              `differ_gift` decimal(10,2) NOT NULL,
              `created_time` datetime DEFAULT NULL,
              `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `account_id` (`account_id`,`actiontime`)
            ) ENGINE=InnoDB AUTO_INCREMENT=1345 DEFAULT CHARSET=utf8;";
        DB::getPdo()->exec($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
