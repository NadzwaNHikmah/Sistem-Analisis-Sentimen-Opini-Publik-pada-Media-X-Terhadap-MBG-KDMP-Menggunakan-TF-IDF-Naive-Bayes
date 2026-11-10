<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tweet;
use App\Models\CrawlHistory;
use App\Services\SentimentSummaryService;
use Carbon\Carbon;

class SentimenController extends Controller
{
    protected $sentimentService;

    public function __construct(SentimentSummaryService $sentimentService)
    {
        $this->sentimentService = $sentimentService;
    }

    public function about()
    {
        $tools = [
            ['name' => 'Laravel', 'icon' => 'fab fa-laravel', 'desc' => 'Framework PHP untuk backend & routing'],
            ['name' => 'Python', 'icon' => 'fab fa-python', 'desc' => 'Processing data & NLP modeling'],
            ['name' => 'Scikit-learn', 'icon' => 'fas fa-chart-line', 'desc' => 'Machine learning (Naive Bayes)'],
            ['name' => 'Sastrawi', 'icon' => 'fas fa-language', 'desc' => 'Stemming Bahasa Indonesia'],
            ['name' => 'Chart.js', 'icon' => 'fas fa-chart-bar', 'desc' => 'Visualisasi data interaktif'],
            ['name' => 'Twitter/X API', 'icon' => 'fab fa-x-twitter', 'desc' => 'Crawling tweet dari platform X'],
            ['name' => 'Pandas', 'icon' => 'fas fa-table', 'desc' => 'Manipulasi dataset'],
            ['name' => 'Seaborn', 'icon' => 'fas fa-chart-pie', 'desc' => 'Heatmap & visualisasi statistik']
        ];

        $features = [
            'Crawling tweet dari platform X (Twitter) secara real-time',
            'Penyimpanan terstruktur pada Database MySQL dengan relasi & index',
            'Preprocessing & stemming teks Bahasa Indonesia',
            'Klasifikasi sentimen (Positif, Negatif, Netral)',
            'Kesimpulan sentimen dinamis berdasarkan rentang waktu / mingguan',
            'Filter dinamis (Tanggal, Provinsi, Sentimen, Keyword Search)',
            'Visualisasi interaktif (Line Chart tren harian, Donut Chart, Rekap per provinsi)',
            'Confusion matrix & heatmap error terpisah untuk MBG dan KDMP',
            'Export laporan ter-filter ke PDF & CSV'
        ];

        return view('about', compact('tools', 'features'));
    }

