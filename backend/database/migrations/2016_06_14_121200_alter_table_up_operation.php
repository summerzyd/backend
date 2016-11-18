<?php

use Illuminate\Database\Migrations\Migration;

class AlterTableUpOperation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
        INSERT INTO `up_operations` VALUES ('31104', 'advertiser-accout', '账号管理', 'ADVERTISER');

        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('14420', 'manager-profit', '数据-毛利', 'MANAGER');
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('14421', 'manager-profit_rate', '数据-毛利率', 'MANAGER');
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('14418', 'manager-sum_revenue_client', '数据-广告主消耗（总数）', 'MANAGER');
        INSERT INTO `up_operations`(`id`,`name`,`description`,`account_type`) VALUES ('14419', 'manager-sum_payment_trafficker', '数据-媒体支出（总数）', 'MANAGER');
        UPDATE up_roles set operation_list = CONCAT(operation_list, ',manager-sum_revenue_client') where find_in_set('manager-sum_revenue', operation_list);
        UPDATE up_roles set operation_list = CONCAT(operation_list, ',manager-sum_revenue_client') where find_in_set('manager-sum_revenue_gift', operation_list);
        UPDATE up_roles set operation_list = CONCAT(operation_list, ',manager-sum_payment_trafficker') where find_in_set('manager-sum_payment', operation_list);
        UPDATE up_roles set operation_list = CONCAT(operation_list, ',manager-sum_payment_trafficker') where find_in_set('manager-sum_payment_gift', operation_list);
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
