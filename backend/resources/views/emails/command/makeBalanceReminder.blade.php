<div>
  <p style="font-size: medium; padding: 10px 0px 5px; margin: 0px; line-height: 26.6000003814697px; font-family: 微软雅黑;">
  	以下广告主余额不足,@if($target == 'sales') 请及时联系广告主充值。@else 请及时协调广告销售处理。 @endif
  </p>

<table border="1" cellspacing="1" cellpadding="0" style="padding: 0px; margin: 0px; border-collapse: collapse; border: 1px solid rgb(221, 221, 221); font-family: 微软雅黑; font-size: 14px; width: 600px;">
	<tbody style="padding: 0px; margin: 0px;">
		<tr style="padding: 0px; margin: 0px;">
			<th style="padding: 8px 25px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">广告主名称</th>
			<th style="padding: 8px 25px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">广告推广</th>
			<th style="padding: 8px 25px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">当前余额</th>
		</tr>
		@foreach($val as $row)
		<tr style="padding: 0px; margin: 0px;">
			<td style="padding: 8px 25px; margin: 0px; text-align: center;">{{$row['clientname']}}</td>
			<td class="subtable" style="padding: 0px; margin: 0px; text-align: center; border:none">
				<ul style="padding: 0px; margin: 0px; list-style: none;">
				@foreach($row['campaigns'] as $k => $v)
					<li style="padding: 8px 0px; margin: 0px; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: rgb(221, 221, 221);list-style: none">{{$v['app_show_name']}}&nbsp;({{$platForm[$v['platform']]}})</li>
				@endforeach
				</ul>
			</td>
			<td style="padding: 8px 25px; margin: 0px; text-align: center;">{{$row['balance']}}</td>
		</tr>
		@endforeach
	</tbody>
</table>
  <p style="font-size: 14px; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑; border-top:1px solid #CBCBCB;margin-top:20px; color:#CBCBCB">
  	本邮件仅用于通知，请勿回复。 如有疑问请登录<a href="http://www.pinxiaotong.com">http://www.pinxiaotong.com</a> 联系我们。
  </p>
</div>