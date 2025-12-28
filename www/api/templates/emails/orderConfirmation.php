<?php
/**
 * Обязательные переменные:
 * @var string $languageTag (ru|en|de)
 */

$translations = require __DIR__ . '/translations/orderConfirmationTranslations.php';
$t = $translations[$languageTag] ?? $translations['en'];
?>
<!DOCTYPE html>
<html lang="<?= $t['lang'] ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center">
      <table width="600" cellpadding="20" cellspacing="0" style="background:#ffffff;">

        <tr>
          <td>
            <h2><?= $t['title'] ?></h2>
          </td>
        </tr>

        <tr>
          <td>
            <strong><?= $t['orderData'] ?></strong><br>
            <?= $t['orderNumber'] ?>: <?= $order['id'] ?><br>
            <?= $t['paymentType'] ?>: <?= $order['paymentType'] ?><br>
            <?= $t['deliveryType'] ?>: <?= $order['deliveryType'] ?>
          </td>
        </tr>

        <tr>
          <td>
            <strong><?= $t['deliveryAddress'] ?></strong><br>
            <?= $order['delivery_info']['street'] ?> <?= $order['delivery_info']['house'] ?><br>
            <?= $order['delivery_info']['zip'] ?> <?= $order['delivery_info']['city'] ?><br>
            <?= $order['delivery_info']['region'] ?>
          </td>
        </tr>

        <tr>
          <td>
            <strong><?= $t['products'] ?></strong>
            <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse:collapse;">
              <?php foreach ($order['items'] as $item): ?>
                <tr>
                  <td><?= $item['name'] ?></td>
                  <td align="right">
                    <?= $item['quantity'] ?> × <?= $item['price'] ?> €
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>
          </td>
        </tr>

        <tr>
          <td align="right">
            <strong><?= $t['total'] ?>: <?= $order['totalAmount'] ?> €</strong>
          </td>
        </tr>

        <tr>
          <td style="font-size:12px;color:#666;">
            <?= $t['footer'] ?>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>