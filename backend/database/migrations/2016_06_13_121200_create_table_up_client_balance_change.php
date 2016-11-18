<?php

use Illuminate\Database\Migrations\Migration;

class CreateTableUpClientBalanceChange extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        CREATE TABLE `up_client_balance_change` (
         `id` int(10) NOT NULL AUTO_INCREMENT,
          `account_id` mediumint(9) NOT NULL,
          `clientname` varchar(255) NOT NULL DEFAULT '',
          `charge` decimal(12,2) NOT NULL DEFAULT '0.00',
          `cost`  decimal(10,4) DEFAULT '0.0000',
          `balance`  decimal(10,4) DEFAULT '0.0000',
          `true_balance`  decimal(10,4) DEFAULT '0.0000',
          `sub` decimal(10,4) DEFAULT '0.0000',
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
        //
    }
}
