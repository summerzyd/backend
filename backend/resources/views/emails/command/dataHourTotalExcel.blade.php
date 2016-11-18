<html>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
tr td {
    background-color: #ffffff;
}
tr > td {
    border: 1px solid #000000;
}
.green{color:#00FF00;}
.red{color: #FF0000;}
</style>
<table>
    <tr>
        <td align="center" width="20"><b>媒体商</b></td>
        <td align="center" width="20"><b>有效广告数量</b></td>
        <td colspan="3"><b style="text-align: center">展示量</b></td>
        <td colspan="3"><b style="text-align: center">点击量</b></td>
        <td colspan="3"><b style="text-align: center">下载量</b></td>
        <td colspan="3"><b style="text-align: center">收入</b></td>
        <td colspan="3"><b style="text-align: center">支出</b></td>
        <td align="center" width="20"><b>平台</b></td>
        <td align="center" width="20"><b>投放类型</b></td>
    </tr>
    @foreach($data as $row)
    <tr>
        <td>{{$row['media']}}</td>
        <td>{{$row['validcampaigns']}}</td>
        @foreach([$row['impressions'], $row['clicks'], $row['conversions'], $row['revenue'], $row['payment']] as $change)
        <td>{{$change['data']}}</td>
        @if($change['flag'] == 'up') 
        <td class="red">{{$change['rate']}}</td>
        <td class="red">{{$change['change']}}</td>
        @elseif($change['flag'] == 'down')
        <td class="green">-{{$change['rate']}}</td>
        <td class="green">-{{$change['change']}}</td>
        @else
        <td>0</td>
        <td>0</td>    
        @endif
        @endforeach
        <td>{{$row['platform']}}</td>
        <td>{{$row['deliverytype']}}</td>
    </tr>
    @endforeach
</table>
</html>