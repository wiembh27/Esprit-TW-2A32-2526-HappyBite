<?php
declare(strict_types=1);

function fix_mojibake_utf8(string $s): string
{
    $s = preg_replace_callback('/\xC3\x83\xC2([\xA0-\xBF])/s', static function (array $m): string {
        return "\xc3" . $m[1];
    }, $s);
    $s = str_replace("\xC3\x83\xE2\x80\xB0", "\xC3\x89", $s);
    return $s;
}

$root = dirname(__DIR__);
$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($rii as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    $raw = file_get_contents($file->getPathname());
    if ($raw === false || (strpos($raw, "\xC3\x83\xC2") === false && strpos($raw, "\xC3\x83\xE2\x80\xB0") === false)) {
        continue;
    }
    $fixed = fix_mojibake_utf8($raw);
    if ($fixed !== $raw) {
        file_put_contents($file->getPathname(), $fixed);
        echo $file->getPathname(), "\n";
    }
}
