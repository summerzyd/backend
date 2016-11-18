<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpAppinfosAddOldMaterialsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('appinfos', function (Blueprint $table) {
            $table->text('old_materials_data')->nullable()->after('materials_data');
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
        Schema::table('appinfos', function (Blueprint $table) {
            $table->dropColumn('old_materials_data');
        });
    }
}
