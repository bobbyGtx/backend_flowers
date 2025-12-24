<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>E-Mail bestätigen</title>
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
              alt="Firmenlogo"
              width="150"
              height="90"
              style="display:block; border:0;"
            >
          </td>
        </tr>

        <!-- CONTENT -->
        <tr>
          <td style="padding:20px 40px; color:#333333; font-size:15px; line-height:1.6;">
            <p>Guten Tag,</p>

            <p>
              vielen Dank für Ihre Registrierung bei unserem Service.
              Um den Vorgang abzuschließen, bestätigen Sie bitte Ihre E-Mail-Adresse,
              indem Sie auf die Schaltfläche unten klicken.
            </p>

            <p style="text-align:center; margin:30px 0;">
              <a href="<?= $actionURL ?>"
                style="
                                    background-color:#456F49;
                                    color:#ffffff;
                                    text-decoration:none;
                                    padding:12px 24px;
                                    border-radius:4px;
                                    font-weight:bold;
                                    display:inline-block;">
                E-Mail bestätigen
              </a>
            </p>

            <p>Falls Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.</p>

            <p style="margin-top:30px;">
              Mit freundlichen Grüßen<br>
              <strong>Ihr Unternehmen</strong>
            </p>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background-color:#f0f2f5; padding:15px 40px; font-size:12px; color:#777777;">
            <p style="margin:0;">
              Dies ist eine automatische Nachricht. Bitte antworten Sie nicht darauf.
            </p>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>