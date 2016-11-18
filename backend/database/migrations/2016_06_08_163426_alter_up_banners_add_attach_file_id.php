<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpBannersAddAttachFileId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('up_banners', function(Blueprint $table)
		{
			$sql = <<<SQL
Alter table `up_banners` add `attach_file_id`  int(11) NOT NULL DEFAULT '0' AFTER `package_file_id`;
UPDATE up_banners, up_package_files set up_banners.attach_file_id = up_package_files.attach_id where up_banners.package_file_id=up_package_files.id;
SQL;
			DB::connection()->getPdo()->exec($sql);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('up_banners', function(Blueprint $table)
		{
			$table->dropColumn('attach_file_id');
		});
	}

}
