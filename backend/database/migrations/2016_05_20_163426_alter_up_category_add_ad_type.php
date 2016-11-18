<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpCategoryAddAdType extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = "Alter table `up_category` add `ad_type` INTEGER DEFAULT 0;";
        DB::getPdo()->exec($sql);
        $sql = "Alter table `up_category` drop index `up_category_media_id_affiliateid_platform_parent_name_unique`;";
        DB::getPdo()->exec($sql);
        $sql = "Alter table `up_category` add unique index `up_category_media_id_affiliateid_platform_parent_name_unique`(`media_id`,`affiliateid`,`platform`,`parent`,`name`,`ad_type`);";
        DB::getPdo()->exec($sql);
        $sql = "Alter table `up_zone_list_type` add `ad_type` INTEGER DEFAULT 0;";
        DB::getPdo()->exec($sql);
        $sql = "Alter table `up_zone_list_type` drop index `up_zone_list_type_af_id_listtypeid_unique`;";
        DB::getPdo()->exec($sql);
        $sql = "Alter table `up_zone_list_type` add unique index `up_zone_list_type_af_id_listtypeid_unique`(`af_id`,`listtypeid`,`ad_type`);";
        DB::getPdo()->exec($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('up_category', function(Blueprint $table)
        {
            $table->dropColumn('ad_type');
        });
    }

}
