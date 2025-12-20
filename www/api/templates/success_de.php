<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Die Operation wurde bestätigt.</title>
    <link rel="stylesheet" href="./templates/styles/success.css">
</head>
<body>
<div class="container">
    <h1><?= htmlspecialchars($message ?? 'Success!') ?></h1>
  <?php if (!empty($link)): ?>
      <p>Über <a href="<?= htmlspecialchars($link) ?>">den Link</a> gelangen Sie zum Online-Shop.</p>
  <?php endif; ?>
</div>
</body>
</html>