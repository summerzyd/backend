<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpAffiliatesUserReport extends Migration
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
        CREATE TABLE `up_affiliates_user_report` (
            `id` INT (10) NOT NULL AUTO_INCREMENT,
            `affiliateid` MEDIUMINT (9) NOT NULL,
            `date` date NOT NULL,
            `type` INT (10) NOT NULL,
            `num` INT (10) NOT NULL DEFAULT '0',
            `span` INT (10) NOT NULL,
            `created_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
            `updated_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `up_affiliates_user_report_date_type` (`date`, `type`) USING BTREE,
            KEY `up_affiliates_user_report` (`affiliateid`)
        ) ENGINE = INNODB AUTO_INCREMENT = 1 DEFAULT CHARSET = utf8;
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
    }
}
