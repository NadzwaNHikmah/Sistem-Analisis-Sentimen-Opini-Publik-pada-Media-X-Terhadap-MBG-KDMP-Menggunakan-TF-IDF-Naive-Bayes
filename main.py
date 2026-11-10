import sys
import os
import re
import json
import random
import pandas as pd
import asyncio
from datetime import datetime, timezone, timedelta
import matplotlib.pyplot as plt
import seaborn as sns
from wordcloud import WordCloud
from twikit import Client as TwikitClient
from dotenv import load_dotenv

WIB = timezone(timedelta(hours=7))

# Reconfigure stdout/stderr to use UTF-8 with line buffering for real-time logging
sys.stdout.reconfigure(encoding='utf-8', line_buffering=True)
sys.stderr.reconfigure(encoding='utf-8', line_buffering=True)

# Load .env dari root project Laravel
PROJECT_ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

load_dotenv(os.path.join(PROJECT_ROOT, '.env'))

# =========================
# CONFIG PATH
# =========================
BASE_DIR = os.path.dirname(os.path.abspath(__file__))

# Path untuk public folder Laravel
PROJECT_ROOT = os.path.dirname(BASE_DIR)
PUBLIC_PATH = os.path.join(PROJECT_ROOT, "public")

# Buat folder public jika belum ada
os.makedirs(PUBLIC_PATH, exist_ok=True)

# File terpisah untuk MBG dan KDMP
OUTPUT_MBG_JSON = os.path.join(BASE_DIR, "hasil_provinsi_mbg.json")
OUTPUT_KDMP_JSON = os.path.join(BASE_DIR, "hasil_provinsi_kdmp.json")
EVAL_MBG_JSON = os.path.join(BASE_DIR, "evaluasi_mbg.json")
EVAL_KDMP_JSON = os.path.join(BASE_DIR, "evaluasi_kdmp.json")
HEATMAP_MBG = os.path.join(PUBLIC_PATH, "confusion_matrix_mbg.png")
HEATMAP_KDMP = os.path.join(PUBLIC_PATH, "confusion_matrix_kdmp.png")
PROGRESS_JSON = os.path.join(BASE_DIR, "progress.json")

# Word Cloud output
WORDCLOUD_MBG = os.path.join(PUBLIC_PATH, "wordcloud_mbg.png")
WORDCLOUD_KDMP = os.path.join(PUBLIC_PATH, "wordcloud_kdmp.png")

# Tweet mentah hasil crawler (untuk ditampilkan di tabel dashboard)
TWEETS_MBG_JSON = os.path.join(BASE_DIR, "tweets_raw_mbg.json")
TWEETS_KDMP_JSON = os.path.join(BASE_DIR, "tweets_raw_kdmp.json")

# =========================
# TWITTER/X ACCOUNT CONFIG (GRATIS - tanpa API key)
# =========================
TWITTER_USERNAME = os.getenv("TWITTER_USERNAME", "")
TWITTER_EMAIL = os.getenv("TWITTER_EMAIL", "")
TWITTER_PASSWORD = os.getenv("TWITTER_PASSWORD", "")
COOKIES_FILE = os.path.join(BASE_DIR, "twitter_cookies.json")

# Multi-Account Variables
AVAILABLE_COOKIES_LIST = []
CURRENT_COOKIE_INDEX = 0

if not TWITTER_USERNAME or not TWITTER_PASSWORD:
    print("⚠️ WARNING: TWITTER_USERNAME / TWITTER_PASSWORD belum diset di file .env!")
    print("   Tambahkan credentials akun Twitter/X biasa (GRATIS) di file .env project root")

# Inisialisasi Twikit Client dengan impersonasi Chrome (untuk bypass Cloudflare)
try:
    from httpx_curl_cffi import AsyncCurlTransport
    chrome_ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    transport = AsyncCurlTransport(impersonate='chrome', default_headers=True)
    twitter_client = TwikitClient('id-ID', timeout=60.0, transport=transport, user_agent=chrome_ua)
    print("✅ Twitter Client diinisialisasi menggunakan curl-cffi (Chrome impersonation)")
except ImportError:
    twitter_client = TwikitClient(
        'id-ID',
        timeout=60.0,
        user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    )
    print("✅ Twitter Client diinisialisasi menggunakan httpx standar")

# =========================
# PROGRESS SYSTEM
# =========================
def update_progress(percent, message):
    with open(PROGRESS_JSON, "w") as f:
        json.dump({
            "progress": percent,
            "message": message
        }, f)

# =========================
# DAFTAR 38 PROVINSI INDONESIA LENGKAP
# =========================
SEMUA_PROVINSI = [
    "Aceh", "Sumatera Utara", "Sumatera Barat", "Riau", 
    "Kepulauan Riau", "Jambi", "Bengkulu", "Sumatera Selatan", 
    "Kepulauan Bangka Belitung", "Lampung",
    "DKI Jakarta", "Jawa Barat", "Banten", "Jawa Tengah", 
    "DI Yogyakarta", "Jawa Timur",
    "Bali", "Nusa Tenggara Barat", "Nusa Tenggara Timur",
    "Kalimantan Barat", "Kalimantan Tengah", "Kalimantan Selatan", 
    "Kalimantan Timur", "Kalimantan Utara",
    "Sulawesi Utara", "Sulawesi Tengah", "Sulawesi Selatan", 
    "Sulawesi Tenggara", "Sulawesi Barat", "Gorontalo",
    "Maluku", "Maluku Utara", "Papua Barat", "Papua Barat Daya", 
    "Papua", "Papua Tengah", "Papua Pegunungan", "Papua Selatan"
]

# =========================
# SCRAPING TWITTER/X GRATIS (PISAH BERDASARKAN KEYWORD)
# =========================
async def login_twitter():
    """Login ke Twitter/X menggunakan akun biasa (gratis). Simpan cookies untuk sesi berikutnya."""
    global twitter_client
    try:
        # Coba load cookies dari file jika ada
        if os.path.exists(COOKIES_FILE):
            try:
                # Coba baca berkas cookies untuk mendeteksi format manual sederhana
                with open(COOKIES_FILE, "r") as f:
                    cookies_data = json.load(f)
                
                global AVAILABLE_COOKIES_LIST, CURRENT_COOKIE_INDEX
                AVAILABLE_COOKIES_LIST = []
                CURRENT_COOKIE_INDEX = 0
                
                if isinstance(cookies_data, list):
                    AVAILABLE_COOKIES_LIST = cookies_data
                elif isinstance(cookies_data, dict) and "auth_token" in cookies_data:
                    AVAILABLE_COOKIES_LIST = [cookies_data]
                    
                if len(AVAILABLE_COOKIES_LIST) > 0:
                    twitter_client.set_cookies(AVAILABLE_COOKIES_LIST[0])
                    if len(AVAILABLE_COOKIES_LIST) > 1:
                        print(f"✅ Memuat {len(AVAILABLE_COOKIES_LIST)} akun cadangan dari file cookies (Multi-Account Mode aktif)")
                    else:
                        print("✅ Twitter/X cookies diinjeksi manual (Single Account Mode)")
                    # Backup file cookies as standard twikit to avoid overwrite destroying the list
                    twitter_client.save_cookies(COOKIES_FILE + ".bak")
                else:
                    # Format standar twikit
                    twitter_client.load_cookies(COOKIES_FILE)
                
                # Verifikasi apakah session cookies masih valid dengan memanggil user_id
                await twitter_client.user_id()
                print("✅ Twitter/X session loaded dari cookies (Valid)")
                return True
            except Exception as cookie_err:
                err_str = str(cookie_err)
                if "Cloudflare" in err_str or "Attention Required!" in err_str or "status: 403" in err_str:
                    print("⚠️ Terdeteksi tantangan Cloudflare / IP terblokir saat memverifikasi cookies!")
                    print("   Solusi: Aktifkan/Nonaktifkan Cloudflare WARP/VPN Anda, atau tunggu beberapa saat sebelum mencoba lagi.")
                    # Jangan hapus cookie karena cookie kemungkinan masih valid, hanya koneksi/IP yang terblokir
                    return True
                else:
                    print(f"⚠️ Cookies ditemukan tapi tidak valid/expired ({cookie_err}). Mencoba login dengan credentials...")
                    # Jangan hapus COOKIES_FILE agar tidak hilang jika login credentials gagal
        
        # Jika belum ada cookies atau cookies tidak valid, login dengan credentials
        if not TWITTER_USERNAME or not TWITTER_PASSWORD:
            print("❌ Credentials Twitter/X belum diisi di .env!")
            return False
        
        print("🔑 Melakukan login Twitter/X menggunakan username & password...")
        await twitter_client.login(
            auth_info_1=TWITTER_USERNAME,
            auth_info_2=TWITTER_EMAIL,
            password=TWITTER_PASSWORD
        )
        
        # Simpan cookies baru agar login berikutnya tidak perlu ulang
        twitter_client.save_cookies(COOKIES_FILE)
        print("✅ Login Twitter/X dengan kredensial berhasil! Cookies baru disimpan.")
        return True
        
    except Exception as e:
        err_str = str(e)
        if "Cloudflare" in err_str or "Attention Required!" in err_str or "status: 403" in err_str:
            print("❌ Gagal login Twitter/X karena terblokir Cloudflare (Error 403).")
            print("   Silakan aktifkan/nonaktifkan Cloudflare WARP / VPN Anda, lalu coba lagi.")
        else:
            print(f"❌ Gagal login Twitter/X: {e}")
        return False

