<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Ошибка запроса</title>
    <link rel="stylesheet" href="./templates/styles/error.css">
</head>
<body>
<div class="container">
<h1><?= htmlspecialchars($message ?? 'Ссылка не действительна!') ?></h1>
<?php if (isset($code)): ?>
    <p>Код ошибки: <?= htmlspecialchars($code) ?></p>
<?php endif; ?>
</div>
</body>
</html>