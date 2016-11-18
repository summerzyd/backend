<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDataUpOperationsManager extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $permission = [
            14401 => 'sum_views', // 展示量
            14402 => 'sum_download_requests', // 下载请求
            14403 => 'sum_clicks', // 下载量(上报)
            14404 => 'ctr', // 下载转化率
            14407 => 'cpd', // 平均单价(广告主)
            14408 => 'media_cpd', // 平均单价(媒体商)
            14409 => 'ecpm', // eCPM
            14410 => 'sum_cpa', // 转化量
            14411 => 'sum_consum', // 广告主消耗,
            14412 => 'sum_cpc_clicks', // 点击量
            14413 => 'cpc_ctr', // 点击转化率
            14454 => 'sum_download_complete', // 数据-下载完成(监控)
            14414 => 'sum_revenue', // 广告主消耗（充值金）
            14415 => 'sum_revenue_gift', // 广告主消耗（赠送金）
            14416 => 'sum_payment', // 媒体支出（充值金）
            14417 => 'sum_payment_gift' // 媒体支出（赠送金）
        ];
        $change = [];
        
        foreach ($permission as $id => $name){
            $op = DB::table('operations')->where('id', $id)->select('name')->first();
            $change[$op->name] = "manager-{$name}";
        }
        
        $roles = DB::table('roles')->select('id','operation_list')->get();
        foreach ($roles as $role) {
            $operationList = $this->replaceRoles($role->operation_list, $change);
            DB::table('roles')->where('id', $role->id)->update(['operation_list' => $operationList]);
        }
        foreach ($permission as $k => $v) {
            DB::table('operations')->where('id', $k)->update(['name' => "manager-{$v}"]);
        }
    }
    
    private function replaceRoles($str, $change){
        foreach ($change as $old => $new) {
            $str = str_replace($old, $new, $str);
        }
        return $str;
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
