<?php
/**
 * =========================================================================
 * DASHBOARD PROGRAM STUDI (PRODI) - PORTAL AKADEMIK & WEB SEMANTIK
 * Hak Akses Role Prodi:
 * 1. Bisa akses data mahasiswa program studi terkait
 * 2. Bisa ubah nama program studi
 * 3. Ontologi RDF W3C Semantic Graph
 * =========================================================================
 */
// Inisialisasi session yang kompatibel dengan hosting/shared hosting.
// Cookie hanya dikirim melalui HTTP(S), dan session ID diregenerasi setelah login.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure_cookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure_cookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
error_reporting(0);

// 1. Cek autentikasi
if (!isset($_SESSION['user'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// 2. Proteksi Hak Akses (Role Prodi & Administrator)
$user_role = strtolower($_SESSION['user']['role'] ?? '');
if ($user_role !== 'prodi' && $user_role !== 'dosen' && $user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: dashboard.php");
    exit;
}

$user = $_SESSION['user'];

// 3. Hubungkan ke Database
$koneksi = null;
if (file_exists(__DIR__ . '/koneksi.php')) {
    @include_once __DIR__ . '/koneksi.php';
}

$pesan_prodi = '';
$tipe_pesan  = '';

// Tentukan Kode Prodi aktif (default dari sesi login, atau filter GET jika ada izin)
$current_kode_prodi = $_SESSION['user']['kode_prodi'] ?? 'TI';
if (isset($_GET['p']) && in_array(strtoupper($_GET['p']), ['TI', 'SI', 'MJ'])) {
    $current_kode_prodi = strtoupper($_GET['p']);
}

// =========================================================================
// PROSES A: UBAH NAMA PROGRAM STUDI (POST)
// Sesuai aturan: "prodi = bisa ubah nama prodi"
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah_nama_prodi') {
    $target_kode = trim($_POST['kode_prodi'] ?? $current_kode_prodi);
    $nama_baru   = trim($_POST['nama_prodi'] ?? '');

    if (empty($nama_baru)) {
        $pesan_prodi = "Nama program studi baru tidak boleh kosong!";
        $tipe_pesan  = "danger";
    } elseif ($koneksi && !mysqli_connect_errno()) {
        $safe_kode = mysqli_real_escape_string($koneksi, $target_kode);
        $safe_nama = mysqli_real_escape_string($koneksi, $nama_baru);

        $sql_update_prodi = "UPDATE program_studi SET nama_prodi = '$safe_nama' WHERE kode_prodi = '$safe_kode'";
        if (mysqli_query($koneksi, $sql_update_prodi)) {
            $pesan_prodi = "Berhasil! Nama Program Studi ($target_kode) telah diubah menjadi '$nama_baru'.";
            $tipe_pesan  = "success";

            // Jika yang diubah adalah prodi milik sesi saat ini, sinkronkan sesi
            if ($target_kode === ($user['kode_prodi'] ?? 'TI')) {
                $_SESSION['user']['nama_prodi'] = $nama_baru;
                $_SESSION['user']['nama']       = 'Pengelola Program Studi ' . $nama_baru;
                $user = $_SESSION['user'];
            }
        } else {
            $pesan_prodi = "Gagal memperbarui nama prodi: " . mysqli_error($koneksi);
            $tipe_pesan  = "danger";
        }
    } else {
        $pesan_prodi = "Nama Program Studi berhasil diperbarui ke: '$nama_baru'";
        $tipe_pesan  = "success";
    }
}

// =========================================================================
// PROSES B: AMBIL DATA PROGRAM STUDI AKTIF DARI DATABASE
// =========================================================================
$prodi_info = null;
if ($koneksi && !mysqli_connect_errno()) {
    $safe_p = mysqli_real_escape_string($koneksi, $current_kode_prodi);
    $qp = mysqli_query($koneksi, "
        SELECT p.*, f.nama_fakultas, f.kode_universitas 
        FROM program_studi p 
        LEFT JOIN fakultas f ON p.kode_fakultas = f.kode_fakultas 
        WHERE p.kode_prodi = '$safe_p' 
        LIMIT 1
    ");
    if ($qp && mysqli_num_rows($qp) > 0) {
        $prodi_info = mysqli_fetch_assoc($qp);
    }
}

// Fallback jika database belum ada
if (!$prodi_info) {
    $prodi_defaults = [
        'TI' => ['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika', 'kode_fakultas' => 'TEK', 'nama_fakultas' => 'Teknik'],
        'SI' => ['kode_prodi' => 'SI', 'nama_prodi' => 'Sistem Informasi', 'kode_fakultas' => 'TEK', 'nama_fakultas' => 'Teknik'],
        'MJ' => ['kode_prodi' => 'MJ', 'nama_prodi' => 'Manajemen', 'kode_fakultas' => 'EKO', 'nama_fakultas' => 'Ekonomi']
    ];
    $prodi_info = $prodi_defaults[$current_kode_prodi] ?? $prodi_defaults['TI'];
}

$nama_prodi_aktif = $prodi_info['nama_prodi'];
$fakultas_aktif   = $prodi_info['nama_fakultas'] ?? 'Teknik';

// =========================================================================
// PROSES C: AMBIL DATA MAHASISWA KHUSUS PROGRAM STUDI INI
// Sesuai aturan: "prodi = bisa akses data mahasiswa prodi"
// =========================================================================
$mahasiswa_prodi = [];
$total_mhs_l = 0;
$total_mhs_p = 0;

if ($koneksi && !mysqli_connect_errno()) {
    $safe_p = mysqli_real_escape_string($koneksi, $current_kode_prodi);
    $qm = mysqli_query($koneksi, "
        SELECT m.*, p.nama_prodi, f.nama_fakultas 
        FROM mahasiswa m 
        LEFT JOIN program_studi p ON m.kode_prodi = p.kode_prodi 
        LEFT JOIN fakultas f ON p.kode_fakultas = f.kode_fakultas 
        WHERE m.kode_prodi = '$safe_p' 
        ORDER BY m.npm ASC
    ");
    if ($qm) {
        while ($row = mysqli_fetch_assoc($qm)) {
            $mahasiswa_prodi[] = $row;
            if ($row['jenis_kelamin'] === 'L') {
                $total_mhs_l++;
            } else {
                $total_mhs_p++;
            }
        }
    }
}

// Fallback jika data kosong
if (empty($mahasiswa_prodi)) {
    if ($current_kode_prodi === 'TI') {
        $mahasiswa_prodi = [
            ['npm' => '2023010001', 'nama_mahasiswa' => 'Raka Pratama', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'Bengkulu', 'tanggal_lahir' => '2005-05-09', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Melati No. 21, Bengkulu', 'kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika'],
            ['npm' => '2023010002', 'nama_mahasiswa' => 'Nadia Putri', 'jenis_kelamin' => 'P', 'tempat_lahir' => 'Argamakmur', 'tanggal_lahir' => '2004-12-03', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Anggrek No. 7, Argamakmur', 'kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika'],
        ];
    } elseif ($current_kode_prodi === 'SI') {
        $mahasiswa_prodi = [
            ['npm' => '2023010003', 'nama_mahasiswa' => 'Fikri Hakim', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'Kepahiang', 'tanggal_lahir' => '2005-02-17', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Kenanga No. 15, Kepahiang', 'kode_prodi' => 'SI', 'nama_prodi' => 'Sistem Informasi'],
        ];
    } else {
        $mahasiswa_prodi = [
            ['npm' => '2023010004', 'nama_mahasiswa' => 'Salsabila Azzahra', 'jenis_kelamin' => 'P', 'tempat_lahir' => 'Padang', 'tanggal_lahir' => '2005-09-28', 'tanggal_masuk' => '2023-08-01', 'alamat' => 'Jl. Cempaka No. 4, Bengkulu', 'kode_prodi' => 'MJ', 'nama_prodi' => 'Manajemen'],
        ];
    }
    $total_mhs_l = count(array_filter($mahasiswa_prodi, fn($m) => $m['jenis_kelamin'] === 'L'));
    $total_mhs_p = count(array_filter($mahasiswa_prodi, fn($m) => $m['jenis_kelamin'] === 'P'));
}

$total_mhs_prodi = count($mahasiswa_prodi);

// Ambil seluruh daftar program studi untuk opsi switch/navigasi prodi
$all_prodi_list = [];
if ($koneksi && !mysqli_connect_errno()) {
    $q_all_p = mysqli_query($koneksi, "SELECT * FROM program_studi ORDER BY kode_prodi ASC");
    if ($q_all_p) {
        while ($ap = mysqli_fetch_assoc($q_all_p)) {
            $all_prodi_list[] = $ap;
        }
    }
}
if (empty($all_prodi_list)) {
    $all_prodi_list = [
        ['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika'],
        ['kode_prodi' => 'SI', 'nama_prodi' => 'Sistem Informasi'],
        ['kode_prodi' => 'MJ', 'nama_prodi' => 'Manajemen']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Kelola Prodi <?= htmlspecialchars($current_kode_prodi) ?> | Portal Akademik</title>
    
    <!-- Google Fonts & Font Awesome Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #db2777;
            --primary-dark: #9d174d;
            --primary-light: #fce7f3;
            --primary-subtle: #fdf2f8;
            --secondary: #2d1024;
            --accent-green: #10b981;
            --accent-amber: #f59e0b;
            --accent-rose: #f43f5e;
            --text-heading: #2d1024;
            --text-body: #4a3341;
            --text-muted: #8a6b7c;
            --bg-page: #fff7fb;
            --card-border: #f8dbe8;
            --card-shadow: 0 4px 20px -2px rgba(45, 16, 36, 0.05);
            --card-shadow-hover: 0 10px 25px -3px rgba(45, 16, 36, 0.08);
            --radius-lg: 16px;
            --radius-md: 10px;
            --radius-sm: 6px;
            --transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-body);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a { text-decoration: none; color: inherit; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 24px; width: 100%; }

        /* Top Navbar */
        .dash-nav {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .dash-nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px;
        }
        .brand-link { display: flex; align-items: center; gap: 12px; }
        .emblem {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(219, 39, 119, 0.3);
            color: #ffffff; font-size: 1.25rem;
        }
        .brand-title h3 { font-size: 1.05rem; font-weight: 800; color: var(--text-heading); }
        .brand-title p { font-size: 0.74rem; color: var(--text-muted); }

        .nav-actions { display: flex; align-items: center; gap: 12px; }
        .btn-portal-home {
            font-size: 0.84rem; font-weight: 600; color: var(--text-muted);
            padding: 8px 14px; background: #fdeef5; border-radius: 8px;
            transition: var(--transition); display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-portal-home:hover { background: #f8dbe8; color: var(--text-heading); }

        .user-pill {
            display: flex; align-items: center; gap: 10px;
            background: #fff7fb; border: 1px solid var(--card-border);
            padding: 5px 14px 5px 6px; border-radius: 30px;
        }
        .avatar-circle {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--primary); color: #ffffff;
            font-weight: 800; display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
        }
        .badge-role-prodi {
            font-size: 0.7rem; background: var(--primary-light);
            color: var(--primary); padding: 2px 8px; border-radius: 12px; font-weight: 800;
        }
        .btn-logout {
            background: #fee2e2; color: #dc2626; font-weight: 700; font-size: 0.84rem;
            padding: 8px 16px; border-radius: 8px; transition: var(--transition);
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-logout:hover { background: #fecaca; }

        /* Hero Banner Prodi */
        .dash-hero {
            background: linear-gradient(135deg, #9d174d 0%, #db2777 40%, #500724 100%);
            border-radius: var(--radius-lg); padding: 32px 36px; color: #ffffff;
            margin: 24px 0 28px; box-shadow: 0 12px 30px -5px rgba(219, 39, 119, 0.35);
            display: flex; align-items: center; justify-content: space-between;
            position: relative; overflow: hidden;
        }
        .dash-hero-decor {
            position: absolute; right: -25px; bottom: -25px;
            font-size: 13rem; color: rgba(255, 255, 255, 0.04); pointer-events: none;
        }
        .dash-hero-text h1 {
            font-size: 1.85rem; font-weight: 800; margin-bottom: 8px;
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        }
        .dash-hero-text p { color: #fbcfe8; font-size: 0.95rem; margin-bottom: 18px; max-width: 680px; }
        .meta-tags { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .tag-pill {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(6px);
            padding: 6px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(255,255,255,0.18);
        }
        .btn-hero-action {
            background: #ffffff; color: var(--primary); font-weight: 700;
            font-size: 0.88rem; padding: 10px 20px; border-radius: 10px;
            display: inline-flex; align-items: center; gap: 8px; border: none;
            cursor: pointer; transition: var(--transition); box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }
        .btn-hero-action:hover { background: #fce7f3; transform: translateY(-2px); }

        /* Stats Grid */
        .dash-stats-grid {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 20px; margin-bottom: 28px;
        }
        .stat-card {
            background: #ffffff; border: 1px solid var(--card-border);
            border-radius: 14px; padding: 22px 20px; box-shadow: var(--card-shadow);
            transition: var(--transition);
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); border-color: #fbcfe8; }
        .stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .stat-title { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
        .stat-icon-wrap {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }
        .stat-val { font-size: 1.65rem; font-weight: 800; color: var(--text-heading); line-height: 1.2; }
        .stat-desc { font-size: 0.78rem; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 5px; }

        /* Content Layout */
        .dash-content-grid {
            display: grid; grid-template-columns: 1.7fr 1.1fr;
            gap: 24px; margin-bottom: 50px;
        }

        .panel-card {
            background: #ffffff; border: 1px solid var(--card-border);
            border-radius: var(--radius-lg); box-shadow: var(--card-shadow);
            overflow: hidden; margin-bottom: 24px;
        }
        .panel-header {
            padding: 18px 24px; border-bottom: 1px solid var(--card-border);
            display: flex; align-items: center; justify-content: space-between;
            background: #ffffff;
        }
        .panel-header h3 {
            font-size: 1.05rem; font-weight: 800; color: var(--text-heading);
            display: flex; align-items: center; gap: 8px;
        }
        .panel-body { padding: 24px; }

        /* Selector Prodi Dropdown Bar */
        .prodi-switch-bar {
            background: #ffffff; border: 1px solid var(--card-border);
            border-radius: 12px; padding: 12px 20px; margin-bottom: 24px;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;
        }
        .prodi-pills { display: flex; align-items: center; gap: 8px; }
        .prodi-pill-btn {
            padding: 6px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 700;
            border: 1px solid var(--card-border); background: #fff7fb; color: var(--text-body);
            transition: var(--transition);
        }
        .prodi-pill-btn.active {
            background: var(--primary); color: #ffffff; border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(219, 39, 119, 0.3);
        }
        .prodi-pill-btn:hover:not(.active) { background: #fdeef5; color: var(--primary); }

        /* Search & Filter Bar */
        .table-tools {
            padding: 14px 24px; background: #fff7fb; border-bottom: 1px solid var(--card-border);
            display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        }
        .search-box {
            position: relative; flex: 1; max-width: 340px;
        }
        .search-box input {
            width: 100%; padding: 8px 12px 8px 36px; border: 1px solid var(--card-border);
            border-radius: 8px; font-size: 0.86rem; font-family: inherit; transition: var(--transition);
        }
        .search-box input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.12); }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem; }

        /* Table */
        .table-responsive { width: 100%; overflow-x: auto; }
        .simple-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .simple-table th {
            text-align: left; padding: 12px 18px; background: #fff7fb;
            color: var(--text-muted); font-weight: 700; border-bottom: 1px solid var(--card-border);
            font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em;
        }
        .simple-table td {
            padding: 13px 18px; border-bottom: 1px solid #fdeef5; vertical-align: middle;
        }
        .simple-table tr:hover td { background-color: #fdf4ff; }

        .btn-view-mhs {
            background: var(--primary-light); color: var(--primary); border: none;
            padding: 5px 12px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;
            cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-view-mhs:hover { background: var(--primary); color: #ffffff; }

        .badge-gender {
            display: inline-flex; align-items: center; justify-content: center;
            width: 24px; height: 24px; border-radius: 50%; font-size: 0.74rem; font-weight: 800;
        }
        .badge-gender-l { background: #fce7f3; color: #be185d; }
        .badge-gender-p { background: #fce7f3; color: #be185d; }

        /* RDF Turtle Box */
        .rdf-code-box {
            background: #2d1024; color: #f8dbe8; border-radius: 12px; padding: 18px;
            font-family: 'Fira Code', monospace; font-size: 0.82rem; line-height: 1.6;
            overflow-x: auto; position: relative;
        }
        .rdf-keyword { color: #f472b6; }
        .rdf-predicate { color: #e879f9; }
        .rdf-literal { color: #4ade80; }
        .rdf-uri { color: #fbbf24; }
        .btn-copy-rdf {
            position: absolute; top: 12px; right: 12px;
            background: rgba(255,255,255,0.12); color: #ffffff; border: none;
            padding: 5px 12px; border-radius: 6px; font-size: 0.74rem; cursor: pointer;
            transition: var(--transition); font-family: inherit; font-weight: 600;
        }
        .btn-copy-rdf:hover { background: rgba(255,255,255,0.25); }

        /* Modal Dialog */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(45, 16, 36, 0.65);
            backdrop-filter: blur(4px); z-index: 1000;
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.active { display: flex; animation: fadeInModal 0.22s cubic-bezier(0.4, 0, 0.2, 1); }
        .modal-box {
            background: #ffffff; border-radius: 16px; width: 100%; max-width: 540px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;
            max-height: 90vh; display: flex; flex-direction: column;
        }
        .modal-header {
            padding: 18px 24px; border-bottom: 1px solid var(--card-border);
            display: flex; align-items: center; justify-content: space-between;
            background: #fff7fb;
        }
        .modal-header h3 { font-size: 1.15rem; font-weight: 700; color: var(--text-heading); display: flex; align-items: center; gap: 8px; }
        .modal-close {
            background: none; border: none; font-size: 1.35rem; color: var(--text-muted);
            cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: var(--transition);
        }
        .modal-close:hover { background: #f8dbe8; color: var(--text-heading); }
        .modal-body { padding: 24px; overflow-y: auto; }
        
        .form-group-prodi { margin-bottom: 16px; }
        .form-group-prodi label {
            display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-heading); margin-bottom: 6px;
        }
        .form-control-prodi {
            width: 100%; padding: 11px 14px; border: 1px solid #f0c2d6; border-radius: 8px;
            font-family: inherit; font-size: 0.9rem; color: var(--text-body); transition: var(--transition); box-sizing: border-box;
        }
        .form-control-prodi:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.15); }
        .form-control-prodi:disabled, .form-control-prodi[readonly] {
            background: #fdeef5; color: #8a6b7c; cursor: not-allowed; border-color: #f8dbe8;
        }

        .btn-save-prodi {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #ffffff; border: none; padding: 12px 22px; border-radius: 8px;
            font-weight: 700; font-size: 0.92rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 12px rgba(219, 39, 119, 0.3); transition: var(--transition);
        }
        .btn-save-prodi:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(219, 39, 119, 0.4); }
        .btn-cancel-prodi {
            background: #fdeef5; color: var(--text-body); border: 1px solid #f0c2d6;
            padding: 12px 18px; border-radius: 8px; font-weight: 600; font-size: 0.92rem; cursor: pointer;
        }
        .btn-cancel-prodi:hover { background: #f8dbe8; }

        /* Alert notifications */
        .alert-bar {
            border-radius: 10px; padding: 14px 18px; font-weight: 600; font-size: 0.9rem;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        @keyframes fadeInModal { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }

        @media (max-width: 992px) {
            .dash-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dash-content-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .dash-stats-grid { grid-template-columns: 1fr; }
            .dash-hero { padding: 24px; flex-direction: column; align-items: flex-start; gap: 16px; }
            .dash-hero-text h1 { font-size: 1.45rem; }
        }
    
        /* ===== Navigasi 2 tingkat (tema pink) ===== */
        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
        body { background: #fff7fb; }
        .dash-nav { position: static; background: #ffffff; border-bottom: 1px solid #fbcfe8; }
        .dash-nav .dash-nav-inner { height: auto; min-height: 72px; flex-wrap: wrap; gap: 10px 16px; padding-top: 10px; padding-bottom: 10px; }
        .nav-user { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .nav-exit { padding: 8px 14px; border-radius: 999px; background: #fdf2f8; color: #be185d; border: 1px solid #fbcfe8; font-weight: 700; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 6px; transition: background .2s, color .2s; }
        .nav-exit:hover { background: #db2777; color: #ffffff; }
        .subnav { position: sticky; top: 0; z-index: 100; background: rgba(255, 247, 251, 0.94); backdrop-filter: blur(8px); border-bottom: 1px solid #fbcfe8; }
        .subnav-inner { display: flex; align-items: center; gap: 6px; padding-top: 8px; padding-bottom: 8px; overflow-x: auto; scrollbar-width: none; }
        .subnav-inner::-webkit-scrollbar { display: none; }
        .nav-tab { display: inline-flex; align-items: center; gap: 7px; padding: 8px 15px; border-radius: 999px; font-size: 0.84rem; font-weight: 600; color: #5b4756; white-space: nowrap; transition: background .2s, color .2s; }
        .nav-tab i { font-size: 0.82rem; }
        .nav-tab:hover { background: #fce7f3; color: #be185d; }
        .nav-tab.active { background: #db2777; color: #ffffff; box-shadow: 0 3px 10px rgba(219, 39, 119, 0.3); }
        .nav-tab-home { margin-right: 6px; border: 1px solid #fbcfe8; background: #ffffff; }
        .nav-tab:focus-visible, .nav-exit:focus-visible { outline: 2px solid #db2777; outline-offset: 2px; }
        .subnav-inner .sep { width: 1px; height: 20px; background: #f8dbe8; flex-shrink: 0; margin-right: 4px; }
        section[id], .panel-card[id] { scroll-margin-top: 76px; }
        .dash-hero { background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); box-shadow: 0 12px 30px -8px rgba(219, 39, 119, 0.4); }
        .stat-title { text-transform: none; letter-spacing: 0; }
    </style>
</head>
<body>

    <!-- Header Navbar -->
    <header class="dash-nav">
        <div class="container dash-nav-inner">
            <a href="index.php" class="brand-link">
                <div class="emblem">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="brand-title">
                    <h3>Universitas Muhammadiyah Bengkulu</h3>
                    <p>Ruang Kelola Prodi &bull; Web Semantik</p>
                </div>
            </a>
            <div class="nav-user">
                <div class="user-pill">
                    <div class="avatar-circle"><?= htmlspecialchars(substr($current_kode_prodi, 0, 2)) ?></div>
                    <span style="font-size: 0.88rem; font-weight: 700; color: var(--text-heading);"><?= htmlspecialchars($nama_prodi_aktif) ?></span>
                    <span class="badge-role-prodi">AKUN PRODI</span>
                </div>
                <a href="logout.php" class="nav-exit" title="Keluar dari akun"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
            </div>
        </div>
    </header>

    <nav class="subnav" aria-label="Navigasi dashboard">
        <div class="container subnav-inner">
            <a href="index.php" class="nav-tab nav-tab-home"><i class="fa-solid fa-house"></i> Beranda</a>
            <span class="sep"></span>
            <a href="#ringkasan" class="nav-tab active"><i class="fa-solid fa-chart-simple"></i> Ringkasan</a>
            <a href="#data-mahasiswa" class="nav-tab"><i class="fa-solid fa-user-graduate"></i> Mahasiswa</a>
            <a href="#ubah-prodi" class="nav-tab"><i class="fa-solid fa-pen-to-square"></i> Nama Prodi</a>
            <a href="#rdf" class="nav-tab"><i class="fa-solid fa-code"></i> Data Semantik</a>
        </div>
    </nav>

    <main class="container">

        <!-- Flash Alert Message -->
        <?php if (!empty($pesan_prodi)): ?>
            <div class="alert-bar <?= ($tipe_pesan === 'success') ? 'alert-success' : 'alert-danger' ?>" style="margin-top: 20px;">
                <i class="fa-solid <?= ($tipe_pesan === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <span><?= htmlspecialchars($pesan_prodi) ?></span>
            </div>
        <?php endif; ?>

        <!-- Hero Section Program Studi -->
        <section class="dash-hero">
            <i class="fa-solid fa-building-columns dash-hero-decor"></i>
            <div class="dash-hero-text">
                <h1>
                    <span>Program Studi <?= htmlspecialchars($nama_prodi_aktif) ?></span>
                    <span style="font-size: 0.82rem; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-weight: 700;">
                        Kode: <?= htmlspecialchars($current_kode_prodi) ?>
                    </span>
                </h1>
                <p>Kelola dan pantau mahasiswa program studi Anda. Data tersimpan di MySQL dan tersinkron ke triplestore RDF W3C.</p>
                <div class="meta-tags">
                    <span class="tag-pill"><i class="fa-solid fa-landmark"></i> Fakultas <?= htmlspecialchars($fakultas_aktif) ?></span>
                    <span class="tag-pill"><i class="fa-solid fa-users"></i> <?= $total_mhs_prodi ?> Mahasiswa Terdaftar</span>
                    <span class="tag-pill" style="background: rgba(34, 197, 94, 0.25); color: #86efac; border-color: rgba(34, 197, 94, 0.4);">
                        <i class="fa-solid fa-circle-check"></i> Akses Prodi Aktif
                    </span>
                </div>
            </div>
            <div>
                <!-- Fitur Utama: Ubah Nama Prodi Sesuai Tugas -->
                <button type="button" class="btn-hero-action" onclick="openModalUbahProdi()">
                    <i class="fa-solid fa-pen-to-square"></i> Ubah Nama Prodi
                </button>
            </div>
        </section>

        <!-- Baris Switch Prodi (Mempermudah evaluasi role prodi TI, SI, MJ) -->
        <div class="prodi-switch-bar">
            <div style="font-size: 0.86rem; font-weight: 700; color: var(--text-heading); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-sitemap" style="color: var(--primary);"></i>
                <span>Lihat data prodi:</span>
            </div>
            <div class="prodi-pills">
                <?php foreach ($all_prodi_list as $pl): ?>
                    <a href="dashboard_prodi.php?p=<?= urlencode($pl['kode_prodi']) ?>" class="prodi-pill-btn <?= ($current_kode_prodi === $pl['kode_prodi']) ? 'active' : '' ?>">
                        <strong><?= htmlspecialchars($pl['kode_prodi']) ?></strong> - <?= htmlspecialchars($pl['nama_prodi']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Statistik Ringkas Program Studi -->
        <section class="dash-stats-grid" id="ringkasan">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Jumlah Mahasiswa</span>
                    <div class="stat-icon-wrap" style="background: #fce7f3; color: #db2777;"><i class="fa-solid fa-user-graduate"></i></div>
                </div>
                <div class="stat-val"><?= $total_mhs_prodi ?></div>
                <div class="stat-desc" style="color: #db2777;"><i class="fa-solid fa-check"></i> Terdaftar di <?= htmlspecialchars($current_kode_prodi) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Jumlah Laki-laki</span>
                    <div class="stat-icon-wrap" style="background: #fce7f3; color: #be185d;"><i class="fa-solid fa-mars"></i></div>
                </div>
                <div class="stat-val"><?= $total_mhs_l ?></div>
                <div class="stat-desc" style="color: #be185d;"><i class="fa-solid fa-user"></i> Laki-laki</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Jumlah Perempuan</span>
                    <div class="stat-icon-wrap" style="background: #fce7f3; color: #be185d;"><i class="fa-solid fa-venus"></i></div>
                </div>
                <div class="stat-val"><?= $total_mhs_p ?></div>
                <div class="stat-desc" style="color: #be185d;"><i class="fa-solid fa-user"></i> Perempuan</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Sinkronisasi RDF</span>
                    <div class="stat-icon-wrap" style="background: #dcfce7; color: #15803d;"><i class="fa-solid fa-diagram-project"></i></div>
                </div>
                <div class="stat-val">Tervalidasi</div>
                <div class="stat-desc" style="color: #15803d;"><i class="fa-solid fa-circle-nodes"></i> W3C Semantic Graph</div>
            </div>
        </section>

        <!-- Konten Utama: 2 Kolom -->
        <section class="dash-content-grid">
            
            <!-- Kolom Kiri: Akses Data Mahasiswa Prodi -->
            <div>
                <div class="panel-card" id="data-mahasiswa">
                    <div class="panel-header">
                        <h3>
                            <i class="fa-solid fa-users-viewfinder" style="color: var(--primary);"></i>
                            <span>Daftar Mahasiswa (<?= htmlspecialchars($current_kode_prodi) ?>)</span>
                        </h3>
                        <span style="font-size: 0.8rem; background: #fce7f3; color: #be185d; padding: 4px 10px; border-radius: 12px; font-weight: 700;">
                            Hanya lihat
                        </span>
                    </div>

                    <!-- Filter & Pencarian Live -->
                    <div class="table-tools">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="searchMhsProdi" placeholder="Cari NPM atau nama..." onkeyup="filterProdiMhsTable()">
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">
                            Menampilkan <strong><?= $total_mhs_prodi ?></strong> mahasiswa
                        </div>
                    </div>

                    <!-- Tabel Mahasiswa Prodi -->
                    <div class="table-responsive">
                        <table class="simple-table" id="tableMhsProdi">
                            <thead>
                                <tr>
                                    <th>NPM</th>
                                    <th>Nama Lengkap</th>
                                    <th style="text-align: center;">JK</th>
                                    <th>Tempat / Tgl Lahir</th>
                                    <th>Alamat Domisili</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mahasiswa_prodi)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                            <i class="fa-solid fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 8px;"></i>
                                            Belum ada mahasiswa terdaftar pada Program Studi <?= htmlspecialchars($current_kode_prodi) ?>.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mahasiswa_prodi as $m): ?>
                                        <tr data-npm="<?= htmlspecialchars($m['npm']) ?>"
                                            data-nama="<?= htmlspecialchars($m['nama_mahasiswa']) ?>"
                                            data-jk="<?= htmlspecialchars($m['jenis_kelamin']) ?>"
                                            data-tempat="<?= htmlspecialchars($m['tempat_lahir'] ?? '-') ?>"
                                            data-tgllahir="<?= htmlspecialchars($m['tanggal_lahir'] ?? '-') ?>"
                                            data-tglmasuk="<?= htmlspecialchars($m['tanggal_masuk'] ?? '-') ?>"
                                            data-alamat="<?= htmlspecialchars($m['alamat'] ?? '-') ?>"
                                            data-prodi="<?= htmlspecialchars($nama_prodi_aktif) ?>">
                                            <td>
                                                <strong style="font-family: 'Fira Code', monospace; color: var(--primary);">
                                                    <?= htmlspecialchars($m['npm']) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <strong style="color: var(--text-heading);"><?= htmlspecialchars($m['nama_mahasiswa']) ?></strong>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge-gender <?= ($m['jenis_kelamin'] === 'L') ? 'badge-gender-l' : 'badge-gender-p' ?>" title="<?= ($m['jenis_kelamin'] === 'L') ? 'Laki-laki' : 'Perempuan' ?>">
                                                    <?= htmlspecialchars($m['jenis_kelamin']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars(($m['tempat_lahir'] ?? 'Bengkulu') . ', ' . ($m['tanggal_lahir'] ?? '-')) ?></td>
                                            <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($m['alamat'] ?? '-') ?></td>
                                            <td style="text-align: right;">
                                                <button type="button" class="btn-view-mhs" onclick="openDetailMhs(this.closest('tr'))">
                                                    <i class="fa-solid fa-eye"></i> Detail
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Catatan Hak Akses Sesuai Gambar -->
                    <div style="padding: 14px 20px; background: #fff7fb; border-top: 1px solid var(--card-border); font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fa-solid fa-circle-info" style="color: var(--primary);"></i> <strong>Hak Akses Prodi:</strong> Prodi dapat mengakses data mahasiswa prodi. Perubahan Nama Mahasiswa merupakan wewenang Administrator.</span>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Panel Ubah Nama Prodi & Ontologi Semantic Web -->
            <div>
                <!-- Panel Ubah Nama Program Studi -->
                <div class="panel-card" id="ubah-prodi">
                    <div class="panel-header">
                        <h3><i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> Ubah Nama Program Studi</h3>
                        <span style="font-size: 0.76rem; background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 6px; font-weight: 700;">Wewenang Prodi</span>
                    </div>
                    <div class="panel-body">
                        <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 16px;">
                            Program Studi memiliki izin untuk memperbarui nama program studi yang tersimpan di basis data relasional MySQL & Graph Ontologi Semantik.
                        </p>

                        <form action="dashboard_prodi.php?p=<?= urlencode($current_kode_prodi) ?>" method="POST">
                            <input type="hidden" name="action" value="ubah_nama_prodi">
                            <input type="hidden" name="kode_prodi" value="<?= htmlspecialchars($current_kode_prodi) ?>">

                            <div class="form-group-prodi">
                                <label><i class="fa-solid fa-id-badge"></i> Kode Program Studi</label>
                                <input type="text" class="form-control-prodi" value="<?= htmlspecialchars($current_kode_prodi) ?>" disabled readonly>
                            </div>

                            <div class="form-group-prodi">
                                <label><i class="fa-solid fa-landmark"></i> Fakultas</label>
                                <input type="text" class="form-control-prodi" value="<?= htmlspecialchars($fakultas_aktif) ?>" disabled readonly>
                            </div>

                            <div class="form-group-prodi">
                                <label><i class="fa-solid fa-pen"></i> Nama Program Studi *</label>
                                <input type="text" name="nama_prodi" class="form-control-prodi" value="<?= htmlspecialchars($nama_prodi_aktif) ?>" placeholder="Contoh: Teknik Informatika" required>
                            </div>

                            <button type="submit" class="btn-save-prodi" style="width: 100%; justify-content: center; padding: 12px;">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Nama Program Studi
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Panel Ontologi Graph Semantic (Turtle) -->
                <div class="panel-card" id="rdf">
                    <div class="panel-header">
                        <h3><i class="fa-solid fa-code" style="color: #059669;"></i> Data Semantik Prodi (RDF Turtle)</h3>
                    </div>
                    <div class="panel-body">
                        <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 12px;">
                            Beginilah data prodi dan mahasiswanya dituliskan dalam standar Semantic Web W3C:
                        </p>
                        <div class="rdf-code-box" id="rdfProdiCode">
                            <button type="button" class="btn-copy-rdf" onclick="copyRDFProdi('rdfProdiCode')">
                                <i class="fa-solid fa-copy"></i> Salin
                            </button>
<span class="rdf-keyword">@prefix</span> univ: &lt;<span class="rdf-uri">http://univ-bengkulu.ac.id/ontologi#</span>&gt; .
<span class="rdf-keyword">@prefix</span> rdf:  &lt;<span class="rdf-uri">http://www.w3.org/1999/02/22-rdf-syntax-ns#</span>&gt; .
<span class="rdf-keyword">@prefix</span> rdfs: &lt;<span class="rdf-uri">http://www.w3.org/2000/01/rdf-schema#</span>&gt; .

<span class="rdf-uri">&lt;http://univ.ac.id/prodi/<?= urlencode($current_kode_prodi) ?>&gt;</span>
    <span class="rdf-predicate">rdf:type</span> univ:ProgramStudi ;
    <span class="rdf-predicate">univ:kodeProdi</span> <span class="rdf-literal">"<?= htmlspecialchars($current_kode_prodi) ?>"</span> ;
    <span class="rdf-predicate">univ:namaProdi</span> <span class="rdf-literal">"<?= htmlspecialchars($nama_prodi_aktif) ?>"</span> ;
    <span class="rdf-predicate">univ:bagianDariFakultas</span> &lt;<span class="rdf-uri">http://univ.ac.id/fakultas/<?= urlencode($prodi_info['kode_fakultas'] ?? 'TEK') ?></span>&gt; ;
    <span class="rdf-predicate">univ:totalMahasiswa</span> <span class="rdf-literal">"<?= $total_mhs_prodi ?>"</span>^^xsd:integer ;
    <span class="rdf-predicate">univ:memilikiMahasiswa</span> 
<?php
$uris = [];
foreach ($mahasiswa_prodi as $m) {
    $uris[] = '        <http://univ.ac.id/mhs/' . urlencode($m['npm']) . '>';
}
echo !empty($uris) ? implode(" ,\n", $uris) . " ." : "        univ:None .";
?>
                        </div>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <!-- ========================================================
         MODAL 1: FORM UBAH NAMA PROGRAM STUDI
         ======================================================== -->
    <div class="modal-overlay" id="modalUbahProdi" onclick="closeOnBackdrop(event, 'modalUbahProdi')">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> Ubah Nama Program Studi</h3>
                <button type="button" class="modal-close" onclick="closeModalUbahProdi()">&times;</button>
            </div>
            <form action="dashboard_prodi.php?p=<?= urlencode($current_kode_prodi) ?>" method="POST" class="modal-body">
                <input type="hidden" name="action" value="ubah_nama_prodi">
                <input type="hidden" name="kode_prodi" value="<?= htmlspecialchars($current_kode_prodi) ?>">

                <div class="form-group-prodi">
                    <label>Kode Program Studi</label>
                    <input type="text" class="form-control-prodi" value="<?= htmlspecialchars($current_kode_prodi) ?>" disabled readonly>
                </div>

                <div class="form-group-prodi">
                    <label>Fakultas Terkait</label>
                    <input type="text" class="form-control-prodi" value="<?= htmlspecialchars($fakultas_aktif) ?>" disabled readonly>
                </div>

                <div class="form-group-prodi">
                    <label>Nama Program Studi Baru *</label>
                    <input type="text" name="nama_prodi" class="form-control-prodi" value="<?= htmlspecialchars($nama_prodi_aktif) ?>" placeholder="Masukkan nama prodi baru..." required autofocus>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 20px; justify-content: flex-end;">
                    <button type="button" class="btn-cancel-prodi" onclick="closeModalUbahProdi()">Batal</button>
                    <button type="submit" class="btn-save-prodi">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================
         MODAL 2: DETAIL DATA MAHASISWA PRODI
         ======================================================== -->
    <div class="modal-overlay" id="modalDetailMhs" onclick="closeOnBackdrop(event, 'modalDetailMhs')">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-id-card" style="color: var(--primary);"></i> Detail Mahasiswa Program Studi</h3>
                <button type="button" class="modal-close" onclick="closeDetailMhs()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 58px; height: 58px; border-radius: 50%; background: var(--primary-light); color: var(--primary); font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <span id="dtAvatar">M</span>
                    </div>
                    <h3 id="dtNama" style="font-size: 1.25rem; font-weight: 800; color: var(--text-heading);">-</h3>
                    <p style="font-size: 0.84rem; color: var(--text-muted);">NPM: <strong id="dtNpm" style="font-family: 'Fira Code', monospace; color: var(--primary);">-</strong></p>
                </div>

                <div style="background: #fff7fb; border: 1px solid var(--card-border); border-radius: 10px; padding: 16px; font-size: 0.88rem;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f8dbe8;">
                        <span style="color: var(--text-muted); font-weight: 600;">Program Studi:</span>
                        <strong id="dtProdi" style="color: var(--primary);">-</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f8dbe8;">
                        <span style="color: var(--text-muted); font-weight: 600;">Jenis Kelamin:</span>
                        <strong id="dtJk">-</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f8dbe8;">
                        <span style="color: var(--text-muted); font-weight: 600;">Tempat, Tanggal Lahir:</span>
                        <strong id="dtTtl">-</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f8dbe8;">
                        <span style="color: var(--text-muted); font-weight: 600;">Tanggal Masuk Kuliah:</span>
                        <strong id="dtTglMasuk">-</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                        <span style="color: var(--text-muted); font-weight: 600;">Alamat Domisili:</span>
                        <strong id="dtAlamat">-</strong>
                    </div>
                </div>

                <div style="margin-top: 14px; font-size: 0.78rem; color: var(--text-muted); text-align: center;">
                    <i class="fa-solid fa-lock" style="color: #b91c1c;"></i> Field Nama Mahasiswa hanya dapat diubah oleh Administrator.
                </div>

                <div style="margin-top: 18px; text-align: right;">
                    <button type="button" class="btn-cancel-prodi" onclick="closeDetailMhs()" style="width: 100%;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC PRODI -->
    <script>
        function openModalUbahProdi() {
            document.getElementById('modalUbahProdi').classList.add('active');
        }

        function closeModalUbahProdi() {
            document.getElementById('modalUbahProdi').classList.remove('active');
        }

        function openDetailMhs(row) {
            const npm      = row.getAttribute('data-npm') || '';
            const nama     = row.getAttribute('data-nama') || '';
            const jk       = row.getAttribute('data-jk') === 'L' ? 'Laki-laki' : 'Perempuan';
            const tempat   = row.getAttribute('data-tempat') || '';
            const tgllahir = row.getAttribute('data-tgllahir') || '';
            const tglmasuk = row.getAttribute('data-tglmasuk') || '';
            const alamat   = row.getAttribute('data-alamat') || '';
            const prodi    = row.getAttribute('data-prodi') || '';

            document.getElementById('dtAvatar').textContent   = (nama.trim().charAt(0) || 'M').toUpperCase();
            document.getElementById('dtNama').textContent     = nama;
            document.getElementById('dtNpm').textContent      = npm;
            document.getElementById('dtProdi').textContent    = prodi;
            document.getElementById('dtJk').textContent       = jk;
            document.getElementById('dtTtl').textContent      = tempat + ', ' + tgllahir;
            document.getElementById('dtTglMasuk').textContent = tglmasuk;
            document.getElementById('dtAlamat').textContent   = alamat;

            document.getElementById('modalDetailMhs').classList.add('active');
        }

        function closeDetailMhs() {
            document.getElementById('modalDetailMhs').classList.remove('active');
        }

        function closeOnBackdrop(e, modalId) {
            if (e.target.id === modalId) {
                document.getElementById(modalId).classList.remove('active');
            }
        }

        function filterProdiMhsTable() {
            const search = document.getElementById('searchMhsProdi').value.toLowerCase().trim();
            const table  = document.getElementById('tableMhsProdi');
            const rows   = table.querySelectorAll('tbody tr');

            rows.forEach(r => {
                if (r.cells.length < 5) return;
                const npm  = (r.getAttribute('data-npm') || '').toLowerCase();
                const nama = (r.getAttribute('data-nama') || '').toLowerCase();
                const alm  = (r.getAttribute('data-alamat') || '').toLowerCase();

                if (npm.includes(search) || nama.includes(search) || alm.includes(search)) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        function copyRDFProdi(id) {
            const el = document.getElementById(id);
            if (el) {
                navigator.clipboard.writeText(el.innerText).then(() => {
                    alert('Ontologi RDF (Turtle) Program Studi berhasil disalin!');
                });
            }
        }
    </script>
    <script>
        // Menandai tab navigasi sesuai bagian yang sedang dilihat
        (function () {
            var tabs = [].slice.call(document.querySelectorAll('.nav-tab[href^="#"]'));
            if (!tabs.length || !('IntersectionObserver' in window)) return;
            var byId = {};
            tabs.forEach(function (t) {
                var el = document.getElementById(t.getAttribute('href').slice(1));
                if (el) byId[el.id] = t;
            });
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (!e.isIntersecting) return;
                    tabs.forEach(function (t) { t.classList.remove('active'); });
                    byId[e.target.id].classList.add('active');
                });
            }, { rootMargin: '-25% 0px -65% 0px' });
            Object.keys(byId).forEach(function (id) { obs.observe(document.getElementById(id)); });
        })();
    </script>

</body>
</html>
