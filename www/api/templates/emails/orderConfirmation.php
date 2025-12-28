<?php
/**
 * Обязательные переменные:
 * @var string $languageTag (ru|en|de)
 * @var array $order
 * @var string $frontendProductUrl
 * @var string $imagesUrl
 * @var string $logoUrl
 * @var string $frontendAddress
 */

$translations = require __DIR__ . '/translations/orderConfirmationTranslations.php';
$t = $translations[$languageTag] ?? $translations['ru'];
?>
<!DOCTYPE html>
<html lang="<?= $languageTag ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?></title>
</head>
<body style="margin:0;padding:0;background:#a4cba3;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center">
      <table width="600" cellpadding="12" cellspacing="0" style="background:#B6D5B9;">
          <!-- HEADER -->
          <tr>
              <td align="center" style="padding:30px 20px;">
                  <a href="<?= htmlspecialchars($frontendAddress) ?>" target="_blank" style="cursor:pointer;">
                  <img
                          src="<?= htmlspecialchars($logoUrl) ?>"
                          alt="<?= htmlspecialchars($t['logo_alt']) ?>"
                          width="150"
                          height="90"
                          style="display:block; border:0;"
                  >
              </td>
          </tr>

        <tr>
          <td>
            <h2><?= $t['title'] ?></h2>
          </td>
        </tr>

        <tr>
          <td>
            <strong><?= $t['orderData'] ?> <?= $order['id'] ?></strong><br>
            <?= $t['person'] ?>: <?= $order['firstName'] ?> <?= $order['lastName'] ?><br>
            <?= $t['paymentType'] ?>: <?= $order['paymentType'] ?><br>
            <?= $t['deliveryType'] ?>: <?= $order['deliveryType'] ?>
          </td>
        </tr>

        <?php if (!empty($order['delivery_info'])): ?>
            <tr>
                <td>
                    <strong><?= $t['deliveryAddress'] ?>:</strong><br>
                  <?= htmlspecialchars($order['delivery_info']->region) ?><br>
                  <?= htmlspecialchars($order['delivery_info']->zip) ?>
                  <?= htmlspecialchars($order['delivery_info']->city) ?><br>
                  <?= htmlspecialchars($order['delivery_info']->street) ?>
                  <?= htmlspecialchars($order['delivery_info']->house) ?>
                </td>
            </tr>
        <?php endif; ?>

        <?php if (!empty($order['comment'])): ?>
            <tr>
                <td>
                    <strong><?= $t['comment'] ?>:</strong><br>
                  <?= htmlspecialchars($order['comment']) ?>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
          <td>
            <strong><?= $t['products'] ?></strong>
            <table width="100%" cellpadding="5" cellspacing="0" style="border-collapse:collapse;">
              <?php foreach ($order['items'] as $item): ?>
                <tr>

                  <td>
                      <a href="<?= htmlspecialchars($frontendProductUrl . $item->url)?>" target="_blank"
                      style="
                        text-decoration:none;
                        color: #456F49;
                        cursor: pointer;
                        ">
                        <?= htmlspecialchars($item->name) ?>
                      </a>
                  </td>
                  <td align="right">
                    <?= $item->quantity ?> × <?= $item->price ?> €
                  </td>
                </tr>
              <?php endforeach; ?>
            </table>
          </td>
        </tr>

        <tr>
          <td align="right">
            <?= $t['deliveryCost'] ?>: <?= $order['deliveryCost'] ?> € <br>
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