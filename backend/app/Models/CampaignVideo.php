<?php

namespace App\Models;

/**
 * This is the model class for table "campaigns_video".
 * @property integer $id int
 * @property integer $campaignid int
 * @property string $url varchar
 * @property string $path varchar
 * @property string $real_name varchar
 * @property string $scale decimal
 * @property integer $duration int
 * @property integer $type tinyint
 * @property string $md5_file char
 * @property integer $width int
 * @property integer $height int
 * @property string $reserve text
 * @property integer $status tinyint
 * @property string $created_time date
 * @property string $updated_time timestamp
 */
class CampaignVideo extends BaseModel
{
    // add your constant definition based on {field + meaning}
     const STATUS_ABANDON = 0;//弃用
     const STATUS_PENDING_APPROVAL = 1;//待审核
     const STATUS_USING = 2;//使用中
     const STATUS_REJECTED = 3;//未通过审核

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'campaigns_video';

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
    const CREATED_AT = 'created_time';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_time';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaignid',
        'url',
        'path',
        'real_name',
        'scale',
        'duration',
        'type',
        'md5_file',
        'width',
        'height',
        'reserve',
        'status',
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
            'url' => trans('Url'),
            'path' => trans('path'),
            'real_name' => trans('real_name'),
            'scale' => trans('Scale'),
            'duration' => trans('Duration'),
            'type' => trans('Type'),
            'md5_file' => 'md5',
            'width' => '宽',
            'height' => '高',
            'reserve' => trans('Reserve'),
            'status' => trans('Status'),
            'created_time' => trans('Created Time'),
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

    /**
     * 保存视频信息
     * @param $campaignId
     * @param $video
     * @return CampaignVideo|null
     */
    public static function store($campaignId, $video)
    {
        $videoInfo = json_decode($video, true);
        $scale = round($videoInfo['streams'][0]['width'] / $videoInfo['streams'][0]['height'], 2);
        $campaignVideo = new CampaignVideo();
        $campaignVideo->campaignid = $campaignId;
        $campaignVideo->scale = $scale;
        $campaignVideo->duration = $videoInfo['format']['duration'];
        $campaignVideo->reserve = $video;
        $campaignVideo->url = $videoInfo['url'];
        $campaignVideo->path = $videoInfo['path'];
        $campaignVideo->real_name = $videoInfo['real_name'];
        $campaignVideo->md5_file = $videoInfo['md5_file'];
        $campaignVideo->width = $videoInfo['streams'][0]['width'];
        $campaignVideo->height = $videoInfo['streams'][0]['height'];
        $campaignVideo->type = self::getVideoType($videoInfo['real_name']);
        if (!$campaignVideo->save()) {
            unset($reuslt);
            return null;
        }
        return $campaignVideo;
    }

    /**
     * 更新视频信息
     * @param $campaignId
     * @param $video
     * @return CampaignVideo|null
     */
    public static function updateVideo($campaignId, $video)
    {
        $videoInfo = json_decode($video, true);
        if (empty($videoInfo['id'])) {
            $campaignVideo = CampaignVideo::where('campaignid', $campaignId)
                ->where('md5_file', $videoInfo['md5_file'])
                ->first();

            CampaignVideo::where('campaignid', $campaignId)->update([
                'status' => CampaignVideo::STATUS_ABANDON,
            ]);
            if ($campaignVideo) {
                $scale = round($videoInfo['streams'][0]['width'] / $videoInfo['streams'][0]['height'], 2);
                $campaignVideo->scale = $scale;
                $campaignVideo->duration = $videoInfo['format']['duration'];
                $campaignVideo->reserve = $video;
                $campaignVideo->url = $videoInfo['url'];
                $campaignVideo->path = $videoInfo['path'];
                $campaignVideo->real_name = $videoInfo['real_name'];
                $campaignVideo->md5_file = $videoInfo['md5_file'];
                $campaignVideo->width = $videoInfo['streams'][0]['width'];
                $campaignVideo->height = $videoInfo['streams'][0]['height'];
                $campaignVideo->type = self::getVideoType($videoInfo['real_name']);
                $campaignVideo->status = self::STATUS_PENDING_APPROVAL;
                if (!$campaignVideo->save()) {
                    unset($campaignVideo);
                    return null;
                }
                return $campaignVideo;
            } else {
                return self::store($campaignId, $video);
            }
        }
        return null;
    }
    /**
     * 获取视频类型
     * @param $realName
     * @return int
     */
    private static function getVideoType($realName)
    {
        $ext = pathinfo($realName, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'flv':
                return 1;
            case 'swf':
                return 2;
            case 'mp4':
                return 3;
            case 'avi':
                return 4;
            case 'mpeg':
                return 5;
            case 'mov':
                return 6;
            default:
                return 7;
        }
    }
}
