<?php

namespace App\Console\Commands;

use App\Services\FilamentMemoryOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FilamentMemoryOptimize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filament:memory-optimize 
                            {--analyze : Analyze current memory usage}
                            {--optimize : Apply memory optimizations}
                            {--report : Generate detailed report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize Filament memory usage and analyze current settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Filament Memory Optimization Tool');
        $this->line('');

        if ($this->option('analyze')) {
            $this->analyzeMemoryUsage();
        }

        if ($this->option('optimize')) {
            $this->applyOptimizations();
        }

        if ($this->option('report')) {
            $this->generateReport();
        }

        if (!$this->option('analyze') && !$this->option('optimize') && !$this->option('report')) {
            $this->showMainMenu();
        }

        return Command::SUCCESS;
    }

    protected function showMainMenu()
    {
        $choice = $this->choice(
            'Pilih aksi yang ingin dilakukan:',
            [
                'analyze' => 'Analisis penggunaan memory',
                'optimize' => 'Terapkan optimisasi',
                'report' => 'Generate laporan lengkap',
                'all' => 'Jalankan semua'
            ],
            'analyze'
        );

        switch ($choice) {
            case 'analyze':
                $this->analyzeMemoryUsage();
                break;
            case 'optimize':
                $this->applyOptimizations();
                break;
            case 'report':
                $this->generateReport();
                break;
            case 'all':
                $this->analyzeMemoryUsage();
                $this->applyOptimizations();
                $this->generateReport();
                break;
        }
    }

    protected function analyzeMemoryUsage()
    {
        $this->info('📊 Analisis Penggunaan Memory');
        $this->line('');

        $memoryInfo = FilamentMemoryOptimizationService::getMemoryUsage();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Usage', $memoryInfo['current_usage_formatted']],
                ['Peak Usage', $memoryInfo['peak_usage_formatted']],
                ['Memory Limit', $memoryInfo['limit']],
                ['Usage Percentage', $memoryInfo['usage_percentage'] . '%'],
                ['Optimal Page Size', $memoryInfo['optimal_page_size']],
                ['Optimal Chunk Size', $memoryInfo['optimal_chunk_size']],
            ]
        );

        // Status berdasarkan usage percentage
        if ($memoryInfo['usage_percentage'] > 80) {
            $this->error('⚠️  Memory usage tinggi! Optimisasi diperlukan.');
        } elseif ($memoryInfo['usage_percentage'] > 60) {
            $this->warn('⚡ Memory usage sedang. Monitor terus.');
        } else {
            $this->info('✅ Memory usage normal.');
        }

        $this->line('');
        $this->analyzeTableSizes();
    }

    protected function analyzeTableSizes()
    {
        $this->info('📋 Analisis Ukuran Tabel Database');
        
        try {
            $tables = DB::select('SHOW TABLE STATUS');
            
            $largeTableData = [];
            foreach ($tables as $table) {
                $size = $table->Data_length + $table->Index_length;
                $sizeMB = round($size / 1024 / 1024, 2);
                
                if ($sizeMB > 10) { // Only show tables > 10MB
                    $largeTableData[] = [
                        'table' => $table->Name,
                        'rows' => number_format($table->Rows),
                        'size' => $sizeMB . ' MB',
                        'recommended_page_size' => $this->getRecommendedPageSize($table->Rows)
                    ];
                }
            }

            if (!empty($largeTableData)) {
                $this->table(
                    ['Table', 'Rows', 'Size', 'Recommended Page Size'],
                    $largeTableData
                );
            } else {
                $this->info('Tidak ada tabel besar yang memerlukan perhatian khusus.');
            }
        } catch (\Exception $e) {
            $this->warn('Tidak dapat menganalisis ukuran tabel: ' . $e->getMessage());
        }
    }

    protected function getRecommendedPageSize(int $rows): int
    {
        if ($rows > 100000) return 10;
        if ($rows > 50000) return 15;
        if ($rows > 10000) return 25;
        return 50;
    }

    protected function applyOptimizations()
    {
        $this->info('⚡ Menerapkan Optimisasi Memory');
        $this->line('');

        $progressBar = $this->output->createProgressBar(5);
        $progressBar->start();

        // 1. Clear memory
        $progressBar->setMessage('Membersihkan memory cache...');
        FilamentMemoryOptimizationService::clearMemory();
        $progressBar->advance();

        // 2. Update .env with optimal settings
        $progressBar->setMessage('Mengupdate konfigurasi...');
        $this->updateEnvFile();
        $progressBar->advance();

        // 3. Clear Laravel caches
        $progressBar->setMessage('Clearing Laravel caches...');
        $this->call('cache:clear');
        $this->call('view:clear');
        $this->call('config:clear');
        $progressBar->advance();

        // 4. Optimize autoloader
        $progressBar->setMessage('Optimizing autoloader...');
        $this->call('optimize');
        $progressBar->advance();

        // 5. Generate config cache
        $progressBar->setMessage('Generating config cache...');
        $this->call('config:cache');
        $progressBar->advance();

        $progressBar->finish();
        $this->line('');
        $this->info('✅ Optimisasi selesai!');
    }

    protected function updateEnvFile()
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            $this->warn('File .env tidak ditemukan, skip update konfigurasi.');
            return;
        }

        $recommendations = FilamentMemoryOptimizationService::getRecommendedSettings();
        
        $envContent = File::get($envPath);
        $updates = [];

        // Recommended settings
        $settings = [
            'FILAMENT_ENABLE_QUERY_CACHING' => 'false',
            'FILAMENT_ENABLE_RESULT_CACHING' => 'false', 
            'FILAMENT_DEBUG_MEMORY' => 'false',
            'FILAMENT_LOG_MEMORY_USAGE' => 'false',
            'FILAMENT_SHOW_MEMORY_UI' => config('app.debug') ? 'true' : 'false',
        ];

        foreach ($settings as $key => $value) {
            if (strpos($envContent, $key) !== false) {
                $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
            $updates[] = "{$key}={$value}";
        }

        File::put($envPath, $envContent);

        if (!empty($updates)) {
            $this->info('Konfigurasi .env diupdate:');
            foreach ($updates as $update) {
                $this->line("  - {$update}");
            }
        }
    }

    protected function generateReport()
    {
        $this->info('📄 Generating Memory Optimization Report');
        $this->line('');

        $memoryInfo = FilamentMemoryOptimizationService::getMemoryUsage();
        $recommendations = FilamentMemoryOptimizationService::getRecommendedSettings();

        $reportPath = storage_path('logs/filament-memory-report-' . date('Y-m-d-H-i-s') . '.txt');
        
        $report = $this->buildReportContent($memoryInfo, $recommendations);
        
        File::put($reportPath, $report);

        $this->info("📋 Report saved: {$reportPath}");
        $this->line('');

        // Show summary
        $this->info('📊 SUMMARY');
        $this->table(
            ['Category', 'Status', 'Recommendation'],
            [
                [
                    'Memory Usage',
                    $memoryInfo['usage_percentage'] . '%',
                    $memoryInfo['usage_percentage'] > 75 ? 'Reduce page size' : 'OK'
                ],
                [
                    'Optimal Page Size',
                    $memoryInfo['optimal_page_size'],
                    $memoryInfo['optimal_page_size'] < 25 ? 'Increase memory limit' : 'OK'
                ],
                [
                    'Memory Limit',
                    $memoryInfo['limit'],
                    $memoryInfo['limit_bytes'] < 256*1024*1024 ? 'Increase to 256M+' : 'OK'
                ]
            ]
        );

        if (!empty($recommendations['recommendations'])) {
            $this->warn('⚠️  RECOMMENDATIONS:');
            foreach ($recommendations['recommendations'] as $recommendation) {
                $this->line("  - {$recommendation}");
            }
        }
    }

    protected function buildReportContent($memoryInfo, $recommendations): string
    {
        return "
FILAMENT MEMORY OPTIMIZATION REPORT
Generated: " . now()->format('Y-m-d H:i:s') . "

=== MEMORY USAGE ===
Current Usage: {$memoryInfo['current_usage_formatted']}
Peak Usage: {$memoryInfo['peak_usage_formatted']}
Memory Limit: {$memoryInfo['limit']}
Usage Percentage: {$memoryInfo['usage_percentage']}%

=== RECOMMENDATIONS ===
Optimal Page Size: {$memoryInfo['optimal_page_size']}
Optimal Chunk Size: {$memoryInfo['optimal_chunk_size']}

=== SETTINGS ===
Pagination Size: {$recommendations['pagination_size']}
Chunk Size: {$recommendations['chunk_size']}
Lazy Loading: " . ($recommendations['lazy_loading'] ? 'Enabled' : 'Disabled') . "
Query Caching: " . ($recommendations['cache_queries'] ? 'Enabled' : 'Disabled') . "

=== ISSUES FOUND ===
" . (!empty($recommendations['recommendations']) ? implode("\n", $recommendations['recommendations']) : 'No issues found.') . "

=== NEXT STEPS ===
1. Apply the recommended settings
2. Monitor memory usage regularly
3. Adjust pagination sizes based on data growth
4. Consider upgrading server memory if needed

";
    }
}
