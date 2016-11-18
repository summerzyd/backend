<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpDailyAddType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('daily', function (Blueprint $table) {
            $table->tinyInteger('type')->default(1)->after('receiver');
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
        Schema::table('daily', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
