<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Email Change Confirmation</title>
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
              alt="Company logo"
              width="150"
              height="90"
              style="display:block; border:0;"
            >
          </td>
        </tr>

        <!-- CONTENT -->
        <tr>
          <td style="padding:20px 40px; color:#333333; font-size:15px; line-height:1.6;">
            <p>Hello,</p>
            <p>You have requested to change your email address to <?= $userEmail ?>.
                To complete the process, please click the button below.
            </p>

            <p style="text-align:center; margin:30px 0;">
              <a href="<?= $actionURL ?>" style="
                                    background-color:#456F49;
                                    color:#ffffff;
                                    text-decoration:none;
                                    padding:12px 24px;
                                    border-radius:4px;
                                    font-weight:bold;
                                    display:inline-block;"> Confirm email change </a>
            </p>
            <p>The link is valid until <?= $passChangeLink ?>.</p>
            <p style="color:red;">If you did not request this action, please immediately <a href="<?= $passChangeUrl ?>">change your password</a> in our online store.</p>
            <p style="margin-top:30px;">Kind regards,<br><strong>The Amora Flowers Team</strong></p>
          </td>
        </tr>
        <!-- FOOTER -->
        <tr>
          <td style="background-color:#f0f2f5; padding:15px 40px; font-size:12px; color:#777777;">
            <p style="margin:0;">This is an automated message. Please do not reply to it.</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

</body>
</html>

