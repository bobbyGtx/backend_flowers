<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Confirm your email</title>
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
              alt="Company Logo"
              width="150"
              height="90"
              style="display:block; border:0; outline:none;"
            >
          </td>
        </tr>

        <!-- CONTENT -->
        <tr>
          <td style="padding:20px 40px; color:#333333; font-size:15px; line-height:1.6;">
            <p>Hello,</p>
            <p>Thank you for registering with our service. To complete the process, please confirm your email address by clicking the button below.</p>

            <p style="text-align:center; margin:30px 0;">
              <a href="<?= $confirmUrl ?>"
                 style="background-color:#1a73e8;
                        color:#ffffff;
                        text-decoration:none;
                        padding:12px 24px;
                        border-radius:4px;
                        font-weight:bold;
                        display:inline-block;"
              >
                Confirm Email</a>
            </p>
            <p>If you did not request this action, you can safely ignore this email.</p>
            <p style="margin-top:30px;">
              Best regards,<br>
              <strong>Your Company Team</strong>
            </p>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background-color:#f0f2f5; padding:15px 40px; font-size:12px; color:#777777;">
            <p style="margin:0;">This is an automated message. Please do not reply.</p>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
