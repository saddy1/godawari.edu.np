<?php

namespace App\Console\Commands;

use App\Support\SiteSettings;
use GdImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GeneratePwaIcons extends Command
{
    protected $signature = 'pwa:icons {surface? : Regenerate icons for one surface only (admin, student, learning). Omit for all.}';

    protected $description = 'Generate PWA icon sets (192/512 any + maskable, apple-touch-icon) per dashboard from the configured site logo';

    private const SURFACES = ['admin', 'student', 'learning'];

    public function handle(): int
    {
        $settings = app(SiteSettings::class);
        $logoPath = public_path($settings->get('site_logo', 'assets/image/logo.png'));
        if (! is_file($logoPath)) {
            $this->components->error("Logo file not found: {$logoPath}");

            return self::FAILURE;
        }

        $bgHex = $settings->get('dark_color', '#0b2415');
        $requested = $this->argument('surface');
        if ($requested && ! in_array($requested, self::SURFACES, true)) {
            $this->components->error("Unknown surface \"{$requested}\". Choose one of: ".implode(', ', self::SURFACES));

            return self::FAILURE;
        }

        foreach ($requested ? [$requested] : self::SURFACES as $surface) {
            $this->generateForSurface($surface, $logoPath, $bgHex);
            $this->components->info("Generated PWA icons for \"{$surface}\".");
        }

        return self::SUCCESS;
    }

    private function generateForSurface(string $surface, string $logoPath, string $bgHex): void
    {
        $dir = public_path("icons/{$surface}");
        File::ensureDirectoryExists($dir);

        // "any" icons keep transparency; maskable/apple icons need a solid background —
        // transparent maskable icons render inconsistently across OS icon masks, and iOS
        // renders transparent apple-touch-icons with a black fill.
        $this->writeIcon($logoPath, "{$dir}/icon-any-192.png", 192, null, 0.88);
        $this->writeIcon($logoPath, "{$dir}/icon-any-512.png", 512, null, 0.88);
        $this->writeIcon($logoPath, "{$dir}/icon-maskable-192.png", 192, $bgHex, 0.65);
        $this->writeIcon($logoPath, "{$dir}/icon-maskable-512.png", 512, $bgHex, 0.65);
        $this->writeIcon($logoPath, "{$dir}/apple-touch-icon.png", 180, $bgHex, 0.78);
    }

    private function writeIcon(string $sourcePath, string $destPath, int $size, ?string $bgHex, float $scale): void
    {
        $source = $this->loadImage($sourcePath);
        if (! $source) return;
        $srcW = imagesx($source);
        $srcH = imagesy($source);

        $canvas = imagecreatetruecolor($size, $size);
        if ($bgHex) {
            [$r, $g, $b] = $this->hexToRgb($bgHex);
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, $r, $g, $b));
        } else {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagealphablending($canvas, true);
        }

        $targetSize = (int) round($size * $scale);
        $ratio = min($targetSize / $srcW, $targetSize / $srcH);
        $newW = max(1, (int) round($srcW * $ratio));
        $newH = max(1, (int) round($srcH * $ratio));
        $dstX = (int) round(($size - $newW) / 2);
        $dstY = (int) round(($size - $newH) / 2);

        imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);
        imagepng($canvas, $destPath, 6);
        imagedestroy($canvas);
        imagedestroy($source);
    }

    private function loadImage(string $path): GdImage|false
    {
        $info = @getimagesize($path);
        if (! $info) return false;

        return match ($info[2]) {
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => false,
        };
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }
}
