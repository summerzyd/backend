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

    <input name="loginUsername" id="loginUsername" placeholder="username" value="fota">
    <input name="loginPassword" id="loginPassword" placeholder="password" value="123456">
    <input name="loginCaptcha" id="loginCaptcha" placeholder="captcha">
    <img src="<?php echo $params['prefixUrl']; ?>site/captcha" id="img_num" style="cursor: pointer" onclick="this.src='<?php echo $params['prefixUrl']; ?>site/captcha?'+new Date().getTime()" ;="" width="86" height="40" alt="">
    <a href="javascript:;" onclick="login()">login</a> <span id="resultLogin"></span><br>
    <a href="javascript:;" onclick="logout()">logout</a> <span id="resultLogout"></span><br>
    <a href="javascript:;" onclick="nav()">nav</a> <span id="resultNav"></span><br>

    <a href="javascript:;" onclick="balanceValue()">common/balance_value</a> <span id="resultBalanceValue"></span><br>
    <a href="javascript:;" onclick="sales()">sales</a> <span id="sales"></span><br>
    <a href="javascript:;" onclick="campaignPendingAudit()">campaign_pending_audit</a> <span id="campaignPendingAudit"></span><br>

    <br><hr><br><br>

    <input type="text" id="indexPN" placeholder="pageNo">
    <input type="text" id="indexPS" placeholder="pageSize">
    <input type="text" id="indexSearch" placeholder="search">
    <input type="text" id="indexSort" placeholder="sort">
    <input type="text" id="indexAdType" placeholder="ad_type">
    <input type="text" id="indexPlatform" placeholder="platform">
    <input type="text" id="indexParent" placeholder="parent">
    <input type="text" id="indexStatus" placeholder="status">
    <input type="text" id="indexAppRank" placeholder="appinfos_app_rank">
    <a href="javascript:;" onclick="index()">campaign/index</a> <span id="index"></span><br>

    <input type="text" id="checkCampaignId" placeholder="bannerId">
    <input type="text" id="checkAction" placeholder="action">
    <input type="text" id="checkCategory" placeholder="category">
    <input type="text" id="checkAppinfos_app_rank" placeholder="appinfos_app_rank">
    <a href="javascript:;" onclick="check()">campaign/check</a> <span id="check"></span><br>

    <input type="text" id="columnListAdType" placeholder="ad_type">
    <a href="javascript:;" onclick="columnList()">campaign/columnList</a> <span id="columnList"></span><br>
    <a href="javascript:;" onclick="rank()">campaign/rank</a> <span id="rank"></span><br>
    <a href="javascript:;" onclick="status()">campaign/status</a> <span id="status"></span><br>
    <a href="javascript:;" onclick="category()">campaign/category</a> <span id="category"></span><br>

    <input type="text" id="updateBannerId" placeholder="bannerId">
    <input type="text" id="updateField" placeholder="field">
    <input type="text" id="updateValue" placeholder="value">
    <a href="javascript:;" onclick="update()">campaign/update</a> <span id="update"></span><br>
    <input type="text" id="indexCampaignId" placeholder="campaignId">
    <a href="javascript:;" onclick="indexKeys()">keywords/index</a> <span id="keywordsIndex"></span><br>

    <input type="text" id="zoneIndexAdType" placeholder="ad_type">
    <a href="javascript:;" onclick="zoneIndex()">zone/index</a> <span id="zoneIndex"></span><br>

    <input type="text" id="storeZone_id" placeholder="zone_id">
    <input type="text" id="storeAd_type" placeholder="ad_type">
    <input type="text" id="storeZone_name" placeholder="zone_name">
    <input type="text" id="storeType" placeholder="type">
    <input type="text" id="storeCategory" placeholder="category">
    <input type="text" id="storePlatform" placeholder="platform">
    <input type="text" id="storeRank_limit" placeholder="rank_limit">
    <input type="text" id="storeDescription" placeholder="description">
    <input type="text" id="storePosition" placeholder="position">
    <input type="text" id="storeList_type_id" placeholder="list_type_id">
    <input type="text" id="storeAd_refresh" placeholder="ad_refresh">
    <a href="javascript:;" onclick="zoneStore()">zone/store</a> <span id="zoneStore"></span><br>

