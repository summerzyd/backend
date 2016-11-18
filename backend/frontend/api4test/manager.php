<?php
echo 'Need params-local_' . $_SERVER['SERVER_NAME'] . '.php' . '<br>';
$params = include 'params-local_' . $_SERVER['SERVER_NAME'] . '.php';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
<body>
<style>
    a {text-decoration: none}
</style>
<div class="container" id="page">

<!--a href="../scene.php">scene</a><br>
<a href="../template.php">template</a><br>
<a href="../comment.php">comment</a><br-->
<br><br>

<div id="content">


<!--a href="javascript:;" onclick="post()">Post</a> <span id="resultPost"></span><br>
<a href="javascript:;" onclick="get()">Get</a> <span id="resultGet"></span><br>
<br-->
<a href="javascript:;" onclick="isLogin()">is_login</a> <span id="resultIsLogin"></span><br>

<input name="loginUsername" id="loginUsername" placeholder="username" value="平台账号">
<input name="loginPassword" id="loginPassword" placeholder="password" value="123456">
<input name="loginCaptcha" id="loginCaptcha" placeholder="captcha">
<img src="<?php echo $params['prefixUrl']; ?>site/captcha" id="img_num" style="cursor: pointer" onclick="this.src='<?php echo $params['prefixUrl']; ?>site/captcha?'+new Date().getTime()" ;="" width="86" height="40" alt="">
<a href="javascript:;" onclick="login()">login</a> <span id="resultLogin"></span><br>
<a href="javascript:;" onclick="logout()">logout</a> <span id="resultLogout"></span><br>

<br><hr><br><br>

    <a href="javascript:;" onclick="commonBalanceValue()">manager/common/balance_value</a> <span id="commonBalanceValue"></span><br>



<input type="text" id="keywordCampaignId" placeholder="CampaignId">
<a href="javascript:;" onclick="keywordIndex()">manager/keyword/index</a> <span id="keywordIndex"></span><br>
<input type="text" id="keywordStoreId" placeholder="Id">
<input type="text" id="keywordStoreCampaignId" placeholder="campaignid">
<input type="text" id="keywordStoreKeyword" placeholder="keyword">
<input type="text" id="keywordStorePriceUp" placeholder="price_up">
<a href="javascript:;" onclick="keywordStore()">manager/keyword/store</a> <span id="keywordStore"></span><br>
<input type="text" id="keywordId" placeholder="Id">
<a href="javascript:;" onclick="keywordDelete()">manager/keyword/delete</a> <span id="keywordDelete"></span><br>

    <input type="text" id="advertiserPageSize" placeholder="pageSize">
    <input type="text" id="advertiserPageNo" placeholder="pageNo">
    <input type="text" id="advertiserSearch" placeholder="search">
    <input type="text" id="advertiserSort" placeholder="sort">
    <input type="text" id="advertiserType" placeholder="type">
    <a href="javascript:;" onclick="advertiserIndex()">manager/advertiser/index</a> <span id="advertiserIndex"></span><br>

    <input type="text" id="advertiserStoreUsername" placeholder="username" value="test12<?php echo rand(10000, 99999) ?>">
    <input type="text" id="advertiserStorePassword" placeholder="password" value="123456">
    <input type="text" id="advertiserStoreClientName" placeholder="clientname" value="name 1<?php echo rand(10000, 99999) ?>">
    <input type="text" id="advertiserStoreBriefName" placeholder="brief_name" value="brief name 1<?php echo rand(10000, 99999) ?>">
    <input type="text" id="advertiserStoreContact" placeholder="contact" value="contact1">
    <input type="text" id="advertiserStoreContactPhone" placeholder="contact_phone" value="132456<?php echo rand(10000, 99999) ?>">
    <input type="text" id="advertiserStoreQq" placeholder="qq" value="123<?php echo rand(10000, 99999) ?>">
    <input type="text" id="advertiserStoreEmail" placeholder="email" value="<?php echo rand(10000, 99999) ?>@qq.com">
    <input type="text" id="advertiserStoreCreatorUid" placeholder="creator_uid" value="2">
    <input type="text" id="advertiserStoreType" placeholder="type" value="1">
    <a href="javascript:;" onclick="advertiserStore()">manager/advertiser/store</a> <span id="advertiserStore"></span><br>

    <input type="text" id="advertiserUpdateId" placeholder="id">
    <input type="text" id="advertiserUpdateField" placeholder="field">
    <input type="text" id="advertiserUpdateValue" placeholder="value">
    <input type="text" id="advertiserUpdateType" placeholder="type" value="1">
    <a href="javascript:;" onclick="advertiserUpdate()">manager/advertiser/update</a> <span id="advertiserUpdate"></span><br>

    <input type="text" id="advertiserHistoryClientId" placeholder="clientid">
    <input type="text" id="advertiserHistoryWay" placeholder="way">
    <a href="javascript:;" onclick="advertiserHistory()">manager/advertiser/rechargeHistory</a> <span id="advertiserRechargeHistory"></span><br>

    <input type="text" id="advertiserGiftApplyClientId" placeholder="clientid">
    <input type="text" id="advertiserGiftApplyAmount" placeholder="amount">
    <input type="text" id="advertiserGiftApplyGiftInfo" placeholder="gift_info">
    <a href="javascript:;" onclick="advertiserGiftApply()">manager/advertiser/giftApply</a> <span id="advertiserGiftApply"></span><br>

    <input type="text" id="advertiserGiftDetailClientId" placeholder="clientid">
    <a href="javascript:;" onclick="advertiserGiftDetail()">manager/advertiser/giftDetail</a> <span id="advertiserGiftDetail"></span><br>

    <input type="text" id="advertiserRechargeApplyClientId" placeholder="clientid">
    <input type="text" id="advertiserRechargeApplyWay" placeholder="way">
    <input type="text" id="advertiserRechargeApplyAccountInfo" placeholder="account_info">
    <input type="text" id="advertiserRechargeApplyDate" placeholder="date">
    <input type="text" id="advertiserRechargeApplyAmount" placeholder="amount">
    <a href="javascript:;" onclick="advertiserRechargeApply()">manager/advertiser/rechargeApply</a> <span id="advertiserRechargeApply"></span><br>

    <input type="text" id="advertiserRechargeDetailClientId" placeholder="clientid">
    <a href="javascript:;" onclick="advertiserRechargeDetail()">manager/advertiser/rechargeDetail</a> <span id="advertiserRechargeDetail"></span><br>
    <br><br>

    <input type="text" id="brokerPageSize" placeholder="pageSize">
    <input type="text" id="brokerPageNo" placeholder="pageNo">
    <input type="text" id="brokerSearch" placeholder="search">
    <input type="text" id="brokerSort" placeholder="sort">
    <a href="javascript:;" onclick="brokerIndex()">manager/broker/index</a> <span id="brokerIndex"></span><br>

    <input type="text" id="brokerStoreUsername" placeholder="username" value="test12<?php echo rand(10000, 99999) ?>">
    <input type="text" id="brokerStorePassword" placeholder="password" value="123456">
    <input type="text" id="brokerStoreName" placeholder="name" value="name 1<?php echo rand(10000, 99999) ?>">
    <input type="text" id="brokerStoreBriefName" placeholder="brief_name" value="brief name 1<?php echo rand(10000, 99999) ?>">
    <input type="text" id="brokerStoreContact" placeholder="contact" value="contact1">
    <input type="text" id="brokerStoreContactPhone" placeholder="contact_phone" value="132456<?php echo rand(10000, 99999) ?>">
    <input type="text" id="brokerStoreQq" placeholder="qq" value="123<?php echo rand(10000, 99999) ?>">
    <input type="text" id="brokerStoreEmail" placeholder="email" value="<?php echo rand(10000, 99999) ?>@qq.com">
    <input type="text" id="brokerStoreCreatorUid" placeholder="creator_uid" value="2">
    <a href="javascript:;" onclick="brokerStore()">manager/broker/store</a> <span id="brokerStore"></span><br>

    <input type="text" id="brokerUpdateId" placeholder="id">
    <input type="text" id="brokerUpdateField" placeholder="field">
    <input type="text" id="brokerUpdateValue" placeholder="value">
    <input type="text" id="brokerUpdateType" placeholder="type" value="1">
    <a href="javascript:;" onclick="brokerUpdate()">manager/broker/update</a> <span id="brokerUpdate"></span><br>

    <input type="text" id="brokerHistoryClientId" placeholder="clientid">
    <input type="text" id="brokerHistoryWay" placeholder="way">
    <a href="javascript:;" onclick="brokerHistory()">manager/broker/rechargeHistory</a> <span id="brokerRechargeHistory"></span><br>

    <input type="text" id="brokerGiftApplyClientId" placeholder="clientid">
    <input type="text" id="brokerGiftApplyAmount" placeholder="amount">
    <input type="text" id="brokerGiftApplyGiftInfo" placeholder="gift_info">
    <a href="javascript:;" onclick="brokerGiftApply()">manager/broker/giftApply</a> <span id="brokerGiftApply"></span><br>

    <input type="text" id="brokerGiftDetailClientId" placeholder="clientid">
    <a href="javascript:;" onclick="brokerGiftDetail()">manager/broker/giftDetail</a> <span id="brokerGiftDetail"></span><br>

    <input type="text" id="brokerRechargeApplyClientId" placeholder="clientid">
    <input type="text" id="brokerRechargeApplyWay" placeholder="way">
    <input type="text" id="brokerRechargeApplyAccountInfo" placeholder="account_info">
    <input type="text" id="brokerRechargeApplyDate" placeholder="date">
    <input type="text" id="brokerRechargeApplyAmount" placeholder="amount">
    <a href="javascript:;" onclick="brokerRechargeApply()">manager/broker/rechargeApply</a> <span id="brokerRechargeApply"></span><br>

    <input type="text" id="brokerRechargeDetailClientId" placeholder="clientid">
    <a href="javascript:;" onclick="brokerRechargeDetail()">manager/broker/rechargeDetail</a> <span id="brokerRechargeDetail"></span><br>
    <br><br>

    <input type="text" id="traffickerPageSize" placeholder="pageSize">
    <input type="text" id="traffickerPageNo" placeholder="pageNo">
    <input type="text" id="traffickerSearch" placeholder="search">
    <input type="text" id="traffickerSort" placeholder="sort">
    <a href="javascript:;" onclick="traffickerIndex()">manager/trafficker/index</a> <span id="traffickerIndex"></span><br>

    <input type="text" id="traffickerStoreAffiliateId" placeholder="affiliateid" value="">
    <input type="text" id="traffickerStoreUsername" placeholder="username" value="test12<?php echo rand(10000, 99999) ?>">
    <input type="text" id="traffickerStorePassword" placeholder="password" value="123456">
    <input type="text" id="traffickerStoreName" placeholder="name" value="name 1<?php echo rand(10000, 99999) ?>">
    <input type="text" id="traffickerStoreBriefName" placeholder="brief_name" value="brief name 1<?php echo rand(10000, 99999) ?>">
    <input type="text" id="traffickerStoreContact" placeholder="contact" value="contact1">
    <input type="text" id="traffickerStoreContactPhone" placeholder="contact_phone" value="132456<?php echo rand(10000, 99999) ?>">
    <input type="text" id="traffickerStoreQq" placeholder="qq" value="123<?php echo rand(10000, 99999) ?>">
    <input type="text" id="traffickerStoreEmail" placeholder="email" value="<?php echo rand(10000, 99999) ?>@qq.com">
    <input type="text" id="traffickerStoreIncomeRate" placeholder="income_rate" value="95">
    <input type="text" id="traffickerStoreMode" placeholder="mode" value="1">
    <input type="text" id="traffickerStoreCreatorUid" placeholder="creator_uid" value="2">
    <input type="text" id="traffickerStoreAppPlatform" placeholder="app_platform" value="15">
    <input type="text" id="traffickerStoreAudit" placeholder="audit" value="2">
    <input type="text" id="traffickerStoreDelivery" placeholder="ad_type" value='[{"ad_type":0,"revenue_type":10,"num":1},{"ad_type":1,"revenue_type":2,"num":1},{"ad_type":1,"revenue_type":10,"num":1},{"ad_type":71,"revenue_type":2,"num":1}]'>
    <input type="text" id="traffickerStoreKind" placeholder="kind" value="1">
    <input type="text" id="traffickerStoreAlipayAccount" placeholder="alipay_account" value="<?php echo rand(10000, 99999) ?>@qq.com">
    <a href="javascript:;" onclick="traffickerStore()">manager/trafficker/store</a> <span id="traffickerStore"></span><br>

    <input type="text" id="traffickerUpdateId" placeholder="id">
    <input type="text" id="traffickerUpdateField" placeholder="field">
    <input type="text" id="traffickerUpdateValue" placeholder="value">
    <a href="javascript:;" onclick="traffickerUpdate()">manager/trafficker/update</a> <span id="traffickerUpdate"></span><br>

    <a href="javascript:;" onclick="traffickerSales()">manager/trafficker/sales</a> <span id="traffickerSales"></span><br>

    <input type="text" id="accountPageSize" placeholder="pageSize">
    <input type="text" id="accountPageNo" placeholder="pageNo">
    <input type="text" id="accountSearch" placeholder="search">
    <input type="text" id="accountSort" placeholder="sort">
    <input type="text" id="accountType" placeholder="type" value="ADVERTISER">
    <a href="javascript:;" onclick="accountIndex()">manager/account/index</a> <span id="accountIndex"></span><br>

    <input type="text" id="accountStoreUsername" placeholder="username" value="test12<?php echo rand(10000, 99999) ?>">
    <input type="text" id="accountStorePassword" placeholder="password" value="123456">
    <input type="text" id="accountStoreContactName" placeholder="contact_name" value="name 1<?php echo rand(10000, 99999) ?>">
    <input type="text" id="accountStoreContactPhone" placeholder="contact_phone" value="132456<?php echo rand(10000, 99999) ?>">
    <input type="text" id="accountStoreEmailAddress" placeholder="email" value="<?php echo rand(10000, 99999) ?>@qq.com">
    <input type="text" id="accountStoreAccountSubTypeId" placeholder="account_sub_type_id" value="1001">
    <input type="text" id="accountStoreOperationList" placeholder="operation_list" value="manager-profile,manager-password,manager-campaign,manager-advertiser,manager-broker,manager-trafficker,manager-stat,manager-balance,manager-audit,manager-package,manager-message,manager-sdk">
    <a href="javascript:;" onclick="accountStore()">manager/account/store</a> <span id="accountStore"></span><br>

    <input type="text" id="accountUpdateId" placeholder="id">
    <input type="text" id="accountUpdateField" placeholder="field">
    <input type="text" id="accountUpdateValue" placeholder="value">
    <a href="javascript:;" onclick="accountUpdate()">manager/account/update</a> <span id="accountUpdate"></span><br>

    <input type="text" id="accountType0" placeholder="type" value="MANAGER">
    <a href="javascript:;" onclick="accountAccountSubType()">site/account_sub_type</a> <span id="accountAccountSubType"></span><br>

    <input type="text" id="accountType1" placeholder="type" value="MANAGER">
    <a href="javascript:;" onclick="accountOperation()">site/operation</a> <span id="accountOperation"></span><br>
