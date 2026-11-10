@extends('layouts.auth')

@section('title', 'Daftar')

@section('content')
<div class="register-container">
    <div class="brand">
        <div class="brand-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <h2>Bergabung Sekarang</h2>
        <p><i class="fas fa-chart-simple"></i> Akses wawasan sentimen publik</p>
    </div>

    <form method="POST" action="{{ route('register.proses') }}">
        @csrf

        <div class="input-group">
            <div class="input-label">
                <i class="fas fa-user-circle"></i>
                <span>Nama Lengkap</span>
            </div>
            <input type="text" name="name" class="input-field" placeholder="Budi Santoso" value="{{ old('name') }}" required autofocus>
        </div>

        <div class="input-group">
            <div class="input-label">
                <i class="fas fa-envelope"></i>
                <span>Alamat Email</span>
            </div>
            <input type="email" name="email" class="input-field" placeholder="budi@nusantara.com" value="{{ old('email') }}" required>
        </div>

        <div class="input-group">
            <div class="input-label">
                <i class="fas fa-lock"></i>
                <span>Kata Sandi</span>
            </div>
            <input type="password" name="password" class="input-field" placeholder="Minimal 6 karakter" required>
            <div class="password-hint">
                <i class="fas fa-shield-alt"></i> Gunakan kombinasi huruf & angka untuk keamanan
            </div>
        </div>

        <div class="input-group">
            <div class="input-label">
                <i class="fas fa-lock"></i>
                <span>Konfirmasi Kata Sandi</span>
            </div>
            <input type="password" name="password_confirmation" class="input-field" placeholder="Ketik ulang kata sandi" required>
        </div>

        <button type="submit" class="register-btn">
            <span>Daftar Sekarang</span>
            <i class="fas fa-arrow-right"></i>
        </button>

        <!-- Tampilkan error global jika ada (opsional dari server validation) -->
        @if($errors->any())
            <div class="error-message">
                <i class="fas fa-circle-exclamation"></i>
                <span>
                    @foreach($errors->all() as $err)
                        {{ $err }}@if(!$loop->last), @endif
                    @endforeach
                </span>
            </div>
        @endif

        @if(session('success'))
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="register-footer">
            <p>
                <i class="fas fa-users"></i> Sudah memiliki akun?
                <a href="{{ route('login') }}">
                    <i class="fas fa-sign-in-alt"></i> Login di sini
                </a>
            </p>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const errorDiv = document.querySelector('.error-message');
        const successDiv = document.querySelector('.success-message');
        const showWithEase = (el) => {
            if(el) {
                el.style.opacity = '0';
                el.style.transform = 'translateY(-6px)';
                setTimeout(() => {
                    el.style.transition = 'all 0.3s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 80);
            }
        };
        showWithEase(errorDiv);
        showWithEase(successDiv);
    });
</script>
@endsection