<input type="text" id="checkZone_id" placeholder="zone_id">
<input type="text" id="checkAction1" placeholder="action">
<a href="javascript:;" onclick="zoneCheck()">zone/check</a> <span id="zoneCheck"></span><br>

<input type="text" id="categoryCategory" placeholder="category">
<input type="text" id="categoryAdType" placeholder="ad_type">
<input type="text" id="categoryName" placeholder="name">
<input type="text" id="categoryParent" placeholder="actionParent">
<a href="javascript:;" onclick="zoneCategory()">zone/category</a> <span id="zoneCategory"></span><br>


<input type="text" id="categoryDeleteCategory" placeholder="category">
<a href="javascript:;" onclick="zoneCategoryDelete()">zone/category_delete</a> <span id="zoneCategoryDelete"></span><br>

<a href="javascript:;" onclick="zoneModuleList()">zone/module_list</a> <span id="zoneModuleList"></span><br>

<input type="text" id="moduleId" placeholder="id">
<input type="text" id="moduleStoreType" placeholder="type">
<input type="text" id="moduleStoreListTypeId" placeholder="list_type_id">
<input type="text" id="moduleStoreName" placeholder="name">
<input type="text" id="moduleStoreAdType" placeholder="ad_type">
<a href="javascript:;" onclick="zoneModule()">zone/module</a> <span id="zoneModule"></span><br>

<input type="text" id="moduleDeleteListTypeId" placeholder="list_type_id">
<input type="text" id="moduleDeleteAdType" placeholder="ad_type">
<a href="javascript:;" onclick="zoneModuleDelete()">zone/module_delete</a> <span id="zoneModuleDelete"></span><br>


<a href="javascript:;" onclick="zoneAdType()">zone/ad_type</a> <span id="zoneAdType"></span><br>

<input id="balancePayee" placeholder="payee">
<input id="balanceBank" placeholder="bank">
<input id="balanceBankAccount" placeholder="bank_account">
<input id="balanceMoney" id="value" placeholder="money">
    <a href="javascript:;" onclick="draw()">draw</a> <span id="resultDraw"></span><br>
    
    <a href="javascript:;" onclick="menu()">trafficker/stat/menu</a> <span id="menu"></span><br>
    <input id="revenue_type_stat" placeholder="revenue_type" value="2">
    <input id="item_num" placeholder="item_num" value="1">
    <a href="javascript:;" onclick="statColumnList()">trafficker/stat/column_list</a> <span id="statColumnList"></span><br>
    
    <input id="period_start" placeholder="period_start" value="2015-12-10">
    <input id="period_end" placeholder="period_end" value="2016-02-10">
    <input id="span" placeholder="span" value="2">
    <input id="zone_offset" placeholder="zone_offset" value="-8">
    <input id="revenue_type" placeholder="revenue_type">
    <a href="javascript:;" onclick="zone()">trafficker/stat/zone</a> <span id="statResult"></span><br>
    
    <input id="period_start_c" placeholder="period_start" value="2015-12-10">
    <input id="period_end_c" placeholder="period_end" value="2016-02-10">
    <input id="span_c" placeholder="span" value="2">
    <input id="zone_offset_c" placeholder="zone_offset" value="-8">
    <input id="revenue_type_c" placeholder="revenue_type">
    <a href="javascript:;" onclick="client()">trafficker/stat/client</a> <span id="statResultC"></span><br>
    <a href="javascript:;" onclick="report()">trafficker/stat/report</a> <span id="reportResult"></span><br>
    <input id="day_type_zone" placeholder="day_type">
    <a href="javascript:;" onclick="zoneReport()">trafficker/stat/zone_report</a> <span id="zoneReportResult"></span><br>
    <input id="day_type_client" placeholder="day_type">
    <a href="javascript:;" onclick="clientReport()">trafficker/stat/client_report</a> <span id="clientReportResult"></span><br>
    
    <input id="period_start_excel" placeholder="period_start" value="2015-12-10">
    <input id="period_end_excel" placeholder="period_end" value="2016-02-10">
    <input id="zone_offset_excel" placeholder="zone_offset" value="-8">
    <input id="revenue_type_excel" placeholder="revenue_type">
    <input id="item_num_excel" placeholder="item_num">
    <a href="javascript:;" onclick="campaignExcel()">trafficker/stat/campaign_excel</a> <span id="campaignExcelResult"></span><br>
    <a href="javascript:;" onclick="selfIndex()">trafficker/stat/self_index</a> <span id="selfIndexResult"></span><br>
    <input type="text" id="traffickerStatTrendType" placeholder="type">
    <a href="javascript:;" onclick="selfTrend()">trafficker/stat/self_trend</a> <span id="selfTrendResult"></span><br>
    <input type="text" id="traffickerSelfZoneStart" placeholder="period_start">
    <input type="text" id="traffickerSelfZoneEnd" placeholder="period_end">
    <a href="javascript:;" onclick="selfZone()">trafficker/stat/self_zone</a> <span id="selfZoneResult"></span><br>
    <input type="text" id="traffickerSelfZoneExcelStart" placeholder="period_start">
    <input type="text" id="traffickerSelfZoneExcelEnd" placeholder="period_end">
    <a href="javascript:;" onclick="selfZoneExcel()">trafficker/stat/self_zone_excel</a> <span id="selfZoneExcelResult"></span><br>