<hr>

<input type="text" id="campaignIndexSearch" placeholder="search">
<input type="text" id="campaignIndexSort" placeholder="sort">
<input type="text" id="campaignIndexCampaignId" placeholder="campaignid">
<input type="text" id="campaignIndexAdType" placeholder="ad_type">
<input type="text" id="campaignIndexPlatform" placeholder="platform">
<input type="text" id="campaignIndexStatus" placeholder="status">
<input type="text" id="campaignIndexDayLimit" placeholder="day_limit">
<input type="text" id="campaignIndexRevenue" placeholder="revenue">
<input type="text" id="campaignIndexRevenueType" placeholder="revenue_type">
<a href="javascript:;" onclick="campaignIndex()">manager/campaign/index</a> <span id="campaignIndex"></span><br>

<input type="text" id="campaignAffiliateUpdateCampaignId" placeholder="campaignid">
<input type="text" id="campaignAffiliateUpdateAffiliateId" placeholder="affiliateid">
<input type="text" id="campaignAffiliateUpdateBannerId" placeholder="bannerid">
<input type="text" id="campaignAffiliateUpdateAdType" placeholder="ad_type">
<input type="text" id="campaignAffiliateUpdateField" placeholder="field">
<input type="text" id="campaignAffiliateUpdateOldAttachId" placeholder="old_attach_id">
<input type="text" id="campaignAffiliateUpdateValue" placeholder="value">
<a href="javascript:;" onclick="campaignAffiliateUpdate()">manager/campaign/affiliate_update</a> <span id="campaignAffiliateUpdate"></span><br>

<input type="text" id="affiliateCampaignId" placeholder="campaignid">
<input type="text" id="campaignAffiliateAdType" placeholder="ad_type">
<input type="text" id="campaignAffiliateProductType" placeholder="products_type">
<input type="text" id="campaignAffiliateMode" placeholder="mode">
<input type="text" id="campaignAffiliateSearch" placeholder="search">
<input type="text" id="campaignAffiliateSort" placeholder="sort">
<a href="javascript:;" onclick="campaignAffiliate()">manager/campaign/affiliate</a> <span id="campaignAffiliate"></span><br>

