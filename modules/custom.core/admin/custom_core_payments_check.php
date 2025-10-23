<?php
use Bitrix\Main\Loader;
use Custom\Core\Services\OrdersPaymentCheckService;

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';

$APPLICATION->SetTitle("Сверка платежей CloudPayments");

// Проверка прав
if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm("Доступ запрещён");
}

Loader::includeModule('custom.core');

// Получаем параметры
$date = $_GET['date'] ?? date('Y-m-d');
$tab = $_GET['tab'] ?? 'diff';

// Запуск сервиса
$service = new OrdersPaymentCheckService();
$service->run($date);

$discrepancies = $service->getLastDiscrepancies();
$payments      = $service->getLastPayments();
$orders        = $service->getLastOrders();

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';
?>

    <form method="get" action="">
        <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
        <input type="hidden" name="tab" value="<?=$tab?>">
        <div style="margin-bottom: 10px;">
            <label for="date">Дата:</label>
            <input type="date" name="date" id="date" value="<?=htmlspecialcharsbx($date)?>">
            <input type="submit" value="Показать" class="adm-btn-save">
        </div>
    </form>

<?php
// Вкладки
$tabs = [
    ['id' => 'diff',     'title' => 'Расхождения'],
    ['id' => 'payments', 'title' => 'Платежи'],
    ['id' => 'orders',   'title' => 'Заказы'],
];

$tabControl = new CAdminTabControl("tabControl", $tabs, false, true);
$tabControl->Begin();
?>

<?php $tabControl->BeginNextTab(); // Расхождения ?>
    <h2>Расхождения за <?=htmlspecialcharsbx($date)?></h2>
<?php if (empty($discrepancies)): ?>
    <p style="color:green">✅ Расхождений не найдено</p>
<?php else: ?>
    <table class="adm-list-table">
        <thead>
        <tr class="adm-list-table-header">
            <td class="adm-list-table-cell">Тип</td>
            <td class="adm-list-table-cell">Номер заказа</td>
            <td class="adm-list-table-cell">Bitrix</td>
            <td class="adm-list-table-cell">CloudPayments</td>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($discrepancies as $d): ?>
            <tr>
                <td class="adm-list-table-cell"><?=htmlspecialcharsbx($d['type'])?></td>
                <td class="adm-list-table-cell"><?=htmlspecialcharsbx($d['orderNumber'])?></td>
                <td class="adm-list-table-cell">
                    <?php if (!empty($d['bitrix'])): ?>
                        id=<?=$d['bitrix']['ID']?>,
                        сумма=<?=$d['bitrix']['PRICE']?> <?=$d['bitrix']['CURRENCY']?>,
                        оплачен=<?=$d['bitrix']['PAID']?>,
                        статус=<?=$d['bitrix']['STATUS_ID']?>
                    <?php else: ?>
                        <em>нет</em>
                    <?php endif; ?>
                </td>
                <td class="adm-list-table-cell">
                    <?php if (!empty($d['tx'])): ?>
                        сумма=<?=$d['tx']['amount']?> <?=$d['tx']['currency']?>,
                        статус=<?=$d['tx']['status']['raw']?>
                    <?php else: ?>
                        <em>нет</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php $tabControl->EndTab(); ?>


<?php $tabControl->BeginNextTab(); // Платежи ?>
    <h2>Платежи CloudPayments</h2>
<?php if (empty($payments)): ?>
    <p style="color:gray">Нет платежей</p>
<?php else: ?>
    <table class="adm-list-table">
        <thead>
        <tr class="adm-list-table-header">
            <td class="adm-list-table-cell">Номер заказа</td>
            <td class="adm-list-table-cell">Сумма</td>
            <td class="adm-list-table-cell">Валюта</td>
            <td class="adm-list-table-cell">Статус</td>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
            <tr>
                <td class="adm-list-table-cell"><?=htmlspecialcharsbx($p['orderNumber'] ?: '(нет)')?></td>
                <td class="adm-list-table-cell"><?=$p['amount']?></td>
                <td class="adm-list-table-cell"><?=$p['currency']?></td>
                <td class="adm-list-table-cell"><?=$p['status']['raw']?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php $tabControl->EndTab(); ?>


<?php $tabControl->BeginNextTab(); // Заказы ?>
    <h2>Заказы Bitrix</h2>
<?php if (empty($orders)): ?>
    <p style="color:gray">Нет заказов</p>
<?php else: ?>
    <table class="adm-list-table">
        <thead>
        <tr class="adm-list-table-header">
            <td class="adm-list-table-cell">Номер заказа</td>
            <td class="adm-list-table-cell">ID</td>
            <td class="adm-list-table-cell">Сумма</td>
            <td class="adm-list-table-cell">Валюта</td>
            <td class="adm-list-table-cell">Оплачен</td>
            <td class="adm-list-table-cell">Статус</td>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td class="adm-list-table-cell"><?=$o['ACCOUNT_NUMBER']?></td>
                <td class="adm-list-table-cell"><?=$o['ID']?></td>
                <td class="adm-list-table-cell"><?=$o['PRICE']?></td>
                <td class="adm-list-table-cell"><?=$o['CURRENCY']?></td>
                <td class="adm-list-table-cell"><?=$o['PAID']?></td>
                <td class="adm-list-table-cell"><?=$o['STATUS_ID']?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<?php $tabControl->EndTab(); ?>


<?php $tabControl->End(); ?>

<?php
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
