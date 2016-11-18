<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpSettingData extends Migration
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
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20312', '2', '203', 'mail_invalid_appstorelink', 'text', '', '', 'wanglinfeng@biddingos.com', '50', '0000-00-00 00:00:00', '2016-09-20 17:13:14'); 
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20313', '2', '203', 'mail_package_notice_afid', 'text', '', '', '102', '50', '0000-00-00 00:00:00', '2016-09-22 17:16:14'); 
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
