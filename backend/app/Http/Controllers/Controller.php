<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Components\Config;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Auth;

class Controller extends BaseController
{
    /**
     * 当前登录账户
     *
     * @var $user
     */
    public $user;

    /**
     * @ignore
     * Auth认证，设置为全部要认证，如果不需要认证请在子类中覆盖该函数
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission');
    }

    /**
     * @ignore
     * Validate the given request with the given rules.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  array                    $rules
     * @param  array                    $messages
     * @param  array                    $customAttributes
     * @return bool|string
     */
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->getValidationFactory()->make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }
        return true;
    }

    /**
     * Response with CORS(https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Access_control_CORS)
     *
     * @param  array $data
     * @return \Illuminate\Http\Response
     */
    protected function corsReturn($data)
    {
        return (new Response($data))
            ->header('Content-Type', 'application/json')
            ->header('Access-Control-Allow-Origin', Config::get('app.origin'))
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Expose-Headers', 'Set-Cookie')
            ->header('Vary', 'Origin')
            ->header('Vary', 'Accept-Encoding');
    }

    /**
     * response with error code which defined in /config/error.php
     *
     * @param  integer $code
     * @param  string $msg
     * @return \Illuminate\Http\Response
     */
    protected function errorCode($code, $msg = null)
    {
        $error = Config::get('error');

        if ($msg === null) {
            $msg = isset($error[$code]) ? $error[$code] : '';
        }

        return $this->corsReturn(
            [
            'res' => $code,
            'msg' => $msg,
            'obj' => null,
            'map' => null,
            'list' => null,
            ]
        );
    }

    /**
     * response with object, map or list to user
     * res 0 for success which defined in /config/error.php
     *
     * @param  array $obj
     * @param  array $map
     * @param  array $list
     * @return \Illuminate\Http\Response
     */
    protected function success($obj = null, $map = null, $list = null)
    {
        $error = Config::get('error');

        return $this->corsReturn(
            [
            'res' => 0,
            'msg' => $error[0],
            'obj' => $this->null2EmptyString($obj),
            'map' => $map,
            'list' => $this->null2EmptyString($list),
            ]
        );
    }

    /**
     * @ignore
     * 将数组中的null转换成空字符串""
     *
     * @param  array $list
     * @return array
     */
    protected function null2EmptyString($list)
    {
        if (empty($list)) {
            return null;
        }

        $data = [];
        if (is_array($list)) {
            foreach ($list as $k => $v) {
                if (is_array($v)) {
                    $data[$k] = $this->null2EmptyString($v);
                } else {
                    $data[$k] = is_null($v) ? '' : $v;
                }
            }
        }
        return $data;
    }


    /**
     * 获取当前登录用户
     *
     * @return string ADMIN MANAGER ADVERTISER TRAFFICKER
     */
    protected function getUser()
    {
        if (!$this->user) {
            $this->user = Auth::user();
        }
        return $this->user;
    }

    /**
     * 获取当前用户账户类型
     *
     * @return string ADMIN MANAGER ADVERTISER TRAFFICKER
     */
    protected function getAccountType()
    {
        $user = $this->getUser();
        $account = $user->account;
        return $account->account_type;
    }

    /**
     * 获取当前用户是否为主账户
     *
     * @return boolean true是主账户  false子账户
     */
    protected function isAccountMain()
    {
        $user = $this->getUser();
        $account = $user->account;
        return $user->user_id == $account->manager_userid;
    }

    /**
     * 是否有operation的权限
     *
     * @param operation
     * @return boolean
     */
    protected function can($operation)
    {
        $user = $this->getUser();
        if (isset($user->role->operation_list)) {
            $operations = $user->role->operation_list;
            if (strtolower($operations) == 'all') {
                return true;
            }
            $operationList = explode(',', $operations);
            return in_array($operation, $operationList);
        }

        return false;
    }
}
