<?php

namespace App\Http\Controllers;

/**
 * Serves the square PWA icons Chrome needs for installability. Icons are
 * rendered from the Rynude logo with GD on first request and cached to
 * public/images, so no manual build step is required.
 */
class PwaIconController extends Controller
{
    /** spec => [canvas size, fraction of canvas the logo occupies, cache file] */
    private const SPECS = [
        '192' => [192, 0.80, 'icon-192.png'],
        '512' => [512, 0.80, 'icon-512.png'],
        'maskable' => [512, 0.55, 'icon-512-maskable.png'],
    ];

    public function show(string $spec)
    {
        abort_unless(isset(self::SPECS[$spec]), 404);
        [$size, $scale, $file] = self::SPECS[$spec];

        $path = public_path('images/' . $file);
        if (! file_exists($path)) {
            abort_unless(function_exists('imagecreatetruecolor'), 500, 'PHP GD extension is required to generate PWA icons.');
            $this->generate($size, $scale, $path);
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function generate(int $size, float $scale, string $path): void
    {
        $logo = imagecreatefrompng(public_path('images/logo_rynudee.png'));
        imagesavealpha($logo, true);
        $lw = imagesx($logo);
        $lh = imagesy($logo);

        $canvas = imagecreatetruecolor($size, $size);
        $bg = imagecolorallocate($canvas, 253, 248, 246); // #fdf8f6, matches theme
        imagefill($canvas, 0, 0, $bg);

        $target = $size * $scale;
        $ratio = min($target / $lw, $target / $lh);
        $w = (int) round($lw * $ratio);
        $h = (int) round($lh * $ratio);
        imagecopyresampled($canvas, $logo, (int) (($size - $w) / 2), (int) (($size - $h) / 2), 0, 0, $w, $h, $lw, $lh);

        imagepng($canvas, $path);
    }
}
