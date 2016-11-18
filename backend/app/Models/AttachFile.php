<?php

namespace App\Models;

use Auth;

/**
 * This is the model class for table "attach_files".
 * @property integer $id int 附件ID
 * @property string $unique char 唯一码
 * @property string $file varchar 文件路径
 * @property string $real_name varchar 真实名称
 * @property string $hash char MD5
 * @property string $reserve text
 * @property string $version varchar 版本名称
 * @property string $version_code varchar 版本号
 * @property string $market_version varchar 应用市场版本名称
 * @property string $market_version_code varchar 应用市场版本号
 * @property string $package_name varchar 包名
 * @property string $created_at timestamp
 * @property string $updated_at timestamp
 * @property integer $campaignid int 推广计划ID
 * @property integer $clientid int 广告主ID
 * @property integer $upload_uid int
 * @property integer $flag smallint
 * 包状态
 * 0：弃用
 * 1：待审核
 * 2：未使用
 * 3：使用中
 * 4：未通过审核
 * @property string $channel varchar 渠道号
 * @property string $updated_time timestamp
 */
class AttachFile extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;
    const FLAG_ABANDON = 0;//弃用
    const FLAG_PENDING_APPROVAL = 1;//待审核
    const FLAG_NOT_USED = 2;//未审核
    const FLAG_USING = 3;//使用中
    const FLAG_REJECTED = 4;//未通过审核

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'attach_files';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'unique',
        'file',
        'real_name',
        'hash',
        'reserve',
        'version',
        'version_code',
        'market_version',
        'market_version_code',
        'package_name',
        'campaignid',
        'clientid',
        'upload_uid',
        'flag',
        'channel',
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
            'unique' => trans('Unique'),
            'file' => trans('File'),
            'real_name' => trans('Real Name'),
            'hash' => trans('Hash'),
            'reserve' => trans('Reserve'),
            'version' => trans('Version'),
            'version_code' => trans('Version Code'),
            'market_version' => trans('Market Version'),
            'market_version_code' => trans('Market Version Code'),
            'package_name' => trans('Package Name'),
            'created_at' => trans('Created At'),
            'updated_at' => trans('Updated At'),
            'campaignid' => trans('Campaignid'),
            'clientid' => trans('Clientid'),
            'upload_uid' => trans('Upload Uid'),
            'flag' => trans('Flag'),
            'channel' => trans('model.channel'),
            'updated_time' => trans('Updated Time'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    // Add relations here

    // Add constant labels here

    /**
     * 包状态
     * @param null $key
     * @return array|null
     */
    public static function getFlagLabels($key = null)
    {
        $data = [
            self::FLAG_ABANDON => '弃用',
            self::FLAG_PENDING_APPROVAL => '待审核',
            self::FLAG_NOT_USED => '未使用',
            self::FLAG_USING => '使用中',
            self::FLAG_REJECTED => '未通过审核',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }
    /**
     * 添加附件
     * @param $params
     * @return AttachFile|null
     */
    public static function store($params, $campaignId)
    {
        $attach = new AttachFile();
        $attach->unique = date('Hi') . str_random(8);
        $attach->file = $params['path'];
        $attach->real_name = $params['real_name'];
        $attach->hash = $params['md5'];
        $attach->reserve = $params['reserve'];
        $attach->version = $params['version_name'];
        $attach->version_code = $params['version_code'];
        $attach->market_version_code = $params['version_code'];
        $attach->market_version = $params['version_name'];
        $attach->package_name = $params['package_name'];
        $attach->campaignid = $campaignId;
        $attach->channel = isset($params['channel']) ? $params['channel'] : '';
        $attach->clientid = Auth::user()->account->client->clientid;
        $attach->upload_uid = Auth::user()->user_id;
        if ($attach->save()) {
            return $attach;
        } else {
            unset($attach);
            return null;
        }
    }

    /**
     * 替换包信息
     * @param $campaignId
     * @param $params
     * @return null
     */
    public static function updateAttachFile($campaignId, $params)
    {
        if (empty($params['package_id'])) {
            $attach = AttachFile::whereMulti([
                'campaignid' => $campaignId,
                'hash' => $params['md5']
            ])->first();
            if ($attach) {
                $attach->file = $params['path'];
                $attach->real_name = $params['real_name'];
                $attach->hash = $params['md5'];
                $attach->reserve = $params['reserve'];
                $attach->version = $params['version_name'];
                $attach->version_code = $params['version_code'];
                $attach->market_version_code = $params['version_code'];
                $attach->market_version = $params['version_name'];
                $attach->package_name = $params['package_name'];
                $attach->flag = AttachFile::FLAG_PENDING_APPROVAL;
                if ($attach->save()) {
                    return $attach;
                } else {
                    unset($attach);
                    return null;
                }
            } else {
                AttachFile::where('campaignid', $campaignId)->update([
                    'flag' => AttachFile::FLAG_ABANDON,
                ]);
                return self::store($params, $campaignId);
            }
        }
        return null;
    }

    /**
     * 更新包状态
     * @param $campaignId
     * @param $packId
     * @param $status
     * @param array $param
     * @return mixed
     * @codeCoverageIgnore
     */
    public static function processPackage($campaignId, $packId, $status, $param = [])
    {
        if (AttachFile::FLAG_PENDING_APPROVAL == $status) {
            //通过审核
            $flag = AttachFile::FLAG_NOT_USED;
        } elseif (AttachFile::FLAG_ABANDON == $status) {
            //弃用
            $flag = AttachFile::FLAG_REJECTED;
        } elseif (AttachFile::FLAG_USING == $status) {
            //使用中
            $flag = AttachFile::FLAG_USING;
        } else {
            //未通过审核
            $flag = AttachFile::FLAG_REJECTED;
        }
        $updateArr = ['flag' => $flag, 'updated_at' => date('Y-m-d H:i:s')];
        if (!empty($param)) {
            $updateArr = array_merge($updateArr, $param);
        }
        $result = AttachFile::where('campaignid', $campaignId)
            ->where('id', $packId)
            ->update($updateArr);
        return $result;
    }
}
