<?php

/**
 * Renders the RATINO favicon: letter R on a rounded #e51f49 square.
 * Uses the Midstar font when available at frontend/public/webfonts/,
 * falls back to a heavy system font otherwise.
 *
 * Mathematically symmetric corners and padded optical centering so glyph
 * never escapes or touches the rounded square background.
 */

$fontPath = __DIR__ . '/../frontend/public/webfonts/Midstar_Personal_Use.otf';

$fallbackCandidates = [
    'C:/Windows/Fonts/arialbd.ttf',
    'C:/Windows/Fonts/segoeuib.ttf',
    'C:/Windows/Fonts/impact.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
];

$font = $fontPath;
if (!file_exists($font)) {
    $font = null;
    foreach ($fallbackCandidates as $candidate) {
        if (file_exists($candidate)) {
            $font = $candidate;
            break;
        }
    }
}

if (!$font) {
    fwrite(STDERR, "No usable font found. Drop Midstar_Personal_Use.otf into frontend/public/webfonts/ and rerun.\n");
    exit(1);
}

$font = realpath($font) ?: $font;
fwrite(STDOUT, "font: " . basename($font) . "\n");

$outDirs = [
    __DIR__ . '/../frontend/public',
    __DIR__ . '/../public'
];

$sizes = [512, 192, 64];
$SS = 4; // supersample factor

foreach ($sizes as $size) {
    $big = $size * $SS;
    $img = imagecreatetruecolor($big, $big);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $accent = imagecolorallocate($img, 0xE5, 0x1F, 0x49);
    $r = (int) round($big * 0.22);

    // Symmetric 4-corner rounded rectangle
    imagefilledarc($img, $r, $r, $r * 2, $r * 2, 180, 270, $accent, IMG_ARC_PIE);
    imagefilledarc($img, $big - 1 - $r, $r, $r * 2, $r * 2, 270, 360, $accent, IMG_ARC_PIE);
    imagefilledarc($img, $big - 1 - $r, $big - 1 - $r, $r * 2, $r * 2, 0, 90, $accent, IMG_ARC_PIE);
    imagefilledarc($img, $r, $big - 1 - $r, $r * 2, $r * 2, 90, 180, $accent, IMG_ARC_PIE);
    imagefilledrectangle($img, 0, $r, $big - 1, $big - 1 - $r, $accent);
    imagefilledrectangle($img, $r, 0, $big - 1 - $r, $r - 1, $accent);
    imagefilledrectangle($img, $r, $big - $r, $big - 1 - $r, $big - 1, $accent);

    // Measure glyph at reference size, then scale to fit inside padded inner square
    $ref = 100;
    $bbox = imagettfbbox($ref, 0, $font, 'R');
    $gw = max($bbox[2], $bbox[4]) - min($bbox[0], $bbox[6]);
    $gh = max($bbox[1], $bbox[3]) - min($bbox[5], $bbox[7]);

    $box = $big * 0.58; // 58% box gives generous margin from corners
    $scale = min($box / $gw, $box / $gh);
    $fontSize = $ref * $scale;

    $bbox2 = imagettfbbox($fontSize, 0, $font, 'R');
    $minX = min($bbox2[0], $bbox2[6]);
    $maxX = max($bbox2[2], $bbox2[4]);
    $minY = min($bbox2[5], $bbox2[7]);
    $maxY = max($bbox2[1], $bbox2[3]);
    $w = $maxX - $minX;
    $h = $maxY - $minY;

    $x = (int) (($big - $w) / 2 - $minX);
    $y = (int) (($big - $h) / 2 - $minY);

    $white = imagecolorallocate($img, 255, 255, 255);
    imagettftext($img, $fontSize, 0, $x, $y, $white, $font, 'R');

    // Downscale with high quality resample
    $final = imagecreatetruecolor($size, $size);
    imagesavealpha($final, true);
    imagefill($final, 0, 0, $transparent);
    imagecopyresampled($final, $img, 0, 0, 0, 0, $size, $size, $big, $big);

    foreach ($outDirs as $outDir) {
        if (!is_dir($outDir)) @mkdir($outDir, 0777, true);
        imagepng($final, $outDir . '/favicon-' . $size . '.png');
    }

    imagedestroy($img);
    imagedestroy($final);
    fwrite(STDOUT, "favicon-$size.png\n");
}

foreach ($outDirs as $outDir) {
    copy($outDir . '/favicon-64.png', $outDir . '/favicon.png');
}
fwrite(STDOUT, "favicon.png\n");
