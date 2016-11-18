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

<input name="loginUsername" id="loginUsername" placeholder="username" value="mingdai">
<input name="loginPassword" id="loginPassword" placeholder="password" value="123456">
<input name="loginCaptcha" id="loginCaptcha" placeholder="captcha">
<img src="<?php echo $params['prefixUrl']; ?>site/captcha" id="img_num" style="cursor: pointer" onclick="this.src='<?php echo $params['prefixUrl']; ?>site/captcha?'+new Date().getTime()" ;="" width="86" height="40" alt="">
<a href="javascript:;" onclick="login()">login</a> <span id="resultLogin"></span><br>
<a href="javascript:;" onclick="logout()">logout</a> <span id="resultLogout"></span><br>

<br><hr><br><br>
    <a href="javascript:;" onclick="recharge()"> broker/balance/recharge</a> <span id="rechargeResult"></span><br>
    <a href="javascript:;" onclick="gift()"> broker/balance/gift</a> <span id="giftResult"></span><br>
    <a href="javascript:;" onclick="invoice_history ()"> broker/balance/invoice_history </a> <span id="invoice_historyResult"></span><br>
    <input type="text" id="invoiceStore_ids" placeholder="invoiceStore_ids" value="316">
    <input type="text" id="invoiceStore_title" placeholder="invoiceStore_title" value="深圳市海数互联技术有限公司">
    <input type="text" id="invoiceStore_prov" placeholder="invoiceStore_prov" value="广东">
    <input type="text" id="invoiceStore_city" placeholder="invoiceStore_city" value="深圳">
    <input type="text" id="invoiceStore_dist" placeholder="invoiceStore_dist" value="福田">
    <input type="text" id="invoiceStore_address" placeholder="invoiceStore_address" value="test天安">
    <input type="text" id="invoiceStore_type" placeholder="invoiceStore_type" value="0">
    <input type="text" id="invoiceStore_receiver" placeholder="invoiceStore_receiver" value="test">
    <input type="text" id="invoiceStore_tel" placeholder="invoiceStore_tel" value="18888888888">
    <a href="javascript:;" onclick="invoiceStore()">broker/invoice/store</a> <span id="invoiceStoreResult"></span><br>
    <a href="javascript:;" onclick="apply()"> broker/balance/apply </a> <span id="applyResult"></span><br>

    <input id="invoiceId" placeholder="invoice_id">
    <a href="javascript:;" onclick="invoice()">broker/balance/invoice</a> <span id="invoiceResult"></span><br>

    <input type="text" id="balanceClientId" placeholder="clientId">
    <a href="javascript:;" onclick="balance_value()">broker/balance/balance_value</a> <span id="balance_value"></span><br>
    <br />

    <input type="text" id="indexPageSize" placeholder="pageSize">
    <input type="text" id="indexPageNo" placeholder="pageNo">
    <input type="text" id="indexSearch" placeholder="search">
    <input type="text" id="indexSort" placeholder="sort">
    <a href="javascript:;" onclick="index()">broker/advertiser/index</a> <span id="index"></span><br>
    <br />

<input type="text" id="storeClient_name" placeholder="client_name">
<input type="text" id="storeBrief_name" placeholder="brief_name">
<input type="text" id="storeUsername" placeholder="username">
<input type="text" id="storePassword" placeholder="password">
<input type="text" id="storeContact_name" placeholder="contact_name">
<input type="text" id="storeEmail_address" placeholder="email_address">
<input type="text" id="storePhone" placeholder="phone">
<input type="text" id="storeQQ" placeholder="qq">
<input type="text" id="storeCreator_uid" placeholder="creator_uid">
<a href="javascript:;" onclick="store()">broker/advertiser/store</a> <span id="store"></span><br>

    <input type="text" id="updateClient_id" placeholder="client_id">
    <input type="text" id="updateField" placeholder="field">
    <input type="text" id="updateValue" placeholder="value">
    <a href="javascript:;" onclick="update()">broker/advertiser/update</a> <span id="update"></span><br>

    <input type="text" id="transferClient_id" placeholder="client_id">
    <input type="text" id="transferAccount_type" placeholder="account_type">
    <input type="text" id="transferAction" placeholder="action">
    <input type="text" id="transferBalance" placeholder="balance">
    <a href="javascript:;" onclick="transfer()">broker/advertiser/transfer</a> <span id="transfer"></span><br>

    <input type="text" id="keyCampaignId" placeholder="campaignId">
    <a href="javascript:;" onclick="keywordIndex()">broker/keywords/index</a> <span id="keywordIndex"></span><br>
    <br />

<input type="text" id="campaignIndexPageSize" placeholder="pageSize">
<input type="text" id="campaignIndexPageNo" placeholder="pageNo">
<input type="text" id="campaignIndexSearch" placeholder="search">
<input type="text" id="campaignIndexSort" placeholder="sort">
<a href="javascript:;" onclick="campaignIndex()">broker/campaign/index</a> <span id="campaignIndex"></span><br>
<a href="javascript:;" onclick="campaignRevenueType()">campaign/revenue_type</a> <span id="campaignRevenueType"></span><br>
<br />
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

function recharge() {
    tool.get(" broker/balance/recharge", "rechargeResult", null);
}
function gift() {
    tool.get(" broker/balance/gift", "giftResult", null);
}
function invoice_history () {
    tool.get(" broker/balance/invoice_history ", "invoice_historyResult", null);
}
function invoiceStore() {
    tool.post('broker/balance/invoice_store', 'invoiceStoreResult', {
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
function invoice() {
    tool.get("broker/balance/invoice", "invoiceResult", {
        "invoice_id": $('invoiceId').value
    });
}
function apply () {
    tool.get("broker/balance/apply ", "applyResult", null);
}

function index() {
    tool.post("broker/advertiser/index", "index", {
        "pageSize": $('indexPageSize').value,
        "pageNo": $('indexPageNo').value,
        "search": $('indexSearch').value,
        "sort": $('indexSort').value
    });
}
function store() {
    tool.post("broker/advertiser/store", "store", {
        "client_name": $('storeClient_name').value,
        "brief_name": $('storeBrief_name').value,
        "username": $('storeUsername').value,
        "password": $('storePassword').value,
        "contact_name": $('storeContact_name').value,
        "email_address": $('storeEmail_address').value,
        "phone": $('storePhone').value,
        "qq": $('storeQQ').value,
        "creator_uid": $('storeCreator_uid').value
    });
}
function update()
{
    tool.post("broker/advertiser/update", "update", {
        "client_id": $('updateClient_id').value,
        "field": $('updateField').value,
        "value": $('updateValue').value
    });
}
function transfer()
{
    tool.post("broker/advertiser/transfer", "transfer", {
        "client_id": $('transferClient_id').value,
        "account_type": $('transferAccount_type').value,
        "action": $('transferAction').value,
        "balance":$('transferBalance').value
    });
}

function balance_value() {
    tool.post("broker/balance/balance_value", "balance_value", {
        "client_id": $('balanceClientId').value
    });
}
    function keywordIndex(){
        tool.post("broker/keywords/index", "keywordIndex", {
            "campaignid": $('keyCampaignId').value
        });
    }

function campaignIndex() {
    tool.post("broker/campaign/index", "campaignIndex", {
        "pageSize": $('campaignIndexPageSize').value,
        "pageNo": $('campaignIndexPageNo').value,
        "search": $('campaignIndexSearch').value,
        "sort": $('campaignIndexSort').value
    });
}

function campaignRevenueType() {
    tool.get("broker/campaign/revenue_type", "campaignRevenueType", null);
}

</script>
</div>
</div>
</body>
</html>
