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

    <input name="loginUsername" id="loginUsername" placeholder="username" value="uc浏览器">
    <input name="loginPassword" id="loginPassword" placeholder="password" value="123456">
    <input name="loginCaptcha" id="loginCaptcha" placeholder="captcha">
    <img src="<?php echo $params['prefixUrl']; ?>site/captcha" id="img_num" style="cursor: pointer" onclick="this.src='<?php echo $params['prefixUrl']; ?>site/captcha?'+new Date().getTime()" ;="" width="86" height="40" alt="">
    <a href="javascript:;" onclick="login()">login</a> <span id="resultLogin"></span><br>

    <a href="javascript:;" onclick="logout()">logout</a> <span id="resultLogout"></span><br>

    <input name="changeId" id="changeId" placeholder="changeId" value="">
    <a href="javascript:;" onclick="change()">change</a> <span id="resultChange"></span><br>

    <a href="javascript:;" onclick="profileView()">profile_view</a> <span id="resultProfileView"></span><br>

    <input id="profileContactName" placeholder="contact_name" />
    <input id="profileEmailAddress" placeholder="email_address" />
    <input id="profileContactPhone" placeholder="contact_phone" />
    <input id="profileQQ" placeholder="qq" />
    <a href="javascript:;" onclick="profile()">profile</a> <span id="resultProfile"></span><br />
    <br />
    <input id="passwordOld" placeholder="password_old" />
    <input id="password" placeholder="password" />
    <input id="passwordConfirmation" placeholder="password_confirmation" />
    <a href="javascript:;" onclick="repassword()">password</a> <span id="resultPassword"></span><br />

    <a href="javascript:;" onclick="nav()">nav</a> <span id="resultNav"></span><br>
    <input type="text" id="noticeListState" placeholder="state">
    <input type="text" id="noticeListSearch" placeholder="search">
    <a href="javascript:;" onclick="notice_list()">notice_list</a> <span id="notice_list"></span><br>
    <input type="text" id="noticeStoreIds" placeholder="ids">
    <input type="text" id="noticeStoreStatus" placeholder="status">
    <a href="javascript:;" onclick="noticeStore()">noticeStore</a> <span id="noticeStore"></span><br>
    <a href="javascript:;" onclick="balance_value()">balance_value</a> <span id="balance_value"></span><br>
    <a href="javascript:;" onclick="sales()">sales</a> <span id="sales"></span><br>
    <input type="text" id="activity" placeholder="activity">
    <a href="javascript:;" onclick="activity()">activity</a> <span id="activity"></span><br>
    <a href="javascript:;" onclick="dashboard()">dashboard</a> <span id="dashboard"></span><br>

    <br>
    <hr>
    <br>
    <a href="javascript:;" onclick="campaignPlatform()">campaign_platform</a> <span id="resultCampaignPlatform"></span><br>
    <a href="javascript:;" onclick="campaignColumnList()">campaign_column_list</a> <span id="resultCampaignColumnList"></span><br>

    <input name="campaignListPageNo" id="campaignListPageNo" placeholder="campaignListPageNo">
    <input name="campaignListPageSize" id="campaignListPageSize" placeholder="campaignListPageSize">
    <input name="campaignListSearch" id="campaignListSearch" placeholder="campaignListSearch">
    <input name="campaignListSort" id="campaignListSort" placeholder="campaignListSort">
    <a href="javascript:;" onclick="campaignList()">campaign_list</a> <span id="resultCampaignList"></span><br>
    <input name="campaignDeleteId" id="campaignDeleteId" placeholder="id">
    <a href="javascript:;" onclick="campaignDelete()">campaign_delete</a> <span id="resultCampaignDelete"></span><br>

    <input name="keywordid" id="keywordid" placeholder="keywordid">
    <input name="campaignid" id="campaignid" placeholder="campaignid">
    <input name="keyword" id="keyword" placeholder="keyword">
    <input name="price_up" id="price_up" placeholder="price_up"><br>
    <a href="javascript:;" onclick="storekeyword()">keywords_store</a><span id="resultstore"></span><br>
    <a href="javascript:;" onclick="keywordlist()">keywords_list</a> <span id="resultLogout"></span><br>
    <a href="javascript:;" onclick="deletekeyword()">keywords_delete</a> <span id="resultLogout"></span><br>

    <input type="text" id="camId" placeholder="id">
    <input type="text" id="camPlatform" placeholder="platform" value="7">
    <input type="text" id="camPlatformName" placeholder="platform_name">
    <input type="text" id="camRevenue" placeholder="revenue" value="<?php echo rand(1, 5) ?>">
