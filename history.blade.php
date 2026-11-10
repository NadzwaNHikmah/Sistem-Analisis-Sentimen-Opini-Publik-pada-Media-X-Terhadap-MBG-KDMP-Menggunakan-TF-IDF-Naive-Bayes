@extends('layouts.dashboard')

@section('title', 'Riwayat Crawling Tweet')

@push('styles')
<style>
    .history-header {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 16px;
        padding: 28px 32px;
        color: #fff;
        margin-bottom: 28px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }
    .history-header h1 {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .history-header p {
        margin: 0;
        color: #94a3b8;
        font-size: 14px;
    }
    .btn-crawl-new {
        background: linear-gradient(135deg, #3cac7a 0%, #2e8b62 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(60, 172, 122, 0.3);
    }
    .btn-crawl-new:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(60, 172, 122, 0.4);
    }
    .btn-clear-all {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 12px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    .btn-clear-all:hover {
        background: #fee2e2;
        transform: translateY(-2px);
    }

    /* Stat Cards */
    .history-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }
    .stat-card-mini {
        background: #ffffff;
        border-radius: 14px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s ease;
    }
    .stat-card-mini:hover {
        transform: translateY(-3px);
    }
    .stat-icon-wrapper {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .stat-icon-blue { background: #e0f2fe; color: #0284c7; }
    .stat-icon-green { background: #dcfce7; color: #16a34a; }
    .stat-icon-purple { background: #f3e8ff; color: #9333ea; }
    .stat-icon-orange { background: #ffedd5; color: #ea580c; }
    .stat-info-title { font-size: 13px; color: #64748b; font-weight: 500; margin-bottom: 4px; }
    .stat-info-value { font-size: 22px; color: #0f172a; font-weight: 700; }

    /* History Table Card */
    .history-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 24px;
        margin-bottom: 30px;
    }
    .filter-bar-history {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .filter-input-wrapper {
        position: relative;
        flex: 1;
        min-width: 240px;
    }
    .filter-input-wrapper i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }
    .filter-input-wrapper input {
        width: 100%;
        padding: 10px 14px 10px 40px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
    }
    .filter-input-wrapper input:focus {
        border-color: #3cac7a;
    }
    .filter-select-history {
        padding: 10px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
        background: #fff;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .custom-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 13px;
        padding: 14px 16px;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
        white-space: nowrap;
    }
    .custom-table td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: #334155;
        vertical-align: middle;
    }
    .custom-table tr:hover td {
        background: #f8fafc;
    }

    /* Badges */
    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-completed { background: #dcfce7; color: #15803d; }
    .badge-running { background: #e0f2fe; color: #0369a1; }
    .badge-failed { background: #fee2e2; color: #b91c1c; }

    .badge-program-mini {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        margin-right: 4px;
    }
    .bg-mbg { background: #e8f5e9; color: #2e7d32; }
    .bg-kdmp { background: #fbe9e7; color: #d84315; }

    .sentiment-pills {
        display: flex;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    .pill-pos { color: #16a34a; background: #f0fdf4; padding: 2px 8px; border-radius: 6px; }
    .pill-neg { color: #dc2626; background: #fef2f2; padding: 2px 8px; border-radius: 6px; }
    .pill-neu { color: #475569; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; }

    .btn-action-group {
        display: flex;
        gap: 8px;
    }
    .btn-action {
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s ease;
    }
    .btn-view { background: #eff6ff; color: #2563eb; }
    .btn-view:hover { background: #dbeafe; }
    .btn-log { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
    .btn-log:hover { background: #f1f5f9; }
    .btn-delete { background: #fef2f2; color: #dc2626; }
    .btn-delete:hover { background: #fee2e2; }

    /* Modals */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
    }
    .modal-container {
        background: #ffffff;
        border-radius: 16px;
        max-width: 900px;
        width: 100%;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
    }
    .modal-header {
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
    }
    .modal-header h3 { margin: 0; font-size: 18px; color: #0f172a; font-weight: 700; }
    .modal-close { background: none; border: none; font-size: 20px; color: #64748b; cursor: pointer; }
    .modal-body { padding: 24px; overflow-y: auto; flex: 1; }
    
    .terminal-box {
        background: #0f172a;
        color: #38bdf8;
        font-family: 'Courier New', Courier, monospace;
        font-size: 13px;
        padding: 16px;
        border-radius: 10px;
        max-height: 450px;
        overflow-y: auto;
        white-space: pre-wrap;
        line-height: 1.5;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="history-header">
        <div>
            <h1><i class="fas fa-history" style="color: #3cac7a;"></i> Riwayat Crawling & Analisis Sentimen</h1>
            <p>Daftar lengkap sesi crawling data Twitter/X real-time per eksekusi beserta log & distribusi sentimennya.</p>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <button class="btn-crawl-new" onclick="triggerCrawlFromHistory()">
                <i class="fas fa-sync-alt"></i> Mulai Crawling Baru
            </button>
            <button class="btn-clear-all" style="background: #dc2626; color: white; border: 1px solid #b91c1c; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);" onclick="exportHistoryPdf()">
                <i class="fas fa-file-pdf"></i> Ekspor PDF History
            </button>
            <button class="btn-clear-all" onclick="clearAllHistory()">
                <i class="fas fa-trash-alt"></i> Reset Total Database
            </button>
        </div>
    </div>

    @if(isset($orphanCount) && $orphanCount > 0)
    <div style="background: #fff7ed; border-left: 4px solid #ea580c; padding: 14px 20px; border-radius: 10px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="color: #9a3412; font-size: 14px; font-weight: 500;">
            <i class="fas fa-exclamation-triangle" style="margin-right: 8px; color: #ea580c;"></i>
            Terdeteksi <strong>{{ $orphanCount }} tweet</strong> di database yang tidak terhubung dengan sesi history manapun.
        </div>
        <button class="btn-action btn-delete" onclick="clearAllHistory()" style="padding: 8px 16px; flex-shrink: 0;">
            <i class="fas fa-broom"></i> Bersihkan Tweet Sisa
        </button>
    </div>
    @endif

    {{-- Stat Cards Mini --}}
    <div class="history-stats-grid">
        <div class="stat-card-mini">
            <div class="stat-icon-wrapper stat-icon-blue">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <div class="stat-info-title">Total Sesi Crawling</div>
                <div class="stat-info-value">{{ number_format($stats['total_sessions']) }}</div>
            </div>
        </div>

        <div class="stat-card-mini">
            <div class="stat-icon-wrapper stat-icon-green">
                <i class="fab fa-x-twitter"></i>
            </div>
            <div>
                <div class="stat-info-title">Total Tweet Ter-crawl</div>
                <div class="stat-info-value">{{ number_format($stats['total_tweets_crawled']) }}</div>
            </div>
        </div>

        <div class="stat-card-mini">
            <div class="stat-icon-wrapper stat-icon-purple">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="stat-info-title">Sesi Berhasil</div>
                <div class="stat-info-value">
                    {{ $stats['total_sessions'] > 0 ? round(($stats['successful_sessions'] / $stats['total_sessions']) * 100) : 0 }}%
                </div>
            </div>
        </div>

        <div class="stat-card-mini">
            <div class="stat-icon-wrapper stat-icon-orange">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="stat-info-title">Sesi Terakhir</div>
                <div class="stat-info-value" style="font-size: 16px;">
                    {{ $stats['last_session'] ? $stats['last_session']->batch_code : '-' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Main History Table Card --}}
    <div class="history-card">
        <form method="GET" action="{{ route('dashboard.history') }}" class="filter-bar-history">
            <div class="filter-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Kode Batch atau Catatan Sesi...">
            </div>
            <select name="status" class="filter-select-history" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
                <option value="running" {{ request('status') == 'running' ? 'selected' : '' }}>Running (Berjalan)</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed (Gagal)</option>
            </select>
            <button type="submit" class="btn-action btn-log" style="padding: 10px 18px;">
                <i class="fas fa-filter"></i> Filter
            </button>
            @if(request('search') || request('status'))
                <a href="{{ route('dashboard.history') }}" class="btn-action btn-delete" style="padding: 10px 18px; text-decoration: none;">
                    <i class="fas fa-times"></i> Reset
                </a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Kode Batch</th>
                        <th>Waktu Mulai</th>
                        <th>Status</th>
                        <th>Durasi</th>
                        <th>Total Tweet</th>
                        <th>Sentimen Breakdown</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($histories as $h)
                    <tr>
                        <td>
                            <strong style="color: #1e293b;">{{ $h->batch_code }}</strong>
                            @if($h->notes)
                                <br><small style="color: #64748b; font-weight: normal;">{{ $h->notes }}</small>
                            @endif
                        </td>
                        <td>{{ $h->formatted_started_at }}</td>
                        <td>
                            @if($h->status === 'completed')
                                <span class="badge-status badge-completed"><i class="fas fa-check"></i> Selesai</span>
                            @elseif($h->status === 'running')
                                <span class="badge-status badge-running"><i class="fas fa-spinner fa-spin"></i> Berjalan</span>
                            @else
                                <span class="badge-status badge-failed"><i class="fas fa-exclamation-triangle"></i> Gagal</span>
                            @endif
                        </td>
                        <td><strong>{{ $h->formatted_duration }}</strong></td>
                        <td>
                            <strong style="font-size: 15px; color: #0f172a;">{{ number_format($h->total_tweets) }}</strong>
                            <div style="margin-top: 3px;">
                                <span class="badge-program-mini bg-mbg">MBG: {{ $h->mbg_count }}</span>
                                <span class="badge-program-mini bg-kdmp">KDMP: {{ $h->kdmp_count }}</span>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 11px; margin-bottom: 4px; color: #1e293b;"><strong>MBG:</strong></div>
                            <div class="sentiment-pills" style="margin-bottom: 6px;">
                                <span class="pill-pos" title="Positif"><i class="fas fa-smile"></i> {{ $h->mbg_pos }}</span>
                                <span class="pill-neg" title="Negatif"><i class="fas fa-frown"></i> {{ $h->mbg_neg }}</span>
                                <span class="pill-neu" title="Netral"><i class="fas fa-meh"></i> {{ $h->mbg_neu }}</span>
                            </div>
                            <div style="font-size: 11px; margin-bottom: 4px; color: #1e293b;"><strong>KDMP:</strong></div>
                            <div class="sentiment-pills">
                                <span class="pill-pos" title="Positif"><i class="fas fa-smile"></i> {{ $h->kdmp_pos }}</span>
                                <span class="pill-neg" title="Negatif"><i class="fas fa-frown"></i> {{ $h->kdmp_neg }}</span>
                                <span class="pill-neu" title="Netral"><i class="fas fa-meh"></i> {{ $h->kdmp_neu }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="btn-action-group">
                                <button class="btn-action btn-view" onclick="openTweetsModal({{ $h->id }}, '{{ $h->batch_code }}')">
                                    <i class="fas fa-database"></i> Data Tweet
                                </button>
                                <button class="btn-action btn-log" onclick="openLogModal({{ $h->id }}, '{{ $h->batch_code }}')">
                                    <i class="fas fa-terminal"></i> Log
                                </button>
                                <button class="btn-action" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;" onclick="exportSessionPdf({{ $h->id }}, '{{ $h->batch_code }}')" title="Ekspor Laporan Sesi PDF">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteHistory({{ $h->id }}, '{{ $h->batch_code }}')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                            <i class="fas fa-inbox fa-3x" style="margin-bottom: 12px; display: block; color: #cbd5e1;"></i>
                            Belum ada riwayat eksekusi crawling. Klik tombol <strong>Mulai Crawling Baru</strong> di atas untuk memicu proses baru.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $histories->links() }}
        </div>
    </div>
</div>

{{-- MODAL DATA TWEET --}}
<div class="modal-overlay" id="modalTweets">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fas fa-database" style="color: #3cac7a;"></i> Detail Tweet — Sesi <span id="modalBatchTitle"></span></h3>
            <button class="modal-close" onclick="closeModal('modalTweets')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;" id="tweetFilters">
                <input type="hidden" id="activeHistoryId">
                <select id="filterProgramModal" class="filter-select-history" onchange="fetchModalTweets()">
                    <option value="">Semua Program (MBG & KDMP)</option>
                    <option value="MBG">MBG (Makanan Bergizi Gratis)</option>
                    <option value="KDMP">KDMP (Koperasi Desa Merah Putih)</option>
                </select>
                <select id="filterSentimenModal" class="filter-select-history" onchange="fetchModalTweets()">
                    <option value="">Semua Sentimen</option>
                    <option value="positif">Positif</option>
                    <option value="negatif">Negatif</option>
                    <option value="netral">Netral</option>
                </select>
                <input type="text" id="filterSearchModal" placeholder="Cari kata di tweet..." class="filter-select-history" style="flex: 1; min-width: 200px;" onkeyup="if(event.key === 'Enter') fetchModalTweets()">
                <button class="btn-action btn-view" onclick="fetchModalTweets()">Cari</button>
            </div>

            <div class="table-responsive">
                <table class="custom-table" id="tableModalTweets">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Username</th>
                            <th>Tweet</th>
                            <th>Provinsi</th>
                            <th>Sentimen</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyModalTweets">
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Memuat data tweet...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- MODAL LOG EXEKUSI --}}
<div class="modal-overlay" id="modalLogs">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fas fa-terminal" style="color: #0284c7;"></i> Log Eksekusi Python — Sesi <span id="modalLogTitle"></span></h3>
            <button class="modal-close" onclick="closeModal('modalLogs')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="terminal-box" id="terminalLogBody">
                Loading log...
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.triggerCrawlFromHistory = function() {
        Swal.fire({
            title: 'Mulai Crawling Real-time Baru?',
            html: `
                <p style="margin-bottom: 15px; color: #4b5563;">Sistem akan menarik data tweet terbaru dari platform X dan menjalankan pipeline NLP sentimen.</p>
                <div style="text-align: left; background: #f3f4f6; padding: 15px; border-radius: 8px;">
                    <label style="font-weight: 600; display: block; margin-bottom: 8px; color: #1f2937;">Rentang Waktu Pencarian:</label>
                    <p style="margin: 0; color: #4b5563; font-size: 14px;">Sistem akan melakukan crawling data tweet untuk <b>7 Hari Terakhir</b>.</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3cac7a',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-play"></i> Mulai Sekarang',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const days = 7;
                Swal.fire({
                    title: 'Memulai Proses...',
                    text: 'Mengirim perintah crawling ke server background.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('/proses', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ days: days })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'started' || data.status === 'success') {
                        Swal.fire({
                            title: 'Berhasil Dimulai!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonColor: '#3cac7a',
                            timer: 2500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Gagal Memulai',
                            text: data.message || 'Gagal memulai proses crawling.',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        title: 'Terjadi Kesalahan',
                        text: 'Gagal terhubung ke server: ' + err.message,
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
            }
        });
    };

    window.openTweetsModal = function(historyId, batchCode) {
        Swal.fire({
            title: `<i class="fas fa-database" style="color: #3cac7a;"></i> Data Tweet — Sesi ${batchCode}`,
            html: `
                <div style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; text-align: left;">
                    <select id="swalFilterProg" class="filter-select-history" style="padding: 8px 12px; font-size: 13px;" onchange="window.fetchSwalTweets('${historyId}')">
                        <option value="">Semua Program (MBG & KDMP)</option>
                        <option value="MBG">MBG (Makanan Bergizi Gratis)</option>
                        <option value="KDMP">KDMP (Koperasi Desa Merah Putih)</option>
                    </select>
                    <select id="swalFilterSent" class="filter-select-history" style="padding: 8px 12px; font-size: 13px;" onchange="window.fetchSwalTweets('${historyId}')">
                        <option value="">Semua Sentimen</option>
                        <option value="positif">Positif</option>
                        <option value="negatif">Negatif</option>
                        <option value="netral">Netral</option>
                    </select>
                    <input type="text" id="swalFilterSearch" placeholder="Cari kata di tweet..." class="filter-select-history" style="flex: 1; min-width: 150px; padding: 8px 12px; font-size: 13px;" onkeyup="if(event.key==='Enter') window.fetchSwalTweets('${historyId}')">
                    <button class="btn-action btn-view" style="padding: 8px 14px;" onclick="window.fetchSwalTweets('${historyId}')">Cari</button>
                </div>
                <div style="max-height: 420px; overflow-y: auto; text-align: left; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <table class="custom-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Program</th>
                                <th>Username</th>
                                <th>Teks Tweet</th>
                                <th>Provinsi</th>
                                <th>Sentimen</th>
                            </tr>
                        </thead>
                        <tbody id="swalTbodyTweets">
                            <tr><td colspan="5" style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            `,
            width: '950px',
            showCloseButton: true,
            showConfirmButton: false,
            cancelButtonText: 'Tutup Modal',
            cancelButtonColor: '#64748b',
            showCancelButton: true
        });

        window.fetchSwalTweets(historyId);
    };

    window.fetchSwalTweets = function(historyId) {
        const prog = document.getElementById('swalFilterProg')?.value || '';
        const sent = document.getElementById('swalFilterSent')?.value || '';
        const search = document.getElementById('swalFilterSearch')?.value || '';

        const tbody = document.getElementById('swalTbodyTweets');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 30px;"><i class="fas fa-spinner fa-spin"></i> Memuat data tweet...</td></tr>';

        const url = `/dashboard/history/${historyId}/tweets?program=${prog}&sentimen=${sent}&search=${encodeURIComponent(search)}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.tweets.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 30px; color: #94a3b8;">Tidak ada tweet yang sesuai dengan filter.</td></tr>';
                        return;
                    }

                    let html = '';
                    data.tweets.forEach(t => {
                        let sentBadge = '';
                        if (t.sentimen === 'positif') sentBadge = '<span class="pill-pos">Positif</span>';
                        else if (t.sentimen === 'negatif') sentBadge = '<span class="pill-neg">Negatif</span>';
                        else sentBadge = '<span class="pill-neu">Netral</span>';

                        let progBadge = t.program === 'MBG' ? '<span class="badge-program-mini bg-mbg">MBG</span>' : '<span class="badge-program-mini bg-kdmp">KDMP</span>';

                        html += `
                            <tr>
                                <td>${progBadge}</td>
                                <td><strong>${t.username || '@anonymous'}</strong></td>
                                <td style="max-width: 350px; font-size: 13px; word-break: break-word;">${t.tweet}</td>
                                <td>${t.provinsi || '-'}</td>
                                <td>${sentBadge}</td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                }
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Gagal memuat data: ${err.message}</td></tr>`;
            });
    };

    window.openLogModal = function(historyId, batchCode) {
        Swal.fire({
            title: `<i class="fas fa-terminal" style="color: #38bdf8;"></i> Log Eksekusi Python — ${batchCode}`,
            html: `
                <div style="text-align: left; background: #0f172a; color: #38bdf8; font-family: monospace; font-size: 13px; padding: 16px; border-radius: 10px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; line-height: 1.5;" id="swalTerminalLog">
                    <i class="fas fa-spinner fa-spin"></i> Memuat log eksekusi...
                </div>
            `,
            width: '850px',
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonColor: '#0284c7',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-copy"></i> Salin Log',
            cancelButtonText: 'Tutup'
        }).then((result) => {
            if (result.isConfirmed) {
                const logText = document.getElementById('swalTerminalLog')?.innerText || '';
                navigator.clipboard.writeText(logText);
                Swal.fire({
                    title: 'Tersalin!',
                    text: 'Log eksekusi berhasil disalin ke clipboard.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });

        fetch(`/dashboard/history/${historyId}/tweets`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('swalTerminalLog');
                if (container) {
                    if (data.status === 'success' && data.history.log_output) {
                        container.innerText = data.history.log_output;
                    } else {
                        container.innerText = "Log tidak ditemukan atau kosong untuk sesi ini.";
                    }
                }
            })
            .catch(err => {
                const container = document.getElementById('swalTerminalLog');
                if (container) {
                    container.innerText = "Gagal memuat log: " + err.message;
                }
            });
    };

    window.closeModal = function(modalId) {
        document.getElementById(modalId).style.display = 'none';
    };

    window.deleteHistory = function(historyId, batchCode) {
        Swal.fire({
            title: `Hapus Batch ${batchCode}?`,
            text: 'Seluruh data tweet yang terkait dengan riwayat crawling ini akan dihapus secara permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-alt"></i> Ya, Hapus Permanen',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Menghapus riwayat crawling dari database.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/dashboard/history/${historyId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.fire({
                        title: 'Terhapus!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#3cac7a',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(err => {
                    Swal.fire({
                        title: 'Gagal Menghapus',
                        text: 'Terjadi kesalahan: ' + err.message,
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
            }
        });
    };

    window.clearAllHistory = function() {
        Swal.fire({
            title: 'Kosongkan Seluruh History & Tweet?',
            text: 'Tindakan ini akan menghapus SELURUH riwayat crawling dan SELURUH data tweet yang tersimpan di database.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-trash-alt"></i> Ya, Reset Total',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Membersihkan Database...',
                    text: 'Menghapus seluruh riwayat crawling dan tweet.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('/dashboard/history/clear-all', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    Swal.fire({
                        title: 'Berhasil Dikosongkan!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#3cac7a',
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(err => {
                    Swal.fire({
                        title: 'Gagal',
                        text: 'Terjadi kesalahan: ' + err.message,
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                });
            }
        });
    };

    window.exportHistoryPdf = function() {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const margin = 12;
            const contentWidth = pageWidth - margin * 2;
            let y = margin;

            const printedBy = @json(Auth::check() ? Auth::user()->name : 'Administrator');
            const exportDate = new Date().toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' }) + ' WIB';

            // Header
            doc.setFontSize(15);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(18, 62, 84);
            doc.text('LAPORAN RIWAYAT CRAWLING & ANALISIS SENTIMEN', margin, y);
            y += 5;
            doc.setFontSize(10);
            doc.setTextColor(60, 172, 122);
            doc.text('Nusantara Sentiment Analysis System', margin, y);
            doc.setFontSize(8);
            doc.setTextColor(100, 116, 139);
            doc.text(`Dicetak oleh: ${printedBy}`, pageWidth - margin, y - 5, { align: 'right' });
            doc.text(`Dicetak pada: ${exportDate}`, pageWidth - margin, y - 1, { align: 'right' });
            y += 4;
            doc.setDrawColor(203, 213, 225);
            doc.line(margin, y, pageWidth - margin, y);
            y += 8;

            // Stats
            const totalSessions = @json(number_format($stats['total_sessions']));
            const totalTweets = @json(number_format($stats['total_tweets_crawled']));
            const successfulSessions = @json(number_format($stats['successful_sessions']));
            const lastSessionCode = @json($stats['last_session'] ? $stats['last_session']->batch_code : '-');
            const statItems = [
                { label: 'Total Sesi', value: totalSessions },
                { label: 'Total Tweet', value: totalTweets },
                { label: 'Sesi Berhasil', value: successfulSessions },
                { label: 'Sesi Terakhir', value: lastSessionCode }
            ];
            const boxW = (contentWidth - 9) / 4;
            statItems.forEach((s, i) => {
                const bx = margin + i * (boxW + 3);
                doc.setFillColor(248, 250, 252);
                doc.roundedRect(bx, y, boxW, 14, 1, 1, 'F');
                doc.setFontSize(7);
                doc.setTextColor(100, 116, 139);
                doc.setFont('helvetica', 'bold');
                doc.text(s.label, bx + boxW / 2, y + 5, { align: 'center' });
                doc.setFontSize(10);
                doc.setTextColor(18, 62, 84);
                doc.text(String(s.value), bx + boxW / 2, y + 11, { align: 'center' });
            });
            y += 20;

            // History Table from DOM
            const historyRows = [];
            document.querySelectorAll('.history-card .custom-table tbody tr').forEach(row => {
                const cells = Array.from(row.querySelectorAll('td'));
                if (cells.length >= 6) {
                    let sentimenText = '-';
                    const sentimentBlocks = cells[5].querySelectorAll('.sentiment-pills');
                    if (sentimentBlocks.length === 2) {
                        const mbgPills = sentimentBlocks[0].querySelectorAll('span');
                        const kdmpPills = sentimentBlocks[1].querySelectorAll('span');
                        
                        const mPos = mbgPills[0]?.textContent.trim() || '0';
                        const mNeg = mbgPills[1]?.textContent.trim() || '0';
                        const mNeu = mbgPills[2]?.textContent.trim() || '0';
                        
                        const kPos = kdmpPills[0]?.textContent.trim() || '0';
                        const kNeg = kdmpPills[1]?.textContent.trim() || '0';
                        const kNeu = kdmpPills[2]?.textContent.trim() || '0';
                        
                        sentimenText = `MBG:\nPositif: ${mPos}  |  Negatif: ${mNeg}  |  Netral: ${mNeu}\n\nKDMP:\nPositif: ${kPos}  |  Negatif: ${kNeg}  |  Netral: ${kNeu}`;
                    } else {
                        sentimenText = cells[5].innerText.trim();
                    }

                    historyRows.push([
                        cells[0]?.innerText.trim() || '-',
                        cells[1]?.innerText.trim() || '-',
                        cells[2]?.innerText.trim() || '-',
                        cells[3]?.innerText.trim() || '-',
                        cells[4]?.innerText.trim() || '-',
                        sentimenText
                    ]);
                }
            });

            doc.setFontSize(11);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(18, 62, 84);
            doc.text('Daftar Sesi Eksekusi Crawling', margin, y);
            y += 2;

            if (historyRows.length > 0) {
                doc.autoTable({
                    startY: y,
                    head: [['Kode Batch', 'Waktu Mulai', 'Status', 'Durasi', 'Total Tweet', 'Sentimen']],
                    body: historyRows,
                    margin: { left: margin, right: margin },
                    styles: { fontSize: 7, cellPadding: 3, overflow: 'linebreak', valign: 'middle' },
                    headStyles: { fillColor: [18, 62, 84], textColor: 255, fontStyle: 'bold', halign: 'center' },
                    columnStyles: {
                        0: { cellWidth: 35 },
                        1: { cellWidth: 30 },
                        2: { cellWidth: 20, halign: 'center', fontStyle: 'bold' },
                        3: { cellWidth: 15, halign: 'center' },
                        4: { cellWidth: 20, halign: 'center' },
                        5: { cellWidth: 'auto', halign: 'center' }
                    },
                    alternateRowStyles: { fillColor: [248, 250, 252] },
                    theme: 'grid',
                    didParseCell: function(data) {
                        if (data.section === 'body' && data.column.index === 2) {
                            const val = data.cell.raw.toLowerCase();
                            if (val.includes('selesai')) {
                                data.cell.styles.textColor = [21, 128, 61];
                                data.cell.styles.fillColor = [220, 252, 231];
                            } else if (val.includes('gagal')) {
                                data.cell.styles.textColor = [185, 28, 28];
                                data.cell.styles.fillColor = [254, 226, 226];
                            } else if (val.includes('running')) {
                                data.cell.styles.textColor = [3, 105, 161];
                                data.cell.styles.fillColor = [224, 242, 254];
                            }
                        }
                    }
                });
            }

            // Footer
            const totalPages = doc.internal.getNumberOfPages();
            for (let i = 1; i <= totalPages; i++) {
                doc.setPage(i);
                doc.setFontSize(7);
                doc.setTextColor(148, 163, 184);
                doc.text('Laporan Riwayat Crawling — Nusantara Sentiment Analysis', margin, pageHeight - 6);
                doc.text(`Halaman ${i} dari ${totalPages}`, pageWidth - margin, pageHeight - 6, { align: 'right' });
            }

            const filename = `Laporan_Riwayat_Crawling_${new Date().toISOString().slice(0, 10)}.pdf`;
            doc.save(filename);
            Swal.fire({ title: 'Berhasil Mengunduh!', text: `File ${filename} berhasil dibuat.`, icon: 'success', confirmButtonColor: '#3cac7a', timer: 2000, showConfirmButton: false });
        } catch (err) {
            Swal.fire({ title: 'Gagal', text: 'Gagal membuat file PDF: ' + err.message, icon: 'error', confirmButtonColor: '#ef4444' });
        }
    };

    window.exportSessionPdf = function(historyId, batchCode) {
        const printedBy = @json(Auth::check() ? Auth::user()->name : 'Administrator');
        const exportDate = new Date().toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' }) + ' WIB';

        Swal.fire({
            title: `Mempersiapkan PDF Sesi ${batchCode}...`,
            text: 'Mengambil data tweet & log sesi.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch(`/dashboard/history/${historyId}/tweets`)
            .then(res => res.json())
            .then(data => {
                if (data.status !== 'success') {
                    throw new Error('Gagal mengambil data sesi dari server.');
                }

                const h = data.history;
                const tweets = data.tweets;

                try {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF('p', 'mm', 'a4');
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const pageHeight = doc.internal.pageSize.getHeight();
                    const margin = 12;
                    const contentWidth = pageWidth - margin * 2;
                    let y = margin;

                    // Header
                    doc.setFontSize(14);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(18, 62, 84);
                    doc.text(`LAPORAN SESI CRAWLING — ${h.batch_code}`, margin, y);
                    y += 5;
                    doc.setFontSize(9);
                    doc.setTextColor(2, 132, 199);
                    doc.text(`Status: ${h.status.toUpperCase()} | Durasi: ${h.duration}`, margin, y);
                    doc.setFontSize(8);
                    doc.setTextColor(100, 116, 139);
                    doc.text(`Dicetak oleh: ${printedBy}`, pageWidth - margin, y - 5, { align: 'right' });
                    doc.text(`Dicetak pada: ${exportDate}`, pageWidth - margin, y - 1, { align: 'right' });
                    y += 4;
                    doc.setDrawColor(203, 213, 225);
                    doc.line(margin, y, pageWidth - margin, y);
                    y += 8;

                    // Stats
                    const sessionStats = [
                        { label: 'Total Tweet', value: String(h.total_tweets) },
                        { label: 'MBG', value: String(h.mbg_count) },
                        { label: 'KDMP', value: String(h.kdmp_count) },
                        { label: 'Positif', value: String(h.positif_count) },
                        { label: 'Negatif', value: String(h.negatif_count) },
                        { label: 'Netral', value: String(h.netral_count) }
                    ];
                    const sBoxW = (contentWidth - 15) / 6;
                    sessionStats.forEach((s, i) => {
                        const bx = margin + i * (sBoxW + 3);
                        doc.setFillColor(248, 250, 252);
                        doc.roundedRect(bx, y, sBoxW, 14, 1, 1, 'F');
                        doc.setFontSize(6.5);
                        doc.setTextColor(100, 116, 139);
                        doc.setFont('helvetica', 'bold');
                        doc.text(s.label, bx + sBoxW / 2, y + 5, { align: 'center' });
                        doc.setFontSize(9);
                        doc.setTextColor(18, 62, 84);
                        doc.text(s.value, bx + sBoxW / 2, y + 11, { align: 'center' });
                    });
                    y += 20;

                    // Tweet table
                    doc.setFontSize(11);
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(18, 62, 84);
                    doc.text(`Detail Tweet Sesi (${tweets.length} entri)`, margin, y);
                    y += 2;

                    if (tweets.length > 0) {
                        const tweetRows = tweets.map((t, idx) => [
                            idx + 1,
                            t.program,
                            t.username || '-',
                            (t.tweet || '-').substring(0, 150), // increased limit
                            t.provinsi || '-',
                            (t.sentimen || 'netral').toUpperCase()
                        ]);

                        doc.autoTable({
                            startY: y,
                            head: [['#', 'Program', 'Username', 'Teks Tweet', 'Provinsi', 'Sentimen']],
                            body: tweetRows,
                            margin: { left: margin, right: margin },
                            styles: { fontSize: 6.5, cellPadding: 1.5, overflow: 'linebreak' },
                            headStyles: { fillColor: [18, 62, 84], textColor: 255, fontStyle: 'bold', fontSize: 7 },
                            columnStyles: {
                                0: { cellWidth: 8, halign: 'center' },
                                5: { cellWidth: 16, halign: 'center', fontStyle: 'bold' }
                            },
                            alternateRowStyles: { fillColor: [248, 250, 252] },
                            theme: 'grid',
                            didParseCell: function(data) {
                                if (data.section === 'body' && data.column.index === 5) {
                                    const val = data.cell.raw;
                                    if (val === 'POSITIF') data.cell.styles.textColor = [22, 163, 74];
                                    else if (val === 'NEGATIF') data.cell.styles.textColor = [220, 38, 38];
                                    else data.cell.styles.textColor = [71, 85, 105];
                                }
                            }
                        });
                    }

                    // Footer
                    const totalPages = doc.internal.getNumberOfPages();
                    for (let i = 1; i <= totalPages; i++) {
                        doc.setPage(i);
                        doc.setFontSize(7);
                        doc.setTextColor(148, 163, 184);
                        doc.text(`Laporan Sesi ${h.batch_code} — Nusantara Sentiment Analysis`, margin, pageHeight - 6);
                        doc.text(`Halaman ${i} dari ${totalPages}`, pageWidth - margin, pageHeight - 6, { align: 'right' });
                    }

                    const filename = `Laporan_Sesi_${batchCode}_${new Date().toISOString().slice(0, 10)}.pdf`;
                    doc.save(filename);
                    Swal.fire({ title: 'Berhasil Mengunduh!', text: `File PDF sesi ${filename} berhasil dibuat.`, icon: 'success', confirmButtonColor: '#3cac7a', timer: 2000, showConfirmButton: false });
                } catch (err) {
                    Swal.fire({ title: 'Gagal', text: 'Gagal membuat PDF: ' + err.message, icon: 'error', confirmButtonColor: '#ef4444' });
                }
            })
            .catch(err => {
                Swal.fire({ title: 'Gagal', text: 'Gagal membuat file PDF sesi: ' + err.message, icon: 'error', confirmButtonColor: '#ef4444' });
            });
    };

    // Close modal when clicking outside modal-container
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.style.display = 'none';
        }
    });
</script>
@endpush
