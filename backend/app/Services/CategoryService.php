<?php
/**
 * Created by PhpStorm.
 * User: Arke
 * Date: 2016/8/30
 * Time: 18:40
 */
namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Category;
use Auth;
use JMS\Serializer\Tests\Fixtures\Publisher;
use Qiniu\json_decode;
use App\Models\Zone;

class CategoryService
{

    public static function getCategoryList($category, $affiliateId)
    {
        if (!is_array($category)) {
            $category = explode(",", $category);
        }
        \DB::setFetchMode(\PDO::FETCH_CLASS);

        $row = DB::table('category')
            ->whereIn('category_id', $category)
            ->where('affiliateid', $affiliateId)
            ->select('category_id', 'name', 'parent', 'platform', 'affiliateid', 'ad_type')
            ->get();

        return $row;
    }
    
    public static function getCategories($category, $affiliateId)
    {
        if (!empty($category) || 0 != $category) {
            if (!is_array($category)) {
                $category = explode(",", $category);
            }
            
            //要显示的分类数据
            $categoryData = [];
            //要排除的分类数据
            $diffData = [];
            $parent = [];
            //用父分类去查找广告位中是否存在父分类
            foreach ([Category::PARENT_APP, Category::PARENT_GAME] as $k => $v) {
                if (in_array($v, $category)) {
                    //如果有父分类，就只显示父分类
                    $categoryData[] = Category::getParentLabels()[$v];
                    $diffData[] = $v;
                    $parent[] = $v;
                    //所有二级分类及三给分类都不显示
                    $secondArr = self::getSubCategory($v, $affiliateId);
                    if (!empty($secondArr)) {
                        foreach ($secondArr as $kk => $vv) {
                            //二级分类的ID
                            $diffData[] = $vv->category_id;
                            //三给分类
                            $thirdArr = self::getSubCategory($vv->category_id, $affiliateId);
                            if (!empty($thirdArr)) {
                                foreach ($thirdArr as $tk => $tv) {
                                    $diffData[] = $tv->category_id;
                                }
                            }
                        }
                    }
                } else {
                    //查找此父分类下的所有二级分类（从category表中获取）
                    $subRow = self::getSubCategory($v, $affiliateId);
                    if (!empty($subRow)) {
                        $parent[] = $v;
                        //有二级分类
                        foreach ($subRow as $sk => $sv) {
                            //存在此才级分类，那么直接显示此二级分类
                            if (in_array($sv->category_id, $category)) {
                                $secondData = self::getCateogryDetail($sv->category_id);
                                $categoryData[] = $secondData->name;
                                $diffData[] = $sv->category_id;
                                //其下所有三级分类都不显示
                                $thirdArr = self::getSubCategory($sv->category_id, $affiliateId);
                                if (!empty($thirdArr)) {
                                    foreach ($thirdArr as $tk => $tv) {
                                        $diffData[] = $tv->category_id;
                                    }
                                }
                            }
                        }
                    }
                }
            }//foreach
            
            if (!empty($diffData)) {
                $lastData =  array_diff($category, $diffData);
            } else {
                $lastData = $category;
            }
            
            //取得分类信息
            if (!empty($lastData)) {
                $lastArr = self::getCategoryListByIds($lastData);
                foreach ($lastArr as $k => $v) {
                    $categoryData[] = $v->name;
                }
            }

            if (count($categoryData) < 4) {
                return ['category_label' => implode("，", $categoryData), 'parent' => $parent];
            } else {
                foreach ($categoryData as $ck => $cv) {
                    if ($ck < 3) {
                        $cList[] = $cv;
                    }
                }
                return [
                    'category_label' => implode("，", $cList)." 等".count($categoryData)."个分类",
                    'parent' => $parent
                ];
            }
        } else {
            return ['category_label' => '不限', 'parent' => [0]];
        }
    }

    
    /**
     * 获取此分类下的所有子分类
     * @param int $parentId
     * @param int $affiliateId
     */
    public static function getSubCategory($parentId, $affiliateId)
    {
        \DB::setFetchMode(\PDO::FETCH_CLASS);
        $row = DB::table("category")
                ->where('affiliateid', $affiliateId)
                ->where('parent', $parentId)
                ->select('category_id')
                ->get();
        return $row;
    }
    
    /**
     * 获取指定分类的详细信息
     * @param int $categoryId
     */
    public static function getCateogryDetail($categoryId)
    {
        $row = DB::table('category')
            ->where("category_id", $categoryId)
            ->first();
        
        return $row;
    }
    
    /**
     * 获取指定广告位或者banner的分类信息
     * @param int $id
     */
    public static function getUsedCategory($id)
    {
        $row = DB::table('zones')
            ->where('zoneid', $id)
            ->select('oac_category_id')
            ->first();
        
        return $row;
    }
    
    /**
     *
     * @param 根据ID返回所有分类信息
     */
    public static function getCategoryListByIds($ids)
    {
        $rows = DB::table('category')
                ->whereIn('category_id', $ids)
                ->select('category_id', 'name', 'parent')
                ->get();

        return $rows;
    }
    
    
    /**
     * 根据分类ID获取此媒体下符合条件的广告位
     * @param int $category
     */
    public static function getZoneList($affiliateId, $category)
    {
        $rows = Zone::where('affiliateid', $affiliateId)
                ->whereRaw("FIND_IN_SET('{$category}', oac_category_id)")
                ->select('zoneid', 'oac_category_id')
                ->get();
        
        return $rows;
    }
}
