<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Operation confirmed</title>
    <link rel="stylesheet" href="./templates/styles/error.css">
</head>
<body>
<div class="container">
    <h1><?= htmlspecialchars($message ?? 'Link is not valid!') ?></h1>
  <?php if (isset($code)): ?>
      <p>Error code: <?= htmlspecialchars($code) ?></p>
  <?php endif; ?>
</div>
</body>
</html>