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
        <input type="text" id="indexPN" placeholder="pageNo">
        <input type="text" id="indexPS" placeholder="pageSize">
        <input type="text" id="indexSearch" placeholder="search">
        <input type="text" id="indexSort" placeholder="sort">
        <input type="text" id="indexAdType" placeholder="ad_type">
        <input type="text" id="indexPlatform" placeholder="platform">
        <input type="text" id="indexCategory" placeholder="category">
        <input type="text" id="indexStatus" placeholder="status">
        <input type="text" id="indexAppRank" placeholder="appinfos_app_rank">
        <a href="javascript:;" onclick="index()">campaign/index</a> <span id="index"></span><br>

        <input type="text" id="checkcampaignid" placeholder="campaignid">
        <input type="text" id="checkaction" placeholder="action">
        <input type="text" id="checkcategory" placeholder="category">
        <input type="text" id="checkappinfos_app_rank" placeholder="appinfos_app_rank">
        <a href="javascript:;" onclick="check()">campaign/check</a> <span id="check"></span><br>

        <input type="text" id="columnListAdtype" placeholder="ad_type">
        <a href="javascript:;" onclick="columnList()">campaign/columnList</a> <span id="columnList"></span><br>
        <a href="javascript:;" onclick="rank()">campaign/rank</a> <span id="rank"></span><br>
        <a href="javascript:;" onclick="status()">campaign/status</a> <span id="status"></span><br>
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
            function index()
            {
                tool.post("trafficker/campaign/index", "index", {
                    "pageNo": $('indexPN').value,
                    "pageSize": $('indexPS').value,
                    "search": $('indexSearch').value,
                    "sort": $('indexSort').value,
                    "ad_type": $('indexAdType').value,
                    "platform": $('indexPlatform').value,
                    "category": $('indexCategory').value,
                    "status": $('indexStatus').value,
                    "appinfos_app_rank": $('indexAppRank').value
                });
            }
            function check()
            {
                tool.post("trafficker/campaign/check", "check", {
                    "campaignid": $('checkcampaignid').value,
                    "action": $('checkaction').value,
                    "category": $('checkcategory').value,
                    "appinfos_app_rank": $('checkappinfos_app_rank').value
                });
            }
            function columnList()
            {
                tool.get("trafficker/campaign/column_list", "columnList", {
                    "ad_type": $('columnListAdtype').value
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
            function menu()
            {
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
            function client()
            {
                tool.get("trafficker/stat/client", "statResult", {
                    "period_start": $('period_start_c').value,
                    "period_end": $('period_end_c').value,
                    "span": $('span_c').value,
                    "zone_offset": $('zone_offset_c').value,
                    "revenue_type": $('revenue_type_c').value
                });
            }
            function report()
            {
                tool.get("trafficker/stat/report", "report",null);
            }
            function clientReport()
            {
                tool.get("trafficker/stat/client_report", "clientReportResult", {
                    "day_type": $('day_type_client').value
                });
            }
            function zoneReport()
            {
                tool.get("trafficker/stat/zone_report", "zoneReportResult", {
                    "day_type": $('day_type_zone').value
                });
            }
            function campaignExcel()
            {
                var url = "<?php echo $params['prefixUrl']; ?>trafficker/stat/campaign_excel?period_start="+$('period_start_excel').value+"&period_end="+ $('period_end_excel').value+"&revenue_type="+$('revenue_type_excel').value
                    +"&zone_offset="+$('zone_offset_excel').value+"&item_num="+$('item_num_excel').value;
                window.location.href = url;
               
            }

        </script>
    </div>
</div>
</body>
</html>
