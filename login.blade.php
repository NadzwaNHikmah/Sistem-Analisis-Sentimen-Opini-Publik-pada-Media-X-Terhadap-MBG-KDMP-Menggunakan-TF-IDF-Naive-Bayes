@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div class="login-container">
    <div class="brand">
        <div class="brand-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <h2>Nusantara Insight</h2>
        <p><i class="fas fa-map-marked-alt"></i> Analisis Sentimen Publik</p>
    </div>

    <form method="POST" action="/login">
        @csrf

        <div class="input-group">
            <div class="input-label">
                <i class="fas fa-envelope"></i>
                <span>Alamat Email</span>
            </div>
            <input type="email" name="email" class="input-field" placeholder="admin@nusantara.com" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="input-group">
            <div class="input-label">
                <i class="fas fa-lock"></i>
                <span>Kata Sandi</span>
            </div>
            <input type="password" name="password" class="input-field" placeholder="********" required>
        </div>

        <button type="submit" class="login-btn">
            <span>Masuk ke Dashboard</span>
            <i class="fas fa-arrow-right"></i>
        </button>

        @if($errors->any())
            <div class="error-message">
                <i class="fas fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @elseif(session('error'))
            <div class="error-message">
                <i class="fas fa-circle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div class="success-message" style="
                background: rgba(220, 250, 230, 0.85);
                backdrop-filter: blur(4px);
                border-radius: 80px;
                padding: 0.7rem 1.2rem;
                margin-top: 1.2rem;
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 0.8rem;
                color: #1a7a3a;
                font-weight: 500;
                border-left: 4px solid #3cac7a;
            ">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="login-footer">
            <p>
                <i class="fas fa-users"></i> Belum memiliki akun?
                <a href="{{ route('register') }}">
                    <i class="fas fa-sign-in-alt"></i> register di sini
                </a>
            </p>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const errorDiv = document.querySelector('.error-message');
        if(errorDiv) {
            errorDiv.style.opacity = '0';
            errorDiv.style.transform = 'translateY(-8px)';
            setTimeout(() => {
                errorDiv.style.transition = 'all 0.3s ease';
                errorDiv.style.opacity = '1';
                errorDiv.style.transform = 'translateY(0)';
            }, 100);
        }
    });
</script>
@endsection