<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use SplFileInfo;

class RepairMediaLibrary extends Command
{
    protected $signature = 'media:repair
        {--apply : Apply the repairs; without this option the command is read-only}
        {--show-orphans : Select recovered orphaned images for the public gallery}
        {--category=Campus : Category assigned to recovered orphaned images}';

    protected $description = 'Reconcile public/media image files with the media database table';

    private const IMAGE_EXTENSIONS = [
        'avif', 'gif', 'jpeg', 'jpg', 'png', 'svg', 'webp',
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('media')) {
            $this->components->error('The media database table does not exist. Run the migrations first.');

            return self::FAILURE;
        }

        $mediaDirectory = public_path('media');
        if (! File::isDirectory($mediaDirectory)) {
            $this->components->error("Media directory not found: {$mediaDirectory}");

            return self::FAILURE;
        }

        $diskImages = collect(File::files($mediaDirectory))
            ->filter(fn (SplFileInfo $file): bool => in_array(
                strtolower($file->getExtension()),
                self::IMAGE_EXTENSIONS,
                true
            ))
            ->mapWithKeys(fn (SplFileInfo $file): array => [
                'media/'.$file->getFilename() => $file,
            ]);

        $databaseMedia = Media::query()->orderBy('id')->get();
        $databasePaths = $databaseMedia
            ->mapWithKeys(fn (Media $media): array => [
                $this->normalizePath((string) $media->file_path) => $media,
            ]);

        $orphanedFiles = $diskImages->reject(
            fn (SplFileInfo $file, string $path): bool => $databasePaths->has($path)
        );

        $missingFiles = $databaseMedia->filter(function (Media $media): bool {
            $path = $this->normalizePath((string) $media->file_path);

            return str_starts_with($path, 'media/') && ! File::isFile(public_path($path));
        });

        $this->components->info('Media reconciliation report');
        $this->line('Images found in public/media: '.$diskImages->count());
        $this->line('Records found in media table: '.$databaseMedia->count());
        $this->line('Files without database records: '.$orphanedFiles->count());
        $this->line('Database records with missing files: '.$missingFiles->count());

        if ($orphanedFiles->isNotEmpty()) {
            $this->newLine();
            $this->warn('Files that can be registered in the database:');
            $this->table(
                ['File', 'Size'],
                $orphanedFiles->map(fn (SplFileInfo $file, string $path): array => [
                    $path,
                    number_format($file->getSize()).' bytes',
                ])->values()->all()
            );
        }

        if ($missingFiles->isNotEmpty()) {
            $this->newLine();
            $this->warn('Database records whose physical files cannot be found:');
            $this->table(
                ['ID', 'Database path', 'Gallery'],
                $missingFiles->map(fn (Media $media): array => [
                    $media->id,
                    $media->file_path,
                    Schema::hasColumn('media', 'show_in_gallery') && $media->show_in_gallery ? 'selected' : 'not selected',
                ])->values()->all()
            );
        }

        if (! $this->option('apply')) {
            $this->newLine();
            $this->components->info('Dry run only; nothing was changed.');
            $this->line('Run `php artisan media:repair --apply --show-orphans` to register existing orphaned images and show them in the gallery.');

            return self::SUCCESS;
        }

        $hasCategory = Schema::hasColumn('media', 'category');
        $hasGallerySelection = Schema::hasColumn('media', 'show_in_gallery');
        $showOrphans = (bool) $this->option('show-orphans');
        $category = trim((string) $this->option('category')) ?: 'Campus';
        $registered = 0;
        $hidden = 0;

        DB::transaction(function () use (
            $orphanedFiles,
            $missingFiles,
            $hasCategory,
            $hasGallerySelection,
            $showOrphans,
            $category,
            &$registered,
            &$hidden
        ): void {
            foreach ($orphanedFiles as $path => $file) {
                $data = [
                    'name' => $file->getFilename(),
                    'mime_type' => $this->imageMimeType($file),
                    'size' => $file->getSize(),
                ];

                if ($hasCategory) {
                    $data['category'] = $category;
                }

                if ($hasGallerySelection) {
                    $data['show_in_gallery'] = $showOrphans;
                }

                $media = Media::firstOrCreate(['file_path' => $path], $data);
                if ($media->wasRecentlyCreated) {
                    $registered++;
                }
            }

            if ($hasGallerySelection) {
                foreach ($missingFiles as $media) {
                    if ($media->show_in_gallery) {
                        $media->update(['show_in_gallery' => false]);
                        $hidden++;
                    }
                }
            }
        });

        $this->newLine();
        $this->components->info("Repair complete: {$registered} file(s) registered and {$hidden} broken gallery item(s) hidden.");

        if ($missingFiles->isNotEmpty()) {
            $this->warn('Missing physical files cannot be recreated. Restore them from backup or upload them again. Their database rows were kept.');
        }

        return self::SUCCESS;
    }

    private function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', trim($path)), '/');
    }

    private function imageMimeType(SplFileInfo $file): string
    {
        $detected = File::mimeType($file->getPathname());

        if (is_string($detected) && str_starts_with($detected, 'image/')) {
            return $detected;
        }

        return match (strtolower($file->getExtension())) {
            'avif' => 'image/avif',
            'gif' => 'image/gif',
            'jpeg', 'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