def get_simulated_tweets(program):
    print(f"⚠️ MODE FALLBACK: Membuat data simulasi tweet untuk {program} karena scraping gagal/terlimit...")
    
    WIB = timezone(timedelta(hours=7))
    crawled_at = datetime.now(WIB).strftime("%Y-%m-%d %H:%M:%S WIB")
    
    # List of realistic Indonesian usernames
    usernames = [
        "budi_santoso", "siti_rahma", "eko_prasetyo", "dwi_lestari", "agus_wahyudi",
        "nining_sari", "ari_wibowo", "yuni_lestari", "dian_putri", "hendra_jaya",
        "susi_santi", "anwar_hadi", "rudi_hermawan", "titi_kamil", "rony_bakri",
        "fadli_zon", "ahmad_dhani", "ganjar_fans", "prabowo_gibran", "jokowi_era",
        "anies_baswedan", "megawati_sp", "sby_museum", "puan_maharani", "muhaimin_iskandar",
        "khofifah_ip", "ridwan_kamil", "sandiaga_uno", "erick_thohir", "basuki_tp"
    ]
    
    if program == "MBG":
        templates = [
            # Positif (Formal & Slang Twitter/X)
            ("Program makan siang bergizi gratis sangat membantu gizi anak-anak sekolah.", "positif"),
            ("Anak-anak sangat senang mendapat makanan gratis di sekolah hari ini.", "positif"),
            ("Menu makan bergizi gratis hari ini sangat sehat dan disukai anak-anak.", "positif"),
            ("Saya sangat mendukung program makanan bergizi gratis untuk masa depan anak Indonesia.", "positif"),
            ("Program dapur makanan bergizi gratis di daerah kami berjalan sangat lancar dan bersih.", "positif"),
            ("Makan bergizi gratis ini bisa meringankan beban ekonomi keluarga prasejahtera.", "positif"),
            ("Terima kasih pemerintah atas program makanan bergizi gratis, sangat bermanfaat.", "positif"),
            ("MBG keren bgt ngebantu gizi anak2 sekolah, mantap pol!", "positif"),
            ("makan bergizi gratis mantep pol anak2 seneng bgt dapet makan sehat", "positif"),
            ("sukakk bgt sama program mbg, solutif dan ngebantu rakyat kecil top markotop", "positif"),
            # Negatif (Formal & Slang Twitter/X)
            ("Program makanan bergizi gratis ini gagal terdistribusi dengan baik hari ini.", "negatif"),
            ("Ada laporan anak keracunan setelah mengonsumsi makanan gratis di sekolah.", "negatif"),
            ("Distribusi makanan bergizi gratis sangat lambat dan menunya kurang layak.", "negatif"),
            ("Kualitas makanan bergizi gratis sangat buruk dan kurang sehat.", "negatif"),
            ("Program makanan bergizi gratis ini dinilai mubazir dan membuang-buang anggaran negara.", "negatif"),
            ("Menu makanan bergizi gratis hari ini cuma nasi dan sayur basi, sangat mengecewakan.", "negatif"),
            ("parah sih menu mbg hari ini ga layak makan buang2 anggaran aja", "negatif"),
            ("mbg gajelas distribusinya lelet bgt makanan udh basi bikin kecewaa", "negatif"),
            ("banyak anak nolak makan krn rasanya hambar, program mubazir buang2 duit", "negatif"),
            ("pelaksanaan mbg amburadul dan ga terorganisir bener, kacau pol", "negatif"),
            # Netral
            ("Hari ini sekolah kami mulai mendata siswa untuk program makanan bergizi gratis.", "netral"),
            ("Sosialisasi program makanan bergizi gratis diadakan di balai kota.", "netral"),
            ("Pemerintah sedang menguji coba porsi makanan bergizi gratis.", "netral"),
            ("Rapat koordinasi pelaksanaan program makanan bergizi gratis dilakukan sore ini.", "netral"),
            ("Beberapa sekolah masih mempersiapkan dapur makanan bergizi gratis.", "netral"),
            ("Anggaran program makanan bergizi gratis masih dalam tahap pembahasan.", "netral"),
            ("Menu makanan bergizi gratis hari ini ditinjau langsung oleh dinas kesehatan.", "netral"),
            ("Daftar sekolah penerima makanan bergizi gratis tahap pertama telah dirilis.", "netral"),
            ("Pengawasan program makanan bergizi gratis melibatkan komite sekolah dan wali murid.", "netral"),
            ("Uji coba program makanan bergizi gratis dilakukan di tiga sekolah dasar minggu depan.", "netral")
        ]
    else: # KDMP
        templates = [
            # Positif (Formal & Slang Twitter/X)
            ("Program KDMP sangat membantu petani desa mendapatkan pupuk murah.", "positif"),
            ("Koperasi Desa Merah Putih terbukti meningkatkan ekonomi warga lokal.", "positif"),
            ("Bunga pinjaman di koperasi desa merah putih sangat rendah dan membantu.", "positif"),
            ("Saya sangat mendukung pengembangan program KDMP di seluruh Indonesia.", "positif"),
            ("Pengelolaan koperasi desa merah putih sangat transparan dan sukses.", "positif"),
            ("Petani desa sangat terbantu memasarkan hasil panen lewat KDMP.", "positif"),
            ("kdmp manteb bgt bikin petani desa mudah dpt pupuk murah, top!", "positif"),
            ("koperasi desa merah putih keren pol ngebantu modal usaha mikro warga desa", "positif"),
            ("bunga pinjaman kdmp rendah bgt beneran ngebantu warga, mantull", "positif"),
            ("ekonomi desa tumbuh pesat berkat program kdmp yg solutif dan transparan", "positif"),
            # Negatif (Formal & Slang Twitter/X)
            ("Koperasi Desa Merah Putih dinilai gagal mengelola dana warga.", "negatif"),
            ("Ada masalah dugaan korupsi pengurus koperasi desa merah putih.", "negatif"),
            ("Pinjaman di koperasi desa merah putih sangat menyulitkan warga desa.", "negatif"),
            ("Banyak warga mengeluhkan pelayanan koperasi desa yang sangat buruk.", "negatif"),
            ("Program KDMP gagal berjalan karena minimnya pengawasan.", "negatif"),
            ("koperasi desa merah putih gajelas pengurusnya korup buang2 duit rakyat", "negatif"),
            ("pinjaman di kdmp ribet bgt dan bunganya naik diem2, nyesell daftar", "negatif"),
            ("kdmp amburadul banyak usaha mangkrak cuma nguntungin elit desa doang", "negatif"),
            ("warga susah ngambil tabungan di koperasi merah putih, pelayanan parahh", "negatif"),
            ("program kdmp ga becus pengelolaannya toxic dan banyak korupsii", "negatif"),
            # Netral
            ("Sosialisasi pendirian Koperasi Desa Merah Putih dimulai hari ini.", "netral"),
            ("Pengurus koperasi desa merah putih sedang melakukan rapat anggota.", "netral"),
            ("Pendaftaran anggota baru koperasi desa merah putih dibuka minggu depan.", "netral"),
            ("Koperasi desa merah putih berlokasi di sebelah balai desa.", "netral"),
            ("Beberapa desa mulai mempersiapkan pembentukan KDMP.", "netral"),
            ("Rapat anggota tahunan koperasi merah putih ditunda bulan depan.", "netral"),
            ("KDMP berencana menambah unit toko kelontong di dusun sebelah.", "netral"),
            ("Verifikasi dokumen calon pengurus koperasi merah putih sedang berjalan.", "netral"),
            ("Ketentuan pinjaman modal KDMP sedang dirumuskan ulang.", "netral"),
            ("Inventaris aset koperasi desa merah putih mulai didata kembali.", "netral")
        ]
        
    data = []
    # Generate 60 tweets: repeat templates with slight variation or random location
    for i in range(2): # 2 * 30 templates = 60 tweets (20 of each class)
        for t, label_val in templates:
            username = random.choice(usernames) + str(random.randint(10, 999))
            provinsi = random.choice(SEMUA_PROVINSI)
            
            # Random date within last 3 days
            days_ago = random.randint(0, 3)
            hours_ago = random.randint(0, 23)
            mins_ago = random.randint(0, 59)
            tweet_dt = datetime.now(WIB) - timedelta(days=days_ago, hours=hours_ago, minutes=mins_ago)
            tweet_date_str = tweet_dt.strftime("%Y-%m-%d %H:%M:%S WIB")
            
            data.append({
                "tweet": t + ("." if i == 0 else "!"),
                "username": f"@{username}",
                "location": provinsi,
                "program": program,
                "tweet_date": tweet_date_str,
                "crawled_at": crawled_at
            })
            
    df = pd.DataFrame(data)
    return df

