<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertUpSettingData extends Migration
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
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('201', '2', '0', 'basic', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('202', '2', '0', 'campaign', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('203', '2', '0', 'mail', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('204', '2', '0', 'balance', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('205', '2', '0', 'role', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('206', '2', '0', 'job', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20101', '2', '201', 'site_name', 'text', '', '', 'BiddingOS', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20201', '2', '202', 'ad_spec', 'json', '{"0":"应用市场","1":"Banner","2":"Feeds","3":"半屏","4":"全屏","71":"AppStore"}', '', '{"0":{"1":"720*1280","2":"480*800"},"1":{"0":"216*36","2":"300*50","4":"320*50","5":"468*60","6":"640*100","9":"640*120","10":"640*260","7":"728*90","8":"1280*200","11":"640*1200","12":"180*150","13":"480*70"},"2":{"9":"1000*560"},"3":["600*500","500*600","300*250"],"4":{"2":"640*960","3":"720*1280","7":"960*640","8":"1280*720","9":"640*360"},"71":{"1":"640*1136"}}', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20301', '2', '203', 'mail_host', 'text', '', '', 'smtp.exmail.qq.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20302', '2', '203', 'mail_port', 'text', '', '', '587', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20303', '2', '203', 'mail_from_address', 'text', '', '', 'test1@iwalnuts.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20304', '2', '203', 'mail_from_name', 'text', '', '', 'Leon', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20305', '2', '203', 'mail_username', 'text', '', '', 'test1@iwalnuts.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20306', '2', '203', 'mail_password', 'password', '', '', 'Hs88362236', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20310', '2', '203', 'client_balance_user', 'text', '', '', 'hexq@iwalnuts.com;funson@iwalnuts.com;simon@iwalnuts.com;fengshenhuang@biddingos.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20311', '2', '203', 'daily_mail_address', 'text', '', '', 'maozhiming@iwalnuts.com;leigh@iwalnuts.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20401', '2', '204', 'alipay_config', 'json', '{"seller_email":"支付宝账号","partner":"Partner","key":"Key","sign_type":"签名方式","input_charset":"字符编码","cacert":"签名文件","transport":"传输方式"}', '', '{"seller_email":"zhifubao@biddingos.com","partner":"2088911144756439","key":"1geo9k0d1n75s4p96roigjcrg4uzdllo","sign_type":"MD5","input_charset":"utf-8","cacert":"cacert.pem","transport":"http"}', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20501', '2', '205', 'default_manager_role', 'text', '', '', '4', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20502', '2', '205', 'default_broker_role', 'text', '', '', '5', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20503', '2', '205', 'default_trafficker_role', 'text', '', '', '6', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20504', '2', '205', 'default_client_role', 'text', '', '', '7', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20601', '2', '206', 'data_exception', 'json', '{"uniformRate":"提醒比例","warningRate":"警告比例","conversionLimit":"下载极限值","compareRate":"对比比例"}', '', '{"uniformRate":0.1,"warningRate":0.3,"conversionLimit":100,"compareRate":"0.2"}', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
/*INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20602', '2', '206', 'attach_banner_relation', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');*/
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20603', '2', '206', 'monitor_banner_relation', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20604', '2', '206', 'monitor_download_url', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20605', '2', '206', 'monitor_pctr', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20606', '2', '206', 'job_ad_start_suspend_account', 'text', '', '', '292|329|376|381|546|576', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20607', '2', '206', 'job_delivery_repair_log', 'text', '', '', 'hexq@iwalnuts.com;simon@iwalnuts.com;funson@iwalnuts.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('20608', '2', '206', 'job_sync_pmp_campaigns', 'text', '', '', 'fengshenhuang@biddingos.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');





INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('101', '1', '0', 'basic', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('102', '1', '0', 'campaign', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('103', '1', '0', 'mail', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('104', '1', '0', 'balance', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('105', '1', '0', 'role', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('106', '1', '0', 'job', 'group', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10101', '1', '201', 'site_name', 'text', '', '', 'BiddingOS', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10201', '1', '202', 'ad_spec', 'json', '{"0":"应用市场","1":"Banner","2":"Feeds","3":"半屏","4":"全屏","71":"AppStore"}', '', '{"0":{"1":"720*1280","2":"480*800"},"1":{"0":"216*36","2":"300*50","4":"320*50","5":"468*60","6":"640*100","9":"640*120","10":"640*260","7":"728*90","8":"1280*200","11":"640*1200","12":"180*150","13":"480*70"},"2":{"9":"1000*560"},"3":["600*500","500*600","300*250"],"4":{"2":"640*960","3":"720*1280","7":"960*640","8":"1280*720","9":"640*360"},"71":{"1":"640*1136"}}', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10301', '1', '203', 'mail_host', 'text', '', '', 'smtp.exmail.qq.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10302', '1', '203', 'mail_port', 'text', '', '', '587', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10303', '1', '203', 'mail_from_address', 'text', '', '', 'test1@iwalnuts.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10304', '1', '203', 'mail_from_name', 'text', '', '', 'Leon', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10305', '1', '203', 'mail_username', 'text', '', '', 'test1@iwalnuts.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10306', '1', '203', 'mail_password', 'password', '', '', 'Hs88362236', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10310', '1', '203', 'client_balance_user', 'text', '', '', 'hexq@iwalnuts.com;funson@iwalnuts.com;simon@iwalnuts.com;fengshenhuang@biddingos.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10311', '1', '203', 'daily_mail_address', 'text', '', '', 'maozhiming@iwalnuts.com;leigh@iwalnuts.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10401', '1', '204', 'alipay_config', 'json', '{"seller_email":"支付宝账号","partner":"Partner","key":"Key","sign_type":"签名方式","input_charset":"字符编码","cacert":"签名文件","transport":"传输方式"}', '', '{"seller_email":"zhifubao@biddingos.com","partner":"2088911144756439","key":"1geo9k0d1n75s4p96roigjcrg4uzdllo","sign_type":"MD5","input_charset":"utf-8","cacert":"cacert.pem","transport":"http"}', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10501', '1', '205', 'default_manager_role', 'text', '', '', '4', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10502', '1', '205', 'default_broker_role', 'text', '', '', '5', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10503', '1', '205', 'default_trafficker_role', 'text', '', '', '6', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10504', '1', '205', 'default_client_role', 'text', '', '', '7', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10601', '1', '206', 'data_exception', 'json', '{"uniformRate":"提醒比例","warningRate":"警告比例","conversionLimit":"下载极限值","compareRate":"对比比例"}', '', '{"uniformRate":0.1,"warningRate":0.3,"conversionLimit":100,"compareRate":"0.2"}', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10603', '1', '206', 'monitor_banner_relation', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10604', '1', '206', 'monitor_download_url', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10605', '1', '206', 'monitor_pctr', 'text', '', '', '', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10606', '1', '206', 'job_ad_start_suspend_account', 'text', '', '', '292|329|376|381|546|576', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10607', '1', '206', 'job_delivery_repair_log', 'text', '', '', 'hexq@iwalnuts.com;simon@iwalnuts.com;funson@iwalnuts.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');
INSERT INTO `up_setting` (`id`, `agencyid`, `parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`, `created_time`, `updated_time`) VALUES ('10608', '1', '206', 'job_sync_pmp_campaigns', 'text', '', '', 'fengshenhuang@biddingos.com', '50', '0000-00-00 00:00:00', '2016-08-12 16:13:14');

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
    }
}