<br />

    <input type="text" id="advertiserPageSize" placeholder="pageSize">
    <input type="text" id="advertiserPageNo" placeholder="pageNo">
    <input type="text" id="advertiserSearch" placeholder="search">
    <input type="text" id="advertiserSort" placeholder="sort">
    <input type="text" id="advertiserType" placeholder="type">
    <a href="javascript:;" onclick="advertiserIndex()">trafficker/advertiser/index</a> <span id="advertiserIndex"></span><br>

    <input type="text" id="advertiserStoreClientId" placeholder="clientid" value="">
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
    <a href="javascript:;" onclick="advertiserStore()">trafficker/advertiser/store</a> <span id="advertiserStore"></span><br>

    <input type="text" id="advertiserUpdateId" placeholder="id" value="620">
    <input type="text" id="advertiserUpdateField" placeholder="field" value="active">
    <input type="text" id="advertiserUpdateValue" placeholder="value" value="1">
    <a href="javascript:;" onclick="advertiserUpdate()">trafficker/advertiser/update</a> <span id="advertiserUpdate"></span><br>

    <a href="javascript:;" onclick="advertiserSales()">trafficker/advertiser/sales</a> <span id="advertiserSales"></span><br>

    <br><br>

    <input type="text" id="accountPageSize" placeholder="pageSize">
    <input type="text" id="accountPageNo" placeholder="pageNo">
    <input type="text" id="accountSearch" placeholder="search">
    <input type="text" id="accountSort" placeholder="sort">
    <input type="text" id="accountType" placeholder="type">
    <a href="javascript:;" onclick="accountIndex()">trafficker/account/index</a> <span id="accountIndex"></span><br>

    <input type="text" id="accountStoreUsername" placeholder="username" value="test12<?php echo rand(10000, 99999) ?>">
    <input type="text" id="accountStorePassword" placeholder="password" value="123456">
    <input type="text" id="accountStoreContactName" placeholder="contact_name" value="contact1">
    <input type="text" id="accountStoreContactPhone" placeholder="contact_phone" value="132456<?php echo rand(10000, 99999) ?>">
    <input type="text" id="accountStoreQq" placeholder="qq" value="123<?php echo rand(10000, 99999) ?>">
    <input type="text" id="accountStoreEmailAddress" placeholder="email_address" value="<?php echo rand(10000, 99999) ?>@qq.com">
    <input type="text" id="accountStoreRoleId" placeholder="role_id" value="1758">
    <a href="javascript:;" onclick="accountStore()">trafficker/account/store</a> <span id="accountStore"></span><br>

    <input type="text" id="accountUpdateId" placeholder="id" value="5680">
    <input type="text" id="accountUpdateField" placeholder="field" value="active">
    <input type="text" id="accountUpdateValue" placeholder="value" value="1">
    <a href="javascript:;" onclick="accountUpdate()">trafficker/account/update</a> <span id="accountUpdate"></span><br>

    <input type="text" id="accountDeleteUserId" placeholder="5679">
    <a href="javascript:;" onclick="accountDelete()">trafficker/account/delete</a> <span id="accountDelete"></span><br>

    <br><br>

    <input type="text" id="rolePageSize" placeholder="pageSize">
    <input type="text" id="rolePageNo" placeholder="pageNo">
    <input type="text" id="roleSearch" placeholder="search">
    <input type="text" id="roleSort" placeholder="sort">
    <input type="text" id="roleType" placeholder="type">
    <a href="javascript:;" onclick="roleIndex()">trafficker/role/index</a> <span id="roleIndex"></span><br>

    <input type="text" id="roleStoreId" placeholder="id" value="0">
    <input type="text" id="roleStoreName" placeholder="name" value="test12<?php echo rand(10000, 99999) ?>">
    <input type="text" id="roleStoreOperationList" placeholder="operation_list" value="trafficker-campaign,trafficker-stat">
    <a href="javascript:;" onclick="roleStore()">trafficker/role/store</a> <span id="roleStore"></span><br>

    <a href="javascript:;" onclick="roleOperationList()">trafficker/role/operation_list</a> <span id="roleOperationList"></span><br>
    <br />
    <input id="game_report_period_start" placeholder="period_start" value="2016-10-10">
    <input id="game_report_period_end" placeholder="period_end" value="2016-11-11">
    <a href="javascript:;" onclick="gameReport()">trafficker/stat/game_report</a> <span id="statGameReport"></span><br>

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