<input type="text" id="campaignReleaseAffiliateId" placeholder="affiliateid">
<input type="text" id="campaignReleaseCampaignId" placeholder="campaignid">
<input type="text" id="campaignReleaseBannerId" placeholder="bannerid">
<input type="text" id="campaignReleaseStatus" placeholder="status">
<input type="text" id="campaignReleaseAction" placeholder="action">
<input type="text" id="campaignReleaseMode" placeholder="mode">
<a href="javascript:;" onclick="campaignRelease()">manager/campaign/release</a> <span id="campaignRelease"></span><br>

<input type="text" id="campaignCheckCampaignId" placeholder="campaignid">
<input type="text" id="campaignCheckStatus" placeholder="status">
<input type="text" id="campaignCheckChannel" placeholder="channel">
<input type="text" id="campaignCheckRate" placeholder="rate">
<input type="text" id="campaignCheckComment" placeholder="comment">
<a href="javascript:;" onclick="campaignCheck()">manager/campaign/check</a> <span id="campaignCheck"></span><br>

<input type="text" id="appSearchAffiliatesId" placeholder="affiliate">
<input type="text" id="appSearchWords" placeholder="words">
<input type="text" id="appSearchPlatform" placeholder="platform">
<a href="javascript:;" onclick="appSearch()">manager/campaign/app_search</a> <span id="appSearch"></span><br>

<input type="text" id="categoryCampaignId" placeholder="affiliateid">
<a href="javascript:;" onclick="campaignCategory()">manager/campaign/category</a> <span id="campaignCategory"></span><br>
<a href="javascript:;" onclick="campaignRevenue()">manager/campaign/revenue</a> <span id="campaignRevenue"></span><br>
<a href="javascript:;" onclick="campaignDayLimit()">manager/campaign/day_limit</a> <span id="campaignDayLimit"></span><br>
<input type="text" id="campaignUpdateCampaignId" placeholder="campaignid">
<input type="text" id="campaignUpdateField" placeholder="field">
<input type="text" id="campaignUpdateValue" placeholder="value">
<a href="javascript:;" onclick="campaignUpdate()">manager/campaign/update</a> <span id="campaignUpdate"></span><br>

<input type="text" id="campaignRevenueHistoryCampaignId" placeholder="campaignid">
<a href="javascript:;" onclick="campaignRevenueHistory()">manager/campaign/revenue_history</a> <span id="campaignRevenueHistory"></span><br>

<input type="text" id="campaignEquivalenceListCampaignId" placeholder="campaignid">
<input type="text" id="campaignEquivalenceListPlatform" placeholder="platform">
<input type="text" id="campaignEquivalenceListAdType" placeholder="ad_type">
<input type="text" id="campaignEquivalenceListRevenueType" placeholder="revenue_type">
<input type="text" id="campaignEquivalenceListSearch" placeholder="search">
<a href="javascript:;" onclick="campaignEquivalenceList()">manager/campaign/equivalence_list</a> <span id="campaignEquivalenceList"></span><br>

<input type="text" id="campaignEquivalenceCampaignId" placeholder="campaignid">
<input type="text" id="campaignEquivalenceCampaignIdRelation" placeholder="campaignid_relation">
<input type="text" id="campaignEquivalenceAction" placeholder="action">
<a href="javascript:;" onclick="campaignEquivalence()">manager/campaign/equivalence</a> <span id="campaignEquivalence"></span><br>

<input type="text" id="campaignConsumeCampaignId" placeholder="campaignid">
<a href="javascript:;" onclick="campaignConsume()">manager/campaign/consume</a> <span id="campaignConsume"></span><br>


<input type="text" id="rankCampaignId" placeholder="campaignid">
<input type="text" id="rankPlatform" placeholder="platform">
<a href="javascript:;" onclick="campaignRank()">manager/campaign/rank</a> <span id="campaignRank"></span><br>

<input type="text" id="revenueTypeAffiliateId" placeholder="affiliateid">
<input type="text" id="revenueTypeAdType" placeholder="ad_type">
<input type="text" id="revenueTypeRevenueType" placeholder="revenue_type">
<a href="javascript:;" onclick="revenueType()">manager/campaign/revenue_type</a> <span id="revenueType"></span><br>

<input type="text" id="infoCampaignId" placeholder="campaignid">
<a href="javascript:;" onclick="campaignInfo()">manager/campaign/info</a> <span id="campaignInfo"></span><br>
<input type="text" id="campaignPackageCampaignId" placeholder="campaignid">
<a href="javascript:;" onclick="campaignPackage()">manager/campaign/client_package</a> <span id="campaignPackage"></span><br>
<input type="text" id="campaignStoreRevenueType" placeholder="revenue_type">
<input type="text" id="campaignStoreClientId" placeholder="clientid">
<input type="text" id="campaignStoreProductId" placeholder="products_id">
<input type="text" id="campaignStorePlatform" placeholder="platform">
<input type="text" id="campaignStoreProductName" placeholder="products_name">
<input type="text" id="campaignStoreProductIcon" placeholder="products_icon">
<input type="text" id="campaignStoreAppInfoAppName" placeholder="appinfos_app_name">
<a href="javascript:;" onclick="campaignStore()">manager/campaign/store</a> <span id="campaignStore"></span><br>
<input type="text" id="campaignClientRevenueType" placeholder="revenue_type">
<a href="javascript:;" onclick="campaignRevenueType()">manager/campaign/client_list</a> <span id="campaignRevenueType"></span><br>
<input type="text" id="campaignProductClient" placeholder="clientid">
<a href="javascript:;" onclick="campaignProduct()">manager/campaign/product_list</a> <span id="campaignProduct"></span><br>

<input type="text" id="campaignLogIndexCategory" placeholder="category">
<input type="text" id="campaignLogIndexTargetId" placeholder="target_id">
<input type="text" id="campaignLogIndexSearch" placeholder="search">
<input type="text" id="campaignLogIndexSort" placeholder="sort">
<a href="javascript:;" onclick="campaignLogIndex()">manager/campaign/log_index</a> <span id="campaignLogIndex"></span><br>

<input type="text" id="campaignLogStoreCategory" placeholder="category">
<input type="text" id="campaignLogStoreTargetId" placeholder="target_id">
<input type="text" id="campaignLogStoreMessage" placeholder="message">
<a href="javascript:;" onclick="campaignLogStore()">manager/campaign/log_store</a> <span id="campaignLogStore"></span><br>

<input type="text" id="campaignBannerLogIndexBannerId" placeholder="bannerid">
<input type="text" id="campaignBannerLogIndexSearch" placeholder="search">
<input type="text" id="campaignBannerLogIndexSort" placeholder="sort">
<a href="javascript:;" onclick="campaignBannerLogIndex()">manager/campaign/banner_log_index</a> <span id="campaignBannerLogIndex"></span><br>

<input type="text" id="campaignBannerLogStoreBannerId" placeholder="bannerid">
<input type="text" id="campaignBannerLogStoreMessage" placeholder="message">
<a href="javascript:;" onclick="campaignBannerLogStore()">manager/campaign/log_store</a> <span id="campaignBannerLogStore"></span><br>


    <input type="text" id="campaignViewCampaignId" placeholder="campaignid">
<a href="javascript:;" onclick="materialView()">manager/material/view</a> <span id="materialView"></span><br>
<hr>



<input type="text" id="CommonAccountType" placeholder="accountType">
<a href="javascript:;" onclick="commonSales()">manager/common/sales</a> <span id="commonSales"></span><br>
<a href="javascript:;" onclick="packageNotLatest()">manager/common/packageNotLatest</a> <span id="packageNotLatest"></span><br>

<br><br>

<input type="text" id="materialIndexPageNo" placeholder="pageNo">
<input type="text" id="materialIndexPageSize" placeholder="pageSize">
<input type="text" id="materialIndexSort" placeholder="search">
<input type="text" id="materialIndexSearch" placeholder="sort">
<a href="javascript:;" onclick="materialIndex()">manager/material/index</a> <span id="materialIndex"></span><br>
<input type="text" id="materialCheckAppId" placeholder="app_id">
<input type="text" id="materialCheckStatus" placeholder="status">
<a href="javascript:;" onclick="materialCheck()">manager/material/check</a> <span id="materialCheck"></span><br>

