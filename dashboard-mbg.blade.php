@extends('layouts.dashboard')

@section('title', 'Dashboard MBG')

@section('css-vars')
    <style>
        :root {
            --program-color: {{ $programInfo['color'] }};
            --program-color-light: {{ $programInfo['color'] }}18;
            --program-color-light-hover: {{ $programInfo['color'] }}22;
            --program-color-border: {{ $programInfo['color'] }}33;
            --program-bg: {{ $programInfo['bgGradient'] }};
        }

        .filter-card {
            background: white;
            border-radius: 1.2rem;
            padding: 1.5rem;
            margin-bottom: 1.8rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            border: 1px solid #e5eff5;
        }

        .preset-btn {
            background: #f0f6fa;
            border: 1px solid #d4e5f0;
            color: #123e54;
            padding: 0.4rem 0.9rem;
            border-radius: 1.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .preset-btn:hover, .preset-btn.active {
            background: var(--program-color);
            color: white;
            border-color: var(--program-color);
        }

        .narrative-card {
            background: linear-gradient(135deg, #ffffff, #f4faf7);
            border-left: 6px solid var(--program-color);
            border-radius: 1.2rem;
            padding: 1.8rem;
            margin-bottom: 2rem;
            box-shadow: 0 6px 24px rgba(0,0,0,0.04);
            position: relative;
        }

        .status-badge-success { background: #3cac7a; color: white; }
        .status-badge-warning { background: #f59e0b; color: white; }
        .status-badge-danger { background: #ef4444; color: white; }
        .status-badge-info { background: #3b82f6; color: white; }
        .status-badge-neutral { background: #64748b; color: white; }

        .stat-box {
            background: white;
            border-radius: 1rem;
            padding: 1.2rem 1rem;
            text-align: center;
            border: 1px solid #e8f2f7;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .page-num-btn {
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #d4e5f0;
            background: white;
            color: #123e54;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .page-num-btn:hover, .page-num-btn.active {
            background: var(--program-color);
            color: white;
            border-color: var(--program-color);
        }
    </style>
@endsection

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 1rem;">
        <div>
            <div class="program-badge">
                <i class="{{ $programInfo['icon'] }}"></i>
                <strong>{{ $programInfo['name'] }}</strong>
            </div>

            @if(isset($lastCrawled))
                <span style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.9); padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.8rem; color: #608eaa; margin-left: 0.5rem; vertical-align: middle; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                    <i class="fas fa-clock"></i> Terakhir diperbarui: <strong>{{ $lastCrawled }}</strong>
                </span>
            @endif
        </div>

        <div style="display: flex; gap: 0.8rem; flex-wrap: wrap; align-items: center; margin-top: 0.5rem;">
            <button onclick="exportToPDF('MBG')" class="btn-export-pdf" id="exportPdfBtn" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; padding: 0.65rem 1.2rem; border-radius: 2rem; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);">
                <i class="fas fa-file-pdf"></i> Export to PDF
            </button>
            <button onclick="showModal()" class="btn-scrap" id="scrapBtn">
                <i class="fab fa-x-twitter"></i> Crawling Tweet & Analisis <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

    {{-- Filter Panel Dinamis --}}
    <div class="filter-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.2rem;">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <i class="fas fa-sliders" style="font-size: 1.3rem; color: var(--program-color);"></i>
                <h4 style="margin: 0; color: #123e54; font-weight: 800;">Filter Laporan Dinamis</h4>
            </div>
            {{-- Presets Rentang Waktu --}}
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="preset-btn active" onclick="setPresetDate('all', this)">Semua Waktu</button>
                <button type="button" class="preset-btn" onclick="setPresetDate('7days', this)">7 Hari Terakhir</button>
                <button type="button" class="preset-btn" onclick="setPresetDate('30days', this)">30 Hari Terakhir</button>
                <button type="button" class="preset-btn" onclick="setPresetDate('this_month', this)">Bulan Ini</button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
            <div>
                <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #608eaa; margin-bottom: 0.4rem;">
                    <i class="fas fa-calendar-day"></i> Tanggal Mulai:
                </label>
                <input type="date" id="startDateInput" onchange="triggerDynamicFilter()" style="width: 100%; padding: 0.55rem 0.8rem; border: 1px solid #d4e5f0; border-radius: 0.8rem; font-size: 0.85rem; outline: none;">
            </div>
            <div>
                <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #608eaa; margin-bottom: 0.4rem;">
                    <i class="fas fa-calendar-check"></i> Tanggal Akhir:
                </label>
                <input type="date" id="endDateInput" onchange="triggerDynamicFilter()" style="width: 100%; padding: 0.55rem 0.8rem; border: 1px solid #d4e5f0; border-radius: 0.8rem; font-size: 0.85rem; outline: none;">
            </div>
            <div>
                <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #608eaa; margin-bottom: 0.4rem;">
                    <i class="fas fa-map-marked-alt"></i> Provinsi:
                </label>
                <select id="filterProvinsiSelect" onchange="triggerDynamicFilter()" style="width: 100%; padding: 0.55rem 0.8rem; border: 1px solid #d4e5f0; border-radius: 0.8rem; font-size: 0.85rem; outline: none; background: white;">
                    <option value="semua">🗺️ Semua 38 Provinsi</option>
                    @foreach($data as $prov => $counts)
                        <option value="{{ $prov }}">{{ $prov }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.78rem; font-weight: 700; color: #608eaa; margin-bottom: 0.4rem;">
                    <i class="fas fa-comments"></i> Sentimen:
                </label>
                <select id="filterSentimenSelect" onchange="triggerDynamicFilter()" style="width: 100%; padding: 0.55rem 0.8rem; border: 1px solid #d4e5f0; border-radius: 0.8rem; font-size: 0.85rem; outline: none; background: white;">
                    <option value="semua">💬 Semua Sentimen</option>
                    <option value="positif">😃 Positif</option>
                    <option value="negatif">😡 Negatif</option>
                    <option value="netral">😐 Netral</option>
                </select>
            </div>
            <div>
                <button onclick="resetDynamicFilter()" style="width: 100%; background: #f0f6fa; color: #123e54; border: 1px solid #d4e5f0; padding: 0.58rem 1rem; border-radius: 0.8rem; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <i class="fas fa-rotate-left"></i> Reset Filter
                </button>
            </div>
        </div>
    </div>

    {{-- Progress Card --}}
    <div id="progressBox" style="margin-bottom:24px; display:none;">
        <div class="card" style="border-left: 4px solid var(--program-color); padding: 2rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <i class="fas fa-satellite-dish fa-spin" style="font-size: 1.8rem; color: var(--program-color);"></i>
                <h4 id="progressTitle" style="margin: 0; font-size: 1.1rem; color: #123e54;">Memulai Proses...</h4>
            </div>
            <div class="progress-modern" style="height: 24px; background: #e7f0f5; border-radius: 12px; overflow: hidden; margin-bottom: 1rem;">
                <div id="progressBar" class="progress-fill" style="width:0%; height: 100%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.85rem; background: var(--program-color); transition: width 0.4s ease;">0%</div>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <strong style="display: block; margin-bottom: 0.5rem; color: #123e54; font-size: 0.9rem;"><i class="fas fa-terminal"></i> Terminal Live Log:</strong>
                <pre id="terminal-output" style="height: 200px; overflow-y: auto; background: #151c24; color: #38ef7d; padding: 1rem; border-radius: 0.8rem; font-family: 'Courier New', Courier, monospace; font-size: 0.8rem; line-height: 1.4; margin: 0; white-space: pre-wrap; word-break: break-all; box-shadow: inset 0 2px 8px rgba(0,0,0,0.5); border: 1px solid #233142;">Menunggu log...</pre>
            </div>
            <div style="padding: 1rem 1.2rem; border-radius: 1rem; background: rgba(0,0,0,0.03); font-size: 0.85rem; color: #1a4a5e; display: flex; gap: 0.8rem; align-items: flex-start;">
                <i class="fas fa-lightbulb" style="color: var(--program-color); font-size: 1.1rem; margin-top: 2px;"></i>
                <div>
                    <strong style="display: block; margin-bottom: 2px; color: #123e54;">Tahukah Anda?</strong>
                    <span id="funFactText">Sistem ini menggunakan TF-IDF + Naive Bayes untuk klasifikasi sentimen.</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Dynamic Narrative Summary Box (Weekly / Custom Date Range Conclusion) --}}
    <div class="narrative-card" id="narrativeCard">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <span class="status-badge-{{ $summary['narrative']['badge_class'] ?? 'success' }}" id="narrativeBadge" style="font-weight: 800; font-size: 0.8rem; padding: 0.4rem 0.9rem; border-radius: 2rem; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; margin-bottom: 0.5rem;">
                    {{ $summary['narrative']['status_label'] ?? 'POSITIF' }}
                </span>
                <h3 id="narrativeTitle" style="margin: 0; color: #123e54; font-family: 'Playfair Display', serif; font-size: 1.4rem;">
                    {{ $summary['narrative']['title'] ?? 'Kesimpulan Sentimen Publik' }}
                </h3>
            </div>
            <span id="narrativePeriod" style="background: #e8f4f0; color: #1a563b; font-weight: 700; font-size: 0.8rem; padding: 0.4rem 0.9rem; border-radius: 1.5rem;">
                <i class="fas fa-calendar-alt"></i> {{ $summary['narrative']['period_str'] ?? 'Seluruh Periode' }}
            </span>
        </div>

        <p id="narrativeBody" style="color: #334e68; font-size: 0.95rem; line-height: 1.7; margin-bottom: 1.5rem;">
            {!! $summary['narrative']['body'] ?? 'Belum ada data sentimen.' !!}
        </p>

        {{-- Stat Pills --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.8rem;">
            <div class="stat-box">
                <span style="font-size: 0.75rem; color: #608eaa; font-weight: 600; display: block;">Total Tweet</span>
                <strong id="statTotal" style="font-size: 1.4rem; color: #123e54;">{{ number_format($summary['total'] ?? 0) }}</strong>
            </div>
            <div class="stat-box" style="border-bottom: 3px solid #3cac7a;">
                <span style="font-size: 0.75rem; color: #3cac7a; font-weight: 700; display: block;">% Positif</span>
                <strong id="statPositif" style="font-size: 1.4rem; color: #3cac7a;">{{ $summary['pct_positif'] ?? 0 }}%</strong>
                <small id="countPositif" style="color: #8ab0c0; display: block; font-size: 0.7rem;">({{ number_format($summary['positif'] ?? 0) }} tweet)</small>
            </div>
            <div class="stat-box" style="border-bottom: 3px solid #e27b6b;">
                <span style="font-size: 0.75rem; color: #e27b6b; font-weight: 700; display: block;">% Negatif</span>
                <strong id="statNegatif" style="font-size: 1.4rem; color: #e27b6b;">{{ $summary['pct_negatif'] ?? 0 }}%</strong>
                <small id="countNegatif" style="color: #8ab0c0; display: block; font-size: 0.7rem;">({{ number_format($summary['negatif'] ?? 0) }} tweet)</small>
            </div>
            <div class="stat-box" style="border-bottom: 3px solid #5da2c4;">
                <span style="font-size: 0.75rem; color: #5da2c4; font-weight: 700; display: block;">% Netral</span>
                <strong id="statNetral" style="font-size: 1.4rem; color: #5da2c4;">{{ $summary['pct_netral'] ?? 0 }}%</strong>
                <small id="countNetral" style="color: #8ab0c0; display: block; font-size: 0.7rem;">({{ number_format($summary['netral'] ?? 0) }} tweet)</small>
            </div>
        </div>
    </div>

    {{-- Line Chart: Tren Sentimen Harian (Time-Series) --}}
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <i class="fas fa-chart-line" style="font-size: 1.8rem; color: var(--program-color);"></i>
            <h3>Tren Grafik Sentimen Harian (Time Series)</h3>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="dailyTrendChart"></canvas>
        </div>
    </div>

    {{-- Rekapitulasi Sentimen per Provinsi --}}
    <div class="card">
        <div class="card-header">
            <i class="fas fa-chart-bar" style="font-size: 1.8rem; color: var(--program-color);"></i>
            <h3>Rekapitulasi Sentimen Masyarakat per Provinsi</h3>
        </div>
        <div class="table-wrapper">
            <table class="sentiment-table">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">#</th>
                        <th>Provinsi</th>
                        <th><i class="fas fa-smile-wink"></i> Positif</th>
                        <th><i class="fas fa-frown-open"></i> Negatif</th>
                        <th><i class="fas fa-expressionless"></i> Netral</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody id="rekapTableBody">
                    @php 
                        $labels = [];
                        $positifData = [];
                        $negatifData = [];
                        $netralData = [];
                        $grandTotal = 0;
                        $provNo = 1;
                    @endphp
                    @foreach($data as $provinsi => $counts)
                        @php
                            $labels[] = $provinsi;
                            $positifData[] = $counts['positif'];
                            $negatifData[] = $counts['negatif'];
                            $netralData[] = $counts['netral'];
                            $totalProv = $counts['positif'] + $counts['negatif'] + $counts['netral'];
                            $grandTotal += $totalProv;
                        @endphp
                        <tr>
                            <td style="text-align: center; color: #8ab0c0; font-weight: 600;">{{ $provNo++ }}</td>
                            <td style="text-align: left; font-weight: 800;"><i class="fas fa-location-dot" style="color: var(--program-color); margin-right: 8px;"></i>{{ $provinsi }}</td>
                            <td class="badge-positive">{{ number_format($counts['positif']) }}</td>
                            <td class="badge-negative">{{ number_format($counts['negatif']) }}</td>
                            <td class="badge-neutral">{{ number_format($counts['netral']) }}</td>
                            <td><strong>{{ number_format($totalProv) }}</strong></td>
                        </tr>
                    @endforeach
                    <tr style="background: #f8fafc; font-weight: 800;" id="totalRow">
                        <td colspan="2" style="text-align: left;">TOTAL</td>
                        <td id="totalPositifCell">{{ number_format(array_sum($positifData)) }}</td>
                        <td id="totalNegatifCell">{{ number_format(array_sum($negatifData)) }}</td>
                        <td id="totalNetralCell">{{ number_format(array_sum($netralData)) }}</td>
                        <td id="totalGrandCell">{{ number_format($grandTotal) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bar Chart Per Provinsi --}}
    <div class="card">
        <div class="card-header">
            <i class="fas fa-chart-column" style="font-size: 1.8rem; color: var(--program-color);"></i>
            <h3>Visualisasi Perbandingan Sentimen per Provinsi</h3>
        </div>
        <canvas id="sentimenChart" width="800" height="380" style="max-width:100%; height:auto;"></canvas>
    </div>

    {{-- Evaluasi Model --}}
    <div class="card">
        <div class="card-header">
            <i class="fas fa-brain" style="font-size: 1.8rem; color: var(--program-color);"></i>
            <h3>Evaluasi Model Klasifikasi - {{ $programInfo['name'] }}</h3>
        </div>

        <div style="margin-bottom: 2rem;">
            <h4 style="margin-bottom: 1rem;"><i class="fas fa-fire-flame-curved"></i> Confusion Matrix Heatmap</h4>
            @if($programInfo['heatmapExists'])
                <div style="text-align: center; background: #f8fafc; padding: 1.5rem; border-radius: 1rem;">
                    <img src="{{ asset($programInfo['heatmapFile']) }}?t={{ time() }}" alt="Confusion Matrix Heatmap - {{ $programInfo['name'] }}" style="max-width: 100%; height: auto; border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                </div>
            @endif
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div style="background: #f8fafc; padding: 1.2rem 1rem; border-radius: 1rem; text-align: center;">
                <h4 style="font-size: 0.85rem; color: #608eaa; margin-bottom: 0.3rem;">Akurasi</h4>
                <p style="font-size: 1.8rem; font-weight: 800; color: var(--program-color); margin: 0;">
                    {{ isset($evaluasi['accuracy']) ? round($evaluasi['accuracy'] * 100, 2) : '0' }}%
                </p>
            </div>
            <div style="background: #f8fafc; padding: 1.2rem 1rem; border-radius: 1rem; text-align: center;">
                <h4 style="font-size: 0.85rem; color: #608eaa; margin-bottom: 0.3rem;">Presisi <small>(Weighted)</small></h4>
                <p style="font-size: 1.8rem; font-weight: 800; color: var(--program-color); margin: 0;">
                    {{ isset($evaluasi['weighted_avg']['precision']) ? round($evaluasi['weighted_avg']['precision'] * 100, 2) : '0' }}%
                </p>
            </div>
            <div style="background: #f8fafc; padding: 1.2rem 1rem; border-radius: 1rem; text-align: center;">
                <h4 style="font-size: 0.85rem; color: #608eaa; margin-bottom: 0.3rem;">Recall <small>(Weighted)</small></h4>
                <p style="font-size: 1.8rem; font-weight: 800; color: var(--program-color); margin: 0;">
                    {{ isset($evaluasi['weighted_avg']['recall']) ? round($evaluasi['weighted_avg']['recall'] * 100, 2) : '0' }}%
                </p>
            </div>
            <div style="background: #f8fafc; padding: 1.2rem 1rem; border-radius: 1rem; text-align: center;">
                <h4 style="font-size: 0.85rem; color: #608eaa; margin-bottom: 0.3rem;">F1-Score <small>(Macro)</small></h4>
                <p style="font-size: 1.8rem; font-weight: 800; color: var(--program-color); margin: 0;">
                    {{ isset($evaluasi['macro_f1']) ? round($evaluasi['macro_f1'] * 100, 2) : '0' }}%
                </p>
            </div>
        </div>
    </div>

    {{-- Word Cloud --}}
    <div class="wordcloud-card">
        <div class="section-title">
            <i class="fas fa-cloud" style="color: var(--program-color);"></i>
            Word Cloud Tweet — {{ $programInfo['name'] }}
        </div>
        @if($wordcloudExists)
            <img src="{{ asset('wordcloud_mbg.png') }}?t={{ time() }}" alt="Word Cloud MBG" crossorigin="anonymous">
        @endif
    </div>

    {{-- Tweets Table --}}
    <div class="tweets-card">
        <div class="tweets-header">
            <div class="section-title" style="margin-bottom: 0">
                <i class="fab fa-x-twitter" style="color: var(--program-color);"></i>
                Data Tweet Ter-filter (Database MySQL)
                <span class="tweets-count-badge" id="tweetBadgeCount">{{ count($tweetsRaw) }} tweet</span>
            </div>
            <input type="text" class="tweets-search" id="tweetsSearch" placeholder="&#xF002; Cari tweet, provinsi..." oninput="triggerDynamicFilter()">
        </div>

        <div class="tweets-table-wrap">
            <table class="tweets-table" id="tweetsTable">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 35%;">Teks Tweet</th>
                        <th style="width: 11%; text-align: center;">Sentimen</th>
                        <th style="width: 12%;">Username</th>
                        <th style="width: 8%;">Program</th>
                        <th style="width: 12%;">Provinsi</th>
                        <th style="width: 9%;">Waktu Tweet</th>
                        <th style="width: 9%;">Waktu Crawl</th>
                    </tr>
                </thead>
                <tbody id="tweetsBody">
                    {{-- Rendered dynamically by JS --}}
                </tbody>
            </table>
        </div>

        {{-- Dynamic Pagination Box --}}
        <div id="tweetsPaginationBox" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; padding: 1.2rem 1.5rem; background: #f8fafc; border-top: 1px solid #eef4f8; border-radius: 0 0 1.2rem 1.2rem;">
            <span id="paginationInfo" style="font-size: 0.85rem; color: #608eaa; font-weight: 600;">
                Menampilkan 0 dari 0 tweet
            </span>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button id="btnPrevPage" onclick="changeTweetPage(-1)" style="padding: 0.4rem 1rem; border-radius: 1.5rem; border: 1px solid #d4e5f0; background: white; font-size: 0.8rem; font-weight: 700; color: #123e54; cursor: pointer;">
                    &#8592; Sebelumnya
                </button>
                <div id="pageNumbersSpan" style="display: inline-flex; gap: 0.3rem; align-items: center;"></div>
                <button id="btnNextPage" onclick="changeTweetPage(1)" style="padding: 0.4rem 1rem; border-radius: 1.5rem; border: 1px solid #d4e5f0; background: white; font-size: 0.8rem; font-weight: 700; color: #123e54; cursor: pointer;">
                    Selanjutnya &#8594;
                </button>
            </div>
        </div>
    </div>

    {{-- Confirmation Modal --}}
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-content">
            <i class="fab fa-x-twitter" style="font-size:3rem; color:var(--program-color); margin-bottom:1rem;"></i>
            <h3 style="font-family:'Playfair Display', serif; font-size:1.5rem; color:#123e54; margin-bottom:0.5rem;">Mulai Crawling Tweet?</h3>
            <p style="color:#608eaa; font-size:0.9rem; line-height:1.5; margin-bottom:2rem;">
                Proses akan mengumpulkan tweet terbaru dari X (Twitter) dan menyimpannya ke <strong>Database MySQL</strong>.<br>Estimasi waktu: <strong>3-5 menit</strong>.
            </p>
            <div style="display:flex; gap:1rem; justify-content:center;">
                <button onclick="closeModal()" class="modal-btn-cancel">Batal</button>
                <button onclick="confirmProcess()" class="modal-btn-confirm">Ya, Mulai</button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>
@endsection

@push('scripts')
    <script>
        const PROGRAM_NAME = 'MBG';
        const labels = @json($labels ?? []);
        const positif = @json($positifData ?? []);
        const negatif = @json($negatifData ?? []);
        const netral = @json($netralData ?? []);
        const initialTrendData = @json($dailyTrend ?? []);
        const initialTweets = @json($tweetsRaw ?? []);

        let dailyTrendChartInstance = null;
        let allCurrentTweets = initialTweets;
        let tweetCurrentPage = 1;
        const TWEETS_PER_PAGE = 15;

        // Date String Formatting Helper (Bahasa Indonesia)
        function formatDateStr(dateStr) {
            if (!dateStr || dateStr === '-' || dateStr === 'null') return '-';
            try {
                const cleanStr = String(dateStr).replace(' WIB', '').replace('T', ' ').substring(0, 19);
                const d = new Date(cleanStr);
                if (isNaN(d.getTime())) return dateStr;
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const day = String(d.getDate()).padStart(2, '0');
                const month = months[d.getMonth()];
                const year = d.getFullYear();
                const hours = String(d.getHours()).padStart(2, '0');
                const mins = String(d.getMinutes()).padStart(2, '0');
                return `${day} ${month} ${year}, ${hours}:${mins} WIB`;
            } catch(e) {
                return dateStr;
            }
        }

        // Render Datatable Pagination
        function renderTweetTablePage() {
            const total = allCurrentTweets.length;
            const totalPages = Math.ceil(total / TWEETS_PER_PAGE) || 1;

            if (tweetCurrentPage > totalPages) tweetCurrentPage = totalPages;
            if (tweetCurrentPage < 1) tweetCurrentPage = 1;

            const startIdx = (tweetCurrentPage - 1) * TWEETS_PER_PAGE;
            const endIdx = Math.min(startIdx + TWEETS_PER_PAGE, total);
            const pageItems = allCurrentTweets.slice(startIdx, endIdx);

            const tweetsBody = document.getElementById('tweetsBody');
            document.getElementById('tweetBadgeCount').textContent = `${total} tweet`;

            if (total === 0) {
                tweetsBody.innerHTML = `<tr class="no-data-row"><td colspan="8" style="text-align:center; padding: 2rem; color: #608eaa;"><i class="fas fa-search" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>Tidak ditemukan tweet yang cocok dengan filter yang dipilih.</td></tr>`;
                document.getElementById('paginationInfo').textContent = 'Menampilkan 0 dari 0 tweet';
                document.getElementById('pageNumbersSpan').innerHTML = '';
                document.getElementById('btnPrevPage').disabled = true;
                document.getElementById('btnNextPage').disabled = true;
                return;
            }

            let tHtml = '';
            pageItems.forEach((r, idx) => {
                const sent = (r.sentimen || r.prediksi || 'netral').toLowerCase();
                const color = sent === 'positif' ? '#3cac7a' : (sent === 'negatif' ? '#e27b6b' : '#5da2c4');
                const icon = sent === 'positif' ? 'fa-smile-wink' : (sent === 'negatif' ? 'fa-frown-open' : 'fa-meh');
                
                const tDateFormatted = formatDateStr(r.tweet_date);
                const cDateFormatted = formatDateStr(r.crawled_at);

                tHtml += `
                    <tr class="tweet-row">
                        <td style="color: #8ab0c0; font-weight: 600;">${startIdx + idx + 1}</td>
                        <td class="tweet-text-cell"><div class="tweet-text">${r.tweet}</div></td>
                        <td style="text-align: center; white-space: nowrap;">
                            <span style="background: ${color}; color: white; font-weight: 700; font-size: 0.75rem; padding: 0.35rem 0.8rem; border-radius: 2rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                                <i class="fas ${icon}"></i> ${sent.charAt(0).toUpperCase() + sent.slice(1)}
                            </span>
                        </td>
                        <td style="white-space: nowrap; font-weight: 600; color: var(--program-color);">${r.username || '@anon'}</td>
                        <td><span style="background: var(--program-color-light); color: var(--program-color); font-weight: 600; font-size: 0.72rem; padding: 0.2rem 0.6rem; border-radius: 1rem;">${r.program || 'MBG'}</span></td>
                        <td><i class="fas fa-map-marker-alt" style="color: var(--program-color); font-size: 0.8rem;"></i> ${r.provinsi || r.location || 'DKI Jakarta'}</td>
                        <td style="white-space: nowrap; font-size: 0.82rem; color: #475569;">${tDateFormatted}</td>
                        <td style="white-space: nowrap; font-size: 0.82rem; color: #64748b;">${cDateFormatted}</td>
                    </tr>
                `;
            });
            tweetsBody.innerHTML = tHtml;

            // Update Pagination Text & Controls
            document.getElementById('paginationInfo').textContent = `Menampilkan ${startIdx + 1}-${endIdx} dari ${total} tweet (Halaman ${tweetCurrentPage} dari ${totalPages})`;
            document.getElementById('btnPrevPage').disabled = (tweetCurrentPage <= 1);
            document.getElementById('btnNextPage').disabled = (tweetCurrentPage >= totalPages);

            // Render Page Number Buttons
            const pageSpan = document.getElementById('pageNumbersSpan');
            let pagesHtml = '';
            const maxVisible = 5;
            let startP = Math.max(1, tweetCurrentPage - 2);
            let endP = Math.min(totalPages, startP + maxVisible - 1);
            if (endP - startP < maxVisible - 1) {
                startP = Math.max(1, endP - maxVisible + 1);
            }

            for (let i = startP; i <= endP; i++) {
                pagesHtml += `<button class="page-num-btn ${i === tweetCurrentPage ? 'active' : ''}" onclick="goToTweetPage(${i})">${i}</button>`;
            }
            pageSpan.innerHTML = pagesHtml;
        }

        function changeTweetPage(dir) {
            tweetCurrentPage += dir;
            renderTweetTablePage();
        }

        function goToTweetPage(pageNum) {
            tweetCurrentPage = pageNum;
            renderTweetTablePage();
        }

        // Inisialisasi Line Chart Tren Harian
        function initDailyTrendChart(trendData) {
            const ctx = document.getElementById('dailyTrendChart').getContext('2d');
            const dates = Object.keys(trendData);
            const posData = dates.map(d => trendData[d].positif || 0);
            const negData = dates.map(d => trendData[d].negatif || 0);
            const netData = dates.map(d => trendData[d].netral || 0);

            if (dailyTrendChartInstance) {
                dailyTrendChartInstance.destroy();
            }

            dailyTrendChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        { label: 'Positif', data: posData, borderColor: '#3cac7a', backgroundColor: 'rgba(60, 172, 122, 0.1)', fill: true, tension: 0.3, pointRadius: 4 },
                        { label: 'Negatif', data: negData, borderColor: '#e27b6b', backgroundColor: 'rgba(226, 123, 107, 0.1)', fill: true, tension: 0.3, pointRadius: 4 },
                        { label: 'Netral', data: netData, borderColor: '#5da2c4', backgroundColor: 'rgba(93, 162, 196, 0.1)', fill: true, tension: 0.3, pointRadius: 4 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#eef4f8' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initDailyTrendChart(initialTrendData);
            renderTweetTablePage();
        });

        // Trigger Filter Dinamis via AJAX
        let filterDebounce = null;
        function triggerDynamicFilter() {
            clearTimeout(filterDebounce);
            filterDebounce = setTimeout(() => {
                const startDate = document.getElementById('startDateInput').value;
                const endDate = document.getElementById('endDateInput').value;
                const provinsi = document.getElementById('filterProvinsiSelect').value;
                const sentimen = document.getElementById('filterSentimenSelect').value;
                const search = document.getElementById('tweetsSearch').value;

                fetch(`/dashboard/filter-data?program=${PROGRAM_NAME}&start_date=${startDate}&end_date=${endDate}&provinsi=${provinsi}&sentimen=${sentimen}&search=${encodeURIComponent(search)}`)
                    .then(res => res.json())
                    .then(resData => {
                        if (resData.status === 'success') {
                            updateDashboardUI(resData);
                        }
                    })
                    .catch(err => console.error("Filter error:", err));
            }, 300);
        }

        function updateDashboardUI(resData) {
            const summary = resData.summary;

            // Update Narrative Box
            document.getElementById('narrativeBadge').className = `status-badge-${summary.narrative.badge_class}`;
            document.getElementById('narrativeBadge').textContent = summary.narrative.status_label;
            document.getElementById('narrativeTitle').textContent = summary.narrative.title;
            document.getElementById('narrativePeriod').innerHTML = `<i class="fas fa-calendar-alt"></i> ${summary.narrative.period_str}`;
            document.getElementById('narrativeBody').innerHTML = summary.narrative.body;

            document.getElementById('statTotal').textContent = summary.total.toLocaleString();
            document.getElementById('statPositif').textContent = `${summary.pct_positif}%`;
            document.getElementById('countPositif').textContent = `(${summary.positif.toLocaleString()} tweet)`;
            document.getElementById('statNegatif').textContent = `${summary.pct_negatif}%`;
            document.getElementById('countNegatif').textContent = `(${summary.negatif.toLocaleString()} tweet)`;
            document.getElementById('statNetral').textContent = `${summary.pct_netral}%`;
            document.getElementById('countNetral').textContent = `(${summary.netral.toLocaleString()} tweet)`;

            // Update Line Chart
            initDailyTrendChart(resData.daily_trend);

            // Update Rekapitulasi Table & Bar Chart
            const rekap = resData.rekap_provinsi;
            const tbody = document.getElementById('rekapTableBody');
            let rowsHtml = '';
            let provNo = 1;
            let sumPos = 0, sumNeg = 0, sumNet = 0, sumGrand = 0;

            const newLabels = [], newPos = [], newNeg = [], newNet = [];

            Object.keys(rekap).forEach(prov => {
                const item = rekap[prov];
                newLabels.push(prov);
                newPos.push(item.positif);
                newNeg.push(item.negatif);
                newNet.push(item.netral);

                sumPos += item.positif;
                sumNeg += item.negatif;
                sumNet += item.netral;
                sumGrand += item.total;

                rowsHtml += `
                    <tr>
                        <td style="text-align: center; color: #8ab0c0; font-weight: 600;">${provNo++}</td>
                        <td style="text-align: left; font-weight: 800;"><i class="fas fa-location-dot" style="color: var(--program-color); margin-right: 8px;"></i>${prov}</td>
                        <td class="badge-positive">${item.positif.toLocaleString()}</td>
                        <td class="badge-negative">${item.negatif.toLocaleString()}</td>
                        <td class="badge-neutral">${item.netral.toLocaleString()}</td>
                        <td><strong>${item.total.toLocaleString()}</strong></td>
                    </tr>
                `;
            });

            rowsHtml += `
                <tr style="background: #f8fafc; font-weight: 800;" id="totalRow">
                    <td colspan="2" style="text-align: left;">TOTAL</td>
                    <td id="totalPositifCell">${sumPos.toLocaleString()}</td>
                    <td id="totalNegatifCell">${sumNeg.toLocaleString()}</td>
                    <td id="totalNetralCell">${sumNet.toLocaleString()}</td>
                    <td id="totalGrandCell">${sumGrand.toLocaleString()}</td>
                </tr>
            `;
            tbody.innerHTML = rowsHtml;

            // Update Bar Chart
            const chartCanvas = document.getElementById('sentimenChart');
            if (chartCanvas) {
                const chartInstance = Chart.getChart(chartCanvas);
                if (chartInstance) {
                    chartInstance.data.labels = newLabels;
                    chartInstance.data.datasets[0].data = newPos;
                    chartInstance.data.datasets[1].data = newNeg;
                    chartInstance.data.datasets[2].data = newNet;
                    chartInstance.update();
                }
            }

            // Update Tweet Table Data & Reset to Page 1
            allCurrentTweets = resData.tweets || [];
            tweetCurrentPage = 1;
            renderTweetTablePage();
        }

        // Preset Date Helper
        function setPresetDate(type, btn) {
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const startInput = document.getElementById('startDateInput');
            const endInput = document.getElementById('endDateInput');

            const today = new Date();
            const formatDate = d => {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            endInput.value = formatDate(today);

            if (type === '7days') {
                const past = new Date();
                past.setDate(today.getDate() - 7);
                startInput.value = formatDate(past);
            } else if (type === '30days') {
                const past = new Date();
                past.setDate(today.getDate() - 30);
                startInput.value = formatDate(past);
            } else if (type === 'this_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                startInput.value = formatDate(firstDay);
            } else { // 'all'
                startInput.value = '';
                endInput.value = '';
            }

            triggerDynamicFilter();
        }

        function resetDynamicFilter() {
            document.getElementById('startDateInput').value = '';
            document.getElementById('endDateInput').value = '';
            document.getElementById('filterProvinsiSelect').value = 'semua';
            document.getElementById('filterSentimenSelect').value = 'semua';
            document.getElementById('tweetsSearch').value = '';
            setPresetDate('all', document.querySelector('.preset-btn'));
        }

        function exportFilteredCsv(program) {
            const startDate = document.getElementById('startDateInput').value;
            const endDate = document.getElementById('endDateInput').value;
            const provinsi = document.getElementById('filterProvinsiSelect').value;
            const sentimen = document.getElementById('filterSentimenSelect').value;
            const search = document.getElementById('tweetsSearch').value;

            window.location.href = `/dashboard/export-csv?program=${program}&start_date=${startDate}&end_date=${endDate}&provinsi=${provinsi}&sentimen=${sentimen}&search=${encodeURIComponent(search)}`;
        }
    </script>
    @include('layouts.partials.scripts')
@endpush