<div>
    <p style="font-size: medium; padding: 10px 0px 5px; margin: 0px; line-height: 26.6000003814697px; font-family: 微软雅黑;">
    <span>广告主的下载地址为0的广告如下：</span>
    </p>

    <table border="1" cellspacing="1" cellpadding="0" style="padding: 0px; margin: 0px; border-collapse: collapse; border: 1px solid rgb(221, 221, 221); width:100%; font-family: 微软雅黑; font-size: 14px;">
        <tbody style="padding: 0px; margin: 0px;">
        <tr style="padding: 0px; margin: 0px;">
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">bannerId</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">媒体ID</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">推广计划ID</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">应用名称</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">媒体商名称</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">连接地址</th>
        </tr>
        @foreach($data as $row)
        <tr style="padding: 0px; margin: 0px;">
            <td style="padding: 8px 15px;min-width:220px;">{{$row['bannerid']}}</td>
            <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['affiliateid']}}</td>
            <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['campaignid']}}</td>
            <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['app_name']}}</td>
            <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['name']}}</td>
            <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['download_url']}}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <p style="font-size: 14px; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑; border-top:1px solid #CBCBCB;margin-top:20px; color:#CBCBCB">
        本邮件仅用于通知，请勿回复。 如有疑问请登录<a href="http://www.pinxiaotong.com">http://www.pinxiaotong.com</a> 联系我们。
    </p>
</div>