function nav() {
    tool.get("site/nav", "resultNav", null);
}

function sales() {
    tool.post("trafficker/common/sales","sales",null);
}

function campaignPendingAudit() {
    tool.get("trafficker/common/campaign_pending_audit", "campaignPendingAudit", null);
}

function balanceValue() {
    tool.get("trafficker/common/balance_value", "resultBalanceValue", null);
}

function index()
{
    tool.post("trafficker/campaign/index", "index", {
        "pageNo": $('indexPN').value,
        "pageSize": $('indexPS').value,
        "search": $('indexSearch').value,
        //"sort": $('indexSort').value,
        "ad_type": $('indexAdType').value,
        "platform": $('indexPlatform').value,
        "parent": $('indexParent').value,
        "status": $('indexStatus').value,
        "appinfos_app_rank": $('indexAppRank').value
    });
}

function check()
{
    tool.post("trafficker/campaign/check", "check", {
        "bannerid": $('checkCampaignid').value,
        "action": $('checkAction').value,
        "category": $('checkCategory').value,
        "appinfos_app_rank": $('checkAppinfos_app_rank').value
    });
}

function columnList()
{
    tool.get("trafficker/campaign/column_list", "columnList", {
        "ad_type": $('columnListAdType').value
    });
}
function rank()
{
    tool.get("trafficker/campaign/rank", "rank",null);
}

function status()
{
    tool.get("trafficker/campaign/status", "status",null);
}

    function category()
    {
        tool.get("trafficker/campaign/category", "category",null);
    }

    function update() {
        tool.post("trafficker/campaign/update", "update", {
            "bannerid": $('updateBannerId').value,
            "field": $('updateField').value,
            "value": $('updateValue').value
        });
    }