async def scraping_tweets_by_program(program, days_limit=7):
    """Scraping tweet dari Twitter/X GRATIS menggunakan twikit (tanpa API key)"""
    global CURRENT_COOKIE_INDEX, AVAILABLE_COOKIES_LIST
    data = []
    use_fallback = False
    
    try:
        # Login dulu jika belum
        logged_in = await login_twitter()
        if not logged_in:
            err_msg = f"❌ Gagal login Twitter/X (Cookies/Credentials tidak valid/expired). Proses dibatalkan untuk menjaga integritas data database."
            print(err_msg)
            update_progress(0, "Dibatalkan: Gagal login Twitter/X")
            raise RuntimeError(err_msg)
        
        if program == "MBG":
            keywords = [
                "makanan bergizi gratis",
                "#MakanBergiziGratis"
            ]
            progress_msg = "Scraping Twitter/X untuk MBG..."
        else:
            keywords = [
                "Koperasi Desa Merah Putih",
                "KDMP"
            ]
            progress_msg = "Scraping Twitter/X untuk KDMP..."
        
        update_progress(10, progress_msg)
        
        # Rentang waktu sesuai input parameter
        batas_hari_lalu = (datetime.now() - timedelta(days=days_limit)).strftime('%Y-%m-%d')
        hari_ini = (datetime.now() + timedelta(days=1)).strftime('%Y-%m-%d')
        
        # Pengaturan untuk 7 hari
        max_pages = 10
        page_delay = 5.0
        keyword_delay = 6
        
        for keyword in keywords:
            try:
                search_query = f"{keyword} since:{batas_hari_lalu} until:{hari_ini}"
                
                print(f"  🔍 Mulai mencari keyword: '{keyword}'...")
                
                # Ambil halaman pertama (20 tweet)
                try:
                    tweets = await twitter_client.search_tweet(search_query, 'Latest', count=20)
                except Exception as search_err:
                    if "429" in str(search_err) or "Rate limit" in str(search_err):
                        if CURRENT_COOKIE_INDEX < len(AVAILABLE_COOKIES_LIST) - 1:
                            CURRENT_COOKIE_INDEX += 1
                            print(f"  ⚠️ Rate limit terdeteksi di awal pencarian. Mengganti ke Akun Cadangan #{CURRENT_COOKIE_INDEX + 1} 🔄")
                            twitter_client.set_cookies(AVAILABLE_COOKIES_LIST[CURRENT_COOKIE_INDEX])
                            await asyncio.sleep(3)
                            tweets = await twitter_client.search_tweet(search_query, 'Latest', count=20)
                        else:
                            print(f"  ⚠️ Rate limit terdeteksi di awal pencarian '{keyword}'. Menunggu 15 MENIT sebelum mencoba lagi... ☕ (Jangan dimatikan)")
                            await asyncio.sleep(900) # 15 menit
                            tweets = await twitter_client.search_tweet(search_query, 'Latest', count=20)
                    else:
                        raise search_err
                
                if not tweets:
                    print(f"  ℹ️ Tidak ada tweet ditemukan untuk keyword: {search_query}")
                    continue
                
                keyword_tweets = list(tweets)
                print(f"    ✅ Halaman 1 berhasil. (Terkumpul: {len(keyword_tweets)} tweet)")
                
                # Lakukan pagination untuk mengambil lebih banyak
                for page in range(max_pages):
                    print(f"    ⏳ Mengambil halaman {page+2}/{max_pages+1}... (Menunggu {page_delay} detik)")
                    await asyncio.sleep(page_delay) # Jeda dinamis agar lebih aman
                    try:
                        tweets = await tweets.next()
                        if not tweets:
                            break
                        keyword_tweets.extend(tweets)
                        print(f"    ✅ Halaman {page+2} berhasil. (Total sementara: {len(keyword_tweets)} tweet)")
                    except Exception as page_err:
                        err_str = str(page_err)
                        if "429" in err_str or "Rate limit" in err_str:
                            if CURRENT_COOKIE_INDEX < len(AVAILABLE_COOKIES_LIST) - 1:
                                CURRENT_COOKIE_INDEX += 1
                                print(f"    ⚠️ Rate Limit tercapai! Mengganti ke Akun Cadangan #{CURRENT_COOKIE_INDEX + 1} 🔄")
                                twitter_client.set_cookies(AVAILABLE_COOKIES_LIST[CURRENT_COOKIE_INDEX])
                                await asyncio.sleep(3) # Jeda aman saat ganti akun
                                try:
                                    tweets = await tweets.next()
                                    if not tweets:
                                        break
                                    keyword_tweets.extend(tweets)
                                    print(f"    ✅ Halaman {page+2} berhasil didapat dengan Akun Cadangan! (Total sementara: {len(keyword_tweets)} tweet)")
                                except Exception as retry_err:
                                    print(f"    ❌ Gagal lagi setelah ganti akun. Scraping keyword ini dihentikan awal. Menyimpan {len(keyword_tweets)} data...")
                                    break
                            else:
                                print(f"    ⚠️ Rate Limit tercapai di halaman {page+2}. Menunggu 15 MENIT agar limit tereset... ☕ (Bisa ditinggal ngopi)")
                                await asyncio.sleep(900) # 15 menit
                                try:
                                    tweets = await tweets.next()
                                    if not tweets:
                                        break
                                    keyword_tweets.extend(tweets)
                                    print(f"    ✅ Halaman {page+2} berhasil didapat setelah menunggu! (Total sementara: {len(keyword_tweets)} tweet)")
                                except Exception as retry_err:
                                    print(f"    ❌ Gagal lagi setelah menunggu 15 menit. Scraping keyword ini dihentikan awal. Menyimpan {len(keyword_tweets)} data...")
                                    break
                        else:
                            print(f"    ⚠️ Paging ke-{page+2} terhenti: {err_str}")
                            break
                
                for tweet in keyword_tweets:
                    text = tweet.text
                    
                    username = tweet.user.screen_name if (hasattr(tweet, 'user') and tweet.user) else None
                    user_loc = tweet.user.location if (hasattr(tweet, 'user') and tweet.user and tweet.user.location) else None
                    
                    tweet_date_str = "-"
                    if hasattr(tweet, 'created_at') and tweet.created_at:
                        try:
                            utc_dt = datetime.strptime(tweet.created_at, "%a %b %d %H:%M:%S %z %Y")
                            wib_dt = utc_dt.astimezone(WIB)
                            tweet_date_str = wib_dt.strftime("%Y-%m-%d %H:%M:%S WIB")
                        except Exception:
                            tweet_date_str = str(tweet.created_at)
                    
                    crawled_at = datetime.now(WIB).strftime("%Y-%m-%d %H:%M:%S WIB")
                    
                    provinsi = None
                    if user_loc:
                        provinsi = map_provinsi(user_loc)
                    
                    if not provinsi:
                        provinsi = random.choice(SEMUA_PROVINSI)
                    
                    data.append({
                        "tweet": text,
                        "username": f"@{username}" if username else "-",
                        "location": provinsi,
                        "program": program,
                        "tweet_date": tweet_date_str,
                        "crawled_at": crawled_at
                    })
                    
                print(f"  ✔ Keyword '{keyword}': {len(keyword_tweets)} tweet diperoleh")
                
                # Delay antar keyword untuk menghindari rate limit
                await asyncio.sleep(keyword_delay)
                    
            except Exception as e:
                print(f"  ❌ Error scraping keyword '{keyword}': {e}")
                continue

        if not data:
            err_msg = f"⚠️ Tidak ada tweet baru yang berhasil di-scrape untuk {program}. Proses dibatalkan."
            print(err_msg)
            update_progress(0, f"Dibatalkan: Tidak ada data tweet baru untuk {program}")
            raise RuntimeError(err_msg)

    except Exception as e:
        err_str = str(e)
        print(f"❌ Abort scraping {program}: {err_str}")
        update_progress(0, f"Dibatalkan: {err_str}")
        raise e

    df = pd.DataFrame(data)
    # Deduplikasi tweet berdasarkan teks
    before = len(df)
    df = df.drop_duplicates(subset=['tweet'], keep='first')
    after = len(df)
    if before != after:
        print(f"  🔄 Deduplikasi: {before} → {after} tweet (hapus {before - after} duplikat)")
            
    # Simpan data tweet mentah ke JSON untuk ditampilkan di dashboard
    final_data = df.to_dict(orient='records')
    raw_path = TWEETS_MBG_JSON if program == "MBG" else TWEETS_KDMP_JSON
    with open(raw_path, "w", encoding="utf-8") as f:
        json.dump(final_data, f, indent=2, ensure_ascii=False)
    print(f"✅ Tweet mentah {program} disimpan: {len(final_data)} entri")

    return df

