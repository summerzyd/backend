<?php

use Zend\I18n\Validator\PostCode;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return Request::all();
});

// 公共功能
$app->post('site/login', 'SiteController@login');
$app->post('site/logout', 'SiteController@logout');
$app->get('site/is_login', 'SiteController@isLogin');
$app->post('site/change', 'SiteController@change');
$app->get('site/captcha', 'SiteController@captcha');
$app->post('site/password', 'SiteController@password');
$app->post('site/profile', 'SiteController@profile');
$app->get('site/profile_view', 'SiteController@profileView');
$app->get('site/nav', 'SiteController@nav');
$app->post('site/notice_list', 'SiteController@noticeList');//消息列表
$app->post('site/notice_store', 'SiteController@noticeStore');//删除消息
$app->get('site/activity', 'SiteController@activity');//预览优惠活动
$app->get('site/qiniu_token', 'SiteController@qiniuToken');
$app->post('site/delete_file', 'SiteController@deleteFile');
$app->get('site/platform', 'SiteController@platform');
$app->get('site/account_sub_type', 'SiteController@accountSubType');
$app->get('site/operation', 'SiteController@operation');
$app->post('site/change_kind', 'SiteController@changeKind');

$app->post('common/affiliate_revenue_type', 'CommonController@affiliateRevenueType');

// api
$app->group(['prefix' => 'interface', 'namespace' => 'App\Http\Controllers\Api'], function () use ($app) {
    $app->get('stat/hourly_impression', ['uses' => 'StatController@hourlyImpression']);//后台主页情况
    $app->post(
        'stat/affiliate_user_report',
        ['uses' => 'StatController@affiliateUserReport']
    );//获取媒介概览日活、留存率等
});

//广告主,在分组里统一把命名空间写上，不需要每个地方都重复写 edit arke.wu 2016-01-28 21:15:03
$app->group(['prefix' => 'advertiser', 'namespace' => 'App\Http\Controllers\Advertiser'], function () use ($app) {
    $app->get('common/balance_value', 'CommonController@balanceValue');//获取广告主账户余额
    $app->post('common/sales', 'CommonController@sales');//获取广告主账户余额

    $app->post('campaign/index', 'CampaignController@index');//查看某个推广计划
    $app->post('campaign/store', 'CampaignController@store');//增加/修改推广计划
    $app->post('campaign/view', 'CampaignController@view'); //查看某个推广计划
    $app->post('campaign/delete', 'CampaignController@delete'); //查看某个推广计划
    $app->get('campaign/column_list', 'CampaignController@columnList'); //查看某个推广计划
    $app->get('campaign/money_limit', 'CampaignController@moneyLimit');
    $app->get('campaign/demand', 'CampaignController@demand');//获取banner广告尺寸和插屏广告尺寸
    $app->post('campaign/product_list', 'CampaignController@productList');
    $app->get('campaign/app_store_view', 'CampaignController@appStoreView');
    $app->get('campaign/revenue_type', 'CampaignController@revenueType');//获取计费类型
    $app->post('campaign/product_exist', 'CampaignController@productExist');//检测产品是否存在
    $app->post('campaign/update', 'CampaignController@update');//广告主暂停，继续投放
    $app->post('campaign/app_list', 'CampaignController@appList');//获取应用列表
    $app->post('campaign/self_store', 'CampaignController@selfStore');//自营新增修改推广计划
    $app->post('campaign/self_view', 'CampaignController@selfView');//查看自营推广计划

    $app->post('keywords/index', 'KeywordsController@index');
    $app->post('keywords/store', 'KeywordsController@store');
    $app->post('keywords/delete', 'KeywordsController@delete');

    $app->post('zone/index', 'ZoneController@index');//广告位加价信息

    $app->get('balance/balance_log', 'BalanceController@balanceLog');
    $app->post('balance/payout', 'BalanceController@payout');
    $app->post('balance/recharge', 'BalanceController@recharge');
    $app->post('balance/self_recharge', 'BalanceController@selfRecharge'); //充值，赠送明细
    $app->post('balance/recharge_invoice', 'BalanceController@rechargeInvoice');
    $app->post('balance/invoice_history', 'BalanceController@invoiceHistory');
    $app->get('balance/invoice', 'BalanceController@invoice');   //4.2 获取发票明细

    $app->get('pay/activity', 'PayController@activity');         //4.3 获取支付活动列表
    $app->post('invoice/store', 'InvoiceController@store');          //4.4 提交开票申请
    $app->get('pay/receiver_info', 'PayController@receiverInfo'); //4.5 取广告主的收件信息

    $app->post('pay/store', 'PayController@store'); //4.6 支付宝充值
    $app->get('pay/alipayReturn', [
        'as' => 'alipayReturn',
        'uses' => 'PayController@alipayReturn',
    ]); //支付宝充值同步通知
    //post
    $app->post('pay/alipayNotify', [
        'as' => 'alipayNotify',
        'uses' => 'PayController@alipayNotify'
    ]); //支付宝充值异步通知

    $app->post('account/index', 'AccountController@index');
    $app->post('account/store', 'AccountController@store');//新增账号
    $app->post('account/update', 'AccountController@update');

    $app->get('stat/index', 'StatController@index'); //报表需求
    $app->get('stat/campaign_excel', 'StatController@campaignExcel'); //报表导出
    $app->get('stat/daily_campaign_excel', 'StatController@dailyCampaignExcel'); //每日报表导出
    $app->get('stat/time_campaign_excel', 'StatController@timeCampaignExcel'); //按时间导出报表
    $app->get('stat/report', 'StatController@report');//概览报表

    $app->get('stat/self_index', 'StatController@selfIndex');//自营广告主报表
    $app->get('stat/self_zone_excel', 'StatController@selfZoneExcel');//自营广告主导出报表
    $app->get('stat/self_report', 'StatController@selfReport');//自营广告主概览报表

});

