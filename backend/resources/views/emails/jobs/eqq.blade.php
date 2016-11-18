<div>
  <p style="font-size: medium; padding: 10px 0px 5px; margin: 0px; line-height: 26.6000003814697px; font-family: 微软雅黑;">
  	从广点通录入人工投放数据，错误如下：
  </p>
@if(is_array($content))
<table border="1" cellspacing="1" cellpadding="0" style="padding: 0px; margin: 0px; border-collapse: collapse; border: 1px solid rgb(221, 221, 221); width:100%; font-family: 微软雅黑; font-size: 14px;">
	<tbody style="padding: 0px; margin: 0px;">
		<tr style="padding: 0px; margin: 0px;">
			<th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">编号</th>
			<th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">描述</th>
		</tr>
		@foreach($content as $k=>$v)
		<tr style="padding: 0px; margin: 0px;">
			<td style="padding: 8px 15px;max-width:20px; text-align: center;">{{$k}}</td>
			<td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$v}}</td>
		</tr>
		@endforeach
	</tbody>
</table>
@else
	<table border="1" cellspacing="1" cellpadding="0" style="padding: 0px; margin: 0px; border-collapse: collapse; border: 1px solid rgb(221, 221, 221); width:100%; font-family: 微软雅黑; font-size: 14px;">
	<tbody style="padding: 0px; margin: 0px;">
		<tr style="padding: 0px; margin: 0px;">
			<th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">编号</th>
			<th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">描述</th>
		</tr>
		<tr style="padding: 0px; margin: 0px;">
			<td style="padding: 8px 15px; max-width:20px; text-align: center;">1</td>
			<td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$content}}</td>
		</tr>
	</tbody>
</table>
@endif
</div>