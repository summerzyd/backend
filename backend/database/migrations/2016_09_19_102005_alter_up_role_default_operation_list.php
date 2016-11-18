<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUpRoleDefaultOperationList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
      -- 销售
        UPDATE up_roles set operation_list = 'manager-sale-overview,manager-advertiser,manager-super-account-self,manager-stat,manager-stats-audit-client,manager-bd-self,manager-sum_views,manager-sum_clicks,manager-cpd,manager-sum_cpa,manager-sum_consum,manager-sum_cpc_clicks,manager-sum_revenue,manager-sum_revenue_gift,manager-sum_revenue_client,manager-profile,manager-password'
        WHERE id = 1001;
        -- 媒介
        UPDATE up_roles set operation_list = 'manager-trafficker-overview,manager-trafficker,manager-trafficker-account-self,manager-stat,manager-stats-audit-trafficker,manager-pub-self,manager-sum_views,manager-sum_download_requests,manager-sum_clicks,manager-ctr,manager-media_cpd,manager-ecpm,manager-sum_cpa,manager-sum_cpc_clicks,manager-cpc_ctr,manager-sum_payment,manager-sum_payment_gift,manager-sum_payment_trafficker,manager-profile,manager-password'
        where id = 1002;
        -- 财务
        UPDATE up_roles set operation_list = 'manager-home,manager-advertiser,manager-super-account-all,manager-broker,manager-trafficker,manager-trafficker-account-all,manager-stat,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-pub-all,manager-bd-all,manager-sum_views,manager-sum_download_requests,manager-sum_clicks,manager-ctr,manager-cpd,manager-media_cpd,manager-ecpm,manager-sum_cpa,manager-sum_consum,manager-sum_cpc_clicks,manager-cpc_ctr,manager-sum_revenue,manager-sum_revenue_gift,manager-sum_payment,manager-sum_payment_gift,manager-sum_revenue_client,manager-sum_payment_trafficker,manager-profit,manager-profit_rate,manager-sum_download_complete,manager-balance,manager-recharge-audit,manager-profile,manager-password'
        WHERE id = 1003;
        -- 运营
        UPDATE up_roles set operation_list = 'manager-home,manager-advertiser,manager-super-account-all,manager-broker,manager-trafficker,manager-trafficker-account-all,manager-stat,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-add_delivery_data,manager-pub-all,manager-bd-all,manager-sum_views,manager-sum_download_requests,manager-sum_clicks,manager-ctr,manager-cpd,manager-media_cpd,manager-ecpm,manager-sum_cpa,manager-sum_consum,manager-sum_cpc_clicks,manager-cpc_ctr,manager-sum_revenue,manager-sum_revenue_gift,manager-sum_payment,manager-sum_payment_gift,manager-sum_revenue_client,manager-sum_payment_trafficker,manager-profit,manager-profit_rate,manager-sum_download_complete,manager-audit,manager-trafficker-audit,manager-audit_check,manager-client_audit,manager-client_audit-check,manager-message,manager-campaign,manager-package,manager-profile,manager-password'
        where id = 1005;
        -- 审计
        UPDATE up_roles set operation_list = 'manager-audit,manager-trafficker-audit,manager-audit_check,manager-client_audit,manager-client_audit-check,manager-profile,manager-password'
        where id = 1006;
        -- 管理员
        UPDATE up_roles set operation_list = 'manager-home,manager-trafficker-overview,manager-sale-overview,manager-advertiser,manager-super-account-all,manager-super-account-self,manager-broker,manager-trafficker,manager-trafficker-account-all,manager-trafficker-account-self,manager-stat,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-add_delivery_data,manager-pub-all,manager-pub-self,manager-bd-all,manager-bd-self,manager-sum_views,manager-sum_download_requests,manager-sum_clicks,manager-ctr,manager-cpd,manager-media_cpd,manager-ecpm,manager-sum_cpa,manager-sum_consum,manager-sum_cpc_clicks,manager-cpc_ctr,manager-sum_revenue,manager-sum_revenue_gift,manager-sum_payment,manager-sum_payment_gift,manager-sum_revenue_client,manager-sum_payment_trafficker,manager-profit,manager-profit_rate,manager-sum_download_complete,manager-balance,manager-recharge-audit,manager-recharge,manager-audit,manager-trafficker-audit,manager-audit_check,manager-client_audit,manager-client_audit-check,manager-message,manager-mail-report-view,manager-weekly-report-view,manager-campaign,manager-package,manager-account,manager-sdk,manager-profile,manager-password,manager-setting'
        where id = 1004;
        -- 总经理
        UPDATE up_roles set operation_list = 'manager-home,manager-trafficker-overview,manager-sale-overview,manager-advertiser,manager-super-account-all,manager-super-account-self,manager-broker,manager-trafficker,manager-trafficker-account-all,manager-trafficker-account-self,manager-stat,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-add_delivery_data,manager-pub-all,manager-pub-self,manager-bd-all,manager-bd-self,manager-sum_views,manager-sum_download_requests,manager-sum_clicks,manager-ctr,manager-cpd,manager-media_cpd,manager-ecpm,manager-sum_cpa,manager-sum_consum,manager-sum_cpc_clicks,manager-cpc_ctr,manager-sum_revenue,manager-sum_revenue_gift,manager-sum_payment,manager-sum_payment_gift,manager-sum_revenue_client,manager-sum_payment_trafficker,manager-profit,manager-profit_rate,manager-sum_download_complete,manager-balance,manager-recharge-audit,manager-recharge,manager-audit,manager-trafficker-audit,manager-audit_check,manager-client_audit,manager-client_audit-check,manager-message,manager-mail-report-view,manager-weekly-report-view,manager-campaign,manager-package,manager-account,manager-sdk,manager-profile,manager-password,manager-setting'
        where id = 1007;
        -- CEO
        UPDATE up_roles set operation_list = 'manager-home,manager-trafficker-overview,manager-sale-overview,manager-advertiser,manager-super-account-all,manager-super-account-self,manager-broker,manager-trafficker,manager-trafficker-account-all,manager-trafficker-account-self,manager-stat,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-add_delivery_data,manager-pub-all,manager-pub-self,manager-bd-all,manager-bd-self,manager-sum_views,manager-sum_download_requests,manager-sum_clicks,manager-ctr,manager-cpd,manager-media_cpd,manager-ecpm,manager-sum_cpa,manager-sum_consum,manager-sum_cpc_clicks,manager-cpc_ctr,manager-sum_revenue,manager-sum_revenue_gift,manager-sum_payment,manager-sum_payment_gift,manager-sum_revenue_client,manager-sum_payment_trafficker,manager-profit,manager-profit_rate,manager-sum_download_complete,manager-balance,manager-recharge-audit,manager-recharge,manager-audit,manager-trafficker-audit,manager-audit_check,manager-client_audit,manager-client_audit-check,manager-message,manager-mail-report-view,manager-weekly-report-view,manager-campaign,manager-package,manager-account,manager-sdk,manager-profile,manager-password,manager-setting'
        where id = 1008;
        -- COO
        UPDATE up_roles set operation_list = 'manager-home,manager-trafficker-overview,manager-sale-overview,manager-advertiser,manager-super-account-all,manager-super-account-self,manager-broker,manager-trafficker,manager-trafficker-account-all,manager-trafficker-account-self,manager-stat,manager-stats-income-trafficker,manager-stats-income-client,manager-stats-audit-trafficker,manager-stats-audit-client,manager-add_delivery_data,manager-pub-all,manager-pub-self,manager-bd-all,manager-bd-self,manager-sum_views,manager-sum_download_requests,manager-sum_clicks,manager-ctr,manager-cpd,manager-media_cpd,manager-ecpm,manager-sum_cpa,manager-sum_consum,manager-sum_cpc_clicks,manager-cpc_ctr,manager-sum_revenue,manager-sum_revenue_gift,manager-sum_payment,manager-sum_payment_gift,manager-sum_revenue_client,manager-sum_payment_trafficker,manager-profit,manager-profit_rate,manager-sum_download_complete,manager-balance,manager-recharge-audit,manager-recharge,manager-audit,manager-trafficker-audit,manager-audit_check,manager-client_audit,manager-client_audit-check,manager-message,manager-mail-report-view,manager-weekly-report-view,manager-campaign,manager-package,manager-account,manager-sdk,manager-profile,manager-password,manager-setting'
        where id = 1009;
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