//媒体商模块
$app->group(['prefix' => 'trafficker', 'namespace' => 'App\Http\Controllers\Trafficker'], function () use ($app) {
    $app->get('common/balance_value', 'CommonController@balanceValue');//获取账户余额
    $app->post('common/sales', 'CommonController@sales');//获取所属销售顾问
    $app->get('common/platform', 'CommonController@platform');//获取媒体支持平台
    $app->get('common/campaign_pending_audit', 'CommonController@campaignPendingAudit');
    $app->get('common/balance_pending_audit', 'CommonController@balancePendingAudit');

    /*广告管理*/
    $app->post('campaign/index', 'CampaignController@index');//广告管理主页
    $app->get('campaign/rank', 'CampaignController@rank');//获取广告等级
    $app->get('campaign/status', 'CampaignController@status');//获取广告状态
    $app->get('campaign/category', 'CampaignController@category');//获取应用分类
    $app->post('campaign/update', 'CampaignController@update');//修改媒体广告管理列表字段
    $app->post('campaign/check', 'CampaignController@check');//媒体审核和完善信息
    $app->post('campaign/self_index', 'CampaignController@selfIndex');//自营广告列表
    $app->post('campaign/self_check', 'CampaignController@selfCheck');//审核广告
    $app->post('campaign/zone_list', 'CampaignController@zoneList');//查看广告位加价列表
    $app->post('campaign/self_update', 'CampaignController@selfUpdate');//修改分类，等级

    $app->post('keywords/index', 'KeywordsController@index');//关键字加价列表
    $app->post('keywords/update', 'KeywordsController@update');//修改关键字类型

    /*广告位管理*/
    $app->post('zone/index', 'ZoneController@index');//广告位管理
    $app->post('zone/store', 'ZoneController@store');//新增，修改广告位
    $app->post('zone/check', 'ZoneController@check');//停用，启用广告位
    $app->post('zone/update', 'ZoneController@update');//修改广告位信息
    $app->post('zone/category_store', 'ZoneController@categoryStore');//分类管理增加/修改
    $app->post('zone/category_delete', 'ZoneController@categoryDelete');//分类管理删除
    $app->get('zone/module_list', 'ZoneController@moduleList');//模块列表
    $app->post('zone/module_store', 'ZoneController@moduleStore');//模块增加/修改
    $app->post('zone/module_delete', 'ZoneController@moduleDelete');//模块删除
    $app->get('zone/ad_type', 'ZoneController@adType');//返回媒体广告类型
    $app->get('zone/kind', 'ZoneController@getAffiliateKind');//返回账户类型

    $app->get('stat/menu', 'StatController@menu');//获取头部导航栏菜单
    $app->get('stat/column_list', 'StatController@columnList');//获取图表，报表显示字段
    $app->get('stat/zone', 'StatController@zone');//获取广告位数据
    $app->get('stat/client', 'StatController@client');//获取广告主数据
    $app->get('stat/campaign_excel', 'StatController@campaignExcel');//导出报表

    $app->get('stat/time_zone_excel', 'StatController@timeZoneExcel');//广告位导出每日报表
    $app->get('stat/time_campaign_excel', 'StatController@timeCampaignExcel');//广告导出每日报表

    $app->get('stat/daily_zone_excel', 'StatController@dailyZoneExcel');//广告位导出每日报表
    $app->get('stat/daily_campaign_excel', 'StatController@dailyCampaignExcel');//广告导出每日报表

    $app->get('stat/report', 'StatController@report');//获取30天概览报表
    $app->get('stat/zone_report', 'StatController@zoneReport');//获取概览-广告位收入
    $app->get('stat/client_report', 'StatController@clientReport');//概览-广告主消耗

    $app->get('stat/self_index', 'StatController@selfIndex');//媒体自营概览
    $app->get('stat/self_trend', 'StatController@selfTrend');//媒体自营概览-30天趋势
    $app->get('stat/self_zone', 'StatController@selfZone');//媒体自营报表
    $app->get('stat/self_zone_excel', 'StatController@selfZoneExcel');//媒体自营报表

    $app->get('stat/game_report', 'StatController@gameReport');//游戏报表
    $app->get('stat/game_report_excel', 'StatController@gameReportExcel');//导出游戏报表

    $app->get('balance/withdraw', 'BalanceController@withdraw');
    $app->get('balance/settlement', 'BalanceController@settlement');
    $app->get('balance/income', 'BalanceController@income');
    $app->get('balance/draw_balance', 'BalanceController@drawBalance');
    $app->post('balance/draw', 'BalanceController@draw');

    $app->post('balance/recharge_index', 'BalanceController@rechargeIndex');
    $app->post('balance/recharge_update', 'BalanceController@rechargeUpdate');
    $app->post('balance/gift_index', 'BalanceController@giftIndex');
    $app->post('balance/gift_update', 'BalanceController@giftUpdate');

    $app->post('advertiser/index', 'AdvertiserController@index');
    $app->get('advertiser/filter', 'AdvertiserController@filter');
    $app->post('advertiser/store', 'AdvertiserController@store');
    $app->post('advertiser/update', 'AdvertiserController@update');
    $app->post('advertiser/recharge_history', 'AdvertiserController@rechargeHistory');
    $app->post('advertiser/recharge_apply', 'AdvertiserController@rechargeApply');
    $app->post('advertiser/recharge_detail', 'AdvertiserController@rechargeDetail');
    $app->post('advertiser/gift_apply', 'AdvertiserController@giftApply');
    $app->post('advertiser/gift_detail', 'AdvertiserController@giftDetail');
    $app->get('advertiser/sales', 'AdvertiserController@sales');

    $app->post('broker/index', 'BrokerController@index');
    $app->post('broker/store', 'BrokerController@store');
    $app->post('broker/update', 'BrokerController@update');
    $app->post('broker/recharge_history', 'BrokerController@rechargeHistory');
    $app->post('broker/recharge_apply', 'BrokerController@rechargeApply');
    $app->post('broker/recharge_detail', 'BrokerController@rechargeDetail');
    $app->post('broker/gift_apply', 'BrokerController@giftApply');
    $app->post('broker/gift_detail', 'BrokerController@giftDetail');

    $app->post('account/index', 'AccountController@index');
    $app->get('account/filter', 'AccountController@filter');
    $app->post('account/store', 'AccountController@store');
    $app->post('account/update', 'AccountController@update');
    $app->post('account/delete', 'AccountController@delete');

    $app->post('role/index', 'RoleController@index');
    $app->get('role/filter', 'RoleController@filter');
    $app->post('role/store', 'RoleController@store');
    $app->get('role/operation_list', 'RoleController@operationList');
    
    $app->post('manual/import', 'ManualController@manualImport');
});