<input type="text" id="materialInfoCampaignId" placeholder="campaignid">
<a href="javascript:;" onclick="materialInfo()">manager/material/info</a> <span id="materialInfo"></span><br>
   <br><br>

    <input type="text" id="packIndexPageNo" placeholder="pageNo">
<input type="text" id="packIndexPageSize" placeholder="pageSize">
<input type="text" id="packIndexSort" placeholder="search">
<input type="text" id="packIndexSearch" placeholder="sort">
<a href="javascript:;" onclick="packIndex()">manager/pack/index</a> <span id="packIndex"></span><br>

<input type="text" id="packIndexCampaignId" placeholder="campaignid">
<a href="javascript:;" onclick="packClientPackage()">manager/pack/client_package</a> <span id="clientPackage"></span><br>

<input type="text" id="packDeliveryAttachId" placeholder="attach_id">
<a href="javascript:;" onclick="packDeliveryAffiliate()">manager/pack/delivery_affiliate</a> <span id="deliveryAffiliate"></span><br>

<input type="text" id="packUpdateAttachId" placeholder="attach_id">
<input type="text" id="packUpdateField" placeholder="field">
<input type="text" id="packUpdateValue" placeholder="value">
<a href="javascript:;" onclick="packUpdate()">manager/pack/update</a> <span id="packUpdate"></span><br>


<br><br>

<input type="text" id="balancePageSize" placeholder="pageSize">
<input type="text" id="balancePageNo" placeholder="pageNo">
<a href="javascript:;" onclick="balanceIncome()">manager/balance/income</a> <span id="balanceIncome"></span><br>


<input type="text" id="balanceRechargePageSize" placeholder="pageSize">
<input type="text" id="balanceRechargePageNo" placeholder="pageNo">
<a href="javascript:;" onclick="rechargeIndex()">manager/balance/recharge_index</a> <span id="rechargeIndex"></span><br>


<input type="text" id="rechargeId" placeholder="rechargeId">
<input type="text" id="rechargeContent" placeholder="rechargeContent">
<input type="text" id="rechargeStatus" placeholder="rechargeStatus">
<a href="javascript:;" onclick="rechargePass()"> manager/balance/recharge_update</a> 
<span id="rechargeUpdate"></span><br>


<input type="text" id="invoiceIndexPageSize" placeholder="invoiceIndexPageSize">
<input type="text" id="invoiceIndexPageNo" placeholder="invoiceIndexPageNo">
<input type="text" id="invoiceSearch" placeholder="invoiceSearch">
<a href="javascript:;" onclick="invoiceIndex()">manager/balance/invoice_index</a> <span id="invoiceIndex"></span><br>


<input type="text" id="invoiceId" placeholder="invoiceId">
<input type="text" id="invoiceField" placeholder="invoiceField">
<input type="text" id="invoiceValue" placeholder="invoiceValue">
<a href="javascript:;" onclick="invoicePass()"> manager/balance/invoice_update</a> 
<span id="invoicePass"></span><br>


<input type="text" id="statTrendType" placeholder="type">
<a href="javascript:;" onclick="statTrend()">manager/stat/trend</a> <span id="statTrend"></span><br>

<input type="text" id="statRankType" placeholder="date_type">
<a href="javascript:;" onclick="statRank()">manager/stat/rank</a> <span id="statRank"></span><br>


<input type="text" id="statTraffickerTrendType" placeholder="type">
<a href="javascript:;" onclick="statTraffickerTrend()">manager/stat/trafficker_trend</a> <span id="statTraffickerTrend"></span><br>

<input type="text" id="statTraffickerDailyType" placeholder="type">
<a href="javascript:;" onclick="statTraffickerDaily()">manager/stat/trafficker_daily</a> <span id="statTraffickerDaily"></span><br>

<input type="text" id="statTraffickerWeekRetainDate" placeholder="date">
<a href="javascript:;" onclick="statTraffickerWeekRetain()">manager/stat/trafficker_week_retain</a> <span id="statTraffickerWeekRetain"></span><br>

    <a href="javascript:;" onclick="statTraffickerMonth()">manager/stat/trafficker_month</a> <span id="statTraffickerMonth"></span><br>

    <input type="text" id="statSaleTrendType" placeholder="type">
    <a href="javascript:;" onclick="statSaleTrend()">manager/stat/sale_trend</a> <span id="statSaleTrend"></span><br>

    <input type="text" id="statSaleRankType" placeholder="date_type">
    <a href="javascript:;" onclick="statSaleRank()">manager/stat/sale_rank</a> <span id="statSaleRankType"></span><br>

<input id="period_start" placeholder="period_start" value="2015-12-10">
<input id="period_end" placeholder="period_end" value="2015-12-12">
<input id="span" placeholder="span" value="2">
<input id="zone_offset" placeholder="zone_offset" value="-8">
<input id="audit" placeholder="audit">
<a href="javascript:;" onclick="zone()">manager/stat/zone</a> <span id="statZone"></span><br>

<input id="statPeriodStart" placeholder="period_start" value="2015-12-10">
<input id="statPeriodEnd" placeholder="period_end" value="2015-12-12">
<input id="statSpan" placeholder="span" value="2">
<input id="statZoneOffset" placeholder="zone_offset" value="-8">
<input id="statAudit" placeholder="audit">
<input id="statAffiliateid" placeholder="affiliateid">
<a href="javascript:;" onclick="zoneAffiliate()">manager/stat/zone_affiliate</a> <span id="statZoneAffiliate"></span><br>

<input id="statExcelPeriodStart" placeholder="period_start" value="2015-12-10">
<input id="statExcelPeriodEnd" placeholder="period_end" value="2015-12-12">
<input id="statExcelSpan" placeholder="span" value="2">
<input id="statExcelOffset" placeholder="zone_offset" value="-8">
<input id="statExcelAudit" placeholder="audit">
<input id="statExcelAffiliateid" placeholder="affiliateid">
<input id="statExcelBannerId" placeholder="bannerid">
<a href="javascript:;" onclick="zoneExcel()">manager/stat/zone_excel</a> <span id="statZoneAffiliate"></span><br>

<input id="zoneDailyExcelPeriodStart" placeholder="period_start" value="2015-12-10">
<input id="zoneDailyExcelPeriodEnd" placeholder="period_end" value="2015-12-12">
<input id="zoneDailyExcelSpan" placeholder="span" value="2">
<input id="zoneDailyExcelOffset" placeholder="zone_offset" value="-8">
<input id="zoneDailyExcelAudit" placeholder="audit">
<input id="zoneDailyExcelAffiliateid" placeholder="affiliateid">
<input id="zoneDailyExcelBannerId" placeholder="bannerid">
<a href="javascript:;" onclick="zoneDailyExcel()">manager/stat/zone_daily_excel</a> <span id="statZoneAffiliate"></span><br>

<input id="ClientPeriodStart" placeholder="period_start" value="2015-12-10">
<input id="ClientPeriodEnd" placeholder="period_end" value="2015-12-12">
<input id="ClientSpan" placeholder="span" value="2">
<input id="ClientZoneOffset" placeholder="zone_offset" value="-8">
<input id="ClientAudit" placeholder="audit">
<a href="javascript:;" onclick="client()">manager/stat/client</a> <span id="statClient"></span><br>

<input id="clientCampaignPeriodStart" placeholder="period_start" value="2015-12-10">
<input id="clientCampaignPeriodEnd" placeholder="period_end" value="2015-12-12">
<input id="clientCampaignSpan" placeholder="span" value="2">
<input id="clientCampaignZoneOffset" placeholder="zone_offset" value="-8">
<input id="clientCampaignAudit" placeholder="audit">
<input id="clientCampaignCampaignId" placeholder="campaignid">
<a href="javascript:;" onclick="clientCampaign()">manager/stat/client_campaign</a> <span id="statClientCampaign"></span><br>

<input id="clientExcelPeriodStart" placeholder="period_start" value="2015-12-10">
<input id="clientExcelPeriodEnd" placeholder="period_end" value="2015-12-12">
<input id="clientExcelSpan" placeholder="span" value="2">
<input id="clientExcelOffset" placeholder="zone_offset" value="-8">
<input id="clientExcelAudit" placeholder="audit">
<input id="clientExcelProductId" placeholder="productid">
<input id="clientExcelCampaignId" placeholder="campaignid">
<input id="clientExcelBannerId" placeholder="bannerid">
<a href="javascript:;" onclick="clientExcel()">manager/stat/client_excel</a> <span id="statZoneAffiliate"></span><br>

