<?php

namespace App\Models;

use Auth;

/**
 * This is the model class for table "products".
 * @property integer $id int
 * @property integer $type tinyint
 * @property integer $platform tinyint
 * @property integer $clientid int
 * @property string $name varchar
 * @property string $show_name varchar
 * @property string $icon varchar
 * @property string $created datetime
 * @property string $link_name varchar
 * @property string $link_url varchar
 * @property string $updated_time timestamp
 * @property string $application_id varchar
 * @property integer $link_status tinyint
 */
class Product extends BaseModel
{
    const TYPE_GAME = -1;
    const TYPE_APP_DOWNLOAD = 0;
    const TYPE_LINK = 1;

    const LINK_STATUS_DISABLE = 0;//失效链接
    const LINK_STATUS_ENABLE = 1;//有效链接

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'products';

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
    const CREATED_AT = 'created';

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
        'type',
        'platform',
        'clientid',
        'name',
        'show_name',
        'icon',
        'link_name',
        'link_url',
        'application_id',
    ];

    /**
     * Returns the text label for the specified attribute or all attribute labels.
     * @param string $key the attribute name
     * @return array|string the attribute labels
     */
    public static function attributeLabels($key = null)
    {
        $data = [
            'id' => trans('Product.id'),
            'type' => trans('Product.type'),
            'platform' => trans('Product.platform'),
            'clientid' => trans('Product.clientid'),
            'name' => trans('Product.name'),
            'show_name' => trans('Product.show_name'),
            'icon' => trans('Product.icon'),
            'created' => trans('Product.created'),
            'link_name' => trans('Product.link_name'),
            'link_url' => trans('Product.link_url'),
            'updated_time' => trans('Product.updated_time'),
            'application_id' => trans('Product.application_id'),
        ];

        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 获取目标平台标签数组或单个标签
     *
     * @var $key
     * @return array or string
     */
    public static function getTypeLabels($key = null)
    {
        $data = [
            self::TYPE_APP_DOWNLOAD => '安装包下载',
            self::TYPE_LINK => '链接推广',
        ];
        if ($key !== null) {
            return isset($data[$key]) ? $data[$key] : null;
        } else {
            return $data;
        }
    }

    /**
     * 保存产品的数据
     * @param $params
     * @return mixed
     */
    public static function storeProduct($params)
    {
        if (empty($params['id']) &&
            (empty($params['products_id']) || intval($params['products_id']) <= 0)
        ) {
            // 新增
            $product = new Product();
            $product->type = $params['products_type'];
            $product->platform = $params['platform'];
            $product->clientid = Auth::user()->account->client->clientid;
            $product->name = $params['products_type'] == Product::TYPE_LINK
                ? $params['link_name'] : $params['products_name'];
            $product->show_name = $params['products_show_name'];
            $product->icon = $params['products_type'] == Product::TYPE_LINK
                ? '' : $params['products_icon'];
            $product->link_name = $params['link_name'];//链接推广需要保存链接名称和URL
            $product->link_url = $params['link_url'];
            $product->save();
            $productId = $product->id;
        } elseif (!empty($params['id'])) {
            $campaign = Campaign::find($params['id']);
            $productId = $campaign->product_id;
        } else {
            $productId = $params['products_id'];
        }
        return $productId;
    }
}