function indexKeys() {
    tool.post("trafficker/keywords/index", "keywordsIndex", {
        "campaignid": $('indexCampaignId').value
    });
}
    function zoneIndex()
    {
        tool.post("trafficker/zone/index", "zoneIndex", {
            "ad_type": $('zoneIndexAdType').value
        });
    }

    function zoneStore() {
        tool.post("trafficker/zone/store", "zoneStore", {
            "zone_id": $('storeZone_id').value,
            "ad_type": $('storeAd_type').value,
            "zone_name": $('storeZone_name').value,
            "type": $('storeType').value,
            "category": $('storeCategory').value,
            "platform": $('storePlatform').value,
            "rank_limit": $('storeRank_limit').value,
            "description": $('storeDescription').value,
            "position": $('storePosition').value,
            "listtypeid": $('storeList_type_id').value,
            "ad_refresh": $('storeAd_refresh').value
        });
    }

    function zoneCheck()
    {
        tool.post("trafficker/zone/check", "zoneCheck", {
            "zone_id": $('checkZone_id').value,
            "action": $('checkAction1').value
        });
    }

    function zoneCategory()
    {
        tool.post("trafficker/zone/category", "zoneCategory", {
            "category": $('categoryCategory').value,
            "ad_type": $('categoryAdType').value,
            "name": $('categoryName').value,
            "parent": $('categoryParent').value
        });
    }

    function zoneCategoryDelete()
    {
        tool.post("trafficker/zone/category_delete", "zoneCategoryDelete", {
            "category": $('categoryCategory').value
        });
    }

    function zoneModuleList()
    {
        tool.get("trafficker/zone/module_list", "zoneModuleList",{
            "ad_type": "0"
        });
    }

    function zoneAdType()
    {
        tool.get("trafficker/zone/ad_type", "zoneAdType",null);
    }

    function zoneModule()
    {
        tool.post("trafficker/zone/module", "zoneModule", {
            "id":$('moduleId').value,
            "type": $('moduleStoreType').value,
            "listtypeid": $('moduleStoreListTypeId').value,
            "name": $('moduleStoreName').value,
            "ad_type":$('moduleStoreAdType').value
        });
    }

function zoneModuleDelete()
{
    tool.post("trafficker/zone/module_delete", "zoneModule", {
        "listtypeid": $('moduleDeleteListTypeId').value,
        "ad_type": $('moduleDeleteAdType').value
    });
}

function draw() {
    tool.post("trafficker/balance/draw", "resultDraw", {
        "payee": $('balancePayee').value,
        "bank": $("balanceBank").value,
        "bank_account": $('balanceBankAccount').value,
        "money": $('balanceMoney').value
    });
}
    function menu(){
        tool.get("trafficker/stat/menu", "menu",null);
    }
    function statColumnList() {
        tool.get("trafficker/stat/column_list", "statResult", {
            "revenue_type": $('revenue_type_stat').value,
            "item_num": $('item_num').value
        });
    }
    function zone() {
        tool.get("trafficker/stat/zone", "statResult", {
            "period_start": $('period_start').value,
            "period_end": $('period_end').value,
            "span": $('span').value,
            "zone_offset": $('zone_offset').value,
            "revenue_type": $('revenue_type').value
        });
    }
    function client(){
        tool.get("trafficker/stat/client", "statResult", {
            "period_start": $('period_start_c').value,
            "period_end": $('period_end_c').value,
            "span": $('span_c').value,
            "zone_offset": $('zone_offset_c').value,
            "revenue_type": $('revenue_type_c').value
        });
    }
    function report(){
        tool.get("trafficker/stat/report", "report",null);
    }
    function clientReport(){
        tool.get("trafficker/stat/client_report", "clientReportResult", {
            "date_type": $('day_type_client').value
        });
    }
    function zoneReport(){
        tool.get("trafficker/stat/zone_report", "zoneReportResult", {
            "date_type": $('day_type_zone').value
            });
    }
    function campaignExcel(){
        var url = "<?php echo $params['prefixUrl']; ?>trafficker/stat/campaign_excel?period_start="+$('period_start_excel').value+"&period_end="+ $('period_end_excel').value+"&revenue_type="+$('revenue_type_excel').value
            +"&zone_offset="+$('zone_offset_excel').value+"&item_num="+$('item_num_excel').value;
        window.location.href = url;
    }
    function selfIndex(){
        tool.get("trafficker/stat/self_index", "selfIndexResult",null);
    }
    function selfTrend() {
        tool.get("trafficker/stat/self_trend", "selfTrendResult", {
            "type": $('traffickerStatTrendType').value
        });
    }
