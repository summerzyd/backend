<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

class AlterUpClientsIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
    drop index up_clients_agencyid_clientname_unique on up_clients;
    drop index up_clients_agencyid_email_unique on up_clients;
    drop index up_users_email_address_unique on up_users;

    ALTER TABLE up_clients ADD UNIQUE KEY `up_clients_agencyid_clientname_broker_affiliate_unique` (`agencyid`,`clientname`, `broker_id`, `affiliateid`);
    ALTER TABLE up_clients ADD UNIQUE KEY `up_clients_agencyid_email_broker_affiliate_unique` (`agencyid`,`email`, `broker_id`, `affiliateid`);
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