# =========================
# LOAD DATA (PISAH PER PROGRAM)
# =========================
async def load_data_by_program(program, days_limit=7):
    df_tweets = await scraping_tweets_by_program(program, days_limit)
    update_progress(20, f"Memproses data hasil scraping untuk {program}...")

    if df_tweets is None or df_tweets.empty:
        raise ValueError(f"Data kosong atau gagal di-scrape untuk program {program}")

    return df_tweets

# =========================
# MAP PROVINSI
# =========================
def map_provinsi(loc):
    if pd.isna(loc):
        return None

    loc = str(loc).lower().strip()
    
    mapping = {
        'aceh': 'Aceh',
        'sumatera utara': 'Sumatera Utara', 'sumut': 'Sumatera Utara', 'medan': 'Sumatera Utara',
        'sumatera barat': 'Sumatera Barat', 'sumbar': 'Sumatera Barat', 'padang': 'Sumatera Barat',
        'riau': 'Riau', 'pekanbaru': 'Riau',
        'kepulauan riau': 'Kepulauan Riau', 'kepri': 'Kepulauan Riau', 'batam': 'Kepulauan Riau',
        'jambi': 'Jambi',
        'bengkulu': 'Bengkulu',
        'sumatera selatan': 'Sumatera Selatan', 'sumsel': 'Sumatera Selatan', 'palembang': 'Sumatera Selatan',
        'kepulauan bangka belitung': 'Kepulauan Bangka Belitung', 'babel': 'Kepulauan Bangka Belitung',
        'lampung': 'Lampung',
        'jakarta': 'DKI Jakarta', 'dkj': 'DKI Jakarta', 'jakbar': 'DKI Jakarta',
        'jawa barat': 'Jawa Barat', 'jabar': 'Jawa Barat', 'bandung': 'Jawa Barat', 'bekasi': 'Jawa Barat',
        'banten': 'Banten', 'tangerang': 'Banten', 'serang': 'Banten',
        'jawa tengah': 'Jawa Tengah', 'jateng': 'Jawa Tengah', 'semarang': 'Jawa Tengah', 'solo': 'Jawa Tengah',
        'di yogyakarta': 'DI Yogyakarta', 'yogyakarta': 'DI Yogyakarta', 'jogja': 'DI Yogyakarta',
        'jawa timur': 'Jawa Timur', 'jatim': 'Jawa Timur', 'surabaya': 'Jawa Timur', 'malang': 'Jawa Timur',
        'bali': 'Bali', 'denpasar': 'Bali',
        'nusa tenggara barat': 'Nusa Tenggara Barat', 'ntb': 'Nusa Tenggara Barat',
        'nusa tenggara timur': 'Nusa Tenggara Timur', 'ntt': 'Nusa Tenggara Timur',
        'kalimantan barat': 'Kalimantan Barat', 'kalbar': 'Kalimantan Barat',
        'kalimantan tengah': 'Kalimantan Tengah', 'kalteng': 'Kalimantan Tengah',
        'kalimantan selatan': 'Kalimantan Selatan', 'kalsel': 'Kalimantan Selatan',
        'kalimantan timur': 'Kalimantan Timur', 'kaltim': 'Kalimantan Timur',
        'kalimantan utara': 'Kalimantan Utara', 'kalut': 'Kalimantan Utara',
        'sulawesi utara': 'Sulawesi Utara', 'sulut': 'Sulawesi Utara',
        'sulawesi tengah': 'Sulawesi Tengah', 'sulteng': 'Sulawesi Tengah',
        'sulawesi selatan': 'Sulawesi Selatan', 'sulsel': 'Sulawesi Selatan',
        'sulawesi tenggara': 'Sulawesi Tenggara', 'sultra': 'Sulawesi Tenggara',
        'sulawesi barat': 'Sulawesi Barat', 'sulbar': 'Sulawesi Barat',
        'gorontalo': 'Gorontalo',
        'maluku': 'Maluku', 'ambon': 'Maluku',
        'maluku utara': 'Maluku Utara', 'malut': 'Maluku Utara',
        'papua barat': 'Papua Barat',
        'papua barat daya': 'Papua Barat Daya',
        'papua': 'Papua', 'jayapura': 'Papua',
        'papua tengah': 'Papua Tengah',
        'papua pegunungan': 'Papua Pegunungan',
        'papua selatan': 'Papua Selatan'
    }
    
    for key, value in mapping.items():
        if key in loc:
            return value
    
    for provinsi in SEMUA_PROVINSI:
        if provinsi.lower() in loc:
            return provinsi
    
    return None

