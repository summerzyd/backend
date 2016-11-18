<?php

namespace App\Models;

use App\Components\Config;

/**
 * This is the model class for table "campaigns_images".
 * @property integer $id int
 * @property integer $campaignid int 推广计划ID
 * @property integer $ad_spec int 素材样式
 * @property string $url varchar 图片url
 * @property string $alt_url varchar 备选图片url
 * @property string $scale decimal width 比 height 的比例
 * @property integer $width int 宽
 * @property integer $height int 高
 * @property integer $type tinyint 文件类型
 * @property string $updated date
 * @property string $updated_time timestamp
 */
class CampaignImage extends BaseModel
{
    // add your constant definition based on {field + meaning}
    // const STATUS_DISABLE = 0;
    // const STATUS_ENABLE = 1;
    //feeds广告
    const FEEDS_DEFAULT_AD_SPEC = 9;
    const FEEDS_DEFAULT_WIDTH = 1000;
    const FEEDS_DEFAULT_HEIGHT = 560;

    const TYPE_JPG = 1;
    const TYPE_GIF = 2;
    const TYPE_PNG = 3;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'campaigns_images';

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
        'ad_spec',
        'url',
        'alt_url',
        'scale',
        'width',
        'height',
        'type',
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
            'ad_spec' => trans('Ad Spec'),
            'url' => trans('Url'),
            'alt_url' => trans('Alt Url'),
            'scale' => trans('Scale'),
            'width' => trans('Width'),
            'height' => trans('Height'),
            'type' => trans('Type'),
            'updated' => trans('Updated'),
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
     * 添加上传图片
     * @param $params
     */
    public static function storeFeedsImage($campaignId, $params)
    {
        foreach ($params as $item) {
            $result = CampaignImage::whereMulti(['campaignid' => $campaignId,
                'ad_spec' => CampaignImage::FEEDS_DEFAULT_AD_SPEC])->first();
            if (count($result)) {
                CampaignImage::where('id', $result->id)->update([
                    'url' => $item['url']
                ]);
            } else {
                $campaignImage = new CampaignImage();
                $campaignImage->campaignid = $campaignId;
                $campaignImage->ad_spec = CampaignImage::FEEDS_DEFAULT_AD_SPEC;
                $campaignImage->url = $item['url'];
                $campaignImage->scale =
                    round(CampaignImage::FEEDS_DEFAULT_WIDTH / CampaignImage::FEEDS_DEFAULT_HEIGHT, 2);
                $campaignImage->width = CampaignImage::FEEDS_DEFAULT_WIDTH;
                $campaignImage->height = CampaignImage::FEEDS_DEFAULT_HEIGHT;
                $campaignImage->type = self::getImageType($item['url']);
                $campaignImage->save();
            }
        }
    }
    /**
     * 保存banner广告的图片
     * @param integer $campaignId
     * @param array $images
     */
    public static function storeBannerOrScreenImage($campaignId, $images, $adType)
    {
        foreach ($images as $img) {
            if (!isset($img['ad_spec']) || !isset($img['url'])) {
                continue;
            }
            $adSpec = Config::get('ad_spec.' . $adType . '.' . $img['ad_spec']);
            $size = explode('*', $adSpec);
            if (count($size) != 2) {
                continue;
            }
            $width = $size[0];
            $height = $size[1];

            $result = CampaignImage::whereMulti(['campaignid' => $campaignId, 'ad_spec' => $img['ad_spec']])->first();
            if (count($result)) {
                $campaignImage = CampaignImage::find($result->id);
                $campaignImage->campaignid = $campaignId;
                $campaignImage->ad_spec = $img['ad_spec'];
                $campaignImage->url = $img['url'];
                $campaignImage->scale = round($width / $height, 2);
                $campaignImage->width = $width;
                $campaignImage->height = $height;
                $campaignImage->type = self::getImageType($img['url']);
                $campaignImage->save();
            } else {
                $campaignImage = new CampaignImage();
                $campaignImage->campaignid = $campaignId;
                $campaignImage->ad_spec = $img['ad_spec'];
                $campaignImage->url = $img['url'];
                $campaignImage->scale = round($width / $height, 2);
                $campaignImage->width = $width;
                $campaignImage->height = $height;
                $campaignImage->type = self::getImageType($img['url']);
                $campaignImage->save();
            }
        }
    }

    /**
     * 获取推广图片
     * @param $campaignId
     * @return mixed
     */
    public static function getCampaignImages($campaignId)
    {
        $images = CampaignImage::where('campaignid', $campaignId)
            ->select('ad_spec', 'url', 'alt_url')
            ->get()
            ->toArray();
        return $images;
    }

    /**
     * 获取文件扩展名
     * @param null $key
     * @return array|null
     */
    public static function getTypeLabels($key = null)
    {
        $data = [
            self::TYPE_JPG => 'jpg',
            self::TYPE_GIF => 'gif',
            self::TYPE_PNG => 'png',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     *
     * @param $url
     * @return int
     */
    public static function getImageType($url)
    {
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'jpg':
                return 1;
            case 'gif':
                return 2;
            case 'png':
                return 3;
        }
        return 0;
    }
}