    private function loadDashboardData(string $program, Request $request = null): array
    {
        $programUpper = strtoupper($program);
        $programLower = strtolower($program);

        $params = [
            'program' => $programUpper,
            'start_date' => $request ? $request->get('start_date') : null,
            'end_date' => $request ? $request->get('end_date') : null,
            'provinsi' => $request ? $request->get('provinsi') : null,
            'sentimen' => $request ? $request->get('sentimen') : null,
            'search' => $request ? $request->get('search') : null,
        ];

        // Ambil metrik & kesimpulan dinamis dari database
        $metrics = $this->sentimentService->getFilteredMetrics($params);
        $data = $metrics['rekap_provinsi'];
        $summary = $metrics['summary'];
        $dailyTrend = $metrics['daily_trend'];

        // Ambil evaluasi model dari JSON evaluasi
        $evaluasi = [];
        $evalPath = base_path("NLP-Sentimen/evaluasi_{$programLower}.json");
        if (file_exists($evalPath)) {
            $evaluasi = json_decode(file_get_contents($evalPath), true);
            if (isset($evaluasi['classification_report'])) {
                $report = $evaluasi['classification_report'];
                $evaluasi['accuracy'] = $report['accuracy'] ?? null;
                $evaluasi['macro_f1'] = $report['macro avg']['f1-score'] ?? null;
                $evaluasi['weighted_avg'] = $report['weighted avg'] ?? null;
            }
        }

        $heatmapExists = file_exists(public_path("confusion_matrix_{$programLower}.png"));
        $wordcloudExists = file_exists(public_path("wordcloud_{$programLower}.png"));

        // Tweet raw dari Database MySQL
        $tweetsQuery = Tweet::program($programUpper)
            ->dateRange($params['start_date'], $params['end_date'])
            ->provinsi($params['provinsi'])
            ->sentimen($params['sentimen']);

        if (!empty($params['search'])) {
            $tweetsQuery->where('tweet', 'like', "%{$params['search']}%");
        }

        $tweetsRaw = $tweetsQuery->orderBy('tweet_date', 'desc')->get()->map(function($t) {
            return [
                'id' => $t->id,
                'program' => $t->program,
                'username' => $t->username,
                'tweet' => $t->tweet,
                'clean_tweet' => $t->clean_tweet,
                'sentimen' => $t->sentimen,
                'provinsi' => $t->provinsi,
                'tweet_date' => $t->tweet_date ? $t->tweet_date->format('Y-m-d H:i:s') : null,
                'crawled_at' => $t->crawled_at ? $t->crawled_at->format('Y-m-d H:i:s') : null,
            ];
        })->toArray();

        // Timestamp terakhir crawl (WIB)
        $lastCrawledRecord = Tweet::program($programUpper)->max('crawled_at');
        $lastCrawled = null;
        if ($lastCrawledRecord) {
            $lastCrawled = Carbon::parse($lastCrawledRecord)->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB';
        } else {
            $lastCrawled = '-';
        }

        return compact('data', 'evaluasi', 'heatmapExists', 'wordcloudExists', 'tweetsRaw', 'lastCrawled', 'summary', 'dailyTrend', 'params');
    }

    public function mbg(Request $request)
    {
        $dashboardData = $this->loadDashboardData('MBG', $request);
        $programInfo = [
            'name' => 'MBG (Makanan Bergizi Gratis)',
            'description' => 'Program pemerintah untuk menyediakan makanan bergizi gratis bagi masyarakat, khususnya anak sekolah dan keluarga prasejahtera.',
            'icon' => 'fas fa-utensils',
            'color' => '#3cac7a',
            'bgGradient' => 'linear-gradient(135deg, #e8f5e9, #c8e6d9)',
            'heatmapFile' => 'confusion_matrix_mbg.png',
            'heatmapExists' => $dashboardData['heatmapExists']
        ];
        return view('dashboard-mbg', array_merge($dashboardData, compact('programInfo')));
    }

    public function kdmp(Request $request)
    {
        $dashboardData = $this->loadDashboardData('KDMP', $request);
        $programInfo = [
            'name' => 'KDMP (Koperasi Desa Merah Putih)',
            'description' => 'Program penguatan ekonomi desa melalui koperasi yang bertujuan meningkatkan kesejahteraan masyarakat desa.',
            'icon' => 'fas fa-handshake',
            'color' => '#e27b6b',
            'bgGradient' => 'linear-gradient(135deg, #fbe9e7, #ffccbc)',
            'heatmapFile' => 'confusion_matrix_kdmp.png',
            'heatmapExists' => $dashboardData['heatmapExists']
        ];
        return view('dashboard-kdmp', array_merge($dashboardData, compact('programInfo')));
    }