<input id="clientDailyExcelPeriodStart" placeholder="period_start" value="2015-12-10">
<input id="clientDailyExcelPeriodEnd" placeholder="period_end" value="2015-12-12">
<input id="clientDailyExcelSpan" placeholder="span" value="2">
<input id="clientDailyExcelOffset" placeholder="zone_offset" value="-8">
<input id="clientDailyExcelAudit" placeholder="audit">
<input id="clientDailyExcelProductId" placeholder="productid">
<input id="clientDailyExcelCampaignId" placeholder="campaignid">
<input id="clientDailyExcelBannerId" placeholder="bannerid">
<a href="javascript:;" onclick="clientDailyExcel()">manager/stat/client_daily_excel</a> <span id="statZoneAffiliate"></span><br>

<input id="manualDataAffiliateId" placeholder="affiliateid">
<input id="manualDataDate" placeholder="date" value="2016-05-15">
<input id="manualDataSearch" placeholder="search" >
<input id="manualDataPageNo" placeholder="pageNo" value="1">
<input id="manualDataPageSize" placeholder="pageSize" value="25">
<a href="javascript:;" onclick="manualData()">manager/stat/manual_data</a> <span id="manualData"></span><br>


<input id="clientDataDate" placeholder="date" value="2016-05-15">
<input id="clientDataPlatform" placeholder="platform">
<input id="clientDataProductId" placeholder="product_id">
<input id="clientDataCampaignId" placeholder="campaignid">
<input id="clientDataSearch" placeholder="search" >
<input id="clientDataPageNo" placeholder="pageNo" value="1">
<input id="clientDataPageSize" placeholder="pageSize" value="25">
<a href="javascript:;" onclick="clientData()">manager/stat/client_data</a> <span id="clientData"></span><br>

<input type="text" id="productDate" placeholder="date" value="2016-05-15">
<input type="text" id="productPlatform" placeholder="platform">
<a href="javascript:;" onclick="product()">manager/stat/product</a> <span id="product"></span><br>

<input type="text" id="campaignDate" placeholder="date" value="2016-05-15">
<input type="text" id="campaignPlatform" placeholder="platform">
<input type="text" id="campaignProductId" placeholder="product_id">
<a href="javascript:;" onclick="campaign()">manager/stat/campaign</a> <span id="campaign"></span><br>

<input type="text" id="giftIndexPageSize" placeholder="pageSize">
<input type="text" id="giftIndexPageNo" placeholder="pageNo">
<a href="javascript:;" onclick="giftIndex()">manager/balance/gift_index</a> <span id="giftIndex"></span><br>


<input type="text" id="withdrawPageSize" placeholder="pageSize">
<input type="text" id="withdrawPageNo" placeholder="pageNo">
<a href="javascript:;" onclick="withdrawalIndex()">manager/balance/withdrawal_index</a> <span id=withdrawalIndex></span><br>

<input type="text" id="incomeIndexSize" placeholder="pageSize">
<input type="text" id="incomeIndexNo" placeholder="pageNo">
<a href="javascript:;" onclick="incomeIndex()">manager/balance/income_index</a> <span id=incomeIndex></span><br>
<br/>
<br/>
<input type="text" id="dailyListType" placeholder="type">
<a href="javascript:;" onclick="dailyList()">manager/activity/report_list</a> <span id=dailyList></span><br>
<a href="javascript:;" onclick="accountList()">manager/activity/account_list</a> <span id=accountList></span><br>
<input type="text" id="updateMailReceiverUserId" placeholder="user_id">
<input type="text" id="updateMailReceiverStatus" placeholder="status">
<a href="javascript:;" onclick="updateMailReceiver()">manager/activity/update_mail_receiver</a> <span id=updateMailReceiver></span><br>
<input type="text" id="pauseSendMailDate" placeholder="id">
<a href="javascript:;" onclick="pauseSendMail()">manager/activity/pause_send_mail</a> <span id=pauseSendMail></span><br>
<input type="text" id="resendMailDate" placeholder="id">
<a href="javascript:;" onclick="resendMail()">manager/activity/resend_mail</a> <span id=resendMail></span><br>

<input type="text" id="settingIndexAgencyid" placeholder="agencyid" value="2">
<input type="text" id="settingIndexSearch" placeholder="search">
<input type="text" id="settingIndexSort" placeholder="sort">
<input type="text" id="settingIndexFilter" placeholder="filter">
<a href="javascript:;" onclick="settingIndex()">manager/setting/index</a> <span id="settingIndex"></span><br>

<input type="text" id="settingStoreData" placeholder="data" value='{"site_name":"TEST"}'>
<a href="javascript:;" onclick="settingStore()">manager/setting/store</a> <span id="settingStore"></span><br>

<input id="game_report_period_start" placeholder="period_start" value="2016-10-10">
<input id="game_report_period_end" placeholder="period_end" value="2016-11-11">
<a href="javascript:;" onclick="gameReport()">manager/stat/game_report</a> <span id="statGameReport"></span><br>

<script>
function $(id) {
    return document.getElementById(id);
}
var xhr = new XMLHttpRequest();
var tool = {
    "_ajax": function (method, subUrl, area, data) {
        data = data || {};
        var dto = [];
        for (var key in data) {
            dto.push(key + "=" + data[key]);
        }
        dto = dto.join("&");
        if (method.toLowerCase() == "get" && dto != "") {
            subUrl += "?" + dto;
        }
        xhr.open(method, "<?php echo $params['prefixUrl']; ?>" + subUrl, true);
        xhr.withCredentials = true;
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
        xhr.setRequestHeader("Accept", "application/json, text/plain, */*");
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                document.getElementById(area).innerHTML = xhr.responseText;
                var b = JSON.parse(xhr.responseText);
                if (b.success == true) {
                    alert(b.msg);
                } else {
                    alert(b.msg);
                }
            }
        };
        xhr.send(dto);
    },
    "get": function (subUrl, area, data) {
        this._ajax("get", subUrl, area, data);
    },
    "post": function (subUrl, area, data) {
        this._ajax("post", subUrl, area, data);
    }
};

function login() {
    tool.post("site/login", "resultLogin", {
        "username": $('loginUsername').value,
        "password": $('loginPassword').value,
        "captcha": $('loginCaptcha').value
    });
}

function isLogin() {
    tool.get("site/is_login", "resultIsLogin", null);
}

function logout() {
    tool.post("site/logout", "resultLogout", null);
}


function commonBalanceValue() {
    tool.get("manager/common/balance_value", "commonBalanceValue", null);
}


function keywordIndex(){
    tool.post("manager/keyword/index", "keywordIndex", {
        "campaignid": $('keywordCampaignId').value
    });
}

function keywordStore()
{
    tool.post("manager/keyword/store", "keywordStore", {
        "id": $('keywordStoreId').value,
        "campaignid": $('keywordStoreCampaignId').value,
        "keyword": $('keywordStoreKeyword').value,
        "price_up": $('keywordStorePriceUp').value
    });
}

function keywordDelete()
{
    tool.post("manager/keyword/delete", "keywordDelete", {
        "id": $('keywordDeleteId').value
    });
}

function recharge() {
    tool.get(" broker/balance/recharge", "rechargeResult", null);
}
function gift() {
    tool.get(" broker/balance/gift", "giftResult", null);
}
function invoice_history () {
    tool.get(" broker/balance/invoice_history ", "invoice_historyResult", null);
}

function advertiserIndex() {
    tool.post("manager/advertiser/index", "advertiserIndex", {
        "pageSize": $('advertiserPageSize').value,
        "pageNo": $('advertiserPageNo').value,
        "search": $('advertiserSearch').value,
        "sort": $('advertiserSort').value,
        "account_type": $('advertiserType').value
    });
}

function advertiserStore() {
    tool.post("manager/advertiser/store", "advertiserStore", {
        "username":$('advertiserStoreUsername').value,
        "password":$('advertiserStorePassword').value,
        "clientname":$('advertiserStoreClientName').value,
        "brief_name":$('advertiserStoreBriefName').value,
        "contact":$('advertiserStoreContact').value,
        "contact_phone":$('advertiserStoreContactPhone').value,
        "qq":$('advertiserStoreQq').value,
        "email":$('advertiserStoreEmail').value,
        "creator_uid":$('advertiserStoreCreatorUid').value,
        "type":$('advertiserStoreType').value
    });
}
function advertiserUpdate()
{
    tool.post("manager/advertiser/update", "advertiserUpdate", {
        "clientid":$('advertiserUpdateId').value,
        "field":$('advertiserUpdateField').value,
        "value":$('advertiserUpdateValue').value,
        "account_type":$('advertiserStoreType').value
    });
}


function brokerIndex() {
    tool.post("manager/broker/index", "brokerIndex", {
        "pageSize": $('brokerPageSize').value,
        "pageNo": $('brokerPageNo').value,
        "search": $('brokerSearch').value,
        "sort": $('brokerSort').value
    });
}

