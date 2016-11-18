<?php

namespace App\Models;

use App\Components\Helper\LogHelper;
use App\Services\CampaignService;
use Illuminate\Support\Facades\Auth;

/**
 * This is the model class for table "ad_zone_keywords".
 * @property integer $id int 关键字ID
 * @property integer $campaignid int 推广计划ID
 * @property string $keyword varchar 关键字
 * @property string $price_up decimal 加价金额
 * @property integer $status tinyint
 * 状态
 * 0：生效中
 * 1：审核中
 * 2：审核不通过
 * @property integer $type tinyint
 * 类型
 * 1：竞品词
 * 2：模糊词
 * @property string $approve_time timestamp 审核时间
 * @property string $approve_comment varchar 审核原因
 * @property integer $approve_uid int 审核人ID
 * @property integer $created_uid int
 * @property integer $operator tinyint 是否平台添加 0否，1是
 * @property string $updated_time timestamp
 */
class AdZoneKeyword extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;

    const STATUS_EFFECT = 0;
    const STATUS_PENDING_APPROVAL = 1;
    const STATUS_REJECT = 2;

    const TYPE_COMPETING = 1;// 竞品词
    const TYPE_VAGUE = 2;//模糊词

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ad_zone_keywords';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaignid',
        'keyword',
        'price_up',
        'status',
        'type',
        'approve_time',
        'approve_comment',
        'approve_uid',
        'operator',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('Id'),
            'campaignid' => trans('Campaignid'),
            'keyword' => trans('Keyword'),
            'price_up' => trans('Price Up'),
            'status' => trans('Status'),
            'type' => trans('Type'),
            'approve_time' => trans('Approve Time'),
            'approve_comment' => trans('Approve Comment'),
            'approve_uid' => trans('Approve Uid'),
            'created_uid' => trans('Created Uid'),
            'operator' => trans('Operator'),
            'updated_time' => trans('Updated Time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here
    /**
     * return user default role
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    /*public function role()
    {
        return $this->hasOne('App\Models\Role', 'id', 'role_id');
    }*/

    // Add constant labels here
    /**
     * Get status labels
     * @param null $key
     * @return array|string
     */
    /*public static function getStatusLabels($key = null)
    {
        $data = [
            self::STATUS_DISABLE => trans('Disable'),
            self::STATUS_ENABLE => trans('Enable'),
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }*/


    /**
     * 获取关键字状态标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getStatusLabels($key = null)
    {
        $data = [
            self::STATUS_EFFECT => '生效中',
            self::STATUS_PENDING_APPROVAL => '审核中',
            self::STATUS_REJECT => '审核不通过',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取关键字类型标签数组或单个标签
     * @param null $key
     * @return array|null
     */
    public static function getTypeLabels($key = null)
    {
        $data = [
            self::TYPE_COMPETING => '竞品词',
            self::TYPE_VAGUE => '模糊词',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 处理关键字加价
     * @param int $campaignId
     * @param array $array
     */
    public static function updateKeyWordAndPrice($campaignId, $params)
    {
        //先找到此广告计划的所有关键字的ID
        $existIds = [];
        $updateIds = [];
        $addIds = [];
        $userId = User::getAllUser();
        if (count($params) != 0) {
            $ids = AdZoneKeyword::where('campaignid', $campaignId)
                ->whereIn('created_uid', $userId)->get();
            if (count($ids)) {
                foreach ($ids as $vv) {
                    $existIds[] = $vv->id;
                }
            }

            //新增关键字
            if (empty($existIds)) {
                foreach ($params as $k => $v) {
                    $adZoneKeyword = AdZoneKeyword::where('campaignid', $campaignId)
                        ->where('keyword', $v['keyword'])->first();

                    if ($adZoneKeyword) {
                        LogHelper::info('ad_zone_keyword ' . $adZoneKeyword->id . ' modify ad_zone_keyword data');
                        self::updateAdZoneKeyword($adZoneKeyword->id, $v);
                    } else {
                        self::storeAdZoneKeyword($campaignId, $v);
                        $addIds[] = $v['keyword'];
                    }
                }
                $campaign = Campaign::find($campaignId);
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $campaignId,
                    'type' => OperationLog::TYPE_MANUAL,
                    'operator' => OperationLog::ADVERTISER,
                    'message' => CampaignService::formatWaring(6014, [$campaign->client->clientname,
                        implode('，', $addIds)]),
                ]);
            } else {
                //如果存在，则表示之前有数据,如果不为空，则表示新增进来
                foreach ($params as $k => $v) {
                    if (!empty($v['id'])) {
                        if (in_array($v['id'], $existIds)) {
                            //如果价格不一样，则状态为审核，否则不变
                            $priceInfo = AdZoneKeyword::where('id', $v['id'])->first();
                            if ($priceInfo->price_up == $v['price_up']) {
                                //更新
                                LogHelper::info('ad_zone_keyword ' . $v['id'] . ' modify ad_zone_keyword data');
                                AdZoneKeyword::where('id', $v['id'])->update([
                                    'keyword' => $v['keyword'],
                                    'price_up' => $v['price_up'],
                                ]);
                            } else {
                                LogHelper::info('ad_zone_keyword ' . $v['id'] . ' modify ad_zone_keyword data');
                                self::updateAdZoneKeyword($v['id'], $v);
                            }
                            $updateIds[] = $v['id'];
                        }
                    } else {
                        //不存在，新的关键词
                        $adZoneKeyword = AdZoneKeyword::where('campaignid', $campaignId)
                            ->where('keyword', $v['keyword'])->first();
                        if ($adZoneKeyword) {
                            LogHelper::info('ad_zone_keyword ' . $adZoneKeyword->id . ' modify ad_zone_keyword data');
                            if (in_array($adZoneKeyword->created_uid, $userId)) {
                                self::updateAdZoneKeyword($adZoneKeyword->id, $v);
                            } else {
                                AdZoneKeyword::where('id', $adZoneKeyword->id)->update([
                                    'keyword' => $v['keyword'],
                                    'price_up' => $v['price_up'],
                                    'status' => AdZoneKeyword::STATUS_EFFECT,
                                    'created_uid' => Auth::user()->user_id,
                                    'operator' => (true == Auth::user()->account->isAdvertiser()) ? 0 : 1,
                                ]);
                            }
                        } else {
                            self::storeAdZoneKeyword($campaignId, $v);
                            $addIds[] = $v['keyword'];
                        }
                    }
                }

                $campaign = Campaign::find($campaignId);
                OperationLog::store([
                    'category' => OperationLog::CATEGORY_CAMPAIGN,
                    'target_id' => $campaign->campaignid,
                    'type' => OperationLog::TYPE_MANUAL,
                    'operator' => OperationLog::ADVERTISER,
                    'message' => CampaignService::formatWaring(6014, [$campaign->client->clientname,
                        implode('，', $addIds)]),
                ]);
            }
            //找出两个数组的差集，existIds 有，而在 updateIds 中不存在的，则删除掉
            $diffIds = array_diff($existIds, $updateIds);
            if (!empty($diffIds)) {
                LogHelper::info('delete ad_zone_keyword data ' . implode(',', $diffIds));
                AdZoneKeyword::whereIn('id', $diffIds)->delete();
            }
        } else {
            //删除
            LogHelper::info('delete ad_zone_keyword campaignId' . $campaignId);
            AdZoneKeyword::where('campaignid', $campaignId)
                ->whereIn('created_uid', $userId)->delete();
        }
    }

    /**
     * 更新关键字信息
     * @param $id
     * @param $params
     */
    public static function updateAdZoneKeyword($id, $params)
    {
        AdZoneKeyword::where('id', $id)->update([
            'keyword' => $params['keyword'],
            'price_up' => $params['price_up'],
            'status' => AdZoneKeyword::STATUS_EFFECT,
            'operator' => (true == Auth::user()->account->isAdvertiser()) ? 0 : 1,
        ]);
    }

    /**
     * 新增关键字
     * @param $campaignId 推广计划ID
     * @param $params
     */
    public static function storeAdZoneKeyword($campaignId, $params)
    {
        $adZoneKeyword = new AdZoneKeyword();
        $adZoneKeyword->campaignid = $campaignId;
        $adZoneKeyword->keyword = $params['keyword'];
        $adZoneKeyword->price_up = $params['price_up'];
        $adZoneKeyword->status = AdZoneKeyword::STATUS_EFFECT;
        $adZoneKeyword->created_uid = Auth::user()->user_id;
        $adZoneKeyword->save();
    }

    /**
     * 获取推广计划所有创建用户
     * @param $campaignId
     * @return mixed
     */
    public static function getCreatedId($campaignId)
    {
        $result = AdZoneKeyword::where('campaignid', $campaignId)
            ->select('created_uid')
            ->distinct()
            ->get()
            ->toArray();
        return $result;
    }
}
