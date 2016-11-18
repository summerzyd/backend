<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpClientsAddOperationUid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = "ALTER TABLE up_clients ADD COLUMN operation_uid int DEFAULT 0 AFTER creator_uid;";
        DB::getPdo()->exec($sql);
        $sql = "ALTER TABLE up_brokers ADD COLUMN operation_uid int DEFAULT 0 AFTER creator_uid;";
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
