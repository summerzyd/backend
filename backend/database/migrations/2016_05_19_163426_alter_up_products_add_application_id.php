<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpProductsAddApplicationId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('up_products', function(Blueprint $table)
		{
			$sql = <<<SQL
Alter table `up_products` add `application_id` varchar(64) DEFAULT '';
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
		Schema::table('up_products', function(Blueprint $table)
		{
			$table->dropColumn('application_id');
		});
	}

}
