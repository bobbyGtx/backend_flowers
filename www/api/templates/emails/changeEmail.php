<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Подтверждение изменения электронной почты</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:20px 0;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:6px; overflow:hidden;">

        <!-- HEADER -->
        <tr>
          <td align="center" style="padding:30px 20px;">
            <img
              src="<?= $logoUrl ?>"
              alt="Логотип компании"
              width="150"
              height="90"
              style="display:block; border:0;"
            >
          </td>
        </tr>

        <!-- CONTENT -->
        <tr>
          <td style="padding:20px 40px; color:#333333; font-size:15px; line-height:1.6;">
            <p>Здравствуйте,</p>
            <p>
              Вы запросили изменнеие email адреса на <?= $email ?>.
              Для завершения процесса, пожалуйста, перейдите по ссылке,
              нажав на кнопку ниже.
            </p>

            <p style="text-align:center; margin:30px 0;">
              <a href="<?= $confirmUrl ?>"
                 style="
                                    background-color:#1a73e8;
                                    color:#ffffff;
                                    text-decoration:none;
                                    padding:12px 24px;
                                    border-radius:4px;
                                    font-weight:bold;
                                    display:inline-block;"> Подтвердить изменение email </a>
            </p>
            <p>Ссылка действительна до <?= $endOfLifeDate ?>.</p>
            <p style="color:red;">Если вы не запрашивали данное действие, немедленно <a href="<?= $passChangeUrl ?>">смените пароль</a> в нашем интернет-магазине.</p>
            <p style="margin-top:30px;">С уважением,<br><strong>Команда компании</strong></p>
          </td>
        </tr>
        <!-- FOOTER -->
        <tr>
          <td style="background-color:#f0f2f5; padding:15px 40px; font-size:12px; color:#777777;">
            <p style="margin:0;">Это автоматическое сообщение. Пожалуйста, не отвечайте на него.</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

</body>
</html>

