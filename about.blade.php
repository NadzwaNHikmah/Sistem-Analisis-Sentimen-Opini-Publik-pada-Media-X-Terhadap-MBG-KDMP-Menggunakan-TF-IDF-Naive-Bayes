@extends('layouts.dashboard')

@section('title', 'Tentang Sistem')

@section('css-vars')
<style>
    :root {
        --program-color: #217394;
        --program-color-light: rgba(33, 115, 148, 0.1);
        --program-color-border: rgba(33, 115, 148, 0.2);
        --program-bg: linear-gradient(135deg, #eef3fc 0%, #dceaf5 100%);
    }
</style>
@endsection

@push('styles')
<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 0;
    }
    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        margin: 3rem 0;
        text-align: center;
    }
    .stat-card {
        background: linear-gradient(135deg, #ffffff, #f8fafc);
        padding: 1.5rem;
        border-radius: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: #2f8cb0;
    }
    .dashboard-buttons {
        display: flex;
        gap: 1.5rem;
        justify-content: center;
        margin: 3rem 0;
        flex-wrap: wrap;
    }
    .btn-dashboard {
        padding: 1rem 2.5rem;
        border-radius: 3rem;
        font-weight: 700;
        text-decoration: none;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.8rem;
    }
    .btn-mbg {
        background: #3cac7a;
        color: white;
        box-shadow: 0 4px 12px rgba(60,172,122,0.3);
    }
    .btn-mbg:hover {
        background: #2d8a62;
        transform: translateY(-2px);
    }
    .btn-kdmp {
        background: #e27b6b;
        color: white;
        box-shadow: 0 4px 12px rgba(226,123,107,0.3);
    }
    .btn-kdmp:hover {
        background: #c55d4d;
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="hero">
        <h1><i class="fab fa-x-twitter"></i> Analisis Sentimen Twitter/X</h1>
        <p>Sistem cerdas untuk memonitor opini masyarakat di platform X (Twitter) terhadap program strategis nasional menggunakan Natural Language Processing & Machine Learning</p>
    </div>

    @guest
    <div class="dashboard-buttons">
        <a href="{{ route('login') }}" class="btn-dashboard btn-mbg">
            <i class="fas fa-sign-in-alt"></i> Login untuk Akses Dashboard
        </a>
        <a href="{{ route('register') }}" class="btn-dashboard btn-kdmp">
            <i class="fas fa-user-plus"></i> Daftar Akun Baru
        </a>
    </div>
    @else
    <div class="dashboard-buttons">
        <a href="{{ route('dashboard.mbg') }}" class="btn-dashboard btn-mbg">
            <i class="fas fa-utensils"></i> Dashboard MBG
        </a>
        <a href="{{ route('dashboard.kdmp') }}" class="btn-dashboard btn-kdmp">
            <i class="fas fa-handshake"></i> Dashboard KDMP
        </a>
    </div>
    @endauth

    <h2 class="section-title"><i class="fas fa-tools"></i> Technology Stack</h2>
    <div class="tools-grid">
        @foreach($tools as $tool)
        <div class="tool-card">
            <i class="{{ $tool['icon'] }} tool-icon"></i>
            <h4>{{ $tool['name'] }}</h4>
            <p>{{ $tool['desc'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="features-list">
        <h3 style="font-size:1.4rem; color:#123e54; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-star-of-life"></i> Fitur Unggulan
        </h3>
        <div class="features-list-items" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:1.2rem;">
            @foreach($features as $feature)
            <div class="feature-item">
                <div class="feature-check"><i class="fas fa-check"></i></div>
                <div class="feature-text">{{ $feature }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number">95%+</div>
            <p>Akurasi Model</p>
        </div>
        <div class="stat-card">
            <div class="stat-number">38</div>
            <p>Provinsi Terpantau</p>
        </div>
        <div class="stat-card">
            <div class="stat-number">Real-time</div>
            <p>Crawling Tweet</p>
        </div>
        <div class="stat-card">
            <div class="stat-number">3</div>
            <p>Kelas Sentimen</p>
        </div>
    </div>

    <div style="text-align: center; padding: 2.5rem; background: rgba(255,255,255,0.7); border-radius: 1.5rem; box-shadow:0 4px 12px rgba(0,0,0,0.02); margin-top:2rem;">
        <i class="fas fa-chart-line" style="font-size: 3rem; color: #2f8cb0; margin-bottom:1rem;"></i>
        <p style="color: #216f8f; font-weight:600;">Powered by Twitter/X API, Naive Bayes Classifier & TF-IDF Vectorization</p>
    </div>
</div>
@endsection