<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpYmMaterialManager extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $sql = "
CREATE TABLE `up_ym_material_manager` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `offer_id` int(11) NOT NULL,
  `offer_name` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `campaign_id` mediumint(9) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `url` varchar(255),
  `material` varchar(255),
  `created_time` datetime,
  `updated_time` datetime,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `up_ym_material_manager`
(offer_id,offer_name,city,campaign_id,url,material) 
VALUES
('113892','App Download - Uber - AffordableRatesforEveryDayRides - IOS(CN) - Non-incentive','中国','1297','http://global.ymtracking.com/trace?offer_id=113892&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_62c0b443cbf7c70a172119dc7b79c0a0.zip'),
('114058','App Download - Uber - A081_A304_C760168 - iOS（Hefei）- Non-incentive','合肥','1304','http://global.ymtracking.com/trace?offer_id=114058&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('102092','App Download - Uber - A081_A304_A882543 - IOS - CN（Beijing）- Non-incentive','北京','1305','http://global.ymtracking.com/trace?offer_id=102092&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('116127','App Download - Uber - A081_A304_C888983 - iOS（Chongqing）- Non-incentive','重庆','1306','http://global.ymtracking.com/trace?offer_id=116127&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_1c65c09495249c750534bfba2907b172.zip'),
('114049','App Download - Uber - A081_A304_C761542 - IOS（Ningbo）- Non-incentive','宁波','1307','http://global.ymtracking.com/trace?offer_id=114049&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('116163','App Download - Uber - A081_A304_C927655 - iOS（Hangzhou）- Non-incentive','杭州','1308','http://global.ymtracking.com/trace?offer_id=116163&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_1c65c09495249c750534bfba2907b172.zip'),
('103024','App Download - Uber - A081_A304_A882584 - IOS（Tianjing）- Non-incentive','天津','1309','http://global.ymtracking.com/trace?offer_id=103024&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('101610','App Download - Uber - A081_A304_A882625 - iOS(Chengdu) - Non-incentive','成都','1310','http://global.ymtracking.com/trace?offer_id=101610&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('101622','App Download - Uber - A081_A304_A882598 - IOS(Shanghai) - Non-incentive','上海','1311','http://global.ymtracking.com/trace?offer_id=101622&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('114043','App Download - Uber - A081_A304_C762443 - IOS（Yantai）- Non-incentive','烟台','1312','http://global.ymtracking.com/trace?offer_id=114043&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_9d6a98d481254f33b22b66ccb0fcb50b.zip'),
('114048','App Download - Uber - A081_A304_C761919 - IOS（Qingdao）- Non-incentive','青岛','1313','http://global.ymtracking.com/trace?offer_id=114048&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_9d6a98d481254f33b22b66ccb0fcb50b.zip'),
('114050','App Download - Uber - A081_A304_C761368 - IOS（Jinan）- Non-incentive','济南','1314','http://global.ymtracking.com/trace?offer_id=114050&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_9d6a98d481254f33b22b66ccb0fcb50b.zip'),
('114054','App Download - Uber - A081_A304_C760669 - iOS（Dalian）- Non-incentive','大连','1315','http://global.ymtracking.com/trace?offer_id=114054&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_9d6a98d481254f33b22b66ccb0fcb50b.zip'),
('114047','App Download - Uber - A081_A304_C762169 - IOS（Suzhou）- Non-incentive','苏州','1316','http://global.ymtracking.com/trace?offer_id=114047&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_9d6a98d481254f33b22b66ccb0fcb50b.zip'),
('110246','App Download - Uber - A081_A304_C187042 - iOS（Nanjing）- Non-incentive','南京','1317','http://global.ymtracking.com/trace?offer_id=110246&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('114056','App Download - Uber - A081_A304_C760392 - iOS（Changsha）- Non-incentive','长沙','1318','http://global.ymtracking.com/trace?offer_id=114056&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_1c65c09495249c750534bfba2907b172.zip'),
('114051','App Download - Uber - A081_A304_C761094 - IOS（Guiyang）- Non-incentive - Private','贵阳','1319','http://global.ymtracking.com/trace?offer_id=114051&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_1c65c09495249c750534bfba2907b172.zip'),
('103022','App Download - Uber - A081_A304_A882579 - iOS(Shenzhen) - Non-incentive','深圳','1320','http://global.ymtracking.com/trace?offer_id=103022&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('101624','App Download - Uber - A081_A304_A882550 - IOS (Guangzhou) - Non-incentive','广州','1321','http://global.ymtracking.com/trace?offer_id=101624&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('114053','App Download - Uber - A081_A304_C760868 - iOS（Foshan）- Non-incentive','佛山','1322','http://global.ymtracking.com/trace?offer_id=114053&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar'),
('114046','App Download - Uber - A081_A304_C762593 - IOS（Xiamen）- Non-incentive','厦门','1323','http://global.ymtracking.com/trace?offer_id=114046&aff_id=102846&aff_sub6=http%3a%2f%2fios.pgzs.com%2fapp%3faction%3dcontent%23appid%3d368677368%26ala','http://uploads.yeahmobi.com/offer_file/thumb_63403bc8fbf6f1ea5797d9e0dafbee00.rar');";
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
