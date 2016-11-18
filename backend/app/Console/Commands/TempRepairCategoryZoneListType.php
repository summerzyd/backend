<?php

namespace App\Console\Commands;

use App\Models\Affiliate;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\ZoneListType;

class TempRepairCategoryZoneListType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_repair_category_zone_list_type';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
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
        $affiliates = Affiliate::get();
        foreach ($affiliates as $affiliate) {
            $model = Category::whereMulti([
                'affiliateid' => $affiliate->affiliateid,
                'ad_type' => Campaign::AD_TYPE_APP_STORE,
                'parent' => 1,
            ])->first();
            if (!$model) {
                $model = new Category([
                    'affiliateid' => $affiliate->affiliateid,
                    'ad_type' => Campaign::AD_TYPE_APP_STORE,
                    'parent' => 1,
                    'platform' => 1,
                    'media_id' => 2,
                    'name' => '默认应用',
                ]);
                $model->save();
            }
            $model = Category::whereMulti([
                'affiliateid' => $affiliate->affiliateid,
                'ad_type' => Campaign::AD_TYPE_APP_STORE,
                'parent' => 2,
            ])->first();
            if (!$model) {
                $model = new Category([
                    'affiliateid' => $affiliate->affiliateid,
                    'ad_type' => Campaign::AD_TYPE_APP_STORE,
                    'parent' => 2,
                    'platform' => 1,
                    'media_id' => 2,
                    'name' => '默认游戏',
                ]);
                $model->save();
            }

            $model = ZoneListType::whereMulti([
                'af_id' => $affiliate->affiliateid,
                'ad_type' => Campaign::AD_TYPE_APP_STORE,
                'listtypeid' => 0,
                'type' => 0,
            ])->first();
            if (!$model) {
                $model = new ZoneListType([
                    'af_id' => $affiliate->affiliateid,
                    'ad_type' => Campaign::AD_TYPE_APP_STORE,
                    'listtypeid' => 0,
                    'type' => 0,
                    'listtype' => '默认榜单模块',
                    'already_used' => 0,
                ]);
                $model->save();
            }
            $model = ZoneListType::whereMulti([
                'af_id' => $affiliate->affiliateid,
                'ad_type' => Campaign::AD_TYPE_APP_STORE,
                'listtypeid' => -1,
                'type' => 1,
            ])->first();
            if (!$model) {
                $model = new ZoneListType([
                    'af_id' => $affiliate->affiliateid,
                    'ad_type' => Campaign::AD_TYPE_APP_STORE,
                    'listtypeid' => -1,
                    'type' => 1,
                    'listtype' => '默认搜索模块',
                    'already_used' => 0,
                ]);
                $model->save();
            }
        }
    }
}