    /**
     * Endpoint AJAX untuk filter data dinamis (Date Range, Provinsi, Sentimen, Search)
     */
    public function filterData(Request $request)
    {
        $program = strtoupper($request->get('program', 'MBG'));
        $metrics = $this->sentimentService->getFilteredMetrics($request->all());

        // Raw tweets paginated / list
        $tweetsQuery = Tweet::program($program)
            ->dateRange($request->get('start_date'), $request->get('end_date'))
            ->provinsi($request->get('provinsi'))
            ->sentimen($request->get('sentimen'));

        if ($request->get('search')) {
            $s = $request->get('search');
            $tweetsQuery->where(function($q) use ($s) {
                $q->where('tweet', 'like', "%{$s}%")
                  ->orWhere('username', 'like', "%{$s}%");
            });
        }

        $tweets = $tweetsQuery->orderBy('tweet_date', 'desc')->get()->map(function($t) {
            return [
                'id' => $t->id,
                'program' => $t->program,
                'username' => $t->username,
                'tweet' => $t->tweet,
                'clean_tweet' => $t->clean_tweet,
                'sentimen' => $t->sentimen,
                'provinsi' => $t->provinsi,
                'tweet_date' => $t->tweet_date ? $t->tweet_date->format('Y-m-d H:i:s') : null,
                'crawled_at' => $t->crawled_at ? $t->crawled_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'summary' => $metrics['summary'],
            'rekap_provinsi' => $metrics['rekap_provinsi'],
            'daily_trend' => $metrics['daily_trend'],
            'tweets' => $tweets
        ]);
    }

    /**
     * Export data ke CSV ter-filter
     */
    public function exportCsv(Request $request)
    {
        $program = strtoupper($request->get('program', 'MBG'));
        $tweetsQuery = Tweet::program($program)
            ->dateRange($request->get('start_date'), $request->get('end_date'))
            ->provinsi($request->get('provinsi'))
            ->sentimen($request->get('sentimen'));

        if ($request->get('search')) {
            $s = $request->get('search');
            $tweetsQuery->where('tweet', 'like', "%{$s}%");
        }

        $tweets = $tweetsQuery->orderBy('tweet_date', 'desc')->get();

        $fileName = "laporan_sentimen_{$program}_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($tweets) {
            $file = fopen('php://output', 'w');
            // Add BOM for UTF-8 Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['No', 'Program', 'Tanggal Tweet', 'Username', 'Provinsi', 'Teks Tweet', 'Hasil Sentimen']);

            foreach ($tweets as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->program,
                    $row->tweet_date ? $row->tweet_date->format('Y-m-d H:i:s') : '-',
                    $row->username,
                    $row->provinsi,
                    $row->tweet,
                    strtoupper($row->sentimen)
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function proses(\Illuminate\Http\Request $request)
    {
        $days = 7;

        // Periksa progress.json untuk mencegah eksekusi ganda secara bersamaan
        $progressPath = base_path('NLP-Sentimen/progress.json');
        if (file_exists($progressPath)) {
            $progressData = json_decode(file_get_contents($progressPath), true);
            $percent = $progressData['progress'] ?? 0;
            if ($percent > 0 && $percent < 100) {
                if (time() - filemtime($progressPath) > 90) {
                    file_put_contents($progressPath, json_encode(["progress" => 0, "message" => "Siap"]));
                } else {
                    return response()->json([
                        "status" => "running",
                        "message" => "Proses crawling sedang berjalan di background. Harap tunggu hingga selesai."
                    ], 400);
                }
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $python = 'python';
        } else {
            $python = 'python3';
        }

        $script = base_path('NLP-Sentimen/main.py');
        $logPath = storage_path('logs/nlp_output.log');

        $batchCode = 'CRW-' . now()->format('Ymd-His');
        $crawlHistory = CrawlHistory::create([
            'batch_code' => $batchCode,
            'status' => 'running',
            'started_at' => now(),
            'notes' => 'Proses crawling data Twitter (' . $days . ' hari) & analisis sentimen NLP',
        ]);

        file_put_contents($logPath, '');
        file_put_contents($progressPath, json_encode([
            "progress" => 1,
            "message" => "Memulai proses baru...",
            "crawl_history_id" => $crawlHistory->id,
            "batch_code" => $batchCode
        ]));

        Log::info("Menjalankan Python script: $python $script $days (Batch: {$batchCode})");

        if (PHP_OS_FAMILY === 'Windows') {
            $command = "start /B $python -u \"$script\" $days > \"$logPath\" 2>&1";
            pclose(popen($command, "r"));
        } else {
            $command = "$python -u \"$script\" $days > \"$logPath\" 2>&1 &";
            exec($command);
        }

        return response()->json([
            "status" => "started",
            "message" => "Proses crawling tweet & analisis sentimen (Batch: {$batchCode}) dimulai di background",
            "crawl_history_id" => $crawlHistory->id,
            "batch_code" => $batchCode
        ]);
    }

    public function status()
    {
        $mbgCount = Tweet::program('MBG')->count();
        $kdmpCount = Tweet::program('KDMP')->count();

        $mbgExists = file_exists(base_path('NLP-Sentimen/hasil_provinsi_mbg.json')) || $mbgCount > 0;
        $kdmpExists = file_exists(base_path('NLP-Sentimen/hasil_provinsi_kdmp.json')) || $kdmpCount > 0;

        return response()->json([
            "ready" => ($mbgExists && $kdmpExists)
        ]);
    }

    public function progress()
    {
        $path = base_path('NLP-Sentimen/progress.json');

        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);

            if (isset($data['progress']) && $data['progress'] > 0 && $data['progress'] < 100) {
                if (time() - filemtime($path) > 90) {
                    $data = [
                        "progress" => 0,
                        "message" => "Proses sebelumnya terhenti. Siap untuk proses baru."
                    ];
                    file_put_contents($path, json_encode($data));
                }
            }

            // Sync database jika baru selesai 100%
            if (isset($data['progress']) && $data['progress'] == 100) {
                try {
                    $crawlHistoryId = $data['crawl_history_id'] ?? null;
                    if ($crawlHistoryId) {
                        Artisan::call('tweets:sync', ['--crawl-id' => $crawlHistoryId]);
                        $crawl = CrawlHistory::find($crawlHistoryId);
                    } else {
                        Artisan::call('tweets:sync');
                        $crawl = CrawlHistory::where('status', 'running')->latest()->first();
                    }

                    if ($crawl && $crawl->status === 'running') {
                        $logContent = file_exists(storage_path('logs/nlp_output.log')) ? file_get_contents(storage_path('logs/nlp_output.log')) : null;
                        
                        $totalTweets = Tweet::where('crawl_history_id', $crawl->id)->count();
                        $mbgCount = Tweet::where('crawl_history_id', $crawl->id)->program('MBG')->count();
                        $kdmpCount = Tweet::where('crawl_history_id', $crawl->id)->program('KDMP')->count();
                        $positifCount = Tweet::where('crawl_history_id', $crawl->id)->where('sentimen', 'positif')->count();
                        $negatifCount = Tweet::where('crawl_history_id', $crawl->id)->where('sentimen', 'negatif')->count();
                        $netralCount = Tweet::where('crawl_history_id', $crawl->id)->where('sentimen', 'netral')->count();

                        $crawl->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                            'duration_seconds' => now()->diffInSeconds($crawl->started_at ?? now()),
                            'total_tweets' => $totalTweets,
                            'mbg_count' => $mbgCount,
                            'kdmp_count' => $kdmpCount,
                            'positif_count' => $positifCount,
                            'negatif_count' => $negatifCount,
                            'netral_count' => $netralCount,
                            'log_output' => $logContent,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Error auto-sync on 100% progress: " . $e->getMessage());
                }
            }

            return response()->json($data);
        }

        return response()->json([
            "progress" => 0,
            "message" => "Belum mulai"
        ]);
    }

    public function logs()
    {
        $logFile = storage_path('logs/nlp_output.log');

        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
        } else {
            $content = "File log belum tersedia\n";
        }

        return response()->json([
            "logs" => $content
        ]);
    }

    /**
     * Halaman Riwayat Crawling (History)
     */
    public function history(Request $request)
    {
        $query = CrawlHistory::withCount([
            'tweets',
            'tweets as mbg_pos' => function ($query) { $query->where('program', 'MBG')->where('sentimen', 'positif'); },
            'tweets as mbg_neg' => function ($query) { $query->where('program', 'MBG')->where('sentimen', 'negatif'); },
            'tweets as mbg_neu' => function ($query) { $query->where('program', 'MBG')->where('sentimen', 'netral'); },
            'tweets as kdmp_pos' => function ($query) { $query->where('program', 'KDMP')->where('sentimen', 'positif'); },
            'tweets as kdmp_neg' => function ($query) { $query->where('program', 'KDMP')->where('sentimen', 'negatif'); },
            'tweets as kdmp_neu' => function ($query) { $query->where('program', 'KDMP')->where('sentimen', 'netral'); },
        ]);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('batch_code', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['running', 'completed', 'failed'])) {
            $query->where('status', $request->status);
        }

        $histories = $query->orderBy('created_at', 'desc')->paginate(10);

        $orphanCount = Tweet::whereNull('crawl_history_id')->count();

        $stats = [
            'total_sessions' => CrawlHistory::count(),
            'total_tweets_crawled' => Tweet::count(),
            'successful_sessions' => CrawlHistory::where('status', 'completed')->count(),
            'last_session' => CrawlHistory::latest()->first(),
        ];

        return view('history', compact('histories', 'stats', 'orphanCount'));
    }

    /**
     * Endpoint API JSON Detail Tweets per Sesi Crawling
     */
    public function historyTweets($id, Request $request)
    {
        $history = CrawlHistory::findOrFail($id);

        $query = Tweet::where('crawl_history_id', $id);

        if ($request->filled('program') && in_array(strtoupper($request->program), ['MBG', 'KDMP'])) {
            $query->where('program', strtoupper($request->program));
        }

        if ($request->filled('sentimen') && in_array(strtolower($request->sentimen), ['positif', 'negatif', 'netral'])) {
            $query->where('sentimen', strtolower($request->sentimen));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('tweet', 'like', "%{$s}%")
                  ->orWhere('username', 'like', "%{$s}%")
                  ->orWhere('provinsi', 'like', "%{$s}%");
            });
        }

