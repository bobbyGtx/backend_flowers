<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Ошибка') ?></title>
</head>
<body>
<h1><?= htmlspecialchars($message ?? 'Ошибка без текста') ?></h1>
<p>Код ошибки: <?= htmlspecialchars($code ?? 'N/A') ?></p>
</body>
</html>