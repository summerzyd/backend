<table style="padding: 0px; width:1200px; font-family: 微软雅黑; border:1px solid #CBCBCB; border-radius:5px; position:relative; background:#fff;">
    <tr>
        <td style="padding:20px 0 10px; border-bottom:1px solid #CBCBCB; text-align:center;">
            <table  style="padding:0px; border-collapse: collapse; display:inline-block;">
                <tr>
                    <td><img src="{{$subject['logo_url']}}" /></td>
                    <td style="vertical-align:middle;">&nbsp;{{$subject['start_date']}} ~ {{$subject['end_date']}}&nbsp;CPD业务周报&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:20px";>
            <table style="padding: 0px; border-collapse: collapse; width:1160px;">
                <tr style="height:320px;">
                    <td style="width:580px; border-right:1px dotted #CBCBCB; border-bottom:1px dotted #CBCBCB; vertical-align:top;">
                        <div style="padding:0 20px; position:relative; width:540px;">
                            <h4 style="font-size:14px; margin:15px 0 20px; padding:0px;">本周汇总</h4>
                            <table style="padding: 0px; border-collapse: collapse; width:540px;">
                                <tr>
                                    <td style="width:33%; padding:0 0 45px; font-size:12px;">
                                        <span style="font-size:20px; font-weight:600; color:#ff6600;">{{$sum_week['sum_revenue']}}</span> 元消耗
                                    </td>
                                    <td style="width:33%; padding:0 0 45px; font-size:12px;">
                                        <span style="font-size:20px; font-weight:600; color:#ff6600;">{{$sum_week['sum_clicks']}}</span> 下载
                                    </td>
                                    <td style="width:33%; padding:0 0 45px; font-size:12px;">
                                        <span style="font-size:20px; font-weight:600; color:#ff6600;">{{$sum_week['price']}}</span> 元/下载
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:33%; padding:0 0 45px; font-size:12px;">
                                        <span style="font-size:20px; font-weight:600; color:#ff6600;">{{$sum_week['sum_client']}}</span> 有消耗客户数
                                    </td>
                                    <td style="width:33%; padding:0 0 45px; font-size:12px;">
                                        <span style="font-size:20px; font-weight:600; color:#ff6600;">{{$sum_week['sum_ad']}}</span> 有消耗广告数
                                    </td>
                                    <td style="width:33%; padding:0 0 45px; font-size:12px;"></td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td style="text-align:center; vertical-align:top; border-bottom:1px dotted #CBCBCB;">
                        <h4 style="font-size:14px; margin:15px 0; padding:0px;">最近5周，每周平均下载
                            <span style="font-size:20px; font-weight:600; color:#ff6600;">{{$chart['avg_clicks']}}万</span>
                            ，每周平均消耗<span style="font-size:20px; font-weight:600; color:#ff6600;">{{$chart['avg_revenue']}}万</span>
                        </h4>
                        <img src="{{$image['seven_url']}}" style=" display: inline-block;" />
                    </td>
                </tr>
                <tr style="height:320px; text-align:center;">
                    <td style="width:580px; border-right:1px dotted #CBCBCB; border-bottom:1px dotted #CBCBCB; vertical-align:top;">
                        <h4 style="font-size:14px; margin:15px 0;">代理商消耗</h4>
                        <img src="{{$image['broker_url']}}" style=" display: inline-block;" />
                    </td>
                    <td style="border-bottom:1px dotted #CBCBCB; vertical-align:top; overflow:hidden; position:relative;">
                        <h4 style="font-size:14px; margin:15px 0;">Top10项目</h4>
                        <table style="padding: 0px; margin: 0 1%; border-collapse: collapse; border:solid #CBCBCB; border-width:1px 0px 0px 1px; font-size:14px;width:98%; font-size:12px; ">
                            <tr style="margin: 0px; text-align:center; background: #f3f3f3;">
                                <th style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; font-weight:500;">项目名称</th>
                                <th style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; font-weight:500;">业务单元</th>
                                <th style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; font-weight:500;">本周下载量</th>
                                <th style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; font-weight:500;">本周消耗金额</th>
                                <th style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; font-weight:500;">占比%</th>
                                <th style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; font-weight:500;">消耗单价</th>
                            </tr>
                            @foreach ($rank as $key => $row)
                            <tr style="padding: 0px; margin: 0px;{{($key % 2 == 0) ? '' : 'background: #fafafa;'}}">
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:left;">{{$row['app_name']}}</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:left;">{{$row['brief_name']}}</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:right;">{{$row['sum_clicks']}}</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:right;">{{$row['sum_revenue']}}</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:right;">{{$row['rate']}}%</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:center;">{{$row['price']}}</th>
                            </tr>
                            @endforeach
                            <tr style="padding: 0px; margin: 0px;">
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:center;">汇总</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:center;">-</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:right;">{{$sum['sum_clicks']}}</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:right;">{{$sum['sum_revenue']}}</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:right;">{{$sum['rate']}}%</td>
                                <td style="padding: 2px; border:solid #CBCBCB; border-width:0px 1px 1px 0px; text-align:center;">{{$sum['price']}}</th>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr style="height:320px; text-align:center;">
                    <td style="width:580px; border-right:1px dotted #CBCBCB; vertical-align:top;">
                        <h4 style="font-size:14px; margin:15px 0;">Top10媒体消耗占比</h4>
                        <img src="{{$image['trafficker_url']}}" style=" display: inline-block;" />
                    </td>
                    <td style="vertical-align:top;">
                        <h4 style="font-size:14px; margin:15px 0;">Top5媒体消耗</h4>
                        <img src="{{$image['trafficker_rank_url']}}" style=" display: inline-block;" />
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<p style="font-size: 14px; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑; margin-top:20px; color:#CBCBCB">
本邮件仅用于通知，请勿回复。 如有疑问请登录 http://www.pinxiaotong.com 联系我们。
</p>
