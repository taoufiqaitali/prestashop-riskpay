<div class="row">
	<div class="col-xs-12">
		<p class="payment_module">
			<a class="riskpay" href="{$link->getModuleLink('riskpay', 'payment')}" title="{l s='Pay by RiskPay' mod='riskpay'}" style="padding-left: 20px;">
                <img src="{$this_path_riskpay}logo.png" alt="{l s='Pay by RiskPay' mod='riskpay'}" height="49" style="margin-right: 10px;"/>
				{$path}{l s='Pay by RiskPay' mod='riskpay'}
			</a>
		</p>
	</div>
</div>

<style>
    p.payment_module a.riskpay {
        background: #fbfbfb;
    }
    
    p.payment_module a.riskpay:after {
      display: block;
      content: "\f054";
      position: absolute;
      right: 15px;
      margin-top: -11px;
      top: 50%;
      font-family: "FontAwesome";
      font-size: 25px;
      height: 22px;
      width: 14px;
      color: #777;
    }
</style>