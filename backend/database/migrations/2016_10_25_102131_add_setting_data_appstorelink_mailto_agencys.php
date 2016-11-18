<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSettingDataAppstorelinkMailtoAgencys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = <<<SQL
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10312', '1', '103', 'mail_invalid_appstorelink', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-10-25 10:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('30312', '3', '303', 'mail_invalid_appstorelink', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-10-25 10:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('40312', '4', '403', 'mail_invalid_appstorelink', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-10-25 10:13:14'); 
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