<input type="text" id="camRevenueType" placeholder="revenue_type" value="2">
<input type="text" id="camDayLimit" placeholder="day_limit" value="<?php echo rand(1000, 50000) ?>">
<input type="text" id="camTotalLimit" placeholder="total_limit" value="<?php echo rand(50000, 99990) ?>">
<input type="text" id="camStatus" placeholder="action" value="1">
<input type="text" id="camProductsId" placeholder="products_id">
<input type="text" id="camProductsType" placeholder="products_type" value="1">
<input type="text" id="camProductsName" placeholder="products_name" value="<?php echo rand(10000000, 99999999) ?>">
<input type="text" id="camProductsShowName" placeholder="products_show_name" value="<?php echo rand(1000, 9999) ?>">
<input type="text" id="camProductsIcon" placeholder="products_icon" value="http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s9uh6g4n23jtsd3unklk0m60.jpg">
<input type="text" id="camAppinfosAppName" placeholder="appinfos_app_name" value="m-test1-Feeds">
<input type="text" id="camLinkName" placeholder="link name" value="link name">
<input type="text" id="camLinkUrl" placeholder="link url" value="https://www.baidu.com">
<input type="text" id="camLinkTitle" placeholder="link title" value="">
<input type="text" id="camAppinfosDescription" placeholder="appinfos_description">
<input type="text" id="appinfos_profile" placeholder="appinfos_profile">
<input type="text" id="appinfos_update_des" placeholder="appinfos_update_des">
<input type="text" id="ad_type" placeholder="ad_type" value="71">
<input type="text" id="camApplicationId" placeholder="application_id" value="610391947">
<input type="text" id="star" placeholder="star" value="5">
<input type="text" id="appinfos_images1" placeholder="appinfos_images1" value="http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s9v9a11o3l1v1r1sn11opa1nldr60.jpg">
<input type="text" id="appinfos_images2" placeholder="appinfos_images2">
<input type="text" id="appinfos_images3" placeholder="appinfos_images3">
<input type="text" id="ad_spec" placeholder="ad_spec">
<input type="text" id="keywords1" placeholder="price_up" value="<?php echo rand(1, 5) ?>">
<input type="text" id="keywords2" placeholder="keyword" value="<?php echo rand(1000, 9999) ?>">
<input type="text" id="keywords3" placeholder="keyword_id">

    <a href="javascript:;" onclick="campaignStore()">campaignStore</a> <span id="campaignStore"></span><br>
    <input type="text" id="campaignViewId" placeholder="id" />
    <a href="javascript:;" onclick="campaignView()">campaignView</a> <span id="campaignView"></span><br>
    <a href="javascript:;" onclick="campaignMoneyLimit()">campaign_money_limit</a> <span id="resultCampaignMoneyLimit"></span><br>
    <a href="javascript:;" onclick="campaignBannerDemand()">campaign_demand</a> <span id="resultCampaignBannerDemand"></span><br>
    <input type="text" placeholder="product_type" id="ProductListType">
    <a href="javascript:;" onclick="product_list()">product_list</a> <span id="product_list"></span><br>
    <a href="javascript:;" onclick="qiniuToken()">qiniu_token</a> <span id="resultQiniuToken"></span><br>
    <br />
    <input id="campaignUpdateId" placeholder="id">
    <input id="campaignUpdateType" placeholder="type">
    <input id="campaignUpdateField" placeholder="field">
    <input id="campaignUpdateValue" id="value" placeholder="value">
    <a href="javascript:;" onclick="campaignUpdate()">campaign_update</a> <span id="resultCampaignUpdate"></span><br>

    <input type="text" id="campaignAppStoreViewId" placeholder="id" />
    <a href="javascript:;" onclick="campaignAppStoreView()">campaign/app_store_view</a> <span id="campaignAppStoreView"></span><br>
    <a href="javascript:;" onclick="campaignRevenueType()">campaign/revenue_type</a> <span id="campaignRevenueType"></span><br>
