<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Bestätigung der Änderung der E-Mail-Adresse</title>
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
                Sie haben die Änderung Ihrer E-Mail-Adresse auf <?= $userEmail ?> angefordert.
                Um den Vorgang abzuschließen, klicken Sie bitte auf den untenstehenden Button.
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
                                    display:inline-block;"> E-Mail-Änderung bestätigen </a>
            </p>
            <p>Der Link ist gültig bis <?= $endOfLifeDate ?>.</p>
            <p style="color:red;">Falls Sie diese Aktion nicht angefordert haben, ändern Sie bitte umgehend
                <a href="<?= $passChangeUrl ?>">Ihr Passwort</a> in unserem Online-Shop.</p>
            <p style="margin-top:30px;">Mit freundlichen Grüßen<br><strong>Ihr Amora-Flowers-Team</strong></p>
          </td>
        </tr>
        <!-- FOOTER -->
        <tr>
          <td style="background-color:#f0f2f5; padding:15px 40px; font-size:12px; color:#777777;">
            <p style="margin:0;">Dies ist eine automatisch generierte Nachricht. Bitte antworten Sie nicht darauf.</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

</body>
</html>

