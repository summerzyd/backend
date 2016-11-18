<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BannerCategoryMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = "DROP TABLE IF EXISTS up_banner_category_map ";
        DB::statement($sql);

        $sql = "CREATE TABLE `up_banner_category_map` (
                    `id`  mediumint(9) UNSIGNED NOT NULL AUTO_INCREMENT ,
                    `banner_id`  mediumint(9) NULL ,
                    `category_id`  varchar(255) NULL ,
                    `category_name`  varchar(255) NULL ,
                    `parent_category_id`  varchar(255) NULL ,
                    `parent_category_name`  varchar(255) NULL ,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uni_banner_id` (`banner_id`)
                )";
        DB::getPdo()->exec($sql);

        // 旧数据生成
        $sql = "select bannerid,category from up_banners where category != ''";
        $list = DB::select($sql);

        foreach ($list as $key => $value) {
            $bannerId = $value->bannerid;
            $categoryId = $value->category;

            // 没有分类跳过执行
            if (empty($categoryId)) {
                continue;
            }

            if (!empty($value->category)) {
                $sql = "select GROUP_CONCAT(`name`) as c_name,GROUP_CONCAT(distinct parent) as p_cid from up_category
                    where category_id in ($value->category)
                    order by field(category_id, $value->category)";
                $category = DB::select($sql);
            } else {
                continue;
            }

            if ($category) {
                $categoryName = $category[0]->c_name;
                $parentCategoryId = $category[0]->p_cid;

                if (empty($parentCategoryId)) {
                    $parentCategoryName = '';
                } else {
                    $sql = "select GROUP_CONCAT(DISTINCT `name`) as p_name from up_category
                            where category_id in ($parentCategoryId)
                            order by field(category_id, $parentCategoryId)";
                    $pcategory = DB::select($sql);
                    $parentCategoryName = $pcategory[0]->p_name;
                }
            } // 没有数据跳过执行
            else {
                continue;
            }

            $data[] = [
                'banner_id' => $bannerId,
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'parent_category_id' => $parentCategoryId,
                'parent_category_name' => $parentCategoryName
            ];
        }

        if (!empty($data)) {
            $map_data = [];
            $sql_map = "insert into up_banner_category_map (banner_id, category_id, category_name, parent_category_id, parent_category_name) values ";
            foreach ($data as $key => $value) {
                $map_data[] = "('" . implode("','", $value) . "')";
                if ($key % 500 == 0 && $key > 0) {
                    $sql_insert = $sql_map . implode(',', $map_data) . '';
                    DB::statement($sql_insert);
                    unset($map_data);
                }
            }

            if (!empty($map_data)) {
                $sql_map = "insert into up_banner_category_map (banner_id, category_id, category_name, parent_category_id, parent_category_name) values ";
                $sql_map .= implode(',', $map_data) . ';';
                DB::statement($sql_map);
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
