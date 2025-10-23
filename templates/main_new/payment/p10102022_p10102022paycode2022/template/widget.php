<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$sum = round($params['sum'], 2);
?>

<div class="mb-4" >
	<p><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_TEMPLATE_PAYSELECTION_WIDGET_DESCRIPTION') ?></p>
	<p><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_TEMPLATE_PAYSELECTION_WIDGET_SUM',
			[
				'#SUM#' => SaleFormatCurrency($sum, $params['currency']),
			]
		) ?></p>
	<div class="d-flex align-items-center mb-3" id="paysystem-button">
		<div class="col-auto pl-0">
			<a class="btn btn-lg btn-success pl-4 pr-4" style="border-radius: 32px;" id="paysystem-button-pay" href="#"><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_TEMPLATE_PAYSELECTION_WIDGET_BUTTON_PAID') ?></a>
		</div>
	</div>

    <div class="alert alert-info"><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_TEMPLATE_PAYSELECTION_WIDGET_WARNING_RETURN') ?></div>
</div>

<script src="<?= CUtil::JSEscape($params['WidgetUrl']) ?>"></script>
<script type="text/javascript" defer>
    function payHandler() {
        document.body.classList.remove('loading');
        this.pay = function() {
            console.log('pay');
            let widget = new pw.PayWidget();
            let pay =
                {
                    MetaData: {
                        PaymentType: "<?= CUtil::JSEscape($params['payment_type']) ?>",
                    },
                    PaymentRequest: {
                        OrderId: "<?= CUtil::JSEscape($params['PaymentRequest']['OrderId']) ?>",
                        Amount: "<?= CUtil::JSEscape($params['PaymentRequest']['Amount']) ?>",
                        Currency: "<?= CUtil::JSEscape($params['PaymentRequest']['Currency']) ?>",
                        Description: "<?= CUtil::JSEscape($params['PaymentRequest']['Description']) ?>",
                        ExtraData: JSON.parse("<?= CUtil::JSEscape(json_encode($params['PaymentRequest']['ExtraData'])) ?>"),
                    },
                };
            if ("<?= CUtil::JSEscape(json_encode($params['ReceiptData'])) ?>" !== "null") {
                pay['ReceiptData'] = JSON.parse("<?= CUtil::JSEscape(json_encode($params['ReceiptData'])) ?>");
            }
            widget.pay(
                {
                    serviceId: "<?= CUtil::JSEscape($params['ServiceId']) ?>",
                    key: "<?= CUtil::JSEscape($params['Key']) ?>",
                },
                pay,
                {
                    onSuccess: function (res) {
                        console.log("onSuccess from shop", res);
                        localStorage.removeItem('seatMapCart');
                        $("#card_form").remove();
                        $("#success_form").show();
                    },
                    onError: function (res) {
                        console.log("onFail from shop", res);
                    },
                    onClose: function (res) {
                        console.log("onClose from shop", res);
                    },
                },
            );
        };

        if (document.getElementById('paysystem-button-pay')) {
            pay();
        }

        function isEmpty(str) {
            return (!str || 0 === str.length);
        }
    }
    setTimeout(payHandler, 1000);
</script>
