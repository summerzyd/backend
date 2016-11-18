<?php
namespace App\Console;

use App\Console\Commands\JobDataAdx;
use App\Console\Commands\JobBannerCategoryMap;
use App\Console\Commands\JobMonitorForEveryDay;
use App\Console\Commands\TempAddIndex2ClickLogDownLog;
use App\Console\Commands\TempAssignUserRole;
use App\Console\Commands\JobAdStartSuspendEmail;
use App\Console\Commands\JobBalanceLog;
use App\Console\Commands\JobBannerBilling;
use App\Console\Commands\JobDataException;
use App\Console\Commands\JobDataHourTotal;
use App\Console\Commands\JobDeleteCacheExpired;
use App\Console\Commands\JobDeleteSogouDownloadLog;
use App\Console\Commands\JobImportDataFromEqqDaily;
use App\Console\Commands\JobMakeBalanceReminder;
use App\Console\Commands\JobMixingAmount;
use App\Console\Commands\JobMixingClientStats;
use App\Console\Commands\JobMixingManageAuditStats;
use App\Console\Commands\JobMixingManageStats;
use App\Console\Commands\JobMonitorFor10Minutes;
use App\Console\Commands\JobOperationClients;
use App\Console\Commands\JobRecoverCampaign;
use App\Console\Commands\JobRecoverCampaignWarning;
use App\Console\Commands\JobRecoverDailyData;
use App\Console\Commands\JobRefreshWhitebox;
use App\Console\Commands\JobRepairDeliveryData;
use App\Console\Commands\JobRepairManageStats;
use App\Console\Commands\JobRLogFlush;
use App\Console\Commands\JobPauseCampaigns;
use App\Console\Commands\JobPauseNotify;
use App\Console\Commands\JobPauseOrRecoverBanners;
use App\Console\Commands\JobPostToUUCUN;
use App\Console\Commands\JobSyncPMPCampaigns;
use App\Console\Commands\JobUpdateColumnFileClick;
use App\Console\Commands\JobUpdateColumnFileDown;
use App\Console\Commands\JobConsumptionTrend;
use App\Console\Commands\TempRepairCategoryZoneListType;
use App\Console\Commands\TempSql;
use App\Console\Commands\TempUpdateBannerPackageToAttach;
use App\Console\Commands\TempUpdateZoneType;
use App\Console\Commands\TempUpdateManagerRole;
use App\Console\Commands\TempRepairUserRole;
use App\Console\Commands\TempUpdateBalance;
use App\Console\Commands\TempUpdateDailyAf;
use App\Console\Commands\TempUpdateDailyClient;
use App\Console\Commands\JobDeliveryRepairLog;
use App\Console\Commands\TempUpdateBannerStatus;
use App\Console\Commands\JobSyncBalance;
use App\Console\Commands\JobOperationDetails;
use App\Console\Commands\JobClientBalance;
use App\Console\Commands\JobDailyReport;
use App\Console\Commands\TempYoukuAdx;
use App\Console\Commands\TempChinaMobileAdx;
use App\Console\Commands\TempIqiyiAdx;
use App\Console\Commands\TempUpdateAppId;
use App\Console\Commands\JobHourlySummaryStats;
use App\Console\Commands\JobMixingClientAuditData;
use App\Console\Commands\JobCheckYMOffer;
use App\Console\Commands\TempSohuAdx;
use App\Console\Commands\TempAttachBannerRelation;
use App\Console\Commands\JobWeeklyReport;
use App\Console\Commands\TempUUCun2TmpDownLog;
use App\Console\Commands\JobMonitorForOneHour;
use App\Console\Commands\JobUpdateAdxStatus;
use App\Console\Commands\JobUpdateDailyView;
use App\Console\Commands\TempRepairSougou;
use App\Console\Commands\TempUpdateAppStoreInfo;
use App\Console\Commands\TempRepairOperationDetail;
use App\Console\Commands\TempDeleteOperationData;
use App\Console\Commands\TempAddOperationData;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     * 规范：
     * 1、日志统一用$this->notice()函数写入到notice文件中
     * 2、继承Command.php的类
     * 3、handle函数不需要写start end日志，已在父类中定义
     *
     * @var array
     */
    protected $commands = [
        JobAdStartSuspendEmail::class,
        JobBalanceLog::class,
        JobBannerBilling::class,
        JobDataException::class,
        JobDataHourTotal::class,
        JobDeleteCacheExpired::class,
        JobDeleteSogouDownloadLog::class,
        JobImportDataFromEqqDaily::class,
        JobMakeBalanceReminder::class,
        JobMixingAmount::class,
        JobMixingClientStats::class,
        JobMixingManageAuditStats::class,
        JobMixingManageStats::class,
        JobMonitorFor10Minutes::class,
        JobOperationClients::class,
        JobRecoverCampaign::class,
        JobRecoverCampaignWarning::class,
        JobRecoverDailyData::class,
        JobRefreshWhitebox::class,
        JobRepairDeliveryData::class,
        JobRepairManageStats::class,
        JobRLogFlush::class,
        JobPauseCampaigns::class,
        JobDeliveryRepairLog::class,
        JobPauseOrRecoverBanners::class,
        JobPostToUUCUN::class,
        JobSyncPMPCampaigns::class,
        JobUpdateColumnFileClick::class,
        JobUpdateColumnFileDown::class,
        JobDeliveryRepairLog::class,
        JobSyncBalance::class,
        JobOperationDetails::class,
        JobPauseNotify::class,
        JobDeliveryRepairLog::class,
        JobClientBalance::class,
        JobDailyReport::class,
        JobWeeklyReport::class,
        JobMonitorForEveryDay::class,
        JobMonitorForOneHour::class,
        JobMixingClientAuditData::class,
        JobCheckYMOffer::class,
        JobUpdateAdxStatus::class,
        JobDataAdx::class,
        JobBannerCategoryMap::class,
        JobConsumptionTrend::class,
        JobUpdateDailyView::class,
        TempAssignUserRole::class,
        TempAddIndex2ClickLogDownLog::class,
        TempUpdateManagerRole::class,
        TempUpdateZoneType::class,
        TempRepairUserRole::class,
        TempUpdateBalance::class,
        TempUpdateBannerStatus::class,
        TempUpdateDailyAf::class,
        TempUpdateDailyClient::class,
        TempRepairCategoryZoneListType::class,
        TempUpdateBannerPackageToAttach::class,
        TempUpdateAppId::class,
        TempYoukuAdx::class,
        TempChinaMobileAdx::class,
        TempSohuAdx::class,
        TempIqiyiAdx::class,
        JobHourlySummaryStats::class,
        TempAttachBannerRelation::class,
        TempSql::class,
        TempUUCun2TmpDownLog::class,
        TempRepairSougou::class,
        TempUpdateAppStoreInfo::class,
        TempRepairOperationDetail::class,
        TempDeleteOperationData::class,
        TempAddOperationData::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        //数据大幅度异常提醒前一小时的数据与前一天同比的振幅过高时邮件提醒相关人员
        $schedule->command('job_data_exception')->cron('9 */1 * * *');
        //启动或暂停广告(配置后)发送邮件给指定配置人员
        $schedule->command('job_ad_start_suspend_email')->cron('15 * * * *');
        //应用汇白名单筛选，请求后将名单刷入redis，广告请求时不返回名单内的包
        $schedule->command('job_refresh_whitebox')->cron('0 */1 * * *');
        
        //data_hourly_daily_client -> balance_log 生成balance_log记录
        $schedule->command('job_balance_log')->cron('11 1 * * *');
        //发送账号余额不足邮件
        $schedule->command('job_make_balance_reminder')->cron('30 4 * * *');
        //每小时的数据监控邮件
        $schedule->command('job_data_hour_total')->cron('15 10-23 * * *');
        
        //刷新banners_billing数据
        $schedule->command('job_banner_billing')->cron('* * * * *');
        //暂停广告并发送相关邮件
        $schedule->command('job_pause_campaigns')->cron('*/3 * * * *');
        //激活因日限额暂停的广告投放
        $schedule->command('job_recover_campaign')->cron('6 0 * * *');
        //激活失败的邮件提醒
        $schedule->command('job_recover_campaign_warning')->cron('15 0 * * *');
        //暂停banner
        $schedule->command('job_pause_recover_banners pause')->cron('*/3 * * * *');
        //激活banner
        $schedule->command('job_pause_recover_banners recover')->cron('25 1 * * *');
        
        //生成媒体审计数据operation_details
        $schedule->command('job_operation_details')->cron('0 */1 * * *');
        //生成前一天的可用广告审计数据
        $schedule->command('job_operation_clients')->cron('5 0 * * *');
        //每月15号月结，结算前一个月的balance
        $schedule->command('job_sync_balance')->cron('30 4 15 * *');
        
        //修复人工录入数据（manual_deliverydata），调用job_mixing_amount，job_repair_manage_stats
        $schedule->command('job_mixing_manage_stats')->cron('*/10 * * * *');
        //处理媒体审计数据，调用job_repair_manage_stats
        $schedule->command('job_mixing_manage_audit_stats')->cron('20 */1 * * *');
        
        //处理manual_deliverydata，数据插入delivery_repair_log
        $schedule->command('job_repair_delivery_data')->cron('0 * * * *');
        //delivery_repair_log 每小时更新一次，修复delivery_log || down_log || click_log的数据
        $schedule->command('job_delivery_repair_log')->cron('5 * * * *');

        //同步数据 delivery_log -> data_summary_ad_hourly
        //调用job_ranking，job_operation_details，job_balance_log
        $schedule->command("job_repair_manage_stats --build-date={$yesterday}")->cron('58 9 * * *');
        $schedule->command("job_repair_manage_stats --days=11")->cron('05 14 * * *');
        //刷新hourly表file_click列，数据download_log -> data_summary_ad_hourly
        //$schedule->command('job_update_column_file_click')->cron('10 */1 * * *');     # 取消无效的job
        //刷新hourly表file_down列，数据download_accomplished -> data_summary_ad_hourly
        //$schedule->command('job_update_column_file_down')->cron('30 * * * *');
        
        //同步数据 data_summary_ad_hourly -> data_hourly_daily
        // 运营反馈说要方便看当天是否跑超过 还是要执行
        $schedule->command('job_recover_daily_data --days=1')->cron('0 * * * *');
        //每小时刷新近一天内审计过的广告主数据
        $schedule->command('job_mixing_client_audit_data')->cron('10 * * * *');
        
        //上报uucun的数据
        $schedule->command('job_post_to_uucun --afid=20')->cron('0 8 * * *');
        //同步pmp数据到adn
        $schedule->command('job_sync_pmp_campaigns')->cron('* * * * *');
        //删除redisCacheMysql数据库的过期数据
        $schedule->command('job_delete_cache_expired')->cron('*/10 * * * *');
        
        //从广点通同步数据至up_manual_deliverydata
        $schedule->command('job_import_data_from_eqq_daily 1')->cron('48 9 * * *');
        $schedule->command('job_import_data_from_eqq_daily 7')->cron('53 9 * * *');
        
        //banner暂停通知第三方 -> 紫贝壳afid为85
        $schedule->command('job_pause_notify')->cron('0 * * * *');
        //刷新api redis数据至log
        $schedule->command('job_rlog_flush')->cron('* * * * *');
        //删除sogou上报的记录 up_delivery_log
        $schedule->command('job_delete_sogou_download_log')->cron('30 6 * * *');
        //监控余额变化，插入数据表up_client_balance_change中，并发送监控邮件
        $schedule->command('job_client_balance')->cron('0 15 * * *');
        //每小时生成hourly数据，包括展示量，下载量，点击量和下载完成等等
        //$schedule->command('job_hourly_summary_stats')->cron('0 */1 * * *');  # billing 已实时写入hourly
        //发送邮件每日报表
        $schedule->command('job_daily_report')->cron('0 1 * * *');
        //发送邮件周报
        $schedule->command('job_weekly_report')->weekly()->mondays()->at('1:00');
        //监听Yeahmobi的广告限额
        $schedule->command('job_check_ym_offer')->cron('*/2 * * * *');
        // 每天执行一次的job汇总
        $schedule->command('job_monitor_for_every_day')->cron('0 3 * * *');
        //监听没有建立关联广告
        $schedule->command('job_monitor_for_10_minutes')->cron('20 */1 * * *');
        //每小时的第15分钟比较广告主的余额
        $schedule->command('job_monitor_for_one_hour')->cron('15 */1 * * *');
        //每天9点增加优酷adx报表数据
        $schedule->command('job_data_adx')->cron('0 9 * * *');

        //没5分钟查询素材审核状态信息
        $schedule->command('job_update_adx_status')->cron('*/5 * * * *');
        //每5分钟更新一下banner和category之间的映射数据
        $schedule->command('job_banner_category_map')->cron('*/5 * * * *');
        //每月30日更新视图，创建月表
        $schedule->command('job_update_daily_view')->cron('1 0 1 * *');
    }
}