$app->group(['prefix' => 'broker', 'namespace' => 'App\Http\Controllers\Broker'], function () use ($app) {
    $app->get('common/balance_value', 'CommonController@balanceValue');//获取账户余额
    $app->post('common/sales', 'CommonController@sales');//获取所属销售顾问

    $app->post('advertiser/index', 'AdvertiserController@index');
    $app->post('advertiser/store', 'AdvertiserController@store');
    $app->post('advertiser/update', 'AdvertiserController@update');
    $app->post('advertiser/transfer', 'AdvertiserController@transfer');
    //获取代理商下的广告主账户余额 以及该代理商的账户余额
    $app->post('advertiser/balance_value', 'AdvertiserController@balanceValue');

    $app->get('campaign/column_list', 'CampaignController@columnList');//广告管理页表显示字段
    $app->get('campaign/revenue', 'CampaignController@revenue');//代理商广告出价
    $app->get('campaign/day_limit', 'CampaignController@dayLimit');//代理商广告日预算
    $app->post('campaign/index', 'CampaignController@index');//广告管理列表
    $app->get('campaign/revenue_type', 'CampaignController@revenueType');//代理商计费类型

    $app->post('keywords/index', 'KeywordsController@index');//关键字加价列表

    //账户明细
    $app->get('balance/recharge', 'BalanceController@recharge');//代理商充值账户明细
    $app->get('balance/gift', 'BalanceController@gift');//代理商赠送账户明细
    $app->get('balance/invoice_history', 'BalanceController@invoiceHistory');//代理商发票申请记录
    $app->post('balance/invoice_store', 'BalanceController@invoiceStore');//提交开票申请
    $app->get('balance/apply', 'BalanceController@apply');//代理商充值账户申请明细
    $app->post('balance/invoice', 'BalanceController@invoice');//获取发票明细

    
    $app->get('stat/index', 'StatController@index');// 获取广告数据stat/column_list
    $app->get('stat/report', 'StatController@report');// 获取概览数据
    $app->get('stat/column_list', 'StatController@columnList');//获取图表，报表显示字段
    $app->get('stat/campaign_excel', 'StatController@campaignExcel');//导出报表
    $app->get('stat/daily_campaign_excel', 'StatController@dailyCampaignExcel'); //每日报表导出
    $app->get('stat/time_campaign_excel', 'StatController@timeCampaignExcel');//导出每日报表

    $app->post('pay/store', 'PayController@store'); //4.6 支付宝充值
    $app->get('pay/alipayReturn', [
        'as' => 'alipayReturn',
        'uses' => 'PayController@alipayReturn',
    ]); //支付宝充值同步通知
    //post
    $app->post('pay/alipayNotify', [
        'as' => 'alipayNotify',
        'uses' => 'PayController@alipayNotify'
    ]); //支付宝充值异步通知

});

