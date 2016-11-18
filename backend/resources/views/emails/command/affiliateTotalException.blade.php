<div>
<table border="1" cellspacing="1" cellpadding="0" style="padding: 0px; margin: 0px; border-collapse: collapse; border: 1px solid rgb(221, 221, 221); width:100%; font-family: 微软雅黑; font-size: 14px;">
	<tbody style="padding: 0px; margin: 0px;">
		<tr style="padding: 0px; margin: 0px;">
			<th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">媒体商</th>
			<th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">时间</th>
			<th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">下载量异常</th>
			<th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">下载完成异常(对比下载量)</th>
			<th style="padding: 8px 15px; margin: 0px; text-align: center; background: rgb(234, 234, 234);">下载量对比(今天截止到当前)</th>
		</tr>
  @foreach ($data as $row)
    <tr style="padding: 0px; margin: 0px;">
    	<td style="padding: 8px 15px;min-width:220px;">{{$row['affiliate_name']}}</td>
    	<td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$date}} {{$Hour}}点</td>
    	<td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['todayHourConversions']}}&nbsp;&nbsp;
    		@if($row['conversion_flag'] == 1)
        		@if($row['action'] == '增长') 
        		<font color="red">↑{{$row['rate']}}% (+{{$row['todayHourConversions'] - $row['lastDayHourConversions']}})</font> 
        		@elseif($row['action'] == '下降') 
        		<font color="green">↓{{$row['rate']}}% (-{{$row['lastDayHourConversions'] - $row['todayHourConversions']}})</font>
        		@else -  @endif
        	@else -- @endif
    	</td>
    	<td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['todayHourFiledown']}}&nbsp;&nbsp;
    		@if($row['compare_flag'] == 1)
        		@if($row['compare_action'] == 'more') 
        		<font color="red">↑{{$row['compare_rate']}}% (+{{$row['todayHourFiledown'] - $row['todayHourConversions']}})</font> 
        		@elseif($row['compare_action'] == 'less') 
        		<font color="green">↓{{$row['compare_rate']}}% (-{{$row['todayHourConversions'] - $row['todayHourFiledown']}})</font>
        		@else -  @endif
        	@else --  @endif
    	</td>
    	<td style="padding: 8px 15px;word-break: keep-all;white-space:nowrap;">{{$row['todaySumConversions']}}&nbsp;&nbsp;
    		@if($row['sum_action'] == '增长') 
    		<font color="red">↑{{$row['sum_rate']}}% (+{{$row['todaySumConversions'] - $row['yesterdaySumConversions']}})</font> 
    		@elseif($row['sum_action'] == '下降') 
    		<font color="green">↓{{$row['sum_rate']}}% (-{{$row['yesterdaySumConversions'] - $row['todaySumConversions']}})</font>
    		@else -  @endif
    	</td>
    </tr>
  @endforeach
  </tbody>
</table>
    <p style="font-size: 14px; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑; border-top:1px solid #CBCBCB;margin-top:20px; color:#CBCBCB">
    	本邮件仅用于通知，请勿回复。 如有疑问请登录 <a href="http://www.pinxiaotong.com">http://www.pinxiaotong.com</a> 联系我们。
    </p>
</div>