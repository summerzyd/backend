<?php
namespace App\Services;

use App\Components\Helper\LogHelper;
use App\Components\Helper\StringHelper;
use App\Models\AdZoneKeyword;
use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\OperationLog;
use App\Models\User;
use Auth;

class KeywordService
{
    /**
     * 获取关键词的信息
     * @param $cid
     * @param $clientId
     * @return array
     */
    public static function getKeywords($campaignId)
    {
        $list = [];
        if ($campaignId > 0) {
            $userId = [];
            if (Auth::user()->account->isManager()) {
                $ret = AdZoneKeyword::where('campaignid', $campaignId)
                    ->get(['created_uid']);
                if ($ret) {
                    $userId = $ret->toArray();
                }
            } else {
                $userId = User::getAllUser();
            }

            $result = CampaignService::getKeyWordPriceList($campaignId, $userId);
            if (!empty($result)) {
                $result = $result[$campaignId];
                foreach ($result as $row) {
                    $list[] = array(
                        'id' => $row->id,
                        'campaignid' => $row->campaignid,
                        'keyword' => $row->keyword,
                        'price_up' => $row->price_up,
                        'rank' => $row->rank,
                        'is_manager' => $row->operator == 0 ? 0 : 1,
                        'type' => $row->type,
                    );
                }
            }
        }
        $list = KeywordService::sortKeyword($list);
        return $list;
    }

    /**
     * 排序
     * @param $data
     * @return mixed
     */
    public static function sortKeyword($data)
    {
        $rank = [];//排名
        $price = [];//加价
        $keyword = [];//关键字
        foreach ($data as $k => $v) {
            $rank[$k] = $v['rank'];
            $price[$k] = $v['price_up'];
            $keyword[$k] = StringHelper::getFirstCharter($v['keyword']);
        }
        array_multisort(
            $rank,
            SORT_NUMERIC,
            SORT_ASC,
            $price,
            SORT_NUMERIC,
            SORT_ASC,
            $keyword,
            SORT_STRING,
            SORT_ASC,
            $data
        );

        return $data;
    }

    /**
     * 存储关键字
     * @param $params
     * @return bool|int
     */
    public static function storeKeyword($params)
    {
        $price_up = floatval($params['price_up']);
        if (!empty($params['id'])) {//修改关键词
            $keyword = AdZoneKeyword::where('id', $params['id'])->first();
            if ($keyword) {
                $keyword->price_up = $price_up; // @codeCoverageIgnore
            } else {    // @codeCoverageIgnore
                return 5101;
            }
        } else {    // @codeCoverageIgnore
            //新增关键字
            $campaignId = $params['campaignid'];
            $keywords = $params['keyword'];
            $k = AdZoneKeyword::where('campaignid', $campaignId)
                ->where('keyword', $keywords)
                ->first(array('id'));
            //判断关键字是否存在
            if ($k) {
                if (Auth::user()->account->isAdvertiser()) {
                    AdZoneKeyword::where('id', $k->id)->update([
                        'keyword' => $keywords,
                        'price_up' => $price_up,
                        'created_uid' => Auth::user()->user_id,
                        'status' => AdZoneKeyword::STATUS_EFFECT,
                        'operator' => 0,
                    ]);
                    return true;
                } else {
                    return 5100;
                }
            }
            // @codeCoverageIgnoreStart
            $keyword = new AdZoneKeyword();
            $keyword->campaignid = $campaignId;
            $keyword->keyword = $keywords;
            $keyword->price_up = $price_up;
            $keyword->created_uid = Auth::user()->user_id;
            $keyword->status = AdZoneKeyword::STATUS_EFFECT;
            $keyword->operator = (true == Auth::user()->account->isAdvertiser()) ? 0 : 1;

            if (Auth::user()->account->isManager()) {
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $campaignId,
                    'type' => OperationLog::TYPE_MANUAL,
                    'operator' => Auth::user()->contact_name,
                    'message' => CampaignService::formatWaring(6023, [$keywords]),
                ]);
            } elseif (Auth::user()->account->isAdvertiser()) {
                $campaign = Campaign::find($campaignId);
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $campaignId,
                    'type' => OperationLog::TYPE_MANUAL,
                    'operator' => OperationLog::ADVERTISER,
                    'message' => CampaignService::formatWaring(6014, [$campaign->client->clientname,
                        $keywords]),
                ]);
            }
        }
        if (!$keyword->save()) {
            return 5001;
        }
        return true;
        // @codeCoverageIgnoreEnd
    }

    /**
     * 删除关键字
     * @param $id
     * @return bool|int
     */
    public static function deleteKeyword($id)
    {
        $user_id = Auth::user()->user_id;
        $keyword = AdZoneKeyword::find($id);
        if (!$keyword) {
            return 5101;
        }
        //判断是否是该广告主 否则无权限
        if (($user_id != $keyword->created_uid)) {
            return 5004;
        }
        // @codeCoverageIgnoreStart
        LogHelper::info('delete ad_zone_keyword' . $id . ' keyword ' . $keyword->keyword);
        if ($keyword->delete()) {
            return true;
        } else {
            return 5001;
        }
        // @codeCoverageIgnoreEnd
    }
}
