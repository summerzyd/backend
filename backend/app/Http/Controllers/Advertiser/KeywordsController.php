<?php

namespace App\Http\Controllers\Advertiser;

use App\Models\AdZoneKeyword;
use App\Models\User;
use App\Services\KeywordService;
use Auth;
use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Services\CampaignService;
use App\Http\Controllers\Controller;

class KeywordsController extends Controller
{
    /**
     * 新增修改关键字
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 关键字ID |  | 是 |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | keyword |  | string | 关键字 |  | 是 |
     * | price_up |  | decimal | 加价金额 |  | 是 |
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'keyword' => 'required',
                'price_up' => 'required',
            ], [], AdZoneKeyword::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        if (floatval($params['price_up']) <= 0) {
            return $this->errorCode(5042);
        }
        $ret = KeywordService::storeKeyword($params);
        if ($ret !== true) {
            return $this->errorCode($ret);
        }
        $result = KeywordService::getKeywords($params['campaignid']);
        return $this->success(null, null, $result);
    }

    /**
     * 关键字列表
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 关键字ID |  | 是 |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | keyword |  | string | 关键字ID |  | 是 |
     * | price_up |  | decimal | 加价金额 |  | 是 |
     * | rank |  | integer | 竞争力 |  | 是 |
     */
    public function index(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
            ], [], AdZoneKeyword::attributeLabels())) !== true) {
            return $this->errorCode(5000, $ret);
        }
        // @codeCoverageIgnoreStart
        $campaignId = $request->input('campaignid');
        $result = KeywordService::getKeywords($campaignId);
        return $this->success(null, null, $result);
        // @codeCoverageIgnoreEnd
    }

    /**
     * 删除关键字
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * @param  Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        if (($ret = $this->validate($request, [
                'id' => 'required',
            ], [], AdZoneKeyword::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $id = $request->input('id');
        $ret = KeywordService::deleteKeyword($id);
        if ($ret !== true) {
            return $this->errorCode($ret);//@codeCoverageIgnore
        }
        return $this->success();//@codeCoverageIgnore
    }
}
