<div>
    <table border="1" cellspacing="1" cellpadding="0" style="padding: 0px; margin: 0px; border-collapse: collapse; border: 1px solid rgb(221, 221, 221); width:100%; font-family: 微软雅黑; font-size: 14px;">
        <tbody style="padding: 0px; margin: 0px;">
        <tr style="padding: 0px; margin: 0px;">
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">账号ID</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">广告主</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">充值金额</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">扣款总额</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">账户余额</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">真实余额</th>
            <th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">余额变化金额</th>
        </tr>
        @foreach ($data as $row)
            <tr style="padding: 0px; margin: 0px;">
                <td style="padding: 8px 15px;min-width:100px;">{{$row['t_account']}}</td>
                <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['clientname']}}</td>
                <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['t_charge']}}</td>
                <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['t_sum_price']}}</td>
                <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['t_balance']}}</td>
                <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['t_true_balance']}}</td>
                <td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['t_sub']}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p style="font-size: 14px; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑; border-top:1px solid #CBCBCB;margin-top:20px; color:#CBCBCB">
        本邮件仅用于通知，请勿回复。 如有疑问请登录 <a href="http://www.pinxiaotong.com">http://www.pinxiaotong.com</a> 联系我们。
    </p>
</div>