<input type="text" id="campaignProductExistId" placeholder="id" />
<input type="text" id="campaignProductExistName" placeholder="name" />
<a href="javascript:;" onclick="campaignProductExist()">campaign/product_exist</a> <span id="campaignProductExist"></span><br>
<input type="text" id="zoneIndex" placeholder="campaignid" />
<a href="javascript:;" onclick="zoneIndex()">zone/index</a> <span id="zoneIndex"></span><br>
    <br />
    <br />
    <input id="balanceLog" placeholder="list">
    <a href="javascript:;" onclick="balanceLog()">advertiser/balance/balance_log</a> <span id="balanceLogResult"></span><br>
    <br />
    <a href="javascript:;" onclick="balancePayout()">advertiser/balance/payout</a> <span id="balancePayoutResult"></span><br>
    <br />
    <a href="javascript:;" onclick="balanceRecharge()">advertiser/balance/recharge</a> <span id="balanceRechargeResult"></span><br>
    <br />
    <a href="javascript:;" onclick="balanceRechargeInvoice()">advertiser/balance/recharge_invoice</a> <span id="balanceRechargeInvoiceResult"></span><br>
    <br />
    <a href="javascript:;" onclick="balanceInvoiceHistory()">advertiser/balance/invoice_history</a> <span id="balanceInvoiceHistoryResult"></span><br>
    <br />
    <input id="invoice" placeholder="list">
    <a href="javascript:;" onclick="invoice()">advertiser/balance/invoice</a> <span id="invoiceResult"></span><br>
    <br />
    <input type="text" id="invoiceStore_ids" placeholder="invoiceStore_ids" value="316">
    <input type="text" id="invoiceStore_title" placeholder="invoiceStore_title" value="深圳市海数互联技术有限公司">
    <input type="text" id="invoiceStore_prov" placeholder="invoiceStore_prov" value="广东">
    <input type="text" id="invoiceStore_city" placeholder="invoiceStore_city" value="深圳">
    <input type="text" id="invoiceStore_dist" placeholder="invoiceStore_dist" value="福田">
    <input type="text" id="invoiceStore_address" placeholder="invoiceStore_address" value="test天安">
    <input type="text" id="invoiceStore_type" placeholder="invoiceStore_type" value="0">
    <input type="text" id="invoiceStore_receiver" placeholder="invoiceStore_receiver" value="test">
    <input type="text" id="invoiceStore_tel" placeholder="invoiceStore_tel" value="18888888888">
    <a href="javascript:;" onclick="invoiceStore()">advertiser/invoice/store</a> <span id="invoiceStoreResult"></span><br>
    <br />
    <a href="javascript:;" onclick="payReceiver_info()">advertiser/pay/receiver_info</a> <span id="payReceiver_infoResult"></span><br>
    <input type="text" id="payStore_recharge" placeholder="payStore_recharge" value="1">
    <input type="text" id="payStore_money" placeholder="payStore_money" value="">
    <a href="javascript:;" onclick="payStore()">advertiser/pay/store</a> <span id="payStoreResult"></span><br>