$app->group(['prefix' => 'manager', 'namespace' => 'App\Http\Controllers\Manager'], function () use ($app) {
    $app->get('common/balance_value', 'CommonController@balanceValue');//获取账户余额
    $app->post('common/log_index', 'CommonController@logIndex');//日志列表
    $app->post('common/log_store', 'CommonController@logStore');//保存人为备忘录

    $app->post('advertiser/index', 'AdvertiserController@index');
    $app->get('advertiser/filter', 'AdvertiserController@filter');
    $app->post('advertiser/store', 'AdvertiserController@store');
    $app->post('advertiser/update', 'AdvertiserController@update');
    $app->post('advertiser/recharge_history', 'AdvertiserController@rechargeHistory');//充值记录
    $app->post('advertiser/recharge_apply', 'AdvertiserController@rechargeApply');//充值申请
    $app->post('advertiser/recharge_detail', 'AdvertiserController@rechargeDetail');//充值明细
    $app->post('advertiser/gift_apply', 'AdvertiserController@giftApply');//赠送申请
    $app->post('advertiser/gift_detail', 'AdvertiserController@giftDetail');//赠送明细
    $app->get('advertiser/view', 'AdvertiserController@view');

    $app->post('broker/index', 'BrokerController@index');
    $app->get('broker/filter', 'BrokerController@filter');
    $app->post('broker/store', 'BrokerController@store');
    $app->post('broker/update', 'BrokerController@update');
    $app->post('broker/recharge_history', 'BrokerController@rechargeHistory');
    $app->post('broker/recharge_apply', 'BrokerController@rechargeApply');
    $app->post('broker/recharge_detail', 'BrokerController@rechargeDetail');
    $app->post('broker/gift_apply', 'BrokerController@giftApply');
    $app->post('broker/gift_detail', 'BrokerController@giftDetail');
    $app->get('broker/view', 'BrokerController@view');

    $app->post('trafficker/index', 'TraffickerController@index');
    $app->get('trafficker/filter', 'TraffickerController@filter');
    $app->post('trafficker/store', 'TraffickerController@store');
    $app->post('trafficker/update', 'TraffickerController@update');
    $app->get('trafficker/sales', 'TraffickerController@sales');

    $app->post('account/index', 'AccountController@index');
    $app->post('account/store', 'AccountController@store');
    $app->post('account/update', 'AccountController@update');

    $app->post('campaign/index', 'CampaignController@index');//广告管理列表
    $app->post('campaign/check', 'CampaignController@check');//广告审核

    $app->post('campaign/info', 'CampaignController@info');//获取审核信息
    $app->get('campaign/revenue', 'CampaignController@revenue');//获取所有广告出价
    $app->get('campaign/day_limit', 'CampaignController@dayLimit');//获取所有日限额
    $app->post('campaign/update', 'CampaignController@update');//更新广告信息
    $app->post('campaign/revenue_history', 'CampaignController@revenueHistory');//广告主历史出价
    $app->post('campaign/store', 'CampaignController@store');//CPA/CPT广告新增
    $app->post('campaign/client_list', 'CampaignController@clientList');//获取广告主列表
    $app->post('campaign/product_list', 'CampaignController@productList');//获取广告主产品列表
    $app->post('campaign/equivalence_list', 'CampaignController@equivalenceList');//等价关系管理列表
    $app->post('campaign/equivalence', 'CampaignController@equivalence');//删除和建立等价关系
    $app->post('campaign/consume', 'CampaignController@consume');//统计日消耗和总消耗
    $app->post('banner/revenue_type', 'BannerController@revenueType');//获取计费类型
    $app->post('banner/affiliate', 'BannerController@affiliate');//获取媒体商信息
    $app->post('banner/affiliate_update', 'BannerController@affiliateUpdate');//媒体商信息修改
    $app->post('banner/category', 'BannerController@category');//获取媒体商分类
    $app->post('banner/rank', 'BannerController@rank');//获取媒体商等级
    $app->post('banner/release', 'BannerController@release');//投放
    $app->post('banner/app_search', 'BannerController@appSearch');//AppId查询
    $app->post('banner/app_update', 'BannerController@appUpdate');//AppId更新
    $app->post('banner/client_package', 'BannerController@clientPackage');//广告渠道包管理列表
    $app->get('banner/trend', 'BannerController@trend');//消耗趋势
    $app->post('campaign/word_list', 'CampaignController@wordList');
    $app->post('campaign/word_new', 'CampaignController@wordNew');
    $app->post('campaign/word_modify', 'CampaignController@wordModify');
    $app->post('campaign/word_delete', 'CampaignController@wordDelete');
    $app->get('campaign/trend', 'CampaignController@trend');//消耗趋势

    $app->post('keyword/index', 'KeywordController@index');//平台账号获取关键字
    $app->post('keyword/store', 'KeywordController@store');//平台账号新增关键字
    $app->post('keyword/delete', 'KeywordController@delete');//平台账号删除关键字

    $app->post('material/index', 'MaterialController@index');//获取素材列表
    $app->post('material/check', 'MaterialController@check');//素材审核
    $app->post('material/view', 'MaterialController@view');//查看素材信息

    $app->post('common/choose_package', 'CommonController@choosePackage');//选择渠道包\
    $app->post('common/sales', 'CommonController@sales');//销售顾问
    $app->post('common/operation', 'CommonController@operation');//运营顾问
    $app->post('common/account_type', 'CommonController@accountType');//销售顾问,运营顾问
    $app->get('common/package_not_latest', 'CommonController@packageNotLatest');//非市场最新包
    //广告审核数和素材审核数量
    $app->get('common/campaign_pending_audit', 'CommonController@campaignPendingAudit');

    $app->post('pack/index', 'PackController@index');//渠道包管理列表
    $app->post('pack/client_package', 'PackController@clientPackage');//广告渠道包管理列表
    $app->post('pack/delivery_affiliate', 'PackController@deliveryAffiliate');//获取可投放媒体
    $app->post('pack/update', 'PackController@update');//修改渠道包信息
    $app->post('pack/upload_callback', 'PackController@uploadCallback');//安装包上传回调

    $app->post('balance/income', 'BalanceController@income');
    $app->post('balance/recharge_index', 'BalanceController@rechargeIndex');
    $app->post('balance/recharge_update', 'BalanceController@rechargeUpdate');
    $app->post('balance/invoice_index', 'BalanceController@invoiceIndex');
    $app->post('balance/invoice_update', 'BalanceController@invoiceUpdate');
    $app->post('balance/invoice_detail', 'BalanceController@invoiceDetail');
    $app->post('balance/gift_index', 'BalanceController@giftIndex');
    $app->post('balance/gift_update', 'BalanceController@giftUpdate');
    $app->post('balance/withdrawal_index', 'BalanceController@withdrawalIndex');
    $app->post('balance/withdrawal_update', 'BalanceController@withdrawalUpdate');
    $app->post('balance/income_index', 'BalanceController@incomeIndex');
    $app->post('balance/trafficker_index', 'AuditController@traffickerIndex');
    $app->get('balance/trafficker_export', 'AuditController@traffickerExport');//媒体商数据导出
    $app->post('balance/trafficker_import', 'AuditController@traffickerImport');//媒体商数据导入
    $app->post('balance/trafficker_update', 'AuditController@traffickerUpdate');//媒体商审计更新
    
    
    $app->post('audit/trafficker_index', 'AuditController@traffickerIndex');//获取媒体商数据审计
    $app->get('audit/trafficker_export', 'AuditController@traffickerExport');//媒体商数据导出
    $app->post('audit/trafficker_import', 'AuditController@traffickerImport');//媒体商数据导入
    $app->post('audit/trafficker_update', 'AuditController@traffickerUpdate');//媒体商审计更新
    $app->post('audit/advertiser_index', 'AuditController@advertiserIndex');//获取广告主数据
    $app->post('audit/advertiser_update', 'AuditController@advertiserUpdate');//更新
    $app->post('audit/advertiser_update_batch', 'AuditController@advertiserUpdateBatch');//批量更新
    $app->post('audit/advertiser_delivery', 'AuditController@advertiserDelivery');//查看投放数据
    $app->post('audit/expense_data', 'AuditController@expenseData'); //获取需要审计的数据
    $app->post('audit/pass', 'AuditController@pass'); //通过审计
    
    $app->post('activity/index', 'ActivityController@index');//获取活动列表
    $app->post('activity/get', 'ActivityController@get');//获取单个活动信息
    $app->post('activity/store', 'ActivityController@store');//发布活动
    $app->post('activity/deal', 'ActivityController@deal');//活动上下线
    $app->post('activity/report_list', 'ActivityController@reportList');//日报信息列表
//    $app->get('activity/account_list', 'ActivityController@accountList');//账户列表
//    $app->post('activity/update_mail_receiver', 'ActivityController@updateMailReceiver');//取消，添加权限
//    $app->post('activity/pause_send_mail', 'ActivityController@pauseSendMail');//暂停发送邮件
    $app->post('activity/resend_mail', 'ActivityController@resendMail');//重发日报邮件
    
    $app->post('notice/index', 'NoticeController@index');//获取站内信列表
    $app->post('notice/store', 'NoticeController@store');//发送站内信
    
    $app->post('notice/email_index', 'NoticeController@emailIndex');//获取邮件列表
    $app->post('notice/email_client', 'NoticeController@emailClient');//获取广告主清单
    $app->post('notice/email_store', 'NoticeController@emailStore');//发送或更新邮件
    $app->post('notice/email_delete', 'NoticeController@emailDelete');//删除邮件

    $app->post('game/index', 'GameController@index');//录数列表
    $app->post('game/game_store', 'GameController@gameStore');//新增游戏
    $app->post('game/game_index', 'GameController@gameIndex');//游戏列表
    $app->post('game/store', 'GameController@store');//新增录数
    $app->post('game/delete', 'GameController@delete');//删除录数
    $app->post('game/filter', 'GameController@filter');//过滤列表
    $app->post('game/client_list', 'GameController@clientList');//过滤列表
    $app->post('game/affiliate_list', 'GameController@affiliateList');//过滤列表
    $app->post('game/game_import', 'GameController@gameImport');//导入数据

    $app->get('stat/index', 'StatController@index');//概览报表
    $app->get('stat/trend', 'StatController@trend');//概览变化趋势
    $app->get('stat/rank', 'StatController@rank');//概览广告排序
    $app->get('stat/trafficker_trend', 'StatController@traffickerTrend');//媒介概览变化趋势
    $app->get('stat/trafficker_daily', 'StatController@traffickerDaily');//媒介概览日新增,日活及次日留存
    $app->get('stat/trafficker_week_retain', 'StatController@traffickerWeekRetain');//媒介概览七日留存
    $app->get('stat/trafficker_month', 'StatController@traffickerMonth');//媒介概览月新增,月活
    $app->get('stat/sale_trend', 'StatController@saleTrend');//销售概览变化趋势
    $app->get('stat/sale_rank', 'StatController@saleRank');//销售概览排行
    $app->get('stat/zone', 'StatController@zone');//媒体商报表
    $app->get('stat/zone_affiliate', 'StatController@zoneAffiliate');//媒体商报表-二级展开
    $app->get('stat/zone_excel', 'StatController@zoneExcel');//媒体商导出报表
    $app->get('stat/zone_daily_excel', 'StatController@zoneDailyExcel');//媒体商导出每日报表
    $app->get('stat/client', 'StatController@client');//广告主报表
    $app->get('stat/client_campaign', 'StatController@clientCampaign');//广告主报表-二级展开
    $app->get('stat/client_excel', 'StatController@clientExcel');//广告主导出报表
    $app->get('stat/client_daily_excel', 'StatController@clientDailyExcel');//广告主导出每日报表
    $app->post('stat/manual_data', 'StatController@manualData');//查看人工数据-媒体商
    $app->post('stat/manual_import', 'StatController@manualImport');//导入人工数据
    $app->post('stat/client_data', 'StatController@clientData');//查看广告主结算数据
    $app->post('stat/product', 'StatController@product');//查看广告主计算价-产品
    $app->post('stat/campaign', 'StatController@campaigns');//查看广告主结算价-广告
    $app->get('stat/adx_report', 'StatController@adxReport');//adx报表
    $app->get('stat/game_report', 'StatController@gameReport');//游戏报表
    $app->get('stat/game_report_excel', 'StatController@gameReportExcel');//导出游戏报表

    $app->post('setting/index', 'SettingController@index');//配置
    $app->post('setting/store', 'SettingController@store');//配置
});

$app->group(['prefix' => 'admin', 'namespace' => 'App\Http\Controllers\Admin'], function () use ($app) {
    $app->get('agency/index', 'AgencyController@index');

    $app->post('withdrawal/index', 'WithdrawalController@index');
    $app->post('withdrawal/update', 'WithdrawalController@update');

    $app->post('channel/index', 'ChannelController@index');
    $app->post('channel/store', 'ChannelController@store');
    $app->post('channel/update', 'ChannelController@update');
});
