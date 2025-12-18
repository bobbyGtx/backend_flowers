<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($title ?? 'OK') ?></title>
    </head>
    <body>
        <h1><?= htmlspecialchars($message ?? 'Success!') ?></h1>
        <p>Можете закрыть это окно и перейти на сайт интернет магазина.</p>
    </body>
</html>