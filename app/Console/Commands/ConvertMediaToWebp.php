<?php

namespace App\Console\Commands;

use App\Services\ImageCompressionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Rewrites legacy JPG/PNG media rows to WebP in place: same directory, new
 * random .webp name, DB column updated before the original is deleted.
 */
class ConvertMediaToWebp extends Command
{
    protected $signature = 'media:convert-webp
        {--dry-run : Report what would change without writing or deleting}
        {--limit=0 : Stop after N converted files (0 = all)}';

    protected $description = 'Convert legacy image uploads to compressed WebP';

    /** model => [column, maxWidth] */
    private const TARGETS = [
        \App\Models\Product::class => ['thumbnail', 1200],
        \App\Models\ProductPhoto::class => ['photo', 1200],
        \App\Models\Category::class => ['photo', 1200],
        \App\Models\SubCategory::class => ['photo', 1200],
        \App\Models\Brand::class => ['logo', 1200],
        \App\Models\CustomerPhoto::class => ['photo', 1600],
        \App\Models\BundlingPhoto::class => ['photo', 1200],
    ];

    public function handle(ImageCompressionService $service): int
    {
        $dry = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $disk = Storage::disk('media');

        $done = 0;
        $skipped = 0;
        $failed = 0;
        $saved = 0;

        foreach (self::TARGETS as $model => [$column, $maxWidth]) {
            $this->info($model.' :: '.$column);

            $query = $model::query()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->where($column, 'not like', '%.webp');

            // Soft-deleted rows keep their files; convert them too so restored
            // records never point at a deleted original.
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model), true)) {
                $query->withTrashed();
            }

            $query
                ->chunkById(100, function ($rows) use ($column, $maxWidth, $disk, $service, $dry, $limit, &$done, &$skipped, &$failed, &$saved) {
                    foreach ($rows as $row) {
                        if ($limit > 0 && $done >= $limit) {
                            return false;
                        }

                        $old = $row->{$column};

                        if (! in_array(strtolower(pathinfo($old, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true)) {
                            $skipped++;
                            continue;
                        }

                        if (! $disk->exists($old)) {
                            $skipped++;
                            continue;
                        }

                        try {
                            $tmp = tempnam(sys_get_temp_dir(), 'webp');
                            file_put_contents($tmp, $disk->get($old));
                            $payload = $service->encodeBinary($tmp, $maxWidth);
                            unlink($tmp);

                            $before = $disk->size($old);
                            $new = dirname($old).'/'.Str::random(40).'.webp';
                            $saved += max(0, $before - strlen($payload));
                            $done++;

                            if ($dry) {
                                $this->line(sprintf('  would convert %s (%d B -> %d B)', $old, $before, strlen($payload)));
                                continue;
                            }

                            $disk->put($new, $payload);
                            $row->forceFill([$column => $new])->save();
                            $disk->delete($old);
                        } catch (\Throwable $e) {
                            $failed++;
                            $this->warn('  failed '.$old.': '.$e->getMessage());
                        }
                    }
                });
        }

        $this->info(sprintf(
            '%s: converted=%d skipped=%d failed=%d saved=%.1f MB',
            $dry ? 'dry-run' : 'done',
            $done,
            $skipped,
            $failed,
            $saved / 1048576,
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}