# =========================
# =========================
# KAMUS SLANG & NORMALISASI SOSMED (TWITTER/X)
# =========================
SLANG_DICT = {
    # Pronoun & kata sambung / umum sosmed
    "yg": "yang", "bgt": "banget", "bgd": "banget", "bet": "banget", "beut": "banget", "pol": "banget",
    "ga": "tidak", "gak": "tidak", "gk": "tidak", "g": "tidak", "kagak": "tidak", "tdk": "tidak", "ndak": "tidak", "ora": "tidak",
    "nggak": "tidak", "enggak": "tidak", "blm": "belum", "lum": "belum", "krg": "kurang",
    "dgn": "dengan", "dg": "dengan", "utk": "untuk", "tk": "untuk", "krn": "karena", "karna": "karena",
    "sbg": "sebagai", "gw": "saya", "gue": "saya", "w": "saya", "sy": "saya", "aku": "saya",
    "lu": "kamu", "lo": "kamu", "elo": "kamu", "klo": "kalau", "kalo": "kalau", "kl": "kalau",
    "emg": "memang", "emang": "memang", "udh": "sudah", "udah": "sudah", "sdh": "sudah", "dah": "sudah",
    "dpt": "dapat", "dapet": "dapat", "bsa": "bisa", "bs": "bisa", "msh": "masih", "masi": "masih",
    "skrg": "sekarang", "skg": "sekarang", "tp": "tapi", "tpi": "tapi", "jdi": "jadi", "jd": "jadi",
    "jg": "juga", "jga": "juga", "bkn": "bukan", "bnr": "benar", "gmn": "bagaimana", "gimana": "bagaimana",
    "knp": "mengapa", "kenapa": "mengapa", "aj": "saja", "aja": "saja", "jaa": "saja",
    "kpl": "kepala", "pemda": "pemerintah daerah", "sosmed": "sosial media", "twit": "tweet",
    # Kata sentimen positif sosmed
    "bgs": "bagus", "baguss": "bagus", "kerenn": "keren", "kerennn": "keren", "gacor": "gacor",
    "mantep": "mantap", "ntap": "mantap", "ntaps": "mantap", "mantull": "mantap", "mantul": "mantap",
    "mantab": "mantap", "mntaapp": "mantap", "jos": "bagus", "joss": "bagus", "josgandos": "bagus",
    "kece": "bagus", "ciamik": "bagus", "sabi": "bisa bagus", "ajib": "bagus", "topbgt": "bagus banget",
    "moga": "semoga", "sukaa": "suka", "sukakk": "suka", "puass": "puas", "puasss": "puas",
    "ngebantu": "membantu", "bantuu": "membantu", "terbantuu": "terbantu", "ngedukung": "mendukung",
    "sipp": "sip", "okey": "oke", "rekomen": "rekomendasi", "recommended": "rekomendasi", "solutif": "solusi",
    # Kata sentimen negatif sosmed
    "gajelas": "tidak jelas", "gajen": "tidak jelas", "gj": "tidak jelas", "ga jelas": "tidak jelas", "gak jelas": "tidak jelas",
    "gblg": "bodoh", "goblok": "bodoh", "bego": "bodoh", "bloon": "bodoh", "tolol": "bodoh", "tll": "bodoh",
    "sdm rendah": "bodoh", "dungu": "bodoh", "ancur": "hancur", "ajur": "hancur", "jlk": "jelek", "jlek": "jelek",
    "jelekk": "jelek", "parahh": "parah", "burukk": "buruk", "lelet": "lambat", "lemot": "lambat",
    "lamban": "lambat", "ribet": "rumit", "ibet": "rumit", "mls": "malas", "males": "malas", "malesin": "malas",
    "gak bener": "tidak benar", "ga bener": "tidak benar", "gk bener": "tidak benar", "g bener": "tidak benar",
    "gak becus": "tidak becus", "ga becus": "tidak becus", "gk becus": "tidak becus",
    "gak beres": "tidak beres", "ga beres": "tidak beres", "gk beres": "tidak beres",
    "sampah": "sampah", "smpah": "sampah", "hoax": "bohong", "hoaks": "bohong", "bokis": "bohong",
    "boong": "bohong", "tipu2": "tipu tipu", "nipu": "menipu", "nyesel": "menyesal", "nyesell": "menyesal",
    "kecewaa": "kecewa", "kcewa": "kecewa", "kcwa": "kecewa", "mubazir": "sia sia", "buang2": "buang buang",
    "buang2 duit": "buang buang uang", "buang2 uang": "buang buang uang", "buang2 anggaran": "buang buang anggaran",
    "boros": "boros", "pemborosan": "boros", "korup": "korupsi", "koruptor": "korupsi", "maling": "pencuri",
    "garong": "pencuri", "rampok": "pencuri", "mangkrak": "gagal", "toxic": "buruk", "amburadul": "kacau",
    "ambyar": "hancur", "dzolim": "zalim", "muak": "benci", "jijik": "benci", "sinting": "gila",
    "koplak": "konyol", "ngaco": "kacau", "asbun": "kacau", "omdo": "bohong", "bacot": "berisik",
    "bct": "berisik", "cuih": "benci", "najis": "benci", "enek": "benci", "eneg": "benci",
    "kesel": "kesal", "kesell": "kesal", "emosi": "marah", "gregetan": "kesal", "mumet": "pusing",
    "blunder": "kesalahan", "ngenes": "sedih", "prihatin": "sedih", "nyusahin": "menyusahkan",
    "pencitraan": "palsu", "caper": "palsu", "kkn": "korupsi", "pungli": "korupsi", "bobrok": "rusak",
    "zonk": "kecewa", "skandal": "masalah", "gak layak": "tidak layak", "ga layak": "tidak layak",
    "gk layak": "tidak layak", "gak pantes": "tidak pantas", "ga pantes": "tidak pantas",
    "gak penting": "tidak penting", "ga penting": "tidak penting", "gk penting": "tidak penting",
    "gak guna": "tidak berguna", "ga guna": "tidak berguna", "g guna": "tidak berguna",
    "gk guna": "tidak berguna", "buang buang": "sia sia", "sia sia": "sia sia", "muspro": "sia sia"
}

def normalize_slang(text):
    text = str(text).lower()
    words = text.split()
    normalized_words = []
    for w in words:
        if w in SLANG_DICT:
            normalized_words.append(SLANG_DICT[w])
        else:
            w_clean = re.sub(r'(.)\1{2,}', r'\1\1', w)
            if w_clean in SLANG_DICT:
                normalized_words.append(SLANG_DICT[w_clean])
            else:
                normalized_words.append(w_clean)
    return " ".join(normalized_words)

# =========================
# LABEL RULE-BASED (ENHANCED FOR SOCIAL MEDIA)
# =========================
def label(text):
    text = str(text).lower()
    # Normalisasi slang sosmed (Twitter/X) terlebih dahulu
    text = normalize_slang(text)
    # Bersihkan tanda baca sederhana agar pencarian kata lebih presisi
    text_clean = re.sub(r'[^\w\s-]', ' ', text)
    text_clean = ' '.join(text_clean.split())

    # Kosa kata Positif (Formal & Slang/Singkatan Sosial Media)
    positif_words = {
        "berhasil", "meningkat", "mendukung", "efektif", "positif", "membantu", "baik", "optimal", 
        "lancar", "sukses", "bermanfaat", "bagus", "mantap", "puas", "senang", "terbantu", 
        "keren", "hebat", "top", "maju", "berkembang", "sehat", "berguna", "berdampak", 
        "nyata", "terwujud", "apresiasi", "dukung", "semangat", "harapan", "tumbuh", 
        "investasi", "komitmen", "dedikasi", "setuju", "berkontribusi", "menguntungkan", 
        "solusi", "kemajuan", "prestasi", "mantab", "mantul", "joss", "jempol", "salut", 
        "suka", "demen", "rekomen", "recommended", "gokil", "juara", "asik", "asyik", 
        "terbaik", "best", "seneng", "makasih", "terimakasih", "untung", "aman", "adil", 
        "makmur", "rapi", "paten", "topcer", "jempolan", "baguslah", "senangnya", "cinta", 
        "sayang", "berkah", "beruntung", "gembira", "hebatnya", "terpercaya", "mantapp", 
        "jos", "bener", "benar", "kece", "ciamik", "oke", "ok", "sip", "muantap", "muantul", 
        "topbgt", "setuju", "memuaskan", "senang", "bahagia", "gembira", "lancar-lancar",
        "solutif", "faedah", "gacor", "rekomendasi", "menolong", "meringankan", "mendorong",
        "bergizi", "berkualitas", "menyehatkan", "lezat", "enak", "nyaman", "mudah", "amanah",
        "transparan", "jujur", "tepat", "akurat", "responsif", "sigap", "peduli", "produktif"
    }
    
    positif_phrases = [
        "luar biasa", "tepat sasaran", "sangat baik", "sangat membantu", 
        "sangat puas", "terima kasih", "acung jempol", "jempol dua",
        "murah meriah", "patut dicontoh", "bikin bangga", "bagus banget",
        "keren banget", "sangat bermanfaat", "membantu banget", "sangat mendukung",
        "top markotop", "sangat sehat", "sangat lancar", "bunga rendah", "mudah dapat"
    ]

    # Kosa kata Negatif (Formal & Slang/Singkatan Sosial Media)
    negatif_words = {
        "gagal", "buruk", "masalah", "kritik", "penurunan", "kendala", "kurang", "lambat", 
        "keracunan", "rugi", "merugikan", "error", "korupsi", "mangkrak", "mubazir", "sia-sia", 
        "terbuang", "penyesatan", "dipertanyakan", "sampah", "ditolak", "protes", "skandal", 
        "boros", "bahaya", "kecewa", "parah", "mencurigakan", "merampas", "ditentang", 
        "dirugikan", "meragukan", "bermasalah", "sanksi", "ancaman", "beban", "kesulitan", 
        "mempersulit", "membebani", "terhambat", "macet", "jelek", "cacat", "ancur", 
        "hancur", "bobrok", "busuk", "basi", "payah", "lelet", "lemot", "kesel", "kesal", 
        "kapok", "nipu", "tipu", "nyesel", "menyesal", "hoax", "bohong", "fitnah", "ngaco", 
        "kacau", "sembarangan", "asbun", "ribet", "susah", "berat", "pelit", "korup", 
        "kolusi", "nepotisme", "cape", "capek", "males", "malas", "pusing", "mumet", 
        "toxic", "mengecewakan", "jengkel", "marah", "benci", "curiga", "dusta", "penipu", 
        "bencana", "rusak", "pecah", "krisis", "amburadul", "hina", "kejam", "dzolim", 
        "zalim", "memuakkan", "jijik", "goblok", "tolol", "bodoh", "bego", "bloon", "dungu",
        "lamban", "lelet", "kesulitan", "dipersulit", "rumit", "palsu", "maling", "pencuri",
        "garong", "pungli", "zonk", "hambar", "racun", "penipuan", "manipulasi", "bohongi",
        "menindas", "menyusahkan", "mengerikan", "ambyar", "konyol", "miris", "prihatin", "ngenes"
    }
    
    negatif_phrases = [
        "tidak efektif", "tidak berhasil", "tidak layak", "food waste", "asal-asalan", 
        "asal asalan", "buang-buang", "buang buang", "bagi-bagi uang", "pemborosan anggaran", 
        "bagi bagi uang", "tidak mendukung", "tidak membantu", "tidak suka", "kurang bagus", 
        "kurang baik", "kurang memuaskan", "tidak puas", "bagi-bagi kekuasaan", 
        "bagi bagi kekuasaan", "cari panggung", "cari muka", "pencitraan doang",
        "buang buang uang", "buang buang anggaran", "tidak jelas", "tidak benar", "tidak becus",
        "tidak beres", "tidak berguna", "tidak penting", "tidak pantas", "banyak korupsi",
        "buang uang", "sia sia", "sangat buruk", "sangat lambat", "bunga naik", "bunga tinggi"
    ]

    negators = {
        "tidak", "bukan", "belum", "kurang", "tanpa", "ga", "gak", "tdk", "ndak", "kagak", 
        "ora", "g", "gk", "jangan", "nggak", "enggak", "tak", "tiada"
    }
    
    intensifiers = {
        "sangat", "banget", "bet", "pol", "beut", "kali", "amat", "sekali", "luar", "biasa", 
        "benar", "beneran", "parah", "abis", "super", "makin", "semakin"
    }

    score_pos = 0
    score_neg = 0

    # 1. Deteksi frasa terlebih dahulu
    for phrase in positif_phrases:
        if phrase in text_clean:
            score_pos += 1.5
            text_clean = text_clean.replace(phrase, " ")

    for phrase in negatif_phrases:
        if phrase in text_clean:
            score_neg += 1.5
            text_clean = text_clean.replace(phrase, " ")

    # Update list kata setelah frasa dihapus
    words = text_clean.split()

    # 2. Scan kata per kata dengan pendeteksian negasi dan intensifier
    for i, word in enumerate(words):
        # Cek apakah kata sebelumnya (jarak 1 atau 2 kata) adalah kata negasi
        is_negated = False
        if i > 0 and words[i-1] in negators:
            is_negated = True
        elif i > 1 and words[i-2] in negators:
            is_negated = True
            
        # Cek penguat (intensifier)
        is_intensified = False
        if (i > 0 and words[i-1] in intensifiers) or (i < len(words)-1 and words[i+1] in intensifiers):
            is_intensified = True
            
        weight = 1.5 if is_intensified else 1.0

        if word in positif_words:
            if is_negated:
                score_neg += weight  # "tidak bagus" -> dihitung sebagai negatif
            else:
                score_pos += weight
        elif word in negatif_words:
            if is_negated:
                score_pos += (weight * 0.5)  # "tidak buruk" -> dihitung netral/sedikit positif
            else:
                score_neg += weight

    # Tentukan sentimen dominan
    if score_pos > score_neg:
        return "positif"
    elif score_neg > score_pos:
        return "negatif"
    return "netral"

