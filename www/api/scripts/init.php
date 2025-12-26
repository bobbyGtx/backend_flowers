<?php
global $errorLogDir, $storageDir, $rateLimitDir;
include_once "variables.php";
$paths = [
  $errorLogDir,
  $storageDir,
  $rateLimitDir,
];

foreach ($paths as $path) {
  if (!is_dir($path)) {
    if (!mkdir($path, 0700, true)) {
      throw new RuntimeException("Cannot create directory: $path");
    }
  }
}

echo "Project structure initialized successfully\n";