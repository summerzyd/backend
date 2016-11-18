<?php

namespace App\Models;

/**
 * This is the model class for table "sohu_client_manager".
 * @property integer $clientid int
 * @property string $customer_key varchar
 * @property string $customer_name varchar
 * @property string $customer_website varchar
 * @property string $company_address varchar
 * @property string $capital varchar
 * @property string $reg_address varchar
 * @property string $contact varchar
 * @property string $phone_number varchar
 * @property string $publish_category varchar
 * @property string $oganization_code varchar
 * @property string $oganization_license varchar
 * @property string $business_license varchar
 * @property string $legalperson_identity varchar
 * @property string $tax_cert varchar
 * @property string $taxreg_cert varchar
 * @property string $ext_license varchar
 * @property string $deadline date
 * @property string $audit_info varchar
 * @property string $tv_audit_info varchar
 * @property integer $tv_status int
 * @property integer $status int
 * @property string $created_time timestamp
 * @property string $updated_time timestamp
 */
class SoHuClientManager extends BaseModel
{
    // add your constant definition based on {field + meaning}
     const TV_STATUS_AUDITING = 0;
     const TV_STATUS_SUCCESS = 1;
     const TV_STATUS_REJECT = 2;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'sohu_client_manager';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'customer_key';

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
        'clientid',
        'customer_name',
        'customer_website',
        'company_address',
        'capital',
        'reg_address',
        'contact',
        'phone_number',
        'publish_category',
        'oganization_code',
        'oganization_license',
        'business_license',
        'legalperson_identity',
        'tax_cert',
        'taxreg_cert',
        'ext_license',
        'deadline',
        'audit_info',
        'tv_audit_info',
        'tv_status',
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
            'clientid' => trans('Clientid'),
            'customer_key' => trans('Customer Key'),
            'customer_name' => trans('Customer Name'),
            'customer_website' => trans('Customer Website'),
            'company_address' => trans('Company Address'),
            'capital' => trans('Capital'),
            'reg_address' => trans('Reg Address'),
            'contact' => trans('Contact'),
            'phone_number' => trans('Phone Number'),
            'publish_category' => trans('Publish Category'),
            'oganization_code' => trans('Oganization Code'),
            'oganization_license' => trans('Oganization License'),
            'business_license' => trans('Business License'),
            'legalperson_identity' => trans('Legalperson Identity'),
            'tax_cert' => trans('Tax Cert'),
            'taxreg_cert' => trans('Taxreg Cert'),
            'ext_license' => trans('Ext License'),
            'deadline' => trans('Deadline'),
            'audit_info' => trans('Audit Info'),
            'tv_audit_info' => trans('Tv Audit Info'),
            'tv_status' => trans('Tv Status'),
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
            'customer_name',
            'customer_website',
            'company_address',
            'capital',
            'reg_address',
            'contact',
            'phone_number',
            'publish_category',
            'oganization_code',
            'oganization_license',
            'business_license',
            'legalperson_identity',
            'tax_cert',
            'taxreg_cert',
            'ext_license',
            'status',
            'audit_info',
            'tv_status',
            'tv_audit_info',
        ];
        return $fill;
    }
}
