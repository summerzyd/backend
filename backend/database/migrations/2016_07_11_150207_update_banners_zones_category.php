<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateBannersZonesCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 更新banner分类信息
        $sql = <<<SQL
        UPDATE up_banners b
INNER JOIN up_category c on c.category_id=b.category AND b.affiliateid = c.affiliateid
LEFT JOIN up_category c1 ON c.`name`=c1.`name` AND c.ad_type=c1.ad_type AND c1.platform=1 AND c.affiliateid = c1.affiliateid
SET b.category = c1.category_id
WHERE b.category != 0;
SQL;
        DB::getPdo()->exec($sql);

        //更新zone分类信息
        $sql = <<<SQL
        UPDATE up_zones z
INNER JOIN up_category c on c.category_id=z.oac_category_id AND z.affiliateid = c.affiliateid
LEFT JOIN up_category c1 ON c.`name`=c1.`name` AND c.ad_type=c1.ad_type AND c1.platform=1 AND c.affiliateid = c1.affiliateid

SET z.oac_category_id = c1.category_id
WHERE z.oac_category_id != 0;
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