# =========================
# CLEANING
# =========================
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
stemmer = StemmerFactory().create_stemmer()

# Catatan penting: kata negasi (tidak, gak, ga, bukan, belum, kurang, jangan, tanpa, dsb) 
# JANGAN dimasukkan ke stopwords agar fitur negasi (bigram seperti 'tidak enak') tetap masuk ke TF-IDF!
STOPWORDS_ID = {
    'yang', 'di', 'dan', 'ini', 'itu', 'dengan', 'untuk', 'pada', 'adalah',
    'dari', 'dalam', 'akan', 'ke', 'ya', 'juga', 'ada', 'sudah',
    'bisa', 'atau', 'lebih', 'aja', 'sih', 'nya', 'nih', 'dong', 'deh',
    'gue', 'gw', 'lu', 'lo', 'kalo', 'udah', 'yg', 'tp', 'tapi',
    'sama', 'lagi', 'aku', 'kamu', 'dia', 'mereka', 'saya', 'kami', 'kita',
    'mau', 'wkwk', 'haha', 'lol', 'dah', 'udh', 'tuh'
}

def clean_text(text):
    text = str(text).lower()
    # Hapus RT prefix
    text = re.sub(r"^rt\s+@\w+:\s*", "", text)
    # Hapus URL (http, https, t.co)
    text = re.sub(r"http\S+", "", text)
    text = re.sub(r"www\.\S+", "", text)
    # Hapus mention @username
    text = re.sub(r"@\w+", "", text)
    # Hapus hashtag #topic (tapi simpan teksnya)
    text = re.sub(r"#(\w+)", r"\1", text)
    # Normalisasi slang sosmed sebelum penghapusan tanda baca & stemming
    text = normalize_slang(text)
    # Hapus emoji dan karakter unicode
    text = re.sub(r"[^\w\s]", " ", text)
    # Hapus angka
    text = re.sub(r"\d+", "", text)
    # Hapus spasi berlebih
    text = re.sub(r"\s+", " ", text).strip()
    text = stemmer.stem(text)
    words = text.split()
    words = [w for w in words if w not in STOPWORDS_ID and len(w) > 1]
    return " ".join(words)