        $tweets = $query->orderBy('tweet_date', 'desc')->get()->map(function($t) {
            return [
                'id' => $t->id,
                'program' => $t->program,
                'username' => $t->username,
                'tweet' => $t->tweet,
                'clean_tweet' => $t->clean_tweet,
                'sentimen' => $t->sentimen,
                'provinsi' => $t->provinsi,
                'tweet_date' => $t->tweet_date ? $t->tweet_date->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'history' => [
                'id' => $history->id,
                'batch_code' => $history->batch_code,
                'status' => $history->status,
                'started_at' => $history->formatted_started_at,
                'completed_at' => $history->formatted_completed_at,
                'duration' => $history->formatted_duration,
                'total_tweets' => $history->total_tweets,
                'mbg_count' => $history->mbg_count,
                'kdmp_count' => $history->kdmp_count,
                'positif_count' => $history->positif_count,
                'negatif_count' => $history->negatif_count,
                'netral_count' => $history->netral_count,
                'log_output' => $history->log_output ?? "Tidak ada log tersimpan.",
            ],
            'tweets' => $tweets,
        ]);
    }

    private function clearResultJsonFiles()
    {
        $jsonFiles = [
            base_path('NLP-Sentimen/tweets_raw_mbg.json') => json_encode([]),
            base_path('NLP-Sentimen/tweets_raw_kdmp.json') => json_encode([]),
            base_path('NLP-Sentimen/hasil_provinsi_mbg.json') => json_encode(new \stdClass()),
            base_path('NLP-Sentimen/hasil_provinsi_kdmp.json') => json_encode(new \stdClass()),
            base_path('NLP-Sentimen/evaluasi_mbg.json') => json_encode(new \stdClass()),
            base_path('NLP-Sentimen/evaluasi_kdmp.json') => json_encode(new \stdClass()),
        ];

        foreach ($jsonFiles as $filePath => $content) {
            if (file_exists($filePath)) {
                file_put_contents($filePath, $content);
            }
        }

        $imageFiles = [
            public_path('confusion_matrix_mbg.png'),
            public_path('confusion_matrix_kdmp.png'),
            public_path('wordcloud_mbg.png'),
            public_path('wordcloud_kdmp.png'),
        ];

        foreach ($imageFiles as $img) {
            if (file_exists($img)) {
                @unlink($img);
            }
        }

        $progressPath = base_path('NLP-Sentimen/progress.json');
        if (file_exists($progressPath)) {
            file_put_contents($progressPath, json_encode(["progress" => 0, "message" => "Siap"]));
        }
    }

