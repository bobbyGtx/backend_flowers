<?php
/**
 * Обязательные переменные:
 * @var string $languageTag (ru|en|de)
 * @var array $order
 * @var array $fullProductsList - полный список продуктов с картинками
 * @var string $frontendProductUrl - базовый url страницы продукта с учетом языка
 * @var string $imagesUrl
 * @var string $productsUrl
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
      <table width="600" cellpadding="12" cellspacing="0" style="background:#B6D5B9; padding:20px 10px;">
          <!-- HEADER -->
          <tr>
              <td align="center" style="padding:30px 20px;">
                  <a href="<?= htmlspecialchars($frontendAddress.'/'.$languageTag) ?>" target="_blank" style="cursor:pointer;">
                  <img
                          src="<?= htmlspecialchars($logoUrl) ?>"
                          alt="<?= htmlspecialchars($t['logo_alt']) ?>"
                          width="150"
                          height="90"
                          style="display:block; border:0;"
                  >
                  </a>
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
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
              <?php foreach ($fullProductsList as $index=>$item): ?>
                <tr style="background-color: <?= $index % 2 ? '#a4cba3' : '#6CAC7280' ?>;">
                  <td rowspan="2" style="width: 85px; border-top-left-radius: 5px; border-bottom-left-radius: 5px; overflow:hidden;">
                      <div style="width: 75px; height: 75px; padding: 5px 10px 5px 5px; display:flex;">
                          <a href="<?= htmlspecialchars($frontendProductUrl. $item['url'])?>" target="_blank" style="cursor: pointer;">
                            <img src="<?= htmlspecialchars($productsUrl.$item['image']) ?>"
                            alt="<?= htmlspecialchars($item['name']) ?>" style="
                                 width: 100%; height: 100%;
                                 border-radius: 4px;
                                 overflow: hidden;
                                 object-fit: cover;">
                          </a>
                      </div>
                  </td>
                  <td style="width: auto; vertical-align: middle; padding-top: 22px;border-top-right-radius: 5px;">
                      <a href="<?= htmlspecialchars($frontendProductUrl. $item['url'])?>" target="_blank"
                      style="
                        text-decoration:none;
                        color: #456F49;
                        cursor: pointer;
                        white-space: wrap;
                        padding-right: 10px;
                        ">
                        <?= htmlspecialchars($item['name']) ?>
                      </a>
                  </td>
                </tr>
              <tr style="background-color: <?= $index % 2 ? '#a4cba3' : '#6CAC7280' ?>; border-radius: 4px; height: 20px">
                  <td style="text-align: right; vertical-align: center; width: auto; border-bottom-right-radius: 5px;">
                    <div style="padding: 0 15px 2px 15px ;text-align: right;">
                      <?= $item['quantity'] ?> × <?= $item['price'] ?> €
                    </div>
                  </td>
              </tr>
              <?php endforeach; ?>
            </table>
          </td>
        </tr>

        <tr>
          <td align="right">
            <?= $t['productsCost'] ?>: <?= (float)$order['totalAmount'] - (float)$order['deliveryCost'] ?> € <br>
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