<br>
<br>
<br>
<input type="text" id="username" placeholder="username">
<input type="text" id="userPassword" placeholder="password">
<input type="text" id="contact" placeholder="contact_name">
<input type="text" id="email" placeholder="email_address">
<input type="text" id="phone" placeholder="phone">
<input type="text" id="qq" placeholder="qq">
<input type="text" id="comment" placeholder="comments">
<input type="text" id="role" placeholder="account_sub_type_id">
<input type="text" id="new_permission" placeholder="operation_list">
    <a href="javascript:;" onclick="user_store()">advertiser/account/store</a> <span id="user_store"></span><br>


    <br>
    <hr>
    <br>
    <input name="accountListPageNo" id="accountListPageNo" placeholder="accountListPageNo">
    <input name="accountListPageSize" id="accountListPageSize" placeholder="accountListPageSize">
    <input name="accountListSearch" id="accountListSearch" placeholder="accountListSearch">
    <a href="javascript:;" onclick="accountList()">account_index</a> <span id="resultAccountList"></span><br>
    <input name="accountUpdateId" id="accountUpdateId" placeholder="accountUpdateId">
    <input name="accountUpdateField" id="accountUpdateField" placeholder="accountUpdateField">
    <input name="accountUpdateValue" id="accountUpdateValue" placeholder="accountUpdateValue">
    <a href="javascript:;" onclick="accountUpdate()">account_update</a> <span id="resultAccountUpdate"></span><br>

    <input id="period_start" placeholder="period_start" value="2015-03-10">
    <input id="period_end" placeholder="period_end" value="2016-02-10">
    <input id="span" placeholder="span" value="2">
    <input id="zone_offset" placeholder="zone_offset" value="-8">
    <input id="type" placeholder="type">
    <a href="javascript:;" onclick="stat()">advertiser/stat/index</a> <span id="statResult"></span><br>

    <input id="axis" placeholder="axis">
    <a href="javascript:;" onclick="campaign_excel()">advertiser/stat/campaign_excel</a> <span id="campaign_excel"></span><br>
    <br />
<a href="javascript:;" onclick="report()">report</a> <span id="report"></span><br>

<input id="period_start_t" placeholder="period_start" value="2015-03-10">
<input id="period_end_t" placeholder="period_end" value="2016-02-10">
<input id="span_t" placeholder="span" value="2">
<input id="zone_offset_t" placeholder="zone_offset" value="-8">
<input id="type_t" placeholder="type">
<input id="campaign_id_t" placeholder="campaign_id">
<input id="product_id_t" placeholder="product_id">
<a href="javascript:;" onclick="time_campaign_excel()">advertiser/stat/time_campaign_excel</a> <span id="campaign_excel_t"></span><br>

    <input type="text" id="selfIndexStart" placeholder="period_start">
    <input type="text" id="selfIndexEnd" placeholder="period_end">
    <a href="javascript:;" onclick="selfZone()">advertiser/stat/self_index</a> <span id="selfZoneResult"></span><br>
    <input type="text" id="selfCampaignExcelStart" placeholder="period_start">
    <input type="text" id="selfCampaignExcelEnd" placeholder="period_end">
    <a href="javascript:;" onclick="selfZoneExcel()">advertiser/stat/self_campaign_excel</a> <span id="selfZoneExcelResult"></span><br>

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

function change() {
    tool.post("site/change", "resultChange", {
        "id": $('changeId').value
    });
}

function profileView() {
    tool.get("site/profile_view", "resultProfileView", null);
}

function profile() {
    tool.post("site/profile", "resultProfile", {
        "contact_name": $('profileContactName').value,
        "email_address": $('profileEmailAddress').value,
        "contact_phone": $('profileContactPhone').value,
        "qq": $('profileQQ').value
    });
}
function repassword() {
    tool.post("site/password", "resultPassword", {
        "password_old": $('passwordOld').value,
        "password": $('password').value,
        "password_confirmation": $('passwordConfirmation').value
    });
}
function nav() {
    tool.get("site/nav", "resultNav", null);
}
function balance_value() {
    tool.get("advertiser/common/balance_value","balance_value",null);
}

function notice_list() {
    tool.post("site/notice_list","notice_list",{
       "status":$('noticeListState').value, "search":$('noticeListSearch').value
    });
}

function noticeStore() {
    tool.post("site/notice_store","notice_store",{
        "ids":$('noticeStoreIds').value,
        "status":$('noticeStoreStatus').value
    });
}

function sales() {
    tool.post("advertiser/common/sales","sales",null);
}

function activity() {
    tool.get("site/activity", "activity", {
        "id":$('activity').value
    });
}

