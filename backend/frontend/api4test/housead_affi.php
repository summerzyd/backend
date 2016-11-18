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

<input name="loginUsername" id="loginUsername" placeholder="username" value="mingceshi1">
<input name="loginPassword" id="loginPassword" placeholder="password" value="123456">
<input name="loginCaptcha" id="loginCaptcha" placeholder="captcha">
<img src="<?php echo $params['prefixUrl']; ?>site/captcha" id="img_num" style="cursor: pointer" onclick="this.src='<?php echo $params['prefixUrl']; ?>site/captcha?'+new Date().getTime()" ;="" width="86" height="40" alt="">
<a href="javascript:;" onclick="login()">login</a> <span id="resultLogin"></span><br>
<a href="javascript:;" onclick="logout()">logout</a> <span id="resultLogout"></span><br>

<input type="text" id="selfIndexPN" placeholder="pageNo">
<input type="text" id="selfIndexPS" placeholder="pageSize">
<input type="text" id="selfIndexSearch" placeholder="search">
<input type="text" id="selfIndexSort" placeholder="sort">
<input type="text" id="selfIndexFilter" placeholder="filter">
<a href="javascript:;" onclick="selfIndex()">campaign/self_index</a> <span id="selfIndex"></span><br>

<input type="text" id="selfCheckCampaignid" placeholder="campaignid">
<input type="text" id="selfCheckStatus" placeholder="status">
<input type="text" id="selfCheckAppRank" placeholder="app_rank">
<input type="text" id="selfCheckCategory" placeholder="category">
<input type="text" id="selfCheckApproveComment" placeholder="approve_comment">
<a href="javascript:;" onclick="selfCheck()">campaign/self_check</a> <span id="selfCheck"></span><br>

<input type="text" id="selfUpdateCampaignid" placeholder="campaignid">
<input type="text" id="selfUpdateAppRank" placeholder="app_rank">
<input type="text" id="selfUpdateCategory" placeholder="category">
<a href="javascript:;" onclick="selfUpdate()">campaign/self_update</a> <span id="selfUpdate"></span><br>

<input type="text" id="zoneListCampaignid" placeholder="campaignid">
<a href="javascript:;" onclick="zoneList()">campaign/self_update</a> <span id="zoneList"></span><br>

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
    function selfIndex()
    {
        tool.post("trafficker/campaign/self_index", "selfIndex", {
            "pageNo": $('selfIndexPN').value,
            "pageSize": $('selfIndexPS').value,
            "search": $('selfIndexSearch').value,
            "sort": $('selfIndexSort').value,
            "filter": $('selfIndexFilter').value
        });
    }
    function selfCheck()
    {
        tool.post("trafficker/campaign/self_check", "selfCheck", {
            "campaignid": $('selfCheckCampaignid').value,
            "status": $('selfCheckStatus').value,
            "app_rank":$('selfCheckAppRank').value,
            "category": $('selfCheckCategory').value,
            "approve_comment": $('selfCheckApproveComment').value
        });
    }
    function selfUpdate()
    {
        tool.post("trafficker/campaign/self_update", "selfUpdate", {
            "campaignid": $('selfUpdateCampaignid').value,
            "app_rank":$('selfUpdateAppRank').value,
            "category": $('selfUpdateCategory').value
        });
    }
    function zoneList()
    {
        tool.post("trafficker/campaign/zone_list", "zoneList", {
            "campaignid": $('zoneListCampaignid').value
        });
    }
</script>
</div>
</div>
</body>
</html>
