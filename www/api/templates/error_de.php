<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Anfragefehler</title>
    <link rel="stylesheet" href="./templates/styles/error.css">
</head>
<body>
<div class="container">
<h1><?= htmlspecialchars($message ?? 'Der Link ist ungültig!') ?></h1>
<?php if (isset($code)): ?>
    <p>Fehlercode: <?= htmlspecialchars($code) ?></p>
<?php endif; ?>
</div>
</body>
</html>