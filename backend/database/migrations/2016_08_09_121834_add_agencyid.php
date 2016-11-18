<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAgencyid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        ALTER TABLE `up_accounts` ADD `agencyid` mediumint(9) NOT NULL DEFAULT '0' AFTER `account_id`;
        ALTER TABLE `up_accounts` ADD index up_accounts_agencyid(agencyid);
        UPDATE `up_accounts` SET agencyid = 2 where agencyid = 0;
        UPDATE `up_accounts` SET agencyid = 1 where account_id = 1;

        ALTER TABLE `up_users` ADD `agencyid` mediumint(9) NOT NULL DEFAULT '0' AFTER `user_id`;
        ALTER TABLE `up_users` ADD index up_users_agencyid(agencyid);
        UPDATE `up_users` SET agencyid = 2 where agencyid = 0;
        UPDATE `up_users` SET agencyid = 1 where user_id = 1;

        ALTER TABLE up_daily ADD `agencyid` mediumint(9) NOT NULL DEFAULT '0' AFTER `id`;
        ALTER TABLE up_daily ADD index up_daily_agencyid(agencyid);
        UPDATE up_daily SET agencyid = 2 where agencyid = 0;
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