function brokerStore() {
    tool.post("manager/broker/store", "brokerStore", {
        "username":$('brokerStoreUsername').value,
        "password":$('brokerStorePassword').value,
        "name":$('brokerStoreName').value,
        "brief_name":$('brokerStoreBriefName').value,
        "contact":$('brokerStoreContact').value,
        "contact_phone":$('brokerStoreContactPhone').value,
        "qq":$('brokerStoreQq').value,
        "email":$('brokerStoreEmail').value,
        "creator_uid":$('brokerStoreCreatorUid').value
    });
}
function brokerUpdate()
{
    tool.post("manager/broker/update", "brokerUpdate", {
        "brokerid":$('brokerUpdateId').value,
        "field":$('brokerUpdateField').value,
        "value":$('brokerUpdateValue').value
    });
}


function traffickerIndex() {
    tool.post("manager/trafficker/index", "traffickerIndex", {
        "pageSize": $('traffickerPageSize').value,
        "pageNo": $('traffickerPageNo').value,
        "search": $('traffickerSearch').value,
        "sort": $('traffickerSort').value
    });
}

function traffickerStore() {
    tool.post("manager/trafficker/store", "traffickerStore", {
        "username":$('traffickerStoreUsername').value,
        "password":$('traffickerStorePassword').value,
        "name":$('traffickerStoreName').value,
        "brief_name":$('traffickerStoreBriefName').value,
        "contact":$('traffickerStoreContact').value,
        "contact_phone":$('traffickerStoreContactPhone').value,
        "qq":$('traffickerStoreQq').value,
        "email":$('traffickerStoreEmail').value,
        "income_rate":$('traffickerStoreIncomeRate').value,
        "mode":$('traffickerStoreMode').value,
        "creator_uid":$('traffickerStoreCreatorUid').value,
        "app_platform":$('traffickerStoreAppPlatform').value,
        "audit":$('traffickerStoreAudit').value,
        "delivery":$('traffickerStoreDelivery').value,
        "kind":$('traffickerStoreKind').value,
        "alipay_account":$('traffickerStoreAlipayAccount').value,
    });
}
function traffickerUpdate()
{
    tool.post("manager/trafficker/update", "traffickerUpdate", {
        "id":$('traffickerUpdateId').value,
        "field":$('traffickerUpdateField').value,
        "value":$('traffickerUpdateValue').value
    });
}

function traffickerSales() {
    tool.get("manager/trafficker/sales", "traffickerSales", null);
}


function accountIndex() {
    tool.post("manager/account/index", "accountIndex", {
        "pageSize": $('accountPageSize').value,
        "pageNo": $('accountPageNo').value,
        "search": $('accountSearch').value,
        "sort": $('accountSort').value,
        "type": $('accountType').value
    });
}

function accountStore() {
    tool.post("manager/account/store", "accountStore", {
        "username":$('accountStoreUsername').value,
        "password":$('accountStorePassword').value,
        "contact_name":$('accountStoreContactName').value,
        "contact_phone":$('accountStoreContactPhone').value,
        "email_address":$('accountStoreEmailAddress').value,
        "account_sub_type_id":$('accountStoreAccountSubTypeId').value,
        "operation_list":$('accountStoreOperationList').value
    });
}

function accountUpdate()
{
    tool.post("manager/account/update", "accountUpdate", {
        "id":$('accountUpdateId').value,
        "field":$('accountUpdateField').value,
        "value":$('accountUpdateValue').value
    });
}

function accountAccountSubType() {
    tool.get("site/account_sub_type", "accountAccountSubType", {
        "type":$('accountType0').value
    });
}

function accountOperation() {
    tool.get("site/operation", "accountOperation", {
        "type":$('accountType1').value
    });
}

function materialCheck()
{
    tool.post("manager/material/check", "materialCheck", {
        "campaignid":$('materialCheckAppId').value,
        "status":$('materialCheckStatus').value
    });
}

function campaignIndex() {
    tool.post("manager/campaign/index", "campaignIndex", {
        "search": $('campaignIndexSearch').value,
        "sort": $('campaignIndexSort').value,
        "campaignid": $('campaignIndexCampaignId').value,
        "ad_type": $('campaignIndexAdType').value,
        "platform": $('campaignIndexPlatform').value,
        "status": $('campaignIndexStatus').value,
        "day_limit": $('campaignIndexDayLimit').value,
        "revenue": $('campaignIndexRevenue').value,
        "revenue_type": $('campaignIndexRevenueType').value
    });
}

function campaignAffiliateUpdate() {
    tool.post("manager/campaign/affiliate_update", "campaignAffiliateUpdate", {
        "campaignid": $('campaignAffiliateUpdateCampaignId').value,
        "affiliateid": $('campaignAffiliateUpdateAffiliateId').value,
        "bannerid": $('campaignAffiliateUpdateBannerId').value,
        "ad_type": $('campaignAffiliateUpdateAdType').value,
        "field": $('campaignAffiliateUpdateField').value,
        "old_attach_id": $('campaignAffiliateUpdateOldAttachId').value,
        "value": $('campaignAffiliateUpdateValue').value
    });
}

function campaignAffiliate()
{
    tool.post("manager/campaign/affiliate", "campaignAffiliate", {
        "campaignid": $('affiliateCampaignId').value,
        "ad_type": $('campaignAffiliateAdType').value,
        "products_type": $('campaignAffiliateProductType').value,
        "mode": $('campaignAffiliateMode').value,
        "search": $('campaignAffiliateSearch').value,
        "sort": $('campaignAffiliateSort').value
    });
}
function appSearch()
{
    tool.post("manager/campaign/app_search", "appSearch", {
        "affiliateid": $('appSearchAffiliatesId').value,
        "words": $('appSearchWords').value,
        "platform": $('appSearchPlatform').value
    });
}
function campaignCheck() {
    tool.post("manager/campaign/check", "campaignCheck", {
        "campaignid": $('campaignCheckCampaignId').value,
        "status": $('campaignCheckStatus').value,
        "channel": $('campaignCheckChannel').value,
        "rate": $('campaignCheckRate').value,
        "comment": $('campaignCheckComment').value
    });
}

function campaignRelease() {
    tool.post("manager/campaign/release", "campaignRelease", {
        "affiliateid": $('campaignReleaseAffiliateId').value,
        "campaignid": $('campaignReleaseCampaignId').value,
        "bannerid": $('campaignReleaseBannerId').value,
        "status": $('campaignReleaseStatus').value,
        "action": $('campaignReleaseAction').value,
        "mode": $('campaignReleaseMode').value
    });
}

function campaignCategory()
{
    tool.post("manager/campaign/category", "campaignCategory", {
        "affiliateid":$('categoryCampaignId').value
    });
}
function campaignRevenue()
{
    tool.get("manager/campaign/revenue", "campaignRevenue", null);
}
function campaignDayLimit()
{
    tool.get("manager/campaign/day_limit", "campaignDayLimit", null);
}

function campaignUpdate()
{
    tool.post("manager/campaign/update", "campaignUpdate", {
        "campaignid":$('campaignUpdateCampaignId').value,
        "field":$('campaignUpdateField').value,
        "value":$('campaignUpdateValue').value
    });
}
function campaignRevenueHistory()
{
    tool.post("manager/campaign/revenue_history", "campaignRevenueHistory", {
        "campaignid":$('campaignRevenueHistoryCampaignId').value
    });
}

function campaignEquivalenceList() {
    tool.post("manager/campaign/equivalence_list", "campaignEquivalenceList", {
        "campaignid": $('campaignEquivalenceListCampaignId').value,
        "platform": $('campaignEquivalenceListPlatform').value,
        "ad_type": $('campaignEquivalenceListAdType').value,
        "revenue_type": $('campaignEquivalenceListRevenueType').value,
        "search": $('campaignEquivalenceListSearch').value
    });
}

function campaignConsume()
{
    tool.post("manager/campaign/consume", "campaignConsume", {
        "campaignid": $('campaignConsumeCampaignId').value
    });
}

function campaignEquivalence() {
    tool.post("manager/campaign/equivalence", "campaignEquivalence", {
        "campaignid": $('campaignEquivalenceCampaignId').value,
        "campaignid_relation": $('campaignEquivalenceCampaignIdRelation').value,
        "action": $('campaignEquivalenceAction').value
    });
}

function campaignRank()
{
    tool.post("manager/campaign/rank", "campaignRank", {
        "affiliateid":$('rankCampaignId').value,
        "platform":$('rankPlatform').value
    });
}

