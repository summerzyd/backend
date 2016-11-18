<div>
    <p style="font-size: medium; padding: 10px 0px 5px; margin: 0px; line-height: 26.6000003814697px; font-family: 微软雅黑;">
        <span>截至{{$date}}，ADN广告程序化投放数据（含同昨天对比）如下：</span>
    </p>
    <table border="1" cellspacing="1" cellpadding="0" style="padding: 0px; margin: 0px; border-collapse: collapse; border: 1px solid rgb(221, 221, 221); width:100%; font-family: 微软雅黑; font-size: 14px;">
        <tbody style="padding: 0px; margin: 0px;">
            <tr style="padding: 0px; margin: 0px;">
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">广告</th>
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">平台</th>
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">计费类型</th>
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">投放媒体</th>
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">展示量</th>
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">点击量</th>
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">下载量</th>
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">收入</th>
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">支出</th>
                <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">毛利</th>
            </tr>
            @foreach($data as $row)
            <tr style="padding: 0px; margin: 0px;">
                <td style="padding: 8px 15px;min-width:220px;">{{$row['app_name']}}</td>
                <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['platform']}}</td>
                <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['revenue_type']}}</td>
                @foreach([$row['affiliate'], $row['impressions'], $row['clicks'], $row['conversions'], $row['revenue'], $row['payment'], $row['income']] as $change)
                <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$change['data']}}&nbsp;&nbsp;
                    @if($change['flag'] == 'up') 
                    <font color="red">↑{{$change['rate']}} (+{{$change['change']}})</font> 
                    @elseif($change['flag'] == 'down') 
                    <font color="green">↓{{$change['rate']}} (-{{abs($change['change'])}})</font>
                    @else →  @endif
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="font-size: 14px; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑; border-top:1px solid #CBCBCB;margin-top:20px; color:#CBCBCB">
    	本邮件仅用于通知，请勿回复。 如有疑问请登录 <a href="http://www.pinxiaotong.com">http://www.pinxiaotong.com</a> 联系我们。
    </p>
</div>