    /**
     * Endpoint API DELETE Hapus Sesi History
     */
    public function destroyHistory($id)
    {
        $history = CrawlHistory::findOrFail($id);
        $batchCode = $history->batch_code;

        // Hapus tweet terkait
        Tweet::where('crawl_history_id', $id)->delete();
        $history->delete();

        // Jika riwayat crawling sudah kosong sama sekali di database, bersihkan sisa tweet yatim & reset JSON
        if (CrawlHistory::count() === 0) {
            Tweet::query()->delete();
            $this->clearResultJsonFiles();
        }

        return response()->json([
            'status' => 'success',
            'message' => "Riwayat crawling {$batchCode} beserta data tweet terkait berhasil dihapus."
        ]);
    }

    /**
     * Endpoint API DELETE Hapus SELURUH Sesi History & Tweet (Reset Total Data)
     */
    public function clearAllHistory()
    {
        $deletedTweetsCount = Tweet::count();
        $deletedHistoriesCount = CrawlHistory::count();

        // Kosongkan seluruh tweet & history
        Tweet::query()->delete();
        CrawlHistory::query()->delete();

        // Bersihkan berkas JSON sisa hasil crawling terdahulu
        $this->clearResultJsonFiles();

        return response()->json([
            'status' => 'success',
            'message' => "Seluruh data ({$deletedHistoriesCount} riwayat crawling & {$deletedTweetsCount} tweet) berhasil dibersihkan dari database."
        ]);
    }

