<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Qiniu\json_decode;

class AddAdTypeSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $rows = DB::table('agency')
                ->select('agencyid')
                ->get();
        if (!empty($rows)) {
            $value = [
                1,2,3
            ];
            foreach ($rows as $k=>$v) {
                DB::table('setting')
                    ->insert([
                        'id' => "{$v->agencyid}0102",
                        'agencyid' => $v->agencyid,
                        'parent_id' => "{$v->agencyid}01",
                        'code' => 'ad_list',
                        'type' => 'json',
                        'value' => json_encode($value),
                    ]);
            }
        }
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
