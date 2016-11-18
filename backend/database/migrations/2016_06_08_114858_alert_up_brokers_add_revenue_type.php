<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertUpBrokersAddRevenueType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('brokers', function (Blueprint $table) {
            $table->smallInteger('revenue_type')->default(3);
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
        Schema::table('brokers', function (Blueprint $table) {
            $table->dropColumn('revenue_type');
        });
    }
}