function selfZone() {
    tool.get("trafficker/stat/self_zone", "selfZoneResult", {
        "period_start": $('traffickerSelfZoneStart').value,
        "period_end": $('traffickerSelfZoneEnd').value,
    });
}
function selfZoneExcel() {
    var url = "<?php echo $params['prefixUrl']; ?>trafficker/stat/self_zone_excel?period_start="+$('traffickerSelfZoneExcelStart').value+
        "&period_end="+ $('traffickerSelfZoneExcelEnd').value;
    window.location.href = url;
}

function advertiserIndex() {
    tool.post("trafficker/advertiser/index", "advertiserIndex", {
        "pageSize": $('advertiserPageSize').value,
        "pageNo": $('advertiserPageNo').value,
        "search": $('advertiserSearch').value,
        "sort": $('advertiserSort').value,
        "account_type": $('advertiserType').value
    });
}

function advertiserStore() {
    tool.post("trafficker/advertiser/store", "advertiserStore", {
        "clientid":$('advertiserStoreClientId').value,
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

function advertiserUpdate() {
    tool.post("trafficker/advertiser/update", "advertiserUpdate", {
        "id": $('advertiserUpdateId').value,
        "field": $('advertiserUpdateField').value,
        "value": $('advertiserUpdateValue').value
    });
}

function advertiserSales()
{
    tool.get("trafficker/advertiser/sales", "advertiserSales", {
    });
}


function accountIndex() {
    tool.post("trafficker/account/index", "accountIndex", {
        "pageSize": $('accountPageSize').value,
        "pageNo": $('accountPageNo').value,
        "search": $('accountSearch').value,
        "sort": $('accountSort').value,
    });
}

function accountStore() {
    tool.post("trafficker/account/store", "accountStore", {
        "username":$('accountStoreUsername').value,
        "password":$('accountStorePassword').value,
        "contact_name":$('accountStoreContactName').value,
        "contact_phone":$('accountStoreContactPhone').value,
        "qq":$('accountStoreQq').value,
        "email_address":$('accountStoreEmailAddress').value,
        "role_id":$('accountStoreRoleId').value
    });
}


function accountUpdate() {
    tool.post("trafficker/account/update", "accountUpdate", {
        "id": $('accountUpdateId').value,
        "field": $('accountUpdateField').value,
        "value": $('accountUpdateValue').value
    });
}
function accountDelete()
{
    tool.post("trafficker/account/delete", "accountDelete", {
        "user_id": $('accountDeleteUserId').value
    });
}

function roleIndex() {
    tool.post("trafficker/role/index", "roleIndex", {
        "pageSize": $('rolePageSize').value,
        "pageNo": $('rolePageNo').value,
        "search": $('roleSearch').value,
        "sort": $('roleSort').value,
        "account_type": $('roleType').value
    });
}

function roleStore() {
    tool.post("trafficker/role/store", "roleStore", {
        "id":$('roleStoreId').value,
        "name":$('roleStoreName').value,
        "operation_list":$('roleStoreOperationList').value,
    });
}
function roleOperationList() {
    tool.get("trafficker/role/operation_list", "roleOperationList", {
    });
}
function gameReport() {
    tool.get("trafficker/stat/game_report", "statGameReport", {
        "period_start": $('game_report_period_start').value,
        "period_end": $('game_report_period_end').value
    });
}
    </script>
</div>
</div>
</body>
</html>