function campaignPlatform() {
    tool.get("site/platform", "resultCampaignPlatform", null);
}

function dashboard() {
    tool.get("advertiser/common/dashboard", "dashboard", null);
}
function report() {
    tool.get("advertiser/stat/report", "report", null);
}

function campaignColumnList() {
    xhr.open("GET", "<?php echo $params['prefixUrl']; ?>advertiser/campaign/column_list", true);
    xhr.withCredentials = true;
    xhr.setRequestHeader("Content-Type", "text/plain; charset=UTF-8");
    xhr.setRequestHeader("Accept", "application/json, text/plain, */*");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById('resultCampaignColumnList').innerHTML = xhr.responseText;
            var b = JSON.parse(xhr.responseText);
            if (b.success == true) {
                alert(b.msg);
            } else {
                alert(b.msg);
            }
        }
    };
    xhr.send(null);
}

function campaignList() {
    var str = 'pageNo=' + $('campaignListPageNo').value + '&pageSize=' + $('campaignListPageSize').value + '&search=' + $('campaignListSearch').value + '&sort=' + $('campaignListSort').value;

    xhr.open("POST", "<?php echo $params['prefixUrl']; ?>advertiser/campaign/index", true);
    xhr.withCredentials = true;
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    xhr.setRequestHeader("Accept", "application/json, text/plain, */*");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {

            document.getElementById('resultCampaignList').innerHTML = xhr.responseText;

            var b = JSON.parse(xhr.responseText);
            if (b.success == true) {
                alert(b.msg);
            } else {
                alert(b.msg);
            }
        }
    };
    xhr.send(str);
}


function campaignDelete() {
    var str = 'campaignid=' + $('campaignDeleteId').value;

    xhr.open("POST", "<?php echo $params['prefixUrl']; ?>advertiser/campaign/delete", true);
    xhr.withCredentials = true;
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    xhr.setRequestHeader("Accept", "application/json, text/plain, */*");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById('resultCampaignDelete').innerHTML = xhr.responseText;
            var b = JSON.parse(xhr.responseText);
            if (b.success == true) {
                alert(b.msg);
            } else {
                alert(b.msg);
            }
        }
    };
    xhr.send(str);
}


function storekeyword() {
    var str = 'campaignid=' + $('campaignid').value + '&keyword=' + $('keyword').value + '&price_up=' + $('price_up').value+'&id='+$('keywordid').value;
    xhr.open("POST", "<?php echo $params['prefixUrl']; ?>advertiser/keywords/store", true);
    xhr.withCredentials = true;
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    xhr.setRequestHeader("Accept", "application/json, text/plain, */*");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById('resultstore').innerHTML = xhr.responseText;
            var b = JSON.parse(xhr.responseText);
            if (b.success == true) {
                alert(b.msg);
            } else {
                alert(b.msg);
            }
        }
    };
    xhr.send(str);
}

function keywordlist() {
	var str = 'campaignid=' + $('campaignid').value;
	 xhr.open("POST", "<?php echo $params['prefixUrl']; ?>advertiser/keywords/index", true);
	    xhr.withCredentials = true;
	    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
	    xhr.setRequestHeader("Accept", "application/json, text/plain, */*");
	    xhr.onreadystatechange = function() {
	        if (xhr.readyState == 4 && xhr.status == 200) {
	            document.getElementById('resultstore').innerHTML = xhr.responseText;
	            var b = JSON.parse(xhr.responseText);
	            if (b.success == true) {
	                alert(b.msg);
	            } else {
	                alert(b.msg);
	            }
	        }
	    };
	    xhr.send(str);
}

function deletekeyword() {
	var str = 'id=' + $('keywordid').value;
	 xhr.open("POST", "<?php echo $params['prefixUrl']; ?>advertiser/keywords/delete", true);
	    xhr.withCredentials = true;
	    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
	    xhr.setRequestHeader("Accept", "application/json, text/plain, */*");
	    xhr.onreadystatechange = function() {
	        if (xhr.readyState == 4 && xhr.status == 200) {
	            document.getElementById('resultstore').innerHTML = xhr.responseText;
	            var b = JSON.parse(xhr.responseText);
	            if (b.success == true) {
	                alert(b.msg);
	            } else {
	                alert(b.msg);
	            }
	        }
	    };
	    xhr.send(str);
}

