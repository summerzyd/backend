<div>
  <p style="font-size: medium; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑;">
    <span style="line-height: 26.6000003814697px;">
      @if($target == 'affiliate_manager' && $result == 'pass') {{$app_name}} 通过 {{$affiliate_name}} 审核，请及时协助媒体商、联盟运营处理传包、投放事宜。
      @elseif($target == 'platform' && $result == 'pass') {{$app_name}} 通过 {{$affiliate_name}} 审核，请及时跟进处理传包、投放事宜。
      @elseif($target == 'affiliate_manager' && $result == 'reject') {{$app_name}} 未通过 {{$affiliate_name}} 审核，请联系媒体商。
      @elseif($target == 'platform' && $result == 'reject') {{$app_name}} 未通过 {{$affiliate_name}} 审核，请知晓。
      @endif
    </span>
  </p>
    <p style="font-size: 14px; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑; border-top:1px solid #CBCBCB;margin-top:20px; color:#CBCBCB">
    	本邮件仅用于通知，请勿回复。 如有疑问请登录 <a href="http://www.pinxiaotong.com">http://www.pinxiaotong.com</a> 联系我们。
    </p>
</div>