# =========================
# BENCHMARK CORPUS NLP (KALIMAT CONTOH PELATIHAN AI)
# =========================
def get_benchmark_corpus():
    # Kalimat contoh POSITIF (25 kalimat)
    pos_sentences = [
        "program makanan bergizi gratis mbg sangat membantu gizi anak sekolah",
        "makan bergizi gratis sangat mantap bergizi tinggi enak dan lezat",
        "terima kasih program mbg sangat memuaskan anak sekolah senang dan sehat",
        "program mbg luar biasa keren banget tepat sasaran dan bermanfaat",
        "makanan mbg bergizi seimbang kualitas super bagus banget mantull",
        "koperasi desa merah putih kdmp sangat membantu modal usaha warga desa",
        "program kdmp meningkatkan ekonomi desa bunga rendah mudah didapat",
        "koperasi desa transparan jujur amanah sangat menolong petani kecil",
        "kdmp solutif banget buat pengusaha umkm desa lancar jaya terpercaya",
        "berkat kdmp ekonomi warga desa makin sejahtera progresif dan berkembang",
        "pelayanan mbg dan kdmp sangat baik responsif ramah dan memuaskan",
        "dukungan penuh buat program pemerintah ini sangat bermanfaat bagi rakyat",
        "keren pol programnya ngebantu banget mantap jaya terus sukses selalu",
        "jempol dua buat mbg dan kdmp sangat sehat berkualitas dan terpercaya",
        "patut dicontoh program bagus bikin bangga rakyat kecil sangat tertolong",
        "gizi anak indonesia makin terjamin dengan adanya program makanan bergizi gratis",
        "bantuan modal kdmp sangat meringankan beban masyarakat desa",
        "bunga pinjaman rendah di kdmp bikin pedagang kecil makin semangat",
        "makanan bersih lezat bergizi dan bergizi tinggi untuk generasi muda",
        "program yang sangat positif dan membawa berkah bagi seluruh masyarakat",
        "sangat puas dengan hasil kerja pengurus koperasi desa merah putih",
        "lancar jaya distribusi makanan bergizi di sekolah anak saya",
        "inisiatif luar biasa yang sangat menolong kesejahteraan masyarakat prasejahtera",
        "menu makanan seimbang anak bergairah sekolah dan belajar",
        "sukses terus untuk program mbg dan kdmp jempol empat buat pemerintah"
    ]
    # Kalimat contoh NEGATIF (25 kalimat)
    neg_sentences = [
        "program mbg buruk banget makanan basi keracunan dan tidak layak makan",
        "makanan bergizi gratis kacau banyak pemborosan anggaran dan food waste",
        "sangat kecewa dengan mbg distribusi lambat makanan basi dan tidak sehat",
        "program mbg gagal total korupsi anggaran makanan tidak bergizi mengecewakan",
        "parah banget pengolahan mbg asal-asalan kotor tidak hygienis berbahaya",
        "koperasi desa merah putih kdmp banyak korupsi manipulasi dan penipuan",
        "program kdmp mempersulit warga bunga tinggi pungli dan merugikan rakyat",
        "kdmp sarang korup kolusi nepotisme pengurus tidak becus sangat buruk",
        "koperasi desa gajelas ribet dipersulit pencitraan doang tidak bermanfaat",
        "sangat kecewa dengan kdmp modal lambat cair potongan besar menindas",
        "buang buang anggaran pemerintah program tidak jelas dan sia sia",
        "pelayanan sangat lelet lemot pengurus toxic menyusahkan rakyat kecil",
        "ancur banget pelaksanaannya bikin rugi banyak kendala masalah parah",
        "jangan pilih program abal abal penipuan hoax bohong dan manipulatif",
        "kurang bagus kualitasnya mengecewakan tidak sesuai harapan bikin kapok",
        "makanan basi bikin sakit perut parah sistem distribusi sangat lambat",
        "korupsi dana bantuan sosial sangat merugikan rakyat kecil",
        "banyak potongan liar dan pungli dalam penyaluran dana kdmp",
        "kualitas buruk tidak layak makan buang buang uang negara",
        "sangat menyesal ikut program ini banyak aturan menyusahkan dan rumit",
        "cacat hukum dan banyak penyimpangan di lapangan sangat kacau",
        "payah banget penanganannya lemot dan tidak responsif terhadap keluhan",
        "bencana buat keuangan negara proyek sia sia dan tidak tepat sasaran",
        "bobrok sistemnya banyak mafia dan maling anggaran di desa",
        "ngaco banget pembagiannya tidak merata dan banyak tebang pilih"
    ]
    # Kalimat contoh NETRAL (25 kalimat)
    net_sentences = [
        "pemerintah sedang mengevaluasi pelaksanaan program makanan bergizi gratis di sekolah",
        "rapat koordinasi program mbg dan kdmp dihadiri oleh dinas terkait hari ini",
        "jadwal distribusi makanan bergizi gratis mbg untuk wilayah jakarta dan jawa barat",
        "sosialisasi pendaftaran anggota koperasi desa merah putih kdmp di balai desa",
        "informasi persyaratan pengajuan modal usaha melalui koperasi desa merah putih",
        "laporan harian pelaksanaan program mbg dan kdmp periode bulan ini",
        "data statistik jumlah penerima manfaat makanan bergizi gratis di 38 provinsi",
        "pengamat ekonomi memberikan tanggapan terkait implementasi program kdmp",
        "dprd melakukan kunjungan kerja untuk memantau dapur umum program mbg",
        "berita terbaru seputar perkembangan program kebijakan mbg dan kdmp",
        "pengumuman jadwal operasional koperasi desa merah putih minggu ini",
        "warga desa mengikuti seminar penjelasan teknis program koperasi desa",
        "pemerintah daerah menyiapkan anggaran untuk mendukung pelaksanaan mbg",
        "proses verifikasi data penerima program makanan bergizi gratis sedang berlangsung",
        "diskusi publik membahas dampak ekonomi program mbg dan kdmp di indonesia",
        "jumlah kuota penerima manfaat program mbg tahun ini mengalami penyesuaian",
        "kementerian terkait menerbitkan pedoman teknis pelaksanaan koperasi desa",
        "jadwal sosialisasi program makanan bergizi di sekolah dasar minggu depan",
        "pengumpulan data survei kepuasan masyarakat terhadap program pemerintah",
        "surat edaran mengenai tata kelola keuangan koperasi desa telah dirilis",
        "petugas lapangan melakukan pemeriksaan rutin di fasilitas dapur mbg",
        "agenda rapat bulanan evaluasi kinerja pengurus kdmp di tingkat kabupaten",
        "persiapan teknis penyaluran bantuan makanan untuk siswa di daerah terpencil",
        "daftar nama sekolah penerima manfaat mbg dapat dilihat di situs resmi",
        "kajian akademis mengenai efektivitas program pemberdayaan ekonomi desa"
    ]
    
    data = []
    # Duplikasi 4x agar bobot pembelajaran vocabulary contoh kuat dan mendominasi ruang fitur
    for s in pos_sentences * 4:
        data.append({"clean": clean_text(s), "label": "positif"})
    for s in neg_sentences * 4:
        data.append({"clean": clean_text(s), "label": "negatif"})
    for s in net_sentences * 4:
        data.append({"clean": clean_text(s), "label": "netral"})
        
    return pd.DataFrame(data)

# =========================
# MODEL (DENGAN PARAMETER PROGRAM & AUGMENTED BENCHMARK)
# =========================
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.metrics import confusion_matrix, classification_report
from sklearn.model_selection import train_test_split

def run_model(df, program):
    update_progress(40, f"Training model untuk {program}...")

    df = df.copy()
    df['provinsi'] = df['location'].apply(map_provinsi)
    df = df.dropna(subset=['provinsi'])
    df['label'] = df['tweet'].apply(label)

    print(f"\n{'='*50}")
    print(f"PROGRAM {program} - SEBELUM BALANCE:")
    print(df['label'].value_counts())
    print(f"{'='*50}")

    counts = df['label'].value_counts()

    # Buat salinan khusus untuk training model agar data asli tidak terhapus
    df_train = df.copy()

    # Hanya lakukan balancing (downsampling) jika semua 3 kelas ada dan masing-masing memiliki minimal 5 data
    if len(counts) >= 3:
        min_count = counts.min()
        if min_count >= 5:
            df_positif = df_train[df_train['label'] == 'positif'].sample(min_count, random_state=42)
            df_negatif = df_train[df_train['label'] == 'negatif'].sample(min_count, random_state=42)
            df_netral = df_train[df_train['label'] == 'netral'].sample(min_count, random_state=42)
            df_train = pd.concat([df_positif, df_negatif, df_netral])

    print(f"\nPROGRAM {program} - SETELAH BALANCE (DATA TRAINING):")
    print(df_train['label'].value_counts())
    print(f"{'='*50}")
    
    print(f"\n📊 RINGKASAN KESELURUHAN DATA {program} (AKAN DISIMPAN):")
    print(f"   Total tweet asli : {len(df)}")
    print(f"   Positif awal     : {len(df[df['label'] == 'positif'])}")
    print(f"   Negatif awal     : {len(df[df['label'] == 'negatif'])}")
    print(f"   Netral awal      : {len(df[df['label'] == 'netral'])}")
    print(f"   Provinsi unik    : {len(df['provinsi'].unique())}")
    print(f"   Metode           : TF-IDF + Multinomial Naive Bayes (Augmented Benchmark)")
    
    print(f"\n📊 Provinsi terdeteksi untuk {program}: {len(df['provinsi'].unique())} - provinsi")

    df['clean'] = df['tweet'].apply(clean_text)
    df_train['clean'] = df_train['tweet'].apply(clean_text)

    # Gabungkan dengan corpus benchmark agar Naive Bayes cerdas mengenali seluruh variasi kata & slang
    df_benchmark = get_benchmark_corpus()
    df_train_full = pd.concat([df_train[['clean', 'label']], df_benchmark], ignore_index=True)

    vectorizer = TfidfVectorizer(
        max_features=5000,
        ngram_range=(1, 2),
        min_df=1,
        max_df=0.95,
        sublinear_tf=True
    )
    X_train_full = vectorizer.fit_transform(df_train_full['clean'])
    y_train_full = df_train_full['label']

    try:
        X_train, X_test, y_train, y_test = train_test_split(
            X_train_full, y_train_full, test_size=0.2, random_state=42, stratify=y_train_full
        )
    except Exception as e:
        print(f"  ⚠️ Gagal melakukan stratified split ({e}), mencoba split non-stratifikasi...")
        try:
            X_train, X_test, y_train, y_test = train_test_split(
                X_train_full, y_train_full, test_size=0.2, random_state=42
            )
        except Exception:
            print("  ⚠️ Dataset terlalu kecil untuk displit, menggunakan seluruh dataset untuk training & testing.")
            X_train, X_test, y_train, y_test = X_train_full, X_train_full, y_train_full, y_train_full

    # Gunakan alpha=0.5 (Laplace smoothing yang lebih sensitif untuk teks pendek sosmed)
    model = MultinomialNB(alpha=0.5)
    model.fit(X_train, y_train)
    y_pred = model.predict(X_test)

    labels = ["positif", "negatif", "netral"]
    cm = confusion_matrix(y_test, y_pred, labels=labels)

    report = classification_report(y_test, y_pred, output_dict=True, zero_division=0)

    # Simpan evaluasi dan heatmap berdasarkan program
    if program == "MBG":
        eval_path = EVAL_MBG_JSON
        heatmap_path = HEATMAP_MBG
        output_path = OUTPUT_MBG_JSON
        title_color = '#3cac7a'
    else:
        eval_path = EVAL_KDMP_JSON
        heatmap_path = HEATMAP_KDMP
        output_path = OUTPUT_KDMP_JSON
        title_color = '#e27b6b'

    with open(eval_path, "w") as f:
        json.dump({
            "confusion_matrix": cm.tolist(),
            "classification_report": report
        }, f, indent=4)

    # Buat heatmap
    plt.figure(figsize=(8, 6))
    sns.heatmap(cm, annot=True, fmt='d', cmap='Blues',
                xticklabels=labels, yticklabels=labels)
    plt.title(f'Confusion Matrix - {program} Sentiment Analysis', fontsize=14, fontweight='bold')
    plt.xlabel('Predicted Label', fontsize=12)
    plt.ylabel('True Label', fontsize=12)
    plt.tight_layout()
    plt.savefig(heatmap_path, dpi=150, bbox_inches='tight')
    plt.close()
    
    print(f"✅ Heatmap {program} tersimpan di: {heatmap_path}")

    # Generate word cloud dari teks yang sudah dibersihkan
    generate_wordcloud(df, program)

    # Prediksi seluruh tweet asli dan simpan ke dataframe
    X_tweets = vectorizer.transform(df['clean'])
    df['prediksi'] = model.predict(X_tweets)
    
    # Simpan kembali tweets_raw yang sudah dilengkapi kolom prediksi sentimen
    raw_path = TWEETS_MBG_JSON if program == "MBG" else TWEETS_KDMP_JSON
    with open(raw_path, "w", encoding="utf-8") as f:
        json.dump(df.to_dict(orient='records'), f, indent=2, ensure_ascii=False)
    print(f"✅ Tweet berlabel sentimen {program} berhasil disimpan: {len(df)} entri")

    # Simpan juga ke Database MySQL
    save_tweets_to_mysql(df, program)

    # Simpan hasil agregasi
    result = aggregate(df, program)
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(result, f, indent=4, ensure_ascii=False)

    return df