function campaignStore() {
	//应用
    var id = $('camId').value
    var status = $('camStatus').value;
    var products_type = $('camProductsType').value;
    var platform = $('camPlatform').value;
    var products_name = $('camProductsName').value;
    var products_show_name = $('camProductsShowName').value;
    var icon = $('camProductsIcon').value;
    var appinfos_app_name = $('camAppinfosAppName').value;
    var revenue = $('camRevenue').value;
    var star = $('star').value;
    var revenue_type = $('camRevenueType').value;
    var day_limit = $('camDayLimit').value;
    var total_limit = $('camTotalLimit').value;
    var ad_type = $('ad_type').value;
    var applicationId = $('camApplicationId').value;
    var linkName = $('camLinkName').value;
    var linkUrl = $('camLinkUrl').value;
var linkTitle = $('camLinkTitle').value;
    if (ad_type == 0 || ad_type == 71) {

    var appinfos_images = [];
    appinfos_images.push($('appinfos_images1').value);
    appinfos_images.push($('appinfos_images2').value);
    appinfos_images.push($('appinfos_images3').value);    }
else {
    var appinfos_images = [];
    appinfos_images.push($('appinfos_images1').value);
    appinfos_images.push($('ad_spec').value);
}
var platform_name = $('camPlatformName').value;
var products_id = $('camProductsId').value;
var appinfos_description=$('camAppinfosDescription').value;
var appinfos_profile=$('appinfos_profile').value;
var appinfos_update_des=$('appinfos_update_des').value;

var keywords=[];
keywords.push($('keywords1').value);
keywords.push($('keywords2').value);
keywords.push($('keywords3').value);

tool.post("advertiser/campaign/store","campaignStore",{
    "id":id,
    "platform":platform,
    "action":status,
    "products_type":products_type,
    "products_name":products_name,
    "products_show_name":products_show_name,
    "products_icon":icon,
    "appinfos_app_name":appinfos_app_name,
    "revenue":revenue,
    "day_limit":day_limit,
    "total_limit":total_limit,
    "ad_type":ad_type,
    "application_id":applicationId,
    "link_name":linkName,
    "link_url":linkUrl,
    "appinfos_images[0][url]":appinfos_images[0],
    "appinfos_images[0][ad_spec]": appinfos_images[1],
    "appinfos_images[1][url]":appinfos_images[1],
    "appinfos_images[2][url]":appinfos_images[2],
    "revenue_type":revenue_type,
    "platform_name":platform_name,
    "products_id":products_id,
    "appinfos_description":appinfos_description,
    "appinfos_profile":appinfos_profile,
    "appinfos_update_des":appinfos_update_des,
    "keywords[0][price_up]":keywords[0],
    "keywords[0][keyword]":keywords[1],
    "keywords[0][id]":keywords[2],
    "star":star,
    "link_name":linkName,
    "link_url":linkUrl,
    "link_title":linkTitle
});
	/*-------------------------------------------------------------------------------------------------------------------------------------------------------*/
	//banner
/*
	var status =10;
	var products_type =0;
	var platform =1;
	var products_name = 'm-test1';
	var products_show_name = 'm-test1-show';
	var icon = "http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1l3u4fkj1soj1oa0kpr1bgpm60.jpg";
	var appinfos_app_name = 'm-test1-Banner';
	var revenue =100;
    var star =0;
    var profile='';
    var revenue_type=1;
	var day_limit = 2000;
	var appinfos_images ='[{"ad_spec":"4","url":"http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s1lr3n13kl1r2132uvbeeqer60.jpg"}]';
	var ad_type = 1;

    var platform_name = '';
    var products_id = '';
    var appinfos_description='';
    var appinfos_profile='';
    var appinfos_update_des='';
    var keywords = '';

    var str ='id=420&platform='+platform+'&status='+status+'&products_type='+products_type+'&products_name='+products_name+'&products_show_name='+products_show_name
        +'&products_icon='+icon+'&appinfos_app_name='+appinfos_app_name+'&revenue='+revenue+'&day_limit='+day_limit+'&ad_type='+ad_type+'&appinfos_images='+appinfos_images+'&star='+star+
        '&appinfos_profile='+profile+'&revenue_type='+revenue_type+'&platform_name='+platform_name+'&products_id='+products_id+'&appinfos_description='+appinfos_description+'&appinfos_profile='+appinfos_profile
        +'&appinfos_update_des='+appinfos_update_des+'&keywords='+keywords;*/
	//feeds
    /*
	var status =10;
	var products_type =0;
	var platform =1;
	var products_name = 'm-test1';
	var products_show_name = 'm-test1-1';
	var icon = "http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s9uh6g4n23jtsd3unklk0m60.jpg";
	var appinfos_app_name = 'm-test1-Feeds';
	var revenue =10;
	var day_limit = 2000;
	var star =5;
	var profile='hehe';
	var revenue_type=1;
	var appinfos_images ='[{"url":"http://7xnoye.com1.z0.glb.clouddn.com/o_1a9s9v9a11o3l1v1r1sn11opa1nldr60.jpg"}]';
	var ad_type = 2;

    var platform_name = '';
    var products_id = '';
    var appinfos_description='';
    var appinfos_profile='';
    var appinfos_update_des='';
    var keywords = '';

	var str ='id=417&platform='+platform+'&status='+status+'&products_type='+products_type+'&products_name='+products_name+'&products_show_name='+products_show_name
	+'&products_icon='+icon+'&appinfos_app_name='+appinfos_app_name+'&revenue='+revenue+'&day_limit='+day_limit+'&ad_type='+ad_type+'&appinfos_images='+appinfos_images+'&star='+star+
	'&appinfos_profile='+profile+'&revenue_type='+revenue_type+'&platform_name='+platform_name+'&products_id='+products_id+'&appinfos_description='+appinfos_description+'&appinfos_profile='+appinfos_profile
        +'&appinfos_update_des='+appinfos_update_des+'&keywords='+keywords;*/

}

