<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpClientsAddBrokerId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('up_users', function(Blueprint $table)
		{
			$sql = <<<SQL
Alter table `up_clients` add broker_id mediumint(9) NOT NULL DEFAULT '0' AFTER `agencyid`;
Alter table `up_clients` add brief_name varchar(255) DEFAULT '' AFTER `clientname`;
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
		Schema::table('clients', function(Blueprint $table)
		{
			$table->dropColumn('broker_id');
			$table->dropColumn('brief_name');
		});
	}

}