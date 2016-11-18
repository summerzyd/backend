<?php

namespace App\Models;

/**
 * This is the model class for table "sohu_material_manager".
 * @property integer $campaign_image_id int
 * @property string $customer_key varchar
 * @property string $material_name varchar
 * @property string $file_source varchar
 * @property integer $file_size int
 * @property integer $width int
 * @property integer $height int
 * @property string $imp varchar
 * @property string $click_monitor varchar
 * @property string $gotourl varchar
 * @property string $advertising_type varchar
 * @property string $submit_to varchar
 * @property string $delivery_type varchar
 * @property integer $campaign_id int
 * @property string $expire varchar
 * @property string $imp_sendtag varchar
 * @property string $clk_sendtag varchar
 * @property string $material_type varchar
 * @property string $template varchar
 * @property string $main_attr varchar
 * @property string $slave varchar
 * @property string $audit_info varchar
 * @property integer $status int
 * @property string $created_time timestamp
 * @property string $updated_time timestamp
 */
class SoHuMaterialManager extends BaseModel
{
    // add your constant definition based on {field + meaning}
    const STATUS_AUDITING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_REJECT = 2;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'sohu_material_manager';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'file_source';

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
        'campaign_image_id',
        'customer_key',
        'material_name',
        'file_size',
        'width',
        'height',
        'imp',
        'click_monitor',
        'gotourl',
        'advertising_type',
        'submit_to',
        'delivery_type',
        'campaign_id',
        'expire',
        'imp_sendtag',
        'clk_sendtag',
        'material_type',
        'template',
        'main_attr',
        'slave',
        'audit_info',
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
            'campaign_image_id' => trans('Campaign Image Id'),
            'customer_key' => trans('Customer Key'),
            'material_name' => trans('Material Name'),
            'file_source' => trans('File Source'),
            'file_size' => trans('File Size'),
            'width' => trans('Width'),
            'height' => trans('Height'),
            'imp' => trans('Imp'),
            'click_monitor' => trans('Click Monitor'),
            'gotourl' => trans('Gotourl'),
            'advertising_type' => trans('Advertising Type'),
            'submit_to' => trans('Submit To'),
            'delivery_type' => trans('Delivery Type'),
            'campaign_id' => trans('Campaign Id'),
            'expire' => trans('Expire'),
            'imp_sendtag' => trans('Imp Sendtag'),
            'clk_sendtag' => trans('Clk Sendtag'),
            'material_type' => trans('Material Type'),
            'template' => trans('Template'),
            'main_attr' => trans('Main Attr'),
            'slave' => trans('Slave'),
            'audit_info' => trans('Audit Info'),
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

    // Add constant labels here
    public static function getColumns()
    {
        $fill = [
            'customer_key',
            'file_source',
            'material_name',
            'imp',
            'click_monitor',
            'gotourl',
            'advertising_type',
            'submit_to',
            'delivery_type',
            'campaign_id',
            'expire',
            'imp_sendtag',
            'clk_sendtag',
            'material_type',
            'template',
            'main_attr',
            'slave',
            'status',
            'audit_info',
        ];
        return $fill;
    }
}