    /**
     * Endpoint API GET Ambil Cookie Twitter/X saat ini
     */
    public function getTwitterCookies()
    {
        $filePath = base_path('NLP-Sentimen/twitter_cookies.json');
        $accounts = [];
        $isSet = false;

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if (!empty(trim($content))) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    if (isset($decoded['auth_token'])) {
                        $accounts[] = ['auth_token' => $decoded['auth_token'], 'ct0' => $decoded['ct0'] ?? '', '__cf_bm' => $decoded['__cf_bm'] ?? ''];
                    } else {
                        foreach ($decoded as $acc) {
                            if (isset($acc['auth_token'])) {
                                $accounts[] = ['auth_token' => $acc['auth_token'], 'ct0' => $acc['ct0'] ?? '', '__cf_bm' => $acc['__cf_bm'] ?? ''];
                            }
                        }
                    }
                    $isSet = count($accounts) > 0;
                }
            }
        }

        if (empty($accounts)) {
            $accounts[] = ['auth_token' => '', 'ct0' => '', '__cf_bm' => ''];
        }

        return response()->json([
            'status' => 'success',
            'accounts' => $accounts,
            'is_set' => $isSet
        ]);
    }

    /**
     * Endpoint API POST Simpan/Update Cookie Twitter/X baru
     */
    public function updateTwitterCookies(Request $request)
    {
        $request->validate([
            'accounts' => 'required|array',
            'accounts.*.auth_token' => 'required|string',
            'accounts.*.ct0' => 'required|string',
        ]);

        $filePath = base_path('NLP-Sentimen/twitter_cookies.json');
        
        $accounts = [];
        foreach ($request->accounts as $acc) {
            if (!empty(trim($acc['auth_token'])) && !empty(trim($acc['ct0']))) {
                $cookie = [
                    'auth_token' => trim($acc['auth_token']),
                    'ct0' => trim($acc['ct0']),
                ];
                if (!empty(trim($acc['__cf_bm'] ?? ''))) {
                    $cookie['__cf_bm'] = trim($acc['__cf_bm']);
                }
                $accounts[] = $cookie;
            }
        }

        if (count($accounts) === 1) {
            file_put_contents($filePath, json_encode($accounts[0], JSON_PRETTY_PRINT));
        } else {
            file_put_contents($filePath, json_encode($accounts, JSON_PRETTY_PRINT));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Konfigurasi Cookie Twitter/X berhasil disimpan! Multi-Account siap digunakan.'
        ]);
    }
}