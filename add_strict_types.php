<?php

$dir = new RecursiveDirectoryIterator(__DIR__ . '/cpd-api');
$iterator = new RecursiveIteratorIterator($dir);

$phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

foreach ($phpFiles as $file) {
    $filePath = $file[0];
    
    // Skip vendor
    if (strpos($filePath, 'vendor' . DIRECTORY_SEPARATOR) !== false || strpos($filePath, 'vendor/') !== false) {
        continue;
    }

    $content = file_get_contents($filePath);
    
    if (strpos($content, 'declare(strict_types=1);') === false) {
        // Replace the first <?php with <?php\n\ndeclare(strict_types=1);
        $content = preg_replace('/<\?php\s*/', "<?php\n\ndeclare(strict_types=1);\n\n", $content, 1);
        file_put_contents($filePath, $content);
        echo "Added strict_types to: $filePath\n";
    }
}
echo "Done.\n";
