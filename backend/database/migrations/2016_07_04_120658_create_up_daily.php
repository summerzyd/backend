<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpDaily extends Migration
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
        CREATE TABLE `up_daily` (
         `id` int(10) NOT NULL AUTO_INCREMENT,
          `date` date NOT NULL,
          `status` tinyint NOT NULL DEFAULT 1,
          `send_time` datetime NULL ,
          `receiver` text NULL,
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
