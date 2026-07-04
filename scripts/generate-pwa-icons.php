<?php
// Generates square PWA icons from the Rynude logo.
// Run once: php scripts/generate-pwa-icons.php

$src = __DIR__ . '/../public/images/logo_rynudee.png';
$logo = imagecreatefrompng($src);
imagesavealpha($logo, true);
$lw = imagesx($logo);
$lh = imagesy($logo);

// $scale = fraction of the canvas the logo occupies; maskable icons need a
// bigger safe zone so the logo is scaled down further.
function makeIcon($logo, $lw, $lh, int $size, float $scale, string $out): void
{
    $canvas = imagecreatetruecolor($size, $size);
    $bg = imagecolorallocate($canvas, 253, 248, 246); // #fdf8f6, matches theme
    imagefill($canvas, 0, 0, $bg);

    $target = $size * $scale;
    $ratio = min($target / $lw, $target / $lh);
    $w = (int) round($lw * $ratio);
    $h = (int) round($lh * $ratio);
    imagecopyresampled($canvas, $logo, (int) (($size - $w) / 2), (int) (($size - $h) / 2), 0, 0, $w, $h, $lw, $lh);

    imagepng($canvas, $out);
    echo 'created ' . realpath($out) . PHP_EOL;
}

$dir = __DIR__ . '/../public/images';
makeIcon($logo, $lw, $lh, 192, 0.80, $dir . '/icon-192.png');
makeIcon($logo, $lw, $lh, 512, 0.80, $dir . '/icon-512.png');
makeIcon($logo, $lw, $lh, 512, 0.55, $dir . '/icon-512-maskable.png');
