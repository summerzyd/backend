<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpUsers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function(Blueprint $table)
		{
			$sql = <<<SQL
ALTER TABLE `up_users` ADD `role_id` INT(11) NOT NULL DEFAULT '0' AFTER `user_role`;
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
		Schema::table('users', function(Blueprint $table)
		{
			$table->dropColumn('role_id');
		});
	}

}