<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TruncateClickLog extends Migration
{
    /**
     * Run the migrations.
     * 清空click_log 数据
     * @return void
     */
    public function up()
    {
        // 保留原来的数据
        $sql = "alter table up_click_log rename up_click_log_history";
        DB::getPdo()->exec($sql);

        //
        $sql = "create table up_click_log like up_click_log_history";
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
