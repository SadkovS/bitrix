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