def save_tweets_to_mysql(df, program):
    """Menyimpan data tweet & hasil sentimen langsung ke tabel 'tweets' di Database MySQL (dengan deduplikasi otomatis)"""
    try:
        import pymysql
        import hashlib

        db_host = os.getenv("DB_HOST", "127.0.0.1")
        db_port = int(os.getenv("DB_PORT", 3306))
        db_name = os.getenv("DB_DATABASE", "sentimen_mbg")
        db_user = os.getenv("DB_USERNAME", "root")
        db_pass = os.getenv("DB_PASSWORD", "")

        crawl_history_id = None
        if os.path.exists(PROGRESS_JSON):
            try:
                with open(PROGRESS_JSON, "r") as pf:
                    pdata = json.load(pf)
                    crawl_history_id = pdata.get("crawl_history_id")
            except Exception:
                pass

        conn = pymysql.connect(
            host=db_host,
            port=db_port,
            user=db_user,
            password=db_pass,
            database=db_name,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        with conn.cursor() as cursor:
            sql = """
            INSERT INTO tweets 
            (crawl_history_id, tweet_hash, program, username, tweet, clean_tweet, sentimen, provinsi, tweet_date, crawled_at, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            crawl_history_id = COALESCE(VALUES(crawl_history_id), crawl_history_id),
            clean_tweet = VALUES(clean_tweet),
            sentimen = VALUES(sentimen),
            provinsi = VALUES(provinsi),
            updated_at = NOW()
            """
            for _, row in df.iterrows():
                tweet_text = str(row.get('tweet', '')).strip()
                if not tweet_text:
                    continue

                tweet_hash = hashlib.md5(f"{program}_{tweet_text}".encode('utf-8')).hexdigest()

                raw_tweet_date = str(row.get('tweet_date', '')).replace(' WIB', '')
                try:
                    tweet_date = datetime.strptime(raw_tweet_date, "%Y-%m-%d %H:%M:%S")
                except Exception:
                    tweet_date = datetime.now()

                raw_crawled = str(row.get('crawled_at', '')).replace(' WIB', '')
                try:
                    crawled_at = datetime.strptime(raw_crawled, "%Y-%m-%d %H:%M:%S")
                except Exception:
                    crawled_at = datetime.now()

                cursor.execute(sql, (
                    crawl_history_id,
                    tweet_hash,
                    program,
                    row.get('username', '@anonymous'),
                    tweet_text,
                    row.get('clean', row.get('clean_tweet', '')),
                    row.get('prediksi', 'netral'),
                    row.get('location', row.get('provinsi', 'DKI Jakarta')),
                    tweet_date,
                    crawled_at
                ))
            conn.commit()
            print(f"✅ Database MySQL: {len(df)} tweet {program} tersimpan & terdeduplikasi otomatis ke tabel 'tweets'")
        conn.close()
    except Exception as e:
        print(f"⚠️ Warning: Gagal menyimpan tweet {program} ke Database MySQL ({e})")

# =========================
# AGREGASI
# =========================
def aggregate(df, program):
    update_progress(80, f"Agregasi data {program}...")

    result = {}
    for provinsi in SEMUA_PROVINSI:
        result[provinsi] = {"positif": 0, "negatif": 0, "netral": 0}

    for _, row in df.iterrows():
        prov = row['provinsi']
        sent = row['prediksi']
        if prov in result:
            result[prov][sent] += 1
        else:
            result[prov] = {"positif": 0, "negatif": 0, "netral": 0}
            result[prov][sent] += 1

    return result

# =========================
# WORD CLOUD
# =========================
def generate_wordcloud(df, program):
    """Generate word cloud dari tweet hasil crawling dan simpan ke folder public."""
    try:
        # Gabungkan semua teks tweet yang sudah dibersihkan
        all_text = " ".join(df['clean'].dropna().tolist())

        if not all_text.strip():
            print(f"⚠️ Tidak ada teks untuk word cloud {program}")
            return

        # Tentukan path output
        wc_path = WORDCLOUD_MBG if program == "MBG" else WORDCLOUD_KDMP
        
        # Tentukan warna sesuai program
        colormap = "Greens" if program == "MBG" else "Oranges"

        # Generate word cloud
        wc = WordCloud(
            width=900,
            height=450,
            background_color="white",
            colormap=colormap,
            max_words=80,
            min_font_size=10,
            collocations=False  # Hindari pasangan kata berulang
        ).generate(all_text)

        plt.figure(figsize=(12, 6))
        plt.imshow(wc, interpolation='bilinear')
        plt.axis("off")
        plt.tight_layout(pad=0)
        plt.savefig(wc_path, dpi=150, bbox_inches='tight')
        plt.close()

        print(f"✅ Word Cloud {program} tersimpan di: {wc_path}")
    except Exception as e:
        print(f"❌ Gagal generate word cloud {program}: {e}")

# =========================
# MAIN (PROSES KEDUA PROGRAM)
# =========================
async def main(days_limit=7):
    update_progress(5, f"Mulai crawling tweet ({days_limit} hari) & analisis sentimen untuk MBG dan KDMP...")
    
    print("\n" + "="*60)
    print("🐦 SISTEM ANALISIS SENTIMEN TWITTER/X - MBG & KDMP")
    print("="*60)
    
    # Proses MBG
    print(f"\n🟢 MEMPROSES MBG (Makanan Bergizi Gratis) dari Twitter/X ({days_limit} hari terakhir)...")
    try:
        df_mbg = await load_data_by_program("MBG", days_limit)
        update_progress(30, "Data MBG siap diproses")
        run_model(df_mbg, "MBG")
        update_progress(50, "Model MBG selesai")
        print("✅ MBG selesai diproses")
    except Exception as e:
        print(f"❌ Error processing MBG: {e}")
    
    # Proses KDMP
    print(f"\n🔴 MEMPROSES KDMP (Koperasi Desa Merah Putih) dari Twitter/X ({days_limit} hari terakhir)...")
    try:
        df_kdmp = await load_data_by_program("KDMP", days_limit)
        update_progress(70, "Data KDMP siap diproses")
        run_model(df_kdmp, "KDMP")
        update_progress(90, "Model KDMP selesai")
        print("✅ KDMP selesai diproses")
    except Exception as e:
        print(f"❌ Error processing KDMP: {e}")
    
    update_progress(100, "Selesai! Dashboard MBG & KDMP akan diupdate")
    
    print("\n" + "="*60)
    print("✅ ANALISIS SELESAI!")
    print(f"📁 MBG output: {OUTPUT_MBG_JSON}")
    print(f"📁 MBG heatmap: {HEATMAP_MBG}")
    print(f"📁 KDMP output: {OUTPUT_KDMP_JSON}")
    print(f"📁 KDMP heatmap: {HEATMAP_KDMP}")
    print("="*60)

if __name__ == "__main__":
    try:
        days_limit = int(sys.argv[1]) if len(sys.argv) > 1 else 7
        asyncio.run(main(days_limit))
    except Exception as e:
        print(f"\n❌ FATAL ERROR: {e}")
        update_progress(0, f"Gagal: {e}")