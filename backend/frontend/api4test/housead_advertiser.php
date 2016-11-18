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

        <input name="loginUsername" id="loginUsername" placeholder="username" value="xiaoklggzha">
        <input name="loginPassword" id="loginPassword" placeholder="password" value="123456">
        <input name="loginCaptcha" id="loginCaptcha" placeholder="captcha">
        <img src="<?php echo $params['prefixUrl']; ?>site/captcha" id="img_num" style="cursor: pointer" onclick="this.src='<?php echo $params['prefixUrl']; ?>site/captcha?'+new Date().getTime()" ;="" width="86" height="40" alt="">
        <a href="javascript:;" onclick="login()">login</a> <span id="resultLogin"></span><br>
        <a href="javascript:;" onclick="logout()">logout</a> <span id="resultLogout"></span><br>

        <input name="campaignWd" id="wd" placeholder="wd">
        <a href="javascript:;" onclick="campaignAppList()">campaign_app_list</a> <span id="campaignAppList"></span><br>

        <input name="selfStore" id="selfStoreCampaignId" placeholder="campaignid">
        <input name="selfStore" id="selfStoreRevenue" placeholder="revenue">
        <input name="selfStore" id="selfStoreDayLimit" placeholder="day_limit">
        <input name="selfStore" id="selfStoreProductsIcon" placeholder="products_icon">
        <input name="selfStore" id="selfStoreAppInfoAppName" placeholder="appinfos_app_name">
        <input name="selfStore" id="selfStoreAppInfoProfile" placeholder="appinfos_profile">
        <input name="selfStore" id="selfStoreAppInfoImages" placeholder="appinfos_images">
        <input name="selfStore" id="selfStoreVersionCode" placeholder="versionCode">
        <input name="selfStore" id="selfStoreVersionName" placeholder="versionName">
        <input name="selfStore" id="selfStorePackageName" placeholder="packageName">
        <input name="selfStore" id="selfStoreFileSize" placeholder="filesize">
        <input name="selfStore" id="selfStoreDownloadUrl" placeholder="downloadurl">
        <input name="selfStore" id="selfStoreAppId" placeholder="app_id">
        <input name="selfStore" id="selfStoreStar" placeholder="star">
        <a href="javascript:;" onclick="campaignStore()">campaign/store</a> <span id="campaignStore"></span><br>

        <input name="campaignWd" id="selfViewCampaignId" placeholder="campaignid">
        <a href="javascript:;" onclick="campaignView()">campaign/view</a> <span id="campaignView"></span><br>

        <input name="campaignWd" id="updateCampaignId" placeholder="campaignid">
        <input name="campaignWd" id="updateStatus" placeholder="status">
        <a href="javascript:;" onclick="campaignUpdate()">campaign/update</a> <span id="campaignUpdate"></span><br>

        <input name="campaignWd" id="zoneIndexCampaignId" placeholder="campaignid">
        <a href="javascript:;" onclick="zoneIndex()">zone/view</a> <span id="zoneIndex"></span><br>

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
            function campaignAppList() {
                tool.post("advertiser/campaign/app_list","campaignAppList",{
                    "wd":$('wd').value
                });
            }
            function campaignStore()
            {
                tool.post("advertiser/campaign/self_store","campaignStore",{
                    "campaignid":$('selfStoreCampaignId').value,
                    "revenue":$('selfStoreRevenue').value,
                    "day_limit":$('selfStoreDayLimit').value,
                    "products_icon":$('selfStoreProductsIcon').value,
                    "appinfos_app_name":$('selfStoreAppInfoAppName').value,
                    "appinfos_profile":$('selfStoreAppInfoProfile').value,
                    "appinfos_images":$('selfStoreAppInfoImages').value,
                    "versionCode":$('selfStoreVersionCode').value,
                    "versionName":$('selfStoreVersionName').value,
                    "packageName":$('selfStorePackageName').value,
                    "filesize":$('selfStoreFileSize').value,
                    "downloadurl":$('selfStoreDownloadUrl').value,
                    "app_id":$('selfStoreAppId').value,
                    "star":$('selfStoreStar').value
                });
            }
            function campaignView() {
                tool.post("advertiser/campaign/self_view","campaignView",{
                    "campaignid":$('selfViewCampaignId').value
                });
            }
            function campaignUpdate()
            {
                tool.post("advertiser/campaign/update","campaignUpdate",{
                    "campaignid":$('updateCampaignId').value,
                    "status":$('updateStatus').value
                });
            }
            function zoneIndex()
            {
                tool.post("advertiser/zone/index","zoneIndex",{
                    "campaignid":$('zoneIndexCampaignId').value
                });
            }
        </script>
    </div>
</div>
</body>
</html>
