<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Fehler') ?></title>
</head>
<body>
<h1><?= htmlspecialchars($message ?? 'Fehler ohne Text') ?></h1>
<p>Fehlercode: <?= htmlspecialchars($code ?? 'N/A') ?></p>
</body>
</html>