function campaignInfo()
{
    tool.post("manager/campaign/info", "campaignInfo", {
        "campaignid":$('infoCampaignId').value
    });
}
function campaignPackage()
{
    tool.post("manager/campaign/client_package", "campaignPackage", {
        "campaignid":$('campaignPackageCampaignId').value
    });
}

function campaignRevenueType()
{
    tool.post("manager/campaign/client_list", "campaignRevenueType", {
        "revenue_type":$('campaignClientRevenueType').value
    });
}
function campaignStore()
{
    tool.post("manager/campaign/store", "campaignStore", {
        "revenue_type":$('campaignClientRevenueType').value,
        "clientid":$('campaignStoreClientId').value,
        "products_id":$('campaignStoreProductId').value,
        "platform":$('campaignStorePlatform').value,
        "products_name":$('campaignStoreProductName').value,
        "products_icon":$('campaignStoreProductIcon').value,
        "appinfos_app_name":$('campaignStoreAppInfoAppName').value
    });
}

function campaignProduct()
{
    tool.post("manager/campaign/product_list", "campaignProduct", {
        "clientid":$('campaignProductClient').value
    });
}


function campaignLogIndex() {
    tool.post("manager/common/log_index", "campaignLogIndex", {
        "category": $('campaignLogIndexCategory').value,
        "target_id": $('campaignLogIndexTargetId').value,
        "search": $('campaignLogIndexSearch').value,
        "sort": $('campaignLogIndexSort').value
    });
}

function campaignLogStore()
{
    tool.post("manager/common/log_store", "campaignLogStore", {
        "category":$('campaignLogStoreCategory').value,
        "target_id":$('campaignLogStoreTargetId').value,
        "message":$('campaignLogStoreMessage').value
    });
}

function campaignBannerLogIndex() {
    tool.post("manager/campaign/banner_log_index", "campaignBannerLogIndex", {
        "bannerid": $('campaignBannerLogIndexBannerId').value,
        "search": $('campaignBannerLogIndexSearch').value,
        "sort": $('campaignBannerLogIndexSort').value
    });
}

function campaignBannerLogStore()
{
    tool.post("manager/campaign/banner_log_store", "campaignBannerLogStore", {
        "bannerid":$('campaignBannerLogStoreBannerId').value,
        "message":$('campaignBannerLogStoreMessage').value
    });
}

function materialView()
{
    tool.post("manager/material/view", "materialView", {
        "campaignid":$('campaignViewCampaignId').value
    });
}

function materialIndex()
{
    tool.post("manager/material/index", "materialIndex", {
        "pageNo":$('materialIndexPageNo').value,
        "pageSize":$('materialIndexPageSize').value,
        "search":$('materialIndexSearch').value,
        "sort":$('materialIndexSort').value
    });
}

function materialInfo()
{
    tool.post("manager/campaign/info", "materialInfo", {
        "campaignid":$('materialInfoCampaignId').value
    });
}

function revenueType()
{
    tool.post("manager/campaign/revenue_type", "revenueType", {
    "affiliateid":$('revenueTypeAffiliateId').value,
    "ad_type":$('revenueTypeAdType').value,
        "revenue_type":$('revenueTypeRevenueType').value
});

}
function commonSales()
{
    tool.post("manager/common/sales", "commonSales", {
        "account_type":$('CommonAccountType').value
    });
}

function packageNotLatest()
{
    tool.get("manager/common/package_not_latest", "packageNotLatest", null);
}
function advertiserHistory()
{
    tool.post("manager/advertiser/recharge_history", "rechargeHistory", {
        "clientid":$('advertiserHistoryClientId').value,
        "way":$('advertiserHistoryWay').value
    });
}

function advertiserRechargeApply()
{
    tool.post("manager/advertiser/recharge_apply", "rechargeApply", {
        "clientid":$('advertiserRechargeApplyClientId').value,
        "way":$('advertiserRechargeApplyWay').value,
        "account_info":$('advertiserRechargeApplyAccountInfo').value,
        "date":$('advertiserRechargeApplyDate').value,
        "amount":$('advertiserRechargeApplyAmount').value
    });
}
function advertiserRechargeDetail()
{
    tool.post("manager/advertiser/recharge_detail", "rechargeDetail", {
        "clientid":$('advertiserRechargeDetailClientId').value
    });
}
function advertiserGiftApply()
{
    tool.post("manager/advertiser/gift_apply", "giftApply", {
        "clientid":$('advertiserGiftApplyClientId').value,
        "gift_info":$('advertiserGiftApplyGiftInfo').value,
        "amount":$('advertiserGiftApplyAmount').value
    });
}
function advertiserGiftDetail()
{
    tool.post("manager/advertiser/gift_detail", "rechargeDetail", {
        "clientid":$('advertiserGiftDetailClientId').value
    });
}
function brokerHistory()
{
    tool.post("manager/broker/recharge_history", "rechargeHistory", {
        "brokerid":$('brokerHistoryClientId').value,
        "way":$('brokerHistoryWay').value
    });
}

function brokerRechargeApply()
{
    tool.post("manager/broker/recharge_apply", "rechargeApply", {
        "brokerid":$('brokerRechargeApplyClientId').value,
        "way":$('brokerRechargeApplyWay').value,
        "account_info":$('brokerRechargeApplyAccountInfo').value,
        "date":$('brokerRechargeApplyDate').value,
        "amount":$('brokerRechargeApplyAmount').value
    });
}
function brokerRechargeDetail()
{
    tool.post("manager/broker/recharge_detail", "rechargeDetail", {
        "brokerid":$('brokerRechargeDetailClientId').value
    });
}
function brokerGiftApply()
{
    tool.post("manager/broker/gift_apply", "giftApply", {
        "brokerid":$('brokerGiftApplyClientId').value,
        "gift_info":$('brokerGiftApplyGiftInfo').value,
        "amount":$('brokerGiftApplyAmount').value
    });
}
function brokerGiftDetail()
{
    tool.post("manager/broker/gift_detail", "rechargeDetail", {
        "brokerid":$('brokerGiftDetailClientId').value
    });
}

function packIndex()
{
    tool.post("manager/pack/index", "packIndex", {
        "pageNo":$('packIndexPageNo').value,
        "pageSize":$('packIndexPageSize').value,
        "search":$('packIndexSearch').value,
        "sort":$('packIndexSort').value
    });
}

function packClientPackage()
{
    tool.post("manager/pack/client_package", "clientPackage", {
        "campaignid":$('packIndexCampaignId').value

    });
}

function packDeliveryAffiliate()
{
    tool.post("manager/pack/delivery_affiliate", "deliveryAffiliate", {
        "attach_id":$('packDeliveryAttachId').value
    });
}
function packUpdate()
{
    tool.post("manager/pack/update", "packUpdate", {
        "attach_id":$('packUpdateAttachId').value,
        "field":$('packUpdateField').value,
        "value":$('packUpdateValue').value
    });
}

function balanceIncome() {
    tool.post("manager/balance/income", "balanceIncome", {
        "pageSize": $('balancePageSize').value,
        "pageNo": $('balancePageNo').value
    });
}

function rechargeIndex() {
    tool.post("manager/balance/recharge_index", "rechargeIndex", {
        "pageSize": $('balanceRechargePageSize').value,
        "pageNo": $('balanceRechargePageNo').value
    });
}

function rechargePass() {
    tool.post("manager/balance/recharge_update", "rechargeUpdate", {
        "id": $('rechargeId').value,
        "content": $('rechargeContent').value,
        "status" : $('rechargeStatus').value
    });
}

function invoiceIndex() {
    tool.post("manager/balance/invoice_index", "invoiceIndex", {
        "pageSize": $('invoiceIndexPageSize').value,
        "pageNo": $('invoiceIndexPageNo').value,
        "search": $('invoiceSearch').value
    });
}

