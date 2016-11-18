<?php

namespace App\Http\Controllers\Api;

use App\Components\Api\AesCrypter;
use App\Components\Formatter;
use App\Components\Helper\LogHelper;
use App\Models\Affiliate;
use App\Models\AffiliateUserReport;
use App\Models\Campaign;
use App\Models\DataSummaryAdHourly;
use App\Models\Product;
use Auth;
use App\Services\StatService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Components\Config;
use Illuminate\Support\Facades\DB;

class StatController extends Controller
{
    /**
     * Auth认证，不用认证
     */
    public function __construct()
    {
    }

    public function affiliateUserReport(Request $request)
    {
        $body = file_get_contents('php://input');
        $aesCrypter = new AesCrypter(Config::get('app.aesKey'));
        $body = $aesCrypter->decrypt($body);
        //将上报数据插入log
        LogHelper::notice($body);
        $data = json_decode($body, true);
        $success = 0;
        $failedList = [];
        foreach ($data as $val) {
            $affiliate = Affiliate::find($val['affiliateid']);
            if ($affiliate) {
                $model = AffiliateUserReport::whereMulti([
                    'type' => $val['type'],
                    'date' => $val['date'],
                    'span' => $val['span'],
                    'affiliateid' => $val['affiliateid'],
                ])->first();
                if ($model) {
                    //如果存在，则更新
                    $model->num = $val['num'];
                } else {
                    $model = new AffiliateUserReport([
                        'type' => $val['type'],
                        'date' => $val['date'],
                        'span' => $val['span'],
                        'affiliateid' => $val['affiliateid'],
                        'created_time' => date('Y-m-d H:i:s'),
                        'num' => $val['num'],
                    ]);
                }
                $ret = $model->save();
                if (!$ret) {
                    LogHelper::error("Update affiliates_user_report
                                type={$val['type']}
                                date={$val['date']}
                                span={$val['span']}
                                affiliateid ={$val['affiliateid']}
                                num ={$val['num']} 失败");
                } else {
                    $success++;
                }
            } else {
                LogHelper::error("此数据的媒体商ID不存在" .json_encode($val));
                $failedList[] = $val;
            }
        }

        return $this->success(
            null,
            null,
            [
                'success' => $success,
                'failed' => count($failedList),
                'failed_list' => $failedList,
            ]
        );
    }
    /*
    public function hourlyImpression(Request $request)
    {
        if (($ret = $this->validate($request, [
                'api_secret_key' => 'required',
                'zone_id' => 'required',
                'ad_id' => 'required',
            ], [], $this->attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }

        if ($request->api_secret_key != Config::get('biddingos.apiSecretKey')) {
            return $this->errorCode(5003);
        }

        $zoneId = $request->zone_id;
        $adId = $request->ad_id;
        if (isset($request->ts)) {
            $time = date('Y-m-d H:00:00', $request->ts - 8 * 60 * 60);
        } else {
            $time = date('Y-m-d H:00:00', time() - 8 * 60 * 60);
        }

        if (isset($request->type)) {
            $span = $request->type * rand(1000, 3000);
        } else {
            $span = rand(1000, 3000);
        }

        $model = DataSummaryAdHourly::whereMulti([
                'zone_id' => $zoneId,
                'ad_id' => $adId,
                'date_time' => $time,
            ])->first();
        if ($model) {
            //如果存在，则更新
            $model->impressions += $span;
        } else {
            $model = new DataSummaryAdHourly([
                'zone_id' => $zoneId,
                'ad_id' => $adId,
                'date_time' => $time,
                'impressions' => $span,
            ]);
        }
        $model->save();
        return $this->success();
    }*/

    public static function attributeLabels()
    {
        return [
            'api_secret_key' => 'api_secret_key',
            'zone_id' => 'zone_id',
            'ad_id' => 'ad_id',
        ];
    }
}
