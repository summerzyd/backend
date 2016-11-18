<?php
namespace App\Console\Commands;

use App\Services\CampaignService;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;
use App\Models\Affiliate;

class JobBannerCategoryMap extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_banner_category_map';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for making banner category map.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // 投放数据更新
        $sql = "select bannerid,category from up_banners where status = 0";
        $list = DB::select($sql);

        foreach ($list as $key => $value) {
            $bannerId = $value->bannerid;
            $categoryId = $value->category;
            // 没有分类跳过执行
            if (!empty($categoryId)) {
                $sql = "SELECT GROUP_CONCAT(c1.`name`) as c_name,
                GROUP_CONCAT(distinct c1.parent) as p2_cid,
                GROUP_CONCAT(distinct c2.`name`) as p2_name,
                GROUP_CONCAT(distinct c2.parent) as p1_cid,
                GROUP_CONCAT(distinct c3.`name`) as p1_name
                FROM up_category c1
                LEFT JOIN up_category c2 ON c1.parent = c2.category_id
                LEFT JOIN up_category c3 ON c2.parent = c3.category_id
                where c1.category_id in ($categoryId)
                order by field(0, $categoryId);";
                $category = DB::select($sql);
                if (!empty($category)) {
                    $categoryName = $category[0]->c_name;
                    $p2Cid = $category[0]->p2_cid;
                    $p2Name = $category[0]->p2_name;
                    $p1Cid = $category[0]->p1_cid;
                    $p1Name = $category[0]->p1_name;
                    
                    $parentCategoryId = $this->distinctVal($p1Cid, $p2Cid);
                    $parentCategoryName = $this->distinctVal($p1Name, $p2Name);

                    $sql = "insert into up_banner_category_map 
                    (banner_id, category_id, category_name,parent_category_id, parent_category_name)
                    values ('$bannerId','$categoryId','$categoryName','$parentCategoryId','$parentCategoryName')
                    ON DUPLICATE KEY update
                    category_id = values(category_id),
                    category_name= values(category_name),
                    parent_category_id = values(parent_category_id),
                    parent_category_name = values(parent_category_name)";
                    DB::statement($sql);
                }
            }
        }
    }
    
    private function distinctVal($v1, $v2)
    {
        $arr1 = [];
        $arr2 = [];
        if (!empty($v1)) {
            $arr1 = explode(',', $v1);
        }
        if (!empty($v2)) {
            $arr2 = explode(',', $v2);
        }
        return implode(',', array_unique(array_merge($arr1, $arr2)));
    }
}
