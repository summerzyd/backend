<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpAppinfosAddApplicationId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('up_appinfos', function(Blueprint $table)
		{
			$sql = <<<SQL
Alter table `up_appinfos` add `application_id` varchar(64) DEFAULT '';
UPDATE up_appinfos, up_campaigns, up_products set up_appinfos.application_id = up_products.application_id where up_appinfos.app_id=up_campaigns.campaignname and up_campaigns.product_id=up_products.id and up_products.application_id <> '';
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
		Schema::table('up_appinfos', function(Blueprint $table)
		{
			$table->dropColumn('application_id');
		});
	}

}
