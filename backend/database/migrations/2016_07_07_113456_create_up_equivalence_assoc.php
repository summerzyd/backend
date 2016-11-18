<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpEquivalenceAssoc extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        CREATE TABLE `up_equivalence_assoc` (
         `id` int(10) NOT NULL AUTO_INCREMENT,
          `equivalence` char(32) NOT NULL,
          `package_name` text NOT NULL,
          `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
          `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`,`equivalence`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
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
