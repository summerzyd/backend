<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpUsersAddAccountSubTypeId extends Migration {

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
ALTER TABLE `up_users` ADD `account_sub_type_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `user_role`;
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
			$table->dropColumn('account_sub_type_id');
		});
	}

}