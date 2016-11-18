<div>
    <p style="font-size: medium; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑;">
        <span style="line-height: 26.6000003814697px;">{{$app_name}}的{{$field}}被{{$clientname}}从
        @if ($old_value == 0)
            不限
        @else
            {{$old_value}}元
        @endif
        改成
        @if ($new_value == 0)
            不限
        @else
            {{$new_value}}元
        @endif
        ，请审核。</span>
    </p>
    <p style="font-size: 14px; padding: 10px 0px 5px; margin: 0px; font-family: 微软雅黑; border-top:1px solid #CBCBCB;margin-top:20px; color:#CBCBCB">
        本邮件仅用于通知，请勿回复。 如有疑问请登录 <a href="http://www.pinxiaotong.com">http://www.pinxiaotong.com</a> 联系我们。
    </p>
</div>