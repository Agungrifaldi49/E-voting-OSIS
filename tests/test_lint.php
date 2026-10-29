<?php
// tests/test_lint.php
$files = array_merge(
    glob(__DIR__ . '/../admin/*.php'),
    glob(__DIR__ . '/../guru/*.php'),
    glob(__DIR__ . '/../siswa/*.php'),
    glob(__DIR__ . '/../app/Views/layouts/*.php'),
    glob(__DIR__ . '/../app/Views/siswa/*.php')
);

$errors = 0;
foreach ($files as $file) {
    $out = shell_exec('php -l "' . $file . '" 2>&1');
    if (strpos($out, 'No syntax errors detected') === false) {
        echo "SYNTAX ERROR in {$file}:\n{$out}\n";
        $errors++;
    }
}

if ($errors === 0) {
    echo "SUCCESS: All " . count($files) . " files passed PHP syntax validation!\n";
} else {
    echo "FAILED with {$errors} errors.\n";
    exit(1);
}
