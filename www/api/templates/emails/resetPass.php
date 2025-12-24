<?php
/**
 * Обязательные переменные:
 * @var string $languageTag (ru|en|de)
 * @var string $actionURL
 * @var string $logoUrl
 * @var string|null $endOfLifeDate (date | null)
 */

$translations = require __DIR__ . '/translations/resetPassTranslations.php';
$t = $translations[$languageTag] ?? $translations['en'];
?>

<!DOCTYPE html>
<html lang="<?= $t['lang'] ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($t['title']) ?></title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:20px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0"
                   style="background-color:#ffffff; border-radius:6px; overflow:hidden;">

                <!-- HEADER -->
                <tr>
                    <td align="center" style="padding:30px 20px;">
                        <img
                                src="<?= htmlspecialchars($logoUrl) ?>"
                                alt="<?= htmlspecialchars($t['logo_alt']) ?>"
                                width="150"
                                height="90"
                                style="display:block; border:0;"
                        >
                    </td>
                </tr>

                <!-- CONTENT -->
                <tr>
                    <td style="padding:20px 40px; color:#333333; font-size:15px; line-height:1.6;">
                        <p><?= $t['greeting'] ?></p>

                        <p><?= nl2br($t['text_main']) ?></p>

                        <p style="text-align:center; margin:30px 0;">
                            <a href="<?= htmlspecialchars($actionURL) ?>"
                               style="
                    background-color:#456F49;
                   color:#ffffff;
                   text-decoration:none;
                   padding:12px 24px;
                   border-radius:4px;
                   font-weight:bold;
                   display:inline-block;">
                              <?= htmlspecialchars($t['button']) ?>
                            </a>
                        </p>
                        <p>  <?= (empty($endOfLifeDate))
                            ? htmlspecialchars($t['link_no_expiry'])
                            : sprintf(htmlspecialchars($t['link_expiry']), htmlspecialchars($endOfLifeDate))
                          ?></p>
                        <p><?= nl2br($t['text_ignore']) ?></p>

                        <p style="margin-top:30px;">
                          <?= $t['signature'] ?>
                        </p>
                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td style="background-color:#f0f2f5; padding:15px 40px; font-size:12px; color:#777777;">
                        <p style="margin:0;"><?= $t['footer'] ?></p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>