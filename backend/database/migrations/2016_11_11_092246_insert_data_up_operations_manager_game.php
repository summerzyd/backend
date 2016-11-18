<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpOperationsManagerGame extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        UPDATE up_operations set id = id - 1000 WHERE id < 20000 AND id > 19000;
        INSERT INTO `up_operations`(id, `name`, `description`, account_type) VALUES ('19101', 'manager-game', '游戏', 'MANAGER');
        INSERT INTO `up_operations`(id, `name`, `description`, account_type) VALUES ('19103', 'manager-game-stat', '统计报表', 'MANAGER');
        INSERT INTO `up_operations`(id, `name`, `description`, account_type) VALUES ('19105', 'manager-game-input', '游戏录数', 'MANAGER');
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
