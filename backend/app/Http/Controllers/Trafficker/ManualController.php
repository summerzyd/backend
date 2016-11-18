<?php
namespace App\Http\Controllers\Trafficker;

use App\Models\Affiliate;
use App\Http\Controllers\Controller;
use App\Services\ManualService;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Maatwebsite\Excel\Facades\Excel;

class ManualController extends Controller
{
    /**
     * 媒体商导入人工投放数据 CPA, CPS数据
     *
     * | name | sub name | type | description | restraint | required|
     * | :--: | :------: | :--: | :--------: | :-------: | :-----: |
     * | campaignid |  | integer | 推广计划ID |  | 是 |
     * | file |  | string | 导入的文件名 | excel格式的数据，详细请参考模板 | 是 |
     * | type |  | string | 所属类型 | CPA, CPS | 是 |
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function manualImport(Request $request)
    {
        set_time_limit(0);
        //必须为自营媒体才允许导入
        $currentKind = intval(Session::get('kind'));
        if ($currentKind != Affiliate::KIND_SELF) {
            return $this->errorCode(4002);
        }
        
        $excel = input::file('file', null);
        //获取导入EXCEL数据
        $importExcelData = Excel::load($excel)->getSheet(0)->toArray();
        $count = count($importExcelData);
        //EXCEL没有数据
        if ($count <= 1) {
            return $this->errorCode(5090);
        }
        //类型A2A，C2C，S2S等格式
        $dataType = $request->type;
        $preData = array_keys(config('biddingos.preAffiliateData'));
        if (!in_array($dataType, $preData)) {
            return $this->errorCode(5091);
        }
        $result = ManualService::import($count, $dataType, $importExcelData);
        if (0 == $result['errorCode']) {
            return $this->success();
        } else {
            return $this->errorCode($result['errorCode'], $result['message']);
        }
    }
}
