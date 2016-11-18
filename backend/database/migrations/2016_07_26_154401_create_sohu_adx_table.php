<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSohuAdxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = <<<SQL
CREATE TABLE `up_sohu_client_manager` (
  `clientid` int(11) DEFAULT NULL,
  `customer_key` varchar(255) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_website` varchar(255) DEFAULT NULL,
  `company_address` varchar(255) DEFAULT NULL,
  `capital` varchar(255) DEFAULT NULL,
  `reg_address` varchar(255) DEFAULT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `publish_category` varchar(255) DEFAULT NULL,
  `oganization_code` varchar(255) DEFAULT NULL,
  `oganization_license` varchar(255) DEFAULT NULL,
  `business_license` varchar(255) DEFAULT NULL,
  `legalperson_identity` varchar(255) DEFAULT NULL,
  `tax_cert` varchar(255) DEFAULT NULL,
  `taxreg_cert` varchar(255) DEFAULT NULL,
  `ext_license` varchar(255) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `audit_info` varchar(255) DEFAULT NULL,
  `tv_audit_info` varchar(255) DEFAULT NULL,
  `tv_status` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_time` timestamp NULL DEFAULT NULL,
  `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `up_sohu_material_manager` (
  `campaign_image_id` int(11) NOT NULL,
  `customer_key` varchar(255) DEFAULT NULL,
  `material_name` varchar(255) DEFAULT NULL,
  `file_source` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `imp` varchar(255) DEFAULT NULL,
  `click_monitor` varchar(255) DEFAULT NULL,
  `gotourl` varchar(255) DEFAULT NULL,
  `advertising_type` varchar(255) DEFAULT NULL,
  `submit_to` varchar(255) DEFAULT NULL,
  `delivery_type` varchar(255) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `expire` varchar(255) DEFAULT NULL,
  `imp_sendtag` varchar(255) DEFAULT NULL,
  `clk_sendtag` varchar(255) DEFAULT NULL,
  `material_type` varchar(255) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `main_attr` varchar(255) DEFAULT NULL,
  `slave` varchar(255) DEFAULT NULL,
  `audit_info` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_time` timestamp NULL DEFAULT NULL,
  `updated_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`file_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SQL;
        DB::getPdo()->exec($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
