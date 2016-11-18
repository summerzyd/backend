<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlertProcLogClick20160722 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 2.8.2 需求，添加CPA结费类型的广告，广告主消耗价设为 0 ,A->C : 广告主的A，媒体上的C，广告主按A结费，媒体按C计费
        $this->logClickAlter();
    }
    
    private function logClickAlter()
    {
        $sql = "DROP PROCEDURE IF EXISTS LogClick ";
        DB::getPdo()->exec($sql);

        $sql = "CREATE PROCEDURE `LogClick`(IN bid INT,IN zid INT,IN cb VARCHAR (20),IN targetType VARCHAR (255),IN targetCat VARCHAR (255),IN targetId VARCHAR (255),IN pup_type INT,IN channel VARCHAR (255))
    SQL SECURITY INVOKER
BEGIN
                        DECLARE _price DECIMAL (8, 2) DEFAULT 0.00;
                        DECLARE _campaignid INT DEFAULT 0;
                        DECLARE _af_income DECIMAL (8, 2) DEFAULT 0.0;
                        DECLARE `_rollback` BOOL DEFAULT 0;
												DECLARE `_status` INT DEFAULT 0; -- 是否已计费 A->C 不计费默认 0，其他为 2
                        DECLARE `isActive` BOOL DEFAULT 1;
                        DECLARE _click_id INT DEFAULT 0; -- 默认有效，不限制计入log
                        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET `_rollback` = 1;
                        
                        -- 数据转换
                        SET targetType = IFNULL(targetType, '');
                        SET targetCat = IFNULL(targetCat, '');
                        SET targetId = IFNULL(targetId, '');
                        SET channel = IFNULL(channel, '');
                        
                        
                        SELECT max(clickid) as clickid INTO _click_id FROM up_click_log AS cl LEFT JOIN up_campaigns AS c ON cl.campaignid = c.campaignid
                                LEFT JOIN up_banners AS b ON b.campaignid = c.campaignid
                                WHERE b.bannerid = bid AND cl.target_type = targetType AND cl.target_cat = targetCat AND cl.target_id = targetId
                                      AND cl.actiontime >= DATE_SUB(DATE_SUB(DATE_FORMAT(NOW(),'%Y-%m-%d 00:00:00'),INTERVAL 8 HOUR),INTERVAL IF(b.affiliateid=86, 1, 2) day) limit 1;
                        
                        START TRANSACTION;
                            -- 获取广告投放状态
                            SELECT IF(c.`status` = 0, 1, 0) INTO isActive FROM up_campaigns AS c
                                    JOIN up_banners AS b ON b.campaignid = c.campaignid
                                    WHERE b.bannerid = bid;
                            -- 投放中
                            IF isActive = 1 THEN
                                    IF pup_type = 0 THEN -- 广告位加价计费
                                            -- 计算引用的价格（包含广告位加价）
                                            SELECT
                                                    -- 如果广告是 A->D ,广告下载计费价设为0
                                                    CASE WHEN b.revenue_type = 2 AND c.revenue_type = 4 THEN 
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
																										CASE 
																											WHEN b.revenue_type = 2 and c.revenue_type = 4 THEN 0
																											WHEN b.revenue_type = 2 and c.revenue_type = 1 THEN 0
																											ELSE 2 END
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
                                    ELSE -- 关键词加价计费
                                            -- 计算引用的价格（包含关键词加价）
                                            SELECT
																										-- 如果广告是 A->D ,广告下载计费价设为0
                                                    CASE WHEN b.revenue_type = 2 AND c.revenue_type = 4 THEN 
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
																										CASE 
																											WHEN b.revenue_type = 2 and c.revenue_type = 4 THEN 0
																											WHEN b.revenue_type = 2 and c.revenue_type = 1 THEN 0
																											ELSE 2 END
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
                                    -- 存在重复的点击记录 记录到重复数据表中
                                    IF _click_id > 0 THEN
                                            INSERT INTO `up_repeat_log` (`campaignid`, `zoneid`, `cb`, `price`, `actiontime`, `target_type`, `target_cat`, `target_id`, `channel`, `af_income`, `status`, `repeat_type`, `repeat_log_id`)
                                                                VALUES (_campaignid, zid, cb, _price, UTC_TIMESTAMP(), targetType, targetCat, targetId, channel, _af_income, _status, 'click', _click_id);
                
                                    ELSE
                                            INSERT INTO up_click_log (campaignid,zoneid,cb,price,actiontime,target_type,target_cat,target_id, channel, af_income, `status`)
                                                                VALUES(_campaignid,zid,cb, _price,UTC_TIMESTAMP (), targetType, targetCat, targetId, channel, _af_income, _status);
                
                                    END IF;
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
