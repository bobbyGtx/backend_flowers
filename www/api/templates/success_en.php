<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($title ?? 'OK') ?></title>
    </head>
    <body>
        <h1><?= htmlspecialchars($message ?? 'Success!') ?></h1>
        <p>You can close this window and go to the online store website.</p>
    </body>
</html>