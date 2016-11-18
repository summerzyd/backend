<?php
namespace App\Http\Controllers\Manager;

use App\Components\Formatter;
use App\Http\Controllers\Controller;
use App\Models\AdZoneKeyword;
use App\Models\Campaign;
use App\Services\CampaignService;
use App\Services\KeywordService;
use Illuminate\Http\Request;
use Auth;

class KeywordController extends Controller
{
    /**
     * 平台查看关键字
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 关键字ID |  | 是 |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | keyword |  | string | 关键字 |  | 是 |
     * | price_up |  | decimal | 加价金额 |  | 是 |
     * | rank |  | integer | 竞争力 |  | 是 |
     * | is_manager |  | integer | 是否管理员添加 |  | 是 |
     */
    public function index(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
            ], [], Campaign::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $campaignId = $request->input('campaignid');
        //获取广告列表所有广告主用户
        $userId = AdZoneKeyword::getCreatedId($campaignId);
        $data = CampaignService::getKeyWordPriceList($campaignId, $userId);
        $keyword = [];
        if (!empty($data)) {
            $data = $data[$campaignId];
            foreach ($data as $item) {
                $keyword[] = [
                    'id' => $item->id,
                    'campaignid' => $item->campaignid,
                    'keyword' => $item->keyword,
                    'price_up' => Formatter::asDecimal($item->price_up),
                    'rank' => $item->rank,
                    'is_manager' => (0 == $item->operator) ? 0 : 1,
                ];
            }
        }
        return $this->success(null, null, $keyword);
    }

    /**
     * 新增关键字
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | keyword |  | string | 关键字 |  | 是 |
     * | price_up |  | decimal | 加价金额 |  | 是 |
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (($ret = $this->validate($request, [
                'campaignid' => 'required',
                'keyword' => 'required',
                'price_up' => 'required|min:0',
            ], [], AdZoneKeyword::attributeLabels())) !== true
        ) {
            return $this->errorCode(5000, $ret);
        }
        $params = $request->all();
        $ret = KeywordService::storeKeyword($params);
        if ($ret !== true) {
            return $this->errorCode($ret);
        }
        $result = KeywordService::getKeywords($params['campaignid']);//@codeCoverageIgnore
        return $this->success(null, null, $result);//@codeCoverageIgnore
    }

    /**
     * 删除关键字
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | id |  | integer | 关键字ID |  | 是 |
     * @param Request $request
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
