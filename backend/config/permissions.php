<?php
return [
    'site/notice_list' => 'advertiser-message,trafficker-message,broker-message,manager-message',
    'site/notice_store' => 'advertiser-message,trafficker-message,broker-message,manager-message',
    'site/activity' => 'advertiser-message,trafficker-message,broker-message,manager-message',

    'site/password' => 'advertiser-password,trafficker-password,broker-password,manager-password',

    'site/profile' => 'advertiser-profile,trafficker-profile,broker-profile,manager-profile',
    'site/profile_view' => 'advertiser-profile,trafficker-profile,broker-profile,manager-profile',

    'advertiser/campaign/index' => 'advertiser-campaign',
    'advertiser/campaign/store' => 'advertiser-campaign',
    'advertiser/campaign/update' => 'advertiser-campaign',
    'advertiser/campaign/view' => 'advertiser-campaign',
    'advertiser/campaign/delete' => 'advertiser-campaign',
    'advertiser/campaign/column_list' => 'advertiser-campaign',
    'advertiser/campaign/money_limit' => 'advertiser-campaign',
    'advertiser/campaign/demand' => 'advertiser-campaign,trafficker-zone,trafficker-self-zone',
    'advertiser/campaign/app_store_view' => 'advertiser-campaign',
    'advertiser/campaign/product_list' => 'advertiser-campaign',
    'advertiser/campaign/revenue_type' => 'advertiser-campaign',
    'advertiser/campaign/app_list' => 'advertiser-campaign',
    'advertiser/campaign/self_view' => 'advertiser-campaign',
    'advertiser/campaign/self_store' => 'advertiser-campaign',

    'advertiser/keywords/index' => 'advertiser-campaign',
    'advertiser/keywords/store' => 'advertiser-campaign',
    'advertiser/keywords/delete' => 'advertiser-campaign',
    'advertiser/zone/index' => 'advertiser-campaign',

    // 'advertiser/common/balance_value' => 'advertiser-balance', //右上角公用
    'advertiser/balance/balance_log' => 'advertiser-balance',
    'advertiser/balance/invoice' => 'advertiser-balance',
    'advertiser/pay/activity' => 'advertiser-balance',
    'advertiser/invoice/store' => 'advertiser-balance',
    'advertiser/pay/receiver_info' => 'advertiser-balance',
    'advertiser/pay/store' => 'advertiser-balance',
    'advertiser/pay/alipayReturn' => 'advertiser-balance',
    'advertiser/pay/alipayNotify' => 'advertiser-balance',

    'advertiser/account/index' => 'advertiser-account',
    'advertiser/account/store' => 'advertiser-account',
    'advertiser/account/update' => 'advertiser-account',

    'advertiser/stat/index' => 'advertiser-stat',
    'advertiser/stat/campaign_excel' => 'advertiser-stat',
    // 'advertiser/stat/report' => 'advertiser-stat', //概览页公用

    'trafficker/campaign/index' => 'trafficker-campaign',
    'trafficker/campaign/rank' => 'trafficker-campaign,trafficker-self-campaign',
    'trafficker/campaign/status' => 'trafficker-campaign',
    'trafficker/campaign/category' => 'trafficker-campaign,trafficker-self-campaign',
    'trafficker/campaign/update' => 'trafficker-campaign',
    'trafficker/campaign/check' => 'trafficker-campaign',

    'trafficker/campaign/self_index' => 'trafficker-self-campaign',
    'trafficker/campaign/self_check' => 'trafficker-self-campaign',
    'trafficker/campaign/zone_list' => 'trafficker-self-campaign',
    'trafficker/campaign/self_update' => 'trafficker-self-campaign',

    'trafficker/keywords/index' => 'trafficker-campaign,trafficker-self-campaign',
    'trafficker/keywords/update' => ',trafficker-self-campaign',

    'trafficker/zone/index' => 'trafficker-zone,trafficker-self-zone',
    'trafficker/zone/store' => 'trafficker-zone,trafficker-self-zone',
    'trafficker/zone/check' => 'trafficker-zone,trafficker-self-zone',
    'trafficker/zone/module_list' => 'trafficker-zone,trafficker-self-zone',
    'trafficker/zone/module_store' => 'trafficker-zone,trafficker-self-zone',
    'trafficker/zone/category_store' => 'trafficker-zone,trafficker-self-zone',
    'trafficker/zone/category_delete' => 'trafficker-zone,trafficker-self-zone',
    'trafficker/zone/module_delete' => 'trafficker-zone,trafficker-self-zone',
    'trafficker/zone/ad_type' => 'trafficker-zone,trafficker-self-zone',

    'trafficker/common/balance_value' => 'trafficker-balance,trafficker-self-balance',
    'trafficker/balance/withdraw' => 'trafficker-balance',
    'trafficker/balance/settlement' => 'trafficker-balance',
    'trafficker/balance/income' => 'trafficker-balance',
    'trafficker/balance/draw_balance' => 'trafficker-balance',
    'trafficker/balance/draw' => 'trafficker-balance',

    // 'trafficker/stat/index' => 'trafficker-stat', //右上角公用
    'trafficker/stat/column_list' => 'trafficker-stat',
    'trafficker/stat/zone' => 'trafficker-stat',
    'trafficker/stat/client' => 'trafficker-stat',
    'trafficker/stat/campaign_excel' => 'trafficker-stat',
    'trafficker/stat/time_zone_excel' => 'trafficker-stat',
    'trafficker/stat/time_campaign_excel' => 'trafficker-stat',

    'trafficker/stat/self_index' => 'trafficker-self-overview',
    'trafficker/stat/self_trend' => 'trafficker-self-overview',
    'trafficker/stat/self_zone' => 'trafficker-self-stat',
    'trafficker/stat/self_zone_excel' => 'trafficker-self-stat',

    // 'trafficker/stat/report' => 'trafficker-stat', //概览页公用
    // 'trafficker/stat/zone_report' => 'trafficker-stat', //概览页公用
    // 'trafficker/stat/client_report' => 'trafficker-stat', //概览页公用

    'broker/advertiser/index' => 'broker-advertiser',
    'broker/advertiser/store' => 'broker-advertiser',
    'broker/advertiser/update' => 'broker-advertiser',
    'broker/advertiser/transfer' => 'broker-advertiser',
    'broker/advertiser/balance_value' => 'broker-advertiser',

    'broker/campaign/column_list' => 'broker-campaign',
    'broker/campaign/index' => 'broker-campaign',
    'broker/campaign/day_limit' => 'broker-campaign',
    'broker/campaign/revenue' => 'broker-campaign',
    'broker/campaign/revenue_type' => 'broker-campaign',

    'broker/balance/recharge' => 'broker-balance',
    'broker/balance/gift' => 'broker-balance',
    'broker/balance/invoice_history' => 'broker-balance',
    'broker/balance/invoice_store' => 'broker-balance',
    'broker/balance/apply' => 'broker-balance',
    'broker/balance/invoice' => 'broker-balance',

    'broker/stat/index' => 'broker-stat',
    //'broker/stat/report' => 'broker-stat',//概览页公用
    'broker/stat/column_list' => 'broker-stat',
    'broker/stat/campaign_excel' => 'broker-stat',
    'broker/stat/time_campaign_excel' => 'broker-stat',


    'manager/advertiser/index' => 'manager-advertiser',
    'manager/advertiser/store' => 'manager-advertiser',
    'manager/advertiser/update' => 'manager-advertiser',

    'manager/broker/index' => 'manager-broker',
    'manager/broker/store' => 'manager-broker',
    'manager/broker/update' => 'manager-broker',

    'manager/trafficker/index' => 'manager-trafficker',
    'manager/trafficker/store' => 'manager-trafficker',
    'manager/trafficker/update' => 'manager-trafficker',
    'manager/trafficker/sales' => 'manager-trafficker',

    'manager/account/index'  => 'manager-account',
    'manager/account/store'  => 'manager-account',
    'manager/account/update' => 'manager-account',

    'manager/campaign/affiliate' => 'manager-campaign',
    'manager/campaign/affiliate_update' => 'manager-campaign',
    'manager/campaign/index' => 'manager-campaign',
    'manager/campaign/check' => 'manager-campaign',
    'manager/campaign/category' => 'manager-campaign',
    'manager/campaign/rank' => 'manager-campaign',
    'manager/campaign/release' => 'manager-campaign',
    'manager/campaign/app_search' => 'manager-campaign',
    'manager/campaign/app_update' => 'manager-campaign',
    'manager/campaign/client_package' => 'manager-campaign',
    'manager/campaign/revenue_type' => 'manager-campaign',
    'manager/campaign/info' => 'manager-campaign',
    'manager/campaign/revenue' => 'manager-campaign',
    'manager/campaign/day_limit' => 'manager-campaign',
    'manager/campaign/update' => 'manager-campaign',
    'manager/campaign/revenue_history' => 'manager-campaign',
    'manager/campaign/store' => 'manager-campaign',
    'manager/campaign/client_list' => 'manager-campaign',
    'manager/campaign/product_list' => 'manager-campaign',

    'manager/keyword/index'  => 'manager-campaign',
    'manager/keyword/store'  => 'manager-campaign',
    'manager/keyword/delete' => 'manager-campaign',

    'manager/material/index' => 'manager-campaign',
    'manager/material/check' => 'manager-campaign',

    'manager/balance/recharge_index'  => 'manager-balance',
    'manager/balance/recharge_update' => 'manager-balance',
    'manager/balance/income'            => 'manager-balance',
    'manager/balance/invoice_index'     => 'manager-balance',
    'manager/balance/invoice_update'    => 'manager-balance',
    'manager/balance/invoice_detail'    => 'manager-balance',
    'manager/balance/gift_index'        => 'manager-balance',
    'manager/balance/gift_update'       => 'manager-balance',
    'manager/balance/withdrawal_index'  => 'manager-balance',
    'manager/balance/withdrawal_update' => 'manager-balance',
    'manager/balance/income_index'      => 'manager-balance',
    'manager/balance/trafficker_index'  => 'manager-balance',
    'manager/balance/trafficker_export' => 'manager-balance',
    'manager/balance/trafficker_import' => 'manager-balance',
    'manager/balance/trafficker_update' => 'manager-balance',

    'manager/audit/trafficker_index' => 'manager-audit',
    'manager/audit/trafficker_export' => 'manager-audit',
    'manager/audit/trafficker_import' => 'manager-audit',
    'manager/audit/trafficker_update' => 'manager-audit',
    'manager/audit/advertiser_index' => 'manager-audit',
    'manager/audit/advertiser_update' => 'manager-audit',
    'manager/audit/advertiser_update_batch' => 'manager-audit',
    'manager/audit/advertiser_delivery' => 'manager-audit',
    
    'manager/activity/index' => 'manager-message',
    'manager/activity/get' => 'manager-message',
    'manager/activity/store' => 'manager-message',
    'manager/activity/deal' => 'manager-message',
    
    'manager/notice/index' => 'manager-message',
    'manager/notice/store' => 'manager-message',
    
    'manager/notice/email_index' => 'manager-message',
    'manager/notice/email_client' => 'manager-message',
    'manager/notice/email_store' => 'manager-message',
    'manager/notice/email_delete' => 'manager-message',

    'manager/stat/zone' => 'manager-stat',
    'manager/stat/zone_affiliate' => 'manager-stat',
    'manager/stat/zone_excel' => 'manager-stat',
    'manager/stat/zone_daily_excel' => 'manager-stat',
    'manager/stat/client' => 'manager-stat',
    'manager/stat/client_campaign' => 'manager-stat',
    'manager/stat/client_excel' => 'manager-stat',
    'manager/stat/client_daily_excel' => 'manager-stat',
    'manager/stat/manual_import' => 'manager-stat',
    'manager/stat/manual_data' => 'manager-stat',
    'manager/stat/client_import' => 'manager-stat',
    'manager/stat/adx_report' => 'manager-stat',
    'manager/stat/index' => 'manager-home',
    'manager/stat/trend' => 'manager-home',
    'manager/stat/rank' => 'manager-home',
    'manager/stat/trafficker_trend' => 'manager-trafficker-overview',
    'manager/stat/trafficker_daily' => 'manager-trafficker-overview',
    'manager/stat/trafficker_month' => 'manager-trafficker-overview',
    'manager/stat/trafficker_week_retain' => 'manager-trafficker-overview',
    'manager/stat/sale_trend' => 'manager-sale-overview',
    'manager/stat/sale_rank' => 'manager-sale-overview',



    'manager/pack/index' => 'manager-package',
    'manager/pack/client_package' => 'manager-package',
    'manager/pack/delivery_affiliate' => 'manager-package',
    'manager/pack/update' => 'manager-package',

    'admin/agency/index' => 'admin-agency',
    'admin/withdrawal/index' => 'admin-withdrawal',



];