function invoicePass() {
    tool.post("manager/balance/invoice_update", "invoiceUpdate", {
        "id": $('invoiceId').value,
        "field": $('invoiceField').value,
        "value" : $('invoiceValue').value
    });
}
function statTrend() {
    tool.get("manager/stat/trend", "statTrend", {
        "type": $('statTrendType').value
    });
}
function statRank() {
    tool.get("manager/stat/rank", "statRank", {
        "date_type": $('statRankType').value
    });
}
function statTraffickerTrend() {
    tool.get("manager/stat/trafficker_trend", "statTraffickerTrend", {
        "type": $('statTraffickerTrendType').value
    });
}
function statTraffickerDaily() {
    tool.get("manager/stat/trafficker_daily", "statTraffickerDaily", {
        "type": $('statTraffickerDailyType').value
    });
}
function statTraffickerWeekRetain() {
    tool.get("manager/stat/trafficker_week_retain", "statTraffickerWeekRetain", {
        "date": $('statTraffickerWeekRetainDate').value
    });
}
function statTraffickerMonth() {
    tool.get("manager/stat/trafficker_month", "statTraffickerMonth");
}
function statSaleTrend() {
    tool.get("manager/stat/sale_trend", "statSaleTrend", {
        "type": $('statSaleTrendType').value
    });
}
function statSaleRank() {
    tool.get("manager/stat/sale_rank", "statSaleRank", {
        "date_type": $('statSaleRankType').value
    });
}
function zone() {
    tool.get("manager/stat/zone", "statZone", {
        "period_start": $('period_start').value,
        "period_end": $('period_end').value,
        "span": $('span').value,
        "zone_offset": $('zone_offset').value,
        "audit": $('audit').value
    });
}
function zoneAffiliate() {
    tool.get("manager/stat/zone_affiliate", "statZoneAffiliate", {
        "period_start": $('statPeriodStart').value,
        "period_end": $('statPeriodEnd').value,
        "span": $('statSpan').value,
        "zone_offset": $('statZoneOffset').value,
        "audit": $('statAudit').value,
        "affiliateid": $('statAffiliateid').value
    });
}
function zoneExcel() {
    var url = "<?php echo $params['prefixUrl']; ?>manager/stat/zone_excel?period_start="+$('statExcelPeriodStart').value+"&period_end="+ $('statExcelPeriodEnd').value+"&span="+$('statExcelSpan').value
        +"&zoneOffset="+$('statExcelOffset').value+"&audit="+$('statExcelAudit').value+"&affiliateid="+$('statExcelAffiliateid').value+"&bannerid="+$('statExcelBannerId').value;
    window.location.href = url;
}

function manualData()
{
    tool.post("manager/stat/manual_data", "manualData", {
        "affiliateid": $('manualDataAffiliateId').value,
        "date": $('manualDataDate').value,
        "search":$('manualDataSearch').value,
        "pageNo": $('manualDataPageNo').value,
        "pageSize": $('manualDataPageSize').value
    });
}


function clientData()
{
    tool.post("manager/stat/client_data", "clientData", {
        "date": $('clientDataDate').value,
        "platform": $('clientDataPlatform').value,
        "product_id": $('clientDataProductId').value,
        "campaignid": $('clientDataCampaignId').value,
        "search":$('clientDataSearch').value,
        "pageNo": $('clientDataPageNo').value,
        "pageSize": $('clientDataPageSize').value
    });
}

function product() {
    tool.post("manager/stat/product", "product", {
        "date": $('productDate').value,
        "platform": $('productPlatform').value
    });
}
function campaign(){
    tool.post("manager/stat/campaign", "campaign", {
        "date": $('campaignDate').value,
        "platform": $('campaignPlatform').value,
        "product_id": $('campaignProductId').value
    });
}

function zone() {
    tool.get("manager/stat/zone", "statZone", {
        "period_start": $('period_start').value,
        "period_end": $('period_end').value,
        "span": $('span').value,
        "zone_offset": $('zone_offset').value,
        "audit": $('audit').value
    });
}
function zoneAffiliate() {
    tool.get("manager/stat/zone_affiliate", "statZoneAffiliate", {
        "period_start": $('statPeriodStart').value,
        "period_end": $('statPeriodEnd').value,
        "span": $('statSpan').value,
        "zone_offset": $('statZoneOffset').value,
        "audit": $('statAudit').value,
        "affiliateid": $('statAffiliateid').value
    });
}
function zoneExcel() {
    var url = "<?php echo $params['prefixUrl']; ?>manager/stat/zone_excel?period_start="+$('statExcelPeriodStart').value+"&period_end="+ $('statExcelPeriodEnd').value+"&span="+$('statExcelSpan').value
        +"&zone_offset="+$('statExcelOffset').value+"&audit="+$('statExcelAudit').value+"&affiliateid="+$('statExcelAffiliateid').value+"&bannerid="+$('statExcelBannerId').value;
    window.location.href = url;
}

function zoneDailyExcel() {
    var url = "<?php echo $params['prefixUrl']; ?>manager/stat/zone_daily_excel?period_start="+$('zoneDailyExcelPeriodStart').value+"&period_end="+ $('zoneDailyExcelPeriodEnd').value+"&span="+$('zoneDailyExcelSpan').value
        +"&zone_offset="+$('zoneDailyExcelOffset').value+"&audit="+$('zoneDailyExcelAudit').value+"&affiliateid="+$('zoneDailyExcelAffiliateid').value+"&bannerid="+$('zoneDailyExcelBannerId').value;
    window.location.href = url;
}
function client() {
    tool.get("manager/stat/client", "statClient", {
        "period_start": $('ClientPeriodStart').value,
        "period_end": $('ClientPeriodEnd').value,
        "span": $('ClientSpan').value,
        "zone_offset": $('ClientZoneOffset').value,
        "audit": $('ClientAudit').value
    });
}
function clientCampaign() {
    tool.get("manager/stat/client_campaign", "statClientCampaign", {
        "period_start": $('clientCampaignPeriodStart').value,
        "period_end": $('clientCampaignPeriodEnd').value,
        "span": $('clientCampaignSpan').value,
        "zone_offset": $('clientCampaignZoneOffset').value,
        "audit": $('clientCampaignAudit').value,
        "campaignid": $('clientCampaignCampaignId').value
    });
}
function clientExcel() {
    var url = "<?php echo $params['prefixUrl']; ?>manager/stat/client_excel?period_start="+$('clientExcelPeriodStart').value
        +"&period_end="+ $('clientExcelPeriodEnd').value
        +"&span=" +$('clientExcelSpan').value
        +"&zone_offset="+$('clientExcelOffset').value
        +"&audit="+$('clientExcelAudit').value
        +"&productid="+$('clientExcelProductId').value
        +"&campaignid="+$('clientExcelCampaignId').value
        +"&bannerid="+$('clientExcelBannerId').value;
    window.location.href = url;
}
function clientDailyExcel() {
    var url = "<?php echo $params['prefixUrl']; ?>manager/stat/client_daily_excel?period_start="+$('clientDailyExcelPeriodStart').value
        +"&period_end="+ $('clientDailyExcelPeriodEnd').value
        +"&span="+$('clientDailyExcelSpan').value
        +"&zone_offset="+$('clientDailyExcelOffset').value
        +"&audit="+$('clientDailyExcelAudit').value
        +"&productid=" +$('clientDailyExcelProductId').value
        +"&campaignid=" +$('clientDailyExcelCampaignId').value
        +"&bannerid="+$('clientDailyExcelBannerId').value;
    window.location.href = url;
}

function giftIndex() {
    tool.post("manager/balance/gift_index", "rechargeIndex", {
        "pageSize": $('giftIndexPageSize').value,
        "pageNo": $('giftIndexPageNo').value
    });
}

function withdrawalIndex() {
    tool.post("manager/balance/withdrawal_index", "withdrawalIndex", {
        "pageSize": $('withdrawPageSize').value,
        "pageNo": $('withdrawPageNo').value
    });
}

function incomeIndex() {
    tool.post("manager/balance/income_index", "incomeIndex", {
        "pageSize": $('incomeIndexSize').value,
        "pageNo": $('incomeIndexNo').value
    });
}

function dailyList() {
    tool.post("manager/activity/report_list", "dailyList", {
        "type": $('dailyListType').value
    });
}


function accountList()
{
    tool.get("manager/activity/account_list", "accountList", {});
}
    function updateMailReceiver()
    {
        tool.post("manager/activity/update_mail_receiver", "updateMailReceiver", {
            "user_id":$('updateMailReceiverUserId').value,
            "status":$('updateMailReceiverStatus').value
        });
    }
function pauseSendMail(){
    tool.post("manager/activity/pause_send_mail", "pauseSendMail", {
        "id":$('pauseSendMailDate').value
    });
}

function resendMail(){
    tool.post("manager/activity/resend_mail", "resendMail", {
        "id":$('resendMailDate').value
    });
}

function settingIndex() {
    tool.post("manager/setting/index", "settingIndex", {
        "search": $('settingIndexSearch').value,
        "sort": $('settingIndexSort').value,
        "filter": $('settingIndexFilter').value,
        "agencyid": $('settingIndexAgencyid').value
    });
}
function settingStore() {
    tool.post("manager/setting/store", "settingStore", {
        "data": $('settingStoreData').value
    });
}
function gameReport() {
    tool.get("manager/stat/game_report", "statGameReport", {
        "period_start": $('game_report_period_start').value,
        "period_end": $('game_report_period_end').value
    });
}

</script>
</div>
</div>
</body>
</html>
