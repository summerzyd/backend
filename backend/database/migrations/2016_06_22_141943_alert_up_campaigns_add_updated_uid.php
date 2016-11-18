<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertUpCampaignsAddUpdatedUid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('campaigns', function (Blueprint $table) {
            $table->integer('updated_uid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('updated_uid');
        });
    }
}