function campaignView() {
    tool.post("advertiser/campaign/view","campaignView",{
        "id":$("campaignViewId").value
    });
}

function campaignAppStoreView() {
    tool.get("advertiser/campaign/app_store_view","campaignAppStoreView",{
        "id":$("campaignAppStoreViewId").value
    });
}

function campaignRevenueType() {
    tool.get("advertiser/campaign/revenue_type", "campaignRevenueType", null);
}
function campaignProductExist() {
    tool.post("advertiser/campaign/product_exist","campaignProductExist",{
        "id": $("campaignProductExistId").value,
       "name": $("campaignProductExistName").value
    });
}
function zoneIndex()
{
    tool.post("advertiser/zone/index","zoneIndex",{
        "campaignid": $("zoneIndex").value
    });
}
function campaignMoneyLimit() {
    tool.get("advertiser/campaign/money_limit", "resultCampaignMoneyLimit", null);
}

function campaignBannerDemand() {
    tool.get("advertiser/campaign/demand", "resultCampaignBannerDemand", null);
}

function product_list()
{
    tool.post("advertiser/campaign/product_list", "product_list", {
        "products_type":$('ProductListType').value
    });
}
function qiniuToken() {
    tool.get("site/qiniu_token", "resultQiniuToken", null);
}

function campaignUpdate() {
    tool.post("advertiser/campaign/update", "resultstore", {
        "id": $('campaignUpdateId').value,
        "type": $("campaignUpdateType").value,
        "field": $('campaignUpdateField').value,
        "value": $('campaignUpdateValue').value
    });
}

function balanceLog() {
    tool.get("advertiser/balance/balance_log", "balanceLogResult", {
        "list": $('balanceLog').value
    });
}

function balancePayout() {
    tool.post("advertiser/balance/payout", "balancePayoutResult", {
    });
}

