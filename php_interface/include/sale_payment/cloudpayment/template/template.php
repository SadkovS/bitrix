<?
	
	/**
	 * @var array $params
	 * */
	
	use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ORM;

	Loc::loadMessages(__FILE__);
	
	$description = Loc::getMessage("VBCH_CLPAY_MM_DESC", array(
		"#ORDER_ID#" => $params['PAYMENT_ID'],
		"#SITE_NAME#" => COption::GetOptionString("main", "server_name", ""),
		"#DATE#" => $params['PAYMENT_DATE_INSERT']
	));
	
	$widget_f = $params['TYPE_SYSTEM'] ? 'auth' : 'charge';
    //$widget_url = "https://widget.cloudpayments." . ($params['WIDGET_URL'] ?: 'ru') . "/bundles/cloudpayments?cms=1CBitrix";
	$lang_widget = $params['WIDGET_LANG'] ?: 'ru-RU';
	$skin = $params['WIDGET_SKIN'] ?: 'classic';
	
	$order = $params['ORDER'];
    $propertyCollection = $order->getPropertyCollection();
	$payment = $params['PAYMENT'];
	$basket = $params['BASKET'];
	
	if (!empty($order)) {

        function getOrganizationInfo(int $orgId): array
        {
            if (!$orgId) {
                return [];
            }

            $result = [];

            $query = new ORM\Query\Query('\Custom\Core\Users\CompaniesTable');
            $resCompany   = $query
                ->setFilter([
                    'ID' => $orgId,
                ])
                ->setSelect([
                    'COMPANY_TYPE_XML_ID' => 'COMPANY_TYPE.XML_ID',
                    'UF_FULL_NAME',
                    'UF_FIO',
                    'UF_INN',
                ])
                ->registerRuntimeField(
                    'COMPANY_TYPE',
                    [
                        'data_type' => '\Custom\Core\FieldEnumTable',
                        'reference' => ['=this.UF_TYPE' => 'ref.ID'],
                        'join_type' => 'LEFT'
                    ]
                )
                ->exec();

            if ($company = $resCompany->fetch()) {
                if ($company["COMPANY_TYPE_XML_ID"] == "person") {
                    $company["UF_FULL_NAME"] = $company["UF_FIO"];
                }
                if ($company["COMPANY_TYPE_XML_ID"] === "ip") {
                    $company["UF_FULL_NAME"] = 'ИП ' . $company["UF_FIO"];
                }
                $result = $company;
            }

            return $result;
        }

		$params["ORDER_ID"] = $order->getId();
        $orgId = null;
        $propertyValue = $propertyCollection->getItemByOrderPropertyCode('ORGANIZER_ID');
        if ($propertyValue) {
            $orgId = (int) $propertyValue->getValue();
            if ($orgId) {
                try {
                    $orgInfo = getOrganizationInfo($orgId);
                } catch (\Bitrix\Main\ObjectPropertyException|\Bitrix\Main\ArgumentException|\Bitrix\Main\SystemException $e) {
                }
            }
        }

		if (!$status_id = $order->getField("STATUS_ID"))
			die(Loc::getMessage("ERROR_ORDER_ID"));
		
		if (
			$params['CHECKONLINE'] != 'N' and
			$status_id != $params['STATUS_AU'] and
			$status_id != $params['STATUS_AUTHORIZE'] and
			!$payment->isPaid()
		) {
            $data = array(
              "cmsData" => [
                "cmsName" => "1C Bitrix",
                "cmsModule" => "cp-1c-bitrix-1.1.5",
              ]
            );
			$items = array();

			foreach ($basket->getBasketItems() as $basketItem) {
				$item = [
					'label' => $basketItem->getField('NAME'),
					'price' => number_format($basketItem->getField('PRICE'), 2, ".", ''),
					'quantity' => $basketItem->getQuantity(),
					'vat' => is_null($basketItem->getField('VAT_RATE')) ? null : $basketItem->getField('VAT_RATE')  * 100,
					"object" => $params['PREDMET_RASCHETA1'] ?: 0,
					"method" => $params['SPOSOB_RASCHETA1'] ?: 0,
                ];
				
				$item['amount'] = number_format($item['price'] * $item['quantity'], 2, ".", '');
				
                $sector = '';
                $row = '';
                $seat = '';
				foreach ($basketItem->getPropertyCollection() as $property) {
					if ($property->getField('CODE') === 'SPIC')
						$item["spic"] = $property->getField('VALUE');
					if ($property->getField('CODE') === 'PACKAGE_CODE')
						$item["packageCode"] = $property->getField('VALUE');
                    if ($property->getField('CODE') === 'BARCODE') {
                        $barcodeId = $property->getField('VALUE');
                        $barcode = \Custom\Core\Tickets\BarcodesTable::getList([
                            'filter' => [
                                'ID' => $barcodeId,
                            ],
                            'select' => ['UF_SERIES', 'UF_TICKET_NUM'],
                        ])->fetch();

                        if ($barcode) {
                            $item['label'] = "{$barcode['UF_SERIES']} {$barcode['UF_TICKET_NUM']} {$item['label']}";
                        }
                    }
                    if ($property->getField('CODE') === 'SECTOR') {
                        $sector = $property->getField('VALUE');
                    }
                    if ($property->getField('CODE') === 'ROW') {
                        $row = $property->getField('VALUE');
                    }
                    if ($property->getField('CODE') === 'PLACE') {
                        $seat = $property->getField('VALUE');
                    }
				}

                $tp = '';
                if (preg_match('/^(.*?)\s*\[([^\]]+)\]\s*$/', $item['label'], $match)) {
                    $item['label'] = $match[1];
                    $tp = $match[2];
                }

                $seatName = (!empty($sector) ? ' ' . $sector : ' ' . $tp) . ($row ? " $row ряд" : '') . ($seat ? " $seat место" : '');

                $lenDiff = 126 - mb_strlen($item['label']) - mb_strlen($seatName);
                if ($lenDiff < 0) {
                    $item['label'] = mb_substr($label, 0, $lenDiff);
                    $item['label'] = mb_substr($label, 0, -3) . '...';
                }

                $item['label'] .= $seatName;

                if ($orgId && $orgInfo['UF_INN'] && $orgInfo['UF_FULL_NAME']) {
                    $item['AgentSign'] = 6;
                    $item['AgentData'] = [
                        'AgentOperationName' => 'Заказ № ' . $order->getField('ACCOUNT_NUMBER'),
                        'PaymentAgentPhone' => null,
                        'PaymentReceiverOperatorPhone' => null,
                        'TransferOperatorPhone' => null,
                        'TransferOperatorName' => null,
                        'TransferOperatorAddress' => null,
                        'TransferOperatorInn' => null,
                    ];
                    $item['PurveyorData'] = [
                        'Name' => $orgInfo['UF_FULL_NAME'],
                        'Inn' => $orgInfo['UF_INN'],
                    ];
                }

                $items[] = $item;
            }

            if (
				$order->getDeliveryPrice() > 0 and
				$order->getField("DELIVERY_ID")
			) {
				
				$item_d = array(
					'label' => GetMessage('DELIVERY_TXT'),
					'price' => number_format($order->getDeliveryPrice(), 2, ".", ''),
					'quantity' => 1,
					'amount' => number_format($order->getDeliveryPrice(), 2, ".", ''),
					'vat' => $params['VAT_DELIVERY' . $order->getField("DELIVERY_ID")] ?: NULL,
					'object' => "4",
					'method' => $params['SPOSOB_RASCHETA1'] ?: 0
				);
				
				if (!empty($params['SPIC']))
					$item_d['spic'] = $params['SPIC'];
				if (!empty($params['PACKAGE_CODE']))
					$item_d['packageCode'] = $params['PACKAGE_CODE'];
				
				$items[] = $item_d;
			}
			
			$data['cloudPayments']['customerReceipt'] = array(
				'Items' => $items,
				'taxationSystem' => $params['TYPE_NALOG'],
				'calculationPlace' => $params['calculationPlace'],
				'email' => $propertyCollection->getUserEmail()->getValue(),
				'phone' => $propertyCollection->getPhone()->getValue(),
				'amounts' => array(
					"electronic" => $payment->getSum(),
					"advancePayment" => 0,
					"credit" => 0,
					"provision" => 0,
				),
			);
			
			if (!empty($params['SPIC']) and !empty($params['PACKAGE_CODE']))
				$data['cloudPayments']['customerReceipt']['AdditionalReceiptInfos'] = array(Loc::getMessage("AdditionalReceiptInfos"));
			
		}
		
		if (
			$status_id != $params['STATUS_AU'] and
			$status_id != $params['STATUS_AUTHORIZE'] and
			!$payment->isPaid() and
			!$order->isCanceled() and
			$status_id != $params['STATUS_CHANCEL']
		):
			$data['PAY_SYSTEM_ID'] = $params['BX_PAYSYSTEM_CODE'];
			?>
      <div>
        <?/*<button class="cloudpay_button"
                id="payButton"><?= Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_CLOUDPAYMENTS_BUTTON_PAID') ?></button>*/?>
          <?/*Перенесено в шаблон компонента корзины*/?>
          <?/*<script src="<?=$widget_url?>" async></script>*/?>
        <script type="text/javascript" defer>
            window.payHandler = function () {
	            window.orderNum = '<?= CUtil::JSEscape($params['PaymentRequest']['OrderId']) ?>'
                const widget = new cp.CloudPayments({
                	language: '<?=$lang_widget?>',
                	applePaySupport: true,
				    googlePaySupport: true,
				    yandexPaySupport: true,
				    tinkoffPaySupport: true,
				    tinkoffInstallmentSupport: true,
				    sbpSupport: true,
                });
                const w_options = { // options
                    publicId: '<?=trim(htmlspecialcharsbx($params["APIPASS"]));?>',
                    description: '<?=$description?>',
                    amount: <?=number_format($params['PAYMENT_SHOULD_PAY'], 2, '.', '')?>,
                    currency: '<?=$params['PAYMENT_CURRENCY']?>',
                    email: '<?=$propertyCollection->getUserEmail()->getValue()?>',
                    invoiceId: '<?=htmlspecialcharsbx($params["PAYMENT_ID"]);?>',
                    accountId: '<?=htmlspecialcharsbx($params["PAYMENT_BUYER_ID"]);?>',
                    skin: '<?=$skin?>',
                }
							
							<?if (isset($data))
							echo "w_options.data =" . CUtil::PhpToJSObject($data, false, true)?>

                widget.<?=$widget_f?>(
                    w_options,
                    function (options) { // success
                    	/*let cart = document.querySelector('#card');
						let btnClose = cart.querySelector('.popup__close');
						btnClose.click();*/

                        localStorage.removeItem('seatMapCart');

//	                      processPurchase();

                        showModal();
                    },
                    function (reason, options) { // fail
                    	/*let cart = document.querySelector('#card');
						let btnClose = cart.querySelector('.popup__close');
						btnClose.click();*/
						
						/*$(".btn__popup").hide();
						$(".jq-pay-handler").show();*/
                    }
                );

                document.body.classList.remove('loading');
            };
            
            function showModal() {
                $("#card_form").remove();
                $("#success_form").show();


			    /*document.dispatchEvent(new CustomEvent("openPopup", {
				    detail: {
				        popup: '#pay-reg-comlete'
				    }
				}));*/
			}
        </script>
      </div>
			<?
        elseif ($order->isCanceled() or $status_id == $params['STATUS_CHANCEL']):
			echo 'ORDER_CANCELED';
        endif;
	}
?>
