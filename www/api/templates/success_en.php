<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Operation confirmed</title>
    <link rel="stylesheet" href="./templates/styles/success.css">
</head>
<body>
<div class="container">
    <h1><?= htmlspecialchars($message ?? 'Success!') ?></h1>
  <?php if (!empty($link)): ?>
      <p>You can go to the online store using the <a href="<?= htmlspecialchars($link) ?>">link</a>.</p>
  <?php endif; ?>
</body>
</body>
</html>