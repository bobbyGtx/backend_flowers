<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($title ?? 'OK') ?></title>
    </head>
    <body>
        <h1><?= htmlspecialchars($message ?? 'Success!') ?></h1>
        <p>Sie können dieses Fenster schließen und zur Webseite des Online-Shops wechseln.</p>
    </body>
</html>