<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertProcLogconversionCpa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 2.8 需求，添加CPA结费类型的广告，广告主消耗价设为 0 ,A->D : 广告主的A，媒体上的D，广告主按A结费，媒体按D计费
        $this->logConversionAlter();
        $this->logConversionTAlter();
    }
    
    private function logConversionTAlter()
    {
        $sql = "DROP PROCEDURE IF EXISTS logConversion_t ";
        DB::getPdo()->exec($sql);

        $sql = "CREATE PROCEDURE `LogConversion_t`(IN bid INT,IN zid INT,IN cb VARCHAR (20),IN targetType VARCHAR (255),IN targetCat VARCHAR (255),IN targetId VARCHAR (255),IN pup_type INT,IN channel VARCHAR (255),IN ts INT)
                    SQL SECURITY INVOKER
                BEGIN
                    DECLARE _price DECIMAL (8, 2) DEFAULT 0.00;
                    DECLARE _campaignid INT DEFAULT 0;
                    DECLARE _af_income DECIMAL (8, 2) DEFAULT 0.0;
                    DECLARE _price_up DECIMAL (8, 2) DEFAULT 0.0;
                    DECLARE `_rollback` BOOL DEFAULT 0;
                    DECLARE `_status` INT DEFAULT 0; -- 是否已计费 A->D 不计费默认 0，其他为 2
                    DECLARE _down_id int DEFAULT 0; -- down_log 是否存在下载，去重使用
                    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET `_rollback` = 1;
                        
                    -- 数据转换
                    SET targetType = IFNULL(targetType, '');
                    SET targetCat = IFNULL(targetCat, '');
                    SET targetId = IFNULL(targetId, '');
                    SET channel = IFNULL(channel, '');
                        
                    SELECT max(d.downid) as downid INTO _down_id FROM up_down_log AS d
                    LEFT JOIN up_campaigns AS c on c.campaignid = d.campaignid
                    LEFT JOIN up_banners AS b on b.campaignid = c.campaignid
                    WHERE b.bannerid = bid AND d.target_type = targetType AND d.target_cat = targetCat AND d.target_id = targetId
                          AND d.actiontime > DATE_SUB(DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-%d 00:00:00'),INTERVAL 8 HOUR),INTERVAL IF(b.affiliateid=86, 1, 2) day)
                    LIMIT 1;
                        
                        
                   START TRANSACTION;
                            IF pup_type = 0 THEN
                                    -- 计算引用的价格（包含广告位加价）
                                    SELECT
                                            CASE WHEN b.revenue_type = 1 and c.revenue_type = 4 THEN
                                                0
                                            ELSE
                                                bil.revenue + IFNULL(p.price_up,0)
                                            END,
                                            c.campaignid,
                                            CASE WHEN b.revenue_type = 2 AND c.revenue_type = 1 THEN
                                                    TRUNCATE(IFNULL(p.price_up,0) * (1 / IFNULL(e.num, 1)) + bil.af_income, 2)
                                            ELSE
                                                    TRUNCATE(IFNULL(p.price_up,0) * IFNULL(aff.income_rate / 100, 1) * IFNULL(c.rate / 100, 1) + bil.af_income, 2)
                                            END AS af_price,
                                            CASE WHEN b.revenue_type = 1 and c.revenue_type = 4 THEN 0 ELSE 2 END
                                            INTO _price, _campaignid, _af_income, _status
                                    FROM
                                            up_campaigns as c
                                            JOIN up_banners AS b ON b.campaignid = c.campaignid
                                            JOIN up_banners_billing AS bil ON b.bannerid = bil.bannerid
                                            LEFT JOIN up_ad_zone_price p ON(p.ad_id=b.bannerid AND p.zone_id = zid)
                                            LEFT JOIN up_affiliates aff ON(aff.affiliateid = b.affiliateid)
                                            LEFT JOIN up_affiliates_extend e ON (b.affiliateid = e.affiliateid AND b.revenue_type = e.revenue_type AND c.ad_type = e.ad_type)
                                    WHERE
                                            c.campaignid = b.campaignid
                                    AND b.bannerid = bid;
                            ELSE
                                    -- 计算引用的价格（包含关键词加价）
                                    SELECT
                                            CASE WHEN b.revnue_type = 1 and c.revenue_type = 4 THEN 
                                                0
                                            ELSE
                                                bil.revenue + IFNULL(p.price_up,0) + IFNULL(pri.price_up,0)
                                            END,
                                            c.campaignid,
                                            CASE WHEN b.revenue_type = 2 AND c.revenue_type = 1 THEN
                                                    TRUNCATE((IFNULL(p.price_up, 0) + IFNULL(pri.price_up, 0)) * (1 / IFNULL(e.num, 1)) + bil.af_income, 2)
                                            ELSE
                                                    TRUNCATE((IFNULL(p.price_up, 0) + IFNULL(pri.price_up, 0)) * IFNULL(aff.income_rate / 100, 1) * IFNULL(c.rate / 100, 1) + bil.af_income, 2)
                                            END AS af_price,
                                            CASE WHEN b.revenue_type = 1 and c.revenue_type = 4 THEN 0 ELSE 2 END
                                            INTO _price, _campaignid, _af_income, _status
                                    FROM
                                            up_campaigns AS c
                                            JOIN up_banners AS b ON b.campaignid = c.campaignid
                                            JOIN up_banners_billing AS bil ON b.bannerid = bil.bannerid
                                            LEFT JOIN up_ad_zone_keywords AS p ON p.campaignid = c.campaignid
                                            LEFT JOIN up_ad_zone_price pri ON(pri.ad_id=b.bannerid AND pri.zone_id = zid)
                                            LEFT JOIN up_affiliates aff ON(aff.affiliateid = b.affiliateid)
                                            LEFT JOIN up_affiliates_extend e ON (b.affiliateid = e.affiliateid AND b.revenue_type = e.revenue_type AND c.ad_type = e.ad_type)
                                    WHERE
                                            b.bannerid = bid
                                    AND p.id = pup_type;
                            END IF;
                        
                            IF _down_id > 0 THEN
                                -- 插入重复数据表
                                    -- 记录重复数据
                                INSERT INTO `up_repeat_log` (`campaignid`, `zoneid`, `cb`, `price`, `actiontime`, `target_type`, `target_cat`, `target_id`, `channel`, `af_income`, `status`, `repeat_type`, `repeat_log_id`)
                                                    VALUES (_campaignid, zid, cb, _price, FROM_UNIXTIME(ts, '%Y-%m-%d %H:%i:%s'), targetType, targetCat, targetId, channel, _af_income, _status, 'down', _down_id);
                            ELSE
                                -- 记录下载数据
                                    INSERT INTO up_down_log (campaignid,zoneid,cb,price,actiontime,target_type,target_cat,target_id, channel, af_income, `status`)
                                                    VALUES(_campaignid, zid, cb, _price, FROM_UNIXTIME(ts, '%Y-%m-%d %H:%i:%s'), targetType, targetCat, targetId, channel, _af_income, _status);
                                --  记录LOG
                                    INSERT INTO proc_mixing_log(cam_id, zone_id, price, time, `description`) VALUES(_campaignid, zid, _price, FROM_UNIXTIME(ts, '%Y-%m-%d %H:%i:%s'), CONCAT('before down; info: ', _rollback));
                            END IF;
                        
                    IF `_rollback` THEN
                        ROLLBACK;
                    ELSE
                        COMMIT;
                    END IF;
                        
                END";
        DB::getPdo()->exec($sql);
    }
    
    private function logConversionAlter()
    {
        $sql = "DROP PROCEDURE IF EXISTS logConversion;";
        db::getPdo()->exec($sql);
        
        $sql = "CREATE PROCEDURE `LogConversion`(IN bid INT,IN zid INT,IN cb VARCHAR (20),IN targetType VARCHAR (255),IN targetCat VARCHAR (255),IN targetId VARCHAR (255),IN pup_type INT,IN channel VARCHAR (255))
                    SQL SECURITY INVOKER
                BEGIN
                    DECLARE _price DECIMAL (8, 2) DEFAULT 0.00;
                    DECLARE _campaignid INT DEFAULT 0;
                    DECLARE _af_income DECIMAL (8, 2) DEFAULT 0.0;
                    DECLARE _price_up DECIMAL (8, 2) DEFAULT 0.0;
                    DECLARE `_rollback` BOOL DEFAULT 0;
                    DECLARE `_status` INT DEFAULT 0; -- 是否已计费 A->D 不计费默认 0，其他为 2
                    DECLARE _down_id int DEFAULT 0; -- down_log 是否存在下载，去重使用
                    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET `_rollback` = 1;
                        
                    -- 数据转换
                    SET targetType = IFNULL(targetType, '');
                    SET targetCat = IFNULL(targetCat, '');
                    SET targetId = IFNULL(targetId, '');
                    SET channel = IFNULL(channel, '');
                        
                    SELECT max(d.downid) as downid INTO _down_id FROM up_down_log AS d
                    LEFT JOIN up_campaigns AS c on c.campaignid = d.campaignid
                    LEFT JOIN up_banners AS b on b.campaignid = c.campaignid
                    WHERE b.bannerid = bid AND d.target_type = targetType AND d.target_cat = targetCat AND d.target_id = targetId
                          AND d.actiontime > DATE_SUB(DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-%d 00:00:00'),INTERVAL 8 HOUR),INTERVAL IF(b.affiliateid=86, 1, 2) day)
                    LIMIT 1;
                        
                    START TRANSACTION;
                            IF pup_type = 0 THEN
                                    -- 计算引用的价格（包含广告位加价）
                                    SELECT
                                            -- 如果广告是 A->D ,广告下载计费价设为0
                                            CASE WHEN b.revenue_type = 1 AND c.revenue_type = 4 THEN 
                                                0
                                            ELSE
                                                bil.revenue + IFNULL(p.price_up,0)
                                            END, 
                                            c.campaignid,
                                            CASE WHEN b.revenue_type = 2 AND c.revenue_type = 1 THEN
                                                    TRUNCATE(IFNULL(p.price_up,0) * (1 / IFNULL(e.num, 1)) + bil.af_income, 2)
                                            ELSE
                                                    TRUNCATE(IFNULL(p.price_up,0) * IFNULL(aff.income_rate / 100, 1) * IFNULL(c.rate / 100, 1) + bil.af_income, 2)
                                            END AS af_price,
                                            CASE WHEN b.revenue_type = 1 and c.revenue_type = 4 THEN 0 ELSE 2 END
                                            INTO _price, _campaignid, _af_income, _status
                                    FROM
                                            up_campaigns as c
                                            JOIN up_banners AS b ON b.campaignid = c.campaignid
                                            JOIN up_banners_billing AS bil ON b.bannerid = bil.bannerid
                                            LEFT JOIN up_ad_zone_price p ON(p.ad_id=b.bannerid AND p.zone_id = zid)
                                            LEFT JOIN up_affiliates aff ON(aff.affiliateid = b.affiliateid)
                                            LEFT JOIN up_affiliates_extend e ON (b.affiliateid = e.affiliateid AND b.revenue_type = e.revenue_type AND c.ad_type = e.ad_type)
                                    WHERE
                                            c.campaignid = b.campaignid
                                    AND b.bannerid = bid;
                            ELSE
                                    -- 计算引用的价格（包含关键词加价）
                                    SELECT
                                            -- 如果广告是 A->D ,广告下载计费价设为 0
                                            CASE WHEN b.revenue_type = 1 AND c.revenue_type = 4 THEN
                                                0
                                            ELSE
                                                bil.revenue + IFNULL(p.price_up,0) + IFNULL(pri.price_up,0)
                                            END, 
                                            c.campaignid,
                                            CASE WHEN b.revenue_type = 2 AND c.revenue_type = 1 THEN
                                                    TRUNCATE((IFNULL(p.price_up, 0) + IFNULL(pri.price_up, 0)) * (1 / IFNULL(e.num, 1)) + bil.af_income, 2)
                                            ELSE
                                                    TRUNCATE((IFNULL(p.price_up, 0) + IFNULL(pri.price_up, 0)) * IFNULL(aff.income_rate / 100, 1) * IFNULL(c.rate / 100, 1) + bil.af_income, 2)
                                            END AS af_price,
                                            CASE WHEN b.revenue_type = 1 and c.revenue_type = 4 THEN 0 ELSE 2 END
                                            INTO _price, _campaignid, _af_income, _status
                                    FROM
                                            up_campaigns AS c
                                            JOIN up_banners AS b ON b.campaignid = c.campaignid
                                            JOIN up_banners_billing AS bil ON b.bannerid = bil.bannerid
                                            LEFT JOIN up_ad_zone_keywords AS p ON p.campaignid = c.campaignid
                                            LEFT JOIN up_ad_zone_price pri ON(pri.ad_id=b.bannerid AND pri.zone_id = zid)
                                            LEFT JOIN up_affiliates aff ON(aff.affiliateid = b.affiliateid)
                                            LEFT JOIN up_affiliates_extend e ON (b.affiliateid = e.affiliateid AND b.revenue_type = e.revenue_type AND c.ad_type = e.ad_type)
                                    WHERE
                                            b.bannerid = bid
                                    AND p.id = pup_type;
                            END IF;
                        
                            IF _down_id > 0 THEN
                                -- 记录重复数据
                                INSERT INTO `up_repeat_log` (`campaignid`, `zoneid`, `cb`, `price`, `actiontime`, `target_type`, `target_cat`, `target_id`, `channel`, `af_income`, `status`, `repeat_type`, `repeat_log_id`)
                                                    VALUES (_campaignid, zid, cb, _price, UTC_TIMESTAMP(), targetType, targetCat, targetId, channel, _af_income, _status, 'down', _down_id);
                            ELSE
                                -- 记录下载
                                INSERT INTO up_down_log (campaignid,zoneid,cb,price,actiontime,target_type,target_cat,target_id, channel, af_income, `status`)
                                                VALUES(_campaignid, zid, cb, _price, UTC_TIMESTAMP(), targetType, targetCat, targetId, channel, _af_income, _status);
                                -- 记录LOG
                                INSERT INTO proc_mixing_log(cam_id, zone_id, price, time, `description`) VALUES(_campaignid, zid, _price, UTC_TIMESTAMP(), CONCAT('before down; info: ', _rollback));
                        
                            END IF;
                        
                    IF `_rollback` THEN
                            ROLLBACK;
                    ELSE
                            COMMIT;
                    END IF;
                END";
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
