<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Операция подтверждена</title>
    <link rel="stylesheet" href="./templates/styles/success.css">
</head>
<body>
<div class="container">
    <h1><?= htmlspecialchars($message ?? 'Success!') ?></h1>
  <?php if (!empty($link)): ?>
      <p>Можете перейти в интернет-магазин по <a href="<?= htmlspecialchars($link) ?>">ссылке</a>.</p>
  <?php endif; ?>
</body>
</div>

</html>