function balanceRecharge() {
    tool.post("advertiser/balance/recharge", "balanceRechargeResult", {
    });
}

function balanceRechargeInvoice() {
    tool.post("advertiser/balance/recharge_invoice", "balanceRechargeInvoiceResult", {
    });
}

function balanceInvoiceHistory() {
    tool.post("advertiser/balance/invoice_history", "balanceInvoiceHistoryResult", {
    });
}

function invoice() {
    tool.get("advertiser/balance/invoice", "invoiceResult", {
        "invoice_id": $('invoice').value
    });
}

function invoiceStore() {
    tool.post('advertiser/invoice/store', 'invoiceStoreResult', {
        "ids": $("invoiceStore_ids").value,
        "title": $("invoiceStore_title").value,
        "prov": $("invoiceStore_prov").value,
        "city": $("invoiceStore_city").value,
        "dist": $("invoiceStore_dist").value,
        "address": $("invoiceStore_address").value,
        "type": $("invoiceStore_type").value,
        "receiver": $("invoiceStore_receiver").value,
        "tel": $("invoiceStore_tel").value
    });
}
function payReceiver_info() {
    tool.get('advertiser/pay/receiver_info', 'payReceiver_infoResult', null);
}

function user_store() {
    tool.post('advertiser/account/store', 'user_store', {
        "username": $("username").value,
        "password": $("userPassword").value,
        "contact_name": $("contact").value,
        "email_address": $("email").value,
        "phone": $("phone").value,
        "qq": $("qq").value,
        "comments": $("comment").value,
        "account_sub_type_id": $("role").value,
        "operation_list": $("new_permission").value
    });
}

function payStore() {
    window.open('<?php echo $params['prefixUrl']; ?>advertiser/pay/store?recharge=1&money=100',"_blank");
    //query_notice_form.submit();
    /*tool.post('advertiser/pay/store', 'payStoreResult', {
        'money': $('payStore_money').value,
        'recharge': $('payStore_recharge').value
    });*/
}


function accountList() {
    tool.post('advertiser/account/index', 'resultAccountList', {
        "pageNo": $("accountListPageNo").value,
        "pageSize": $("accountListPageSize").value,
        "search": $("accountListSearch").value
    });
}


function accountUpdate() {
    tool.post('advertiser/account/update', 'resultAccountUpdate', {
        "id": $("accountUpdateId").value,
        "field": $("accountUpdateField").value,
        "value": $("accountUpdateValue").value
    });
}
function stat() {
    tool.get("advertiser/stat/index", "statResult", {
        "period_start": $('period_start').value,
        "period_end": $('period_end').value,
        "span": $('span').value,
        "zone_offset": $('zone_offset').value,
        "type": $('type').value
    });
}

function campaign_excel(){
    var url = "<?php echo $params['prefixUrl']; ?>advertiser/stat/campaign_excel?period_start="+$('period_start').value+"&period_end="+ $('period_end').value+"&axis="+$('axis').value
        +"&zoneOffset="+$('zone_offset').value+"&type="+$('type').value;
    window.location.href = url;
}


function time_campaign_excel(){
    var url = "<?php echo $params['prefixUrl']; ?>advertiser/stat/time_campaign_excel?period_start="+$('period_start_t').value+"&period_end="+ $('period_end_t').value+"&span="+$('span_t').value
        +"&zone_offset="+$('zone_offset_t').value+"&type="+$('type_t').value+"&campaign_id="+$('campaign_id_t').value+"&product_id="+$('product_id_t').value;
    window.location.href = url;
}
function selfZone() {
    tool.get("advertiser/stat/self_index", "selfZoneResult", {
        "period_start": $('selfIndexStart').value,
        "period_end": $('selfIndexEnd').value,
    });
}
function selfZoneExcel() {
    var url = "<?php echo $params['prefixUrl']; ?>advertiser/stat/self_campaign_excel?period_start="+$('selfCampaignExcelStart').value+
        "&period_end="+ $('selfCampaignExcelEnd').value;
    window.location.href = url;
}

</script>
</div>
</div>
</body>
</html>
