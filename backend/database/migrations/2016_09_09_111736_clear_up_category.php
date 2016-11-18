<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ClearUpCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //删除掉平台大于1，类型为0的数据
        $date = date('Y-m-d H:i:s');
        $sql = "DELETE FROM up_category WHERE 1 AND platform > 1 AND ad_type = 0;
                UPDATE up_category SET category_id = 3 WHERE 1 AND category_id = 1;
                INSERT INTO up_category (category_id, name, media_id, parent, created_at, updated_at, platform, affiliateid, ad_type)
                VALUES (1, '应用', 0, 0,'{$date}', '{$date}', 1, 0, 0), (2, '游戏', 0, 0, '{$date}', '{$date}', 1, 0, 0);
                UPDATE up_banners SET category = 3 WHERE 1 AND affiliateid = 1 AND category = 1;
            ";
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
