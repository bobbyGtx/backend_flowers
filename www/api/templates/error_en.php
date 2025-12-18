<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Error') ?></title>
</head>
<body>
<h1><?= htmlspecialchars($message ?? 'Error without text') ?></h1>
<p>Error code: <?= htmlspecialchars($code ?? 'N/A') ?></p>
</body>
</html>