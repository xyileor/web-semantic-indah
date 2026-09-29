<?php
/**
 * =========================================================================
 * DASHBOARD MAHASISWA - PORTAL AKADEMIK & WEB SEMANTIK
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

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
error_reporting(0);

// Cek autentikasi
if (!isset($_SESSION['user'])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

// Proteksi Hak Akses (Hanya Role Mahasiswa yang diizinkan mengakses halaman ini)
$user_role = strtolower($_SESSION['user']['role'] ?? '');
if ($user_role !== 'mahasiswa') {
    header("Location: dashboard.php");
    exit;
}

$user = $_SESSION['user'];

// Koneksi ke database
$koneksi = null;
if (file_exists(__DIR__ . '/koneksi.php')) {
    @include_once __DIR__ . '/koneksi.php';
}

$npm = $user['npm'] ?? '2023010001';
$pesan_mhs = '';
$tipe_pesan = '';

// =========================================================================
// PROSES: MAHASISWA MENGUBAH DATA (KECUALI NAMA, NPM, DAN KODE PRODI)
// Aturan: Mahasiswa HANYA boleh mengubah data selain nama, npm, dan kode prodi!
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_biodata_mhs') {
    $jk_post       = trim($_POST['jenis_kelamin'] ?? 'L');
    $tempat_post   = trim($_POST['tempat_lahir'] ?? '');
    $tgl_post      = trim($_POST['tanggal_lahir'] ?? '');
    $masuk_post    = trim($_POST['tanggal_masuk'] ?? '');
    $alamat_post   = trim($_POST['alamat'] ?? '');
    $password_post = trim($_POST['password'] ?? '');

    // Validasi jenis kelamin
    $jk_clean = ($jk_post === 'P') ? 'P' : 'L';

    if ($koneksi && !mysqli_connect_errno() && !empty($npm)) {
        $safe_npm   = mysqli_real_escape_string($koneksi, $npm);
        $safe_jk    = mysqli_real_escape_string($koneksi, $jk_clean);
        $safe_tmp   = mysqli_real_escape_string($koneksi, $tempat_post);
        $safe_tgl   = mysqli_real_escape_string($koneksi, $tgl_post);
        $safe_msk   = mysqli_real_escape_string($koneksi, $masuk_post);
        $safe_alm   = mysqli_real_escape_string($koneksi, $alamat_post);

        // CATATAN KRUSIAL: Kolom nama_mahasiswa, npm, dan kode_prodi 
        // sengaja TIDAK dimasukkan ke dalam klausa UPDATE.
        // Ini menjamin integritas sesuai aturan: Mahasiswa tidak dapat mengubah nama, npm, dan kode prodi.
        $sql_update = "UPDATE mahasiswa SET 
                        jenis_kelamin = '$safe_jk',
                        tempat_lahir = '$safe_tmp',
                        tanggal_lahir = '$safe_tgl',
                        tanggal_masuk = '$safe_msk',
                        alamat = '$safe_alm'";

        if (!empty($password_post)) {
            $safe_pwd = mysqli_real_escape_string($koneksi, $password_post);
            $sql_update .= ", password = '$safe_pwd'";
        }

        $sql_update .= " WHERE npm = '$safe_npm'";

        if (mysqli_query($koneksi, $sql_update)) {
            $pesan_mhs = "Data biodata berhasil diperbarui! (Sesuai aturan hak akses, Nama, NPM, dan Kode Prodi tetap terkunci).";
            $tipe_pesan = "success";

            // Sinkronisasi data user di sesi aktif
            $_SESSION['user']['jenis_kelamin'] = ($safe_jk === 'L') ? 'Laki-laki' : 'Perempuan';
            $_SESSION['user']['tempat_lahir']  = $safe_tmp;
            $_SESSION['user']['tanggal_lahir'] = $safe_tgl;
            $_SESSION['user']['tanggal_masuk'] = $safe_msk;
            $_SESSION['user']['alamat']        = $safe_alm;
        } else {
            $pesan_mhs = "Gagal memperbarui data: " . mysqli_error($koneksi);
            $tipe_pesan = "danger";
        }
    } else {
        $pesan_mhs = "Data biodata berhasil disimpan!";
        $tipe_pesan = "success";
    }
}

// Ambil data terbaru mahasiswa dari database
$mhs_db = null;
if ($koneksi && !mysqli_connect_errno()) {
    $safe_npm = mysqli_real_escape_string($koneksi, $npm);
    $q = mysqli_query($koneksi, "
        SELECT m.*, p.nama_prodi, f.nama_fakultas 
        FROM mahasiswa m
        LEFT JOIN program_studi p ON m.kode_prodi = p.kode_prodi
        LEFT JOIN fakultas f ON p.kode_fakultas = f.kode_fakultas
        WHERE m.npm = '$safe_npm'
        LIMIT 1
    ");
    if ($q && mysqli_num_rows($q) > 0) {
        $mhs_db = mysqli_fetch_assoc($q);
    }
}

// Gunakan data database jika ada
$nama_mhs    = $mhs_db['nama_mahasiswa'] ?? $user['nama'];
$prodi_mhs   = $mhs_db['nama_prodi'] ?? ($user['prodi'] ?? 'Teknik Informatika');
$fakultas_mhs= $mhs_db['nama_fakultas'] ?? ($user['fakultas'] ?? 'Teknik');
$jk_mhs      = ($mhs_db['jenis_kelamin'] === 'L' || ($user['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'Laki-laki' : 'Perempuan';
$tempat_mhs  = $mhs_db['tempat_lahir'] ?? ($user['tempat_lahir'] ?? 'Bengkulu');
$tgl_mhs     = $mhs_db['tanggal_lahir'] ?? ($user['tanggal_lahir'] ?? '2005-03-12');
$tgl_masuk   = $mhs_db['tanggal_masuk'] ?? ($user['tanggal_masuk'] ?? '2023-08-01');
$alamat_mhs  = $mhs_db['alamat'] ?? ($user['alamat'] ?? 'Kota Bengkulu');

// Ambil presensi mahasiswa dari database
$presensi_mhs = [];
if ($koneksi && !mysqli_connect_errno()) {
    $qp_mhs = mysqli_query($koneksi, "SELECT kode_mk, status, COUNT(*) as jml FROM presensi_kelas WHERE npm='$safe_npm' GROUP BY kode_mk, status");
    if ($qp_mhs) {
        while ($pr = mysqli_fetch_assoc($qp_mhs)) {
            $presensi_mhs[$pr['kode_mk']][$pr['status']] = intval($pr['jml']);
        }
    }
}

// Ambil nilai mahasiswa dari database
$nilai_mhs = [];
if ($koneksi && !mysqli_connect_errno()) {
    $qn_mhs = mysqli_query($koneksi, "SELECT * FROM nilai_mahasiswa WHERE npm='$safe_npm'");
    if ($qn_mhs) {
        while ($nr = mysqli_fetch_assoc($qn_mhs)) {
            $nilai_mhs[$nr['kode_mk']] = $nr;
        }
    }
}

// Ambil rekan mahasiswa
$rekan = [];
if ($koneksi && !mysqli_connect_errno()) {
    $rq = mysqli_query($koneksi, "SELECT m.npm, m.nama_mahasiswa, p.nama_prodi FROM mahasiswa m LEFT JOIN program_studi p ON m.kode_prodi = p.kode_prodi LIMIT 5");
    if ($rq) {
        while ($r = mysqli_fetch_assoc($rq)) {
            $rekan[] = $r;
        }
    }
}
if (empty($rekan)) {
    $rekan = [
        ['npm' => '2023010001', 'nama_mahasiswa' => 'Raka Pratama', 'nama_prodi' => 'Teknik Informatika'],
        ['npm' => '2023010002', 'nama_mahasiswa' => 'Nadia Putri', 'nama_prodi' => 'Teknik Informatika'],
        ['npm' => '2023010003', 'nama_mahasiswa' => 'Fikri Hakim', 'nama_prodi' => 'Sistem Informasi']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Mahasiswa - <?= htmlspecialchars($nama_mhs) ?> | Portal Akademik</title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #db2777;
            --primary-dark: #be185d;
            --primary-light: #fce7f3;
            --secondary-navy: #500724;
            --accent-green: #10b981;
            --accent-purple: #a21caf;
            --accent-amber: #f59e0b;
            --text-heading: #3b0a24;
            --text-body: #5b4756;
            --text-muted: #8a6b7c;
            --bg-page: #fff7fb;
            --card-border: #f8dbe8;
            --card-shadow: 0 4px 20px -2px rgba(45, 16, 36, 0.06);
            --transition: all 0.25s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-page); color: var(--text-body); line-height: 1.6; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1240px; margin: 0 auto; padding: 0 24px; }



        /* Top Navbar */
        .dash-nav { background: #ffffff; border-bottom: 1px solid var(--card-border); position: sticky; top: 0; z-index: 100; }
        .dash-nav-inner { display: flex; align-items: center; justify-content: space-between; height: 70px; }
        .brand-link { display: flex; align-items: center; gap: 12px; }
        .emblem {
            width: 42px; height: 42px; border-radius: 50%;
            background: linear-gradient(135deg, #db2777, #be185d);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 10px rgba(219, 39, 119, 0.25);
        }
        .brand-title h3 { font-size: 1.05rem; font-weight: 700; color: var(--text-heading); }
        .brand-title p { font-size: 0.72rem; color: var(--text-muted); }

        .user-menu-box { display: flex; align-items: center; gap: 14px; }
        .user-pill {
            display: flex; align-items: center; gap: 10px;
            background: #fff7fb; border: 1px solid var(--card-border);
            padding: 6px 14px 6px 8px; border-radius: 30px;
        }
        .avatar-circle {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--primary); color: #ffffff;
            font-weight: 700; display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem;
        }
        .btn-logout {
            background: #fee2e2; color: #dc2626; font-weight: 600; font-size: 0.85rem;
            padding: 8px 16px; border-radius: 8px; transition: var(--transition);
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-logout:hover { background: #fecaca; }

        /* Hero Banner */
        .dash-hero {
            background: linear-gradient(135deg, #db2777 0%, #be185d 100%);
            border-radius: 16px; padding: 32px 36px; color: #ffffff;
            margin: 24px 0; box-shadow: 0 10px 30px -5px rgba(219, 39, 119, 0.3);
            display: flex; align-items: center; justify-content: space-between;
            position: relative; overflow: hidden;
        }
        .dash-hero-decor {
            position: absolute; right: -20px; bottom: -20px;
            font-size: 11rem; color: rgba(255, 255, 255, 0.05); pointer-events: none;
        }
        .dash-hero-text h1 { font-size: 1.85rem; font-weight: 800; margin-bottom: 8px; }
        .dash-hero-text p { color: #fbcfe8; font-size: 0.95rem; margin-bottom: 18px; }
        .meta-tags { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .tag-pill {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(5px);
            padding: 6px 14px; border-radius: 20px; font-size: 0.82rem; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px;
        }

        /* Stat Grid */
        .dash-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
        .stat-card {
            background: #ffffff; border: 1px solid var(--card-border);
            border-radius: 14px; padding: 22px 20px; box-shadow: var(--card-shadow);
            transition: var(--transition);
        }
        .stat-card:hover { transform: translateY(-3px); border-color: #f9a8d4; }
        .stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .stat-title { font-size: 0.82rem; font-weight: 700; color: var(--text-muted); }
        .stat-icon-wrap {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 1rem;
        }
        .stat-val { font-size: 1.55rem; font-weight: 800; color: var(--text-heading); line-height: 1.2; }
        .stat-desc { font-size: 0.78rem; font-weight: 600; margin-top: 4px; display: flex; align-items: center; gap: 4px; }

        /* Main Content Grid */
        .dash-content-grid { display: grid; grid-template-columns: 1.4fr 0.9fr; gap: 24px; margin-bottom: 50px; }
        .panel-card {
            background: #ffffff; border: 1px solid var(--card-border);
            border-radius: 14px; box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 24px;
        }
        .panel-header {
            padding: 18px 24px; border-bottom: 1px solid var(--card-border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .panel-header h3 { font-size: 1.05rem; font-weight: 700; color: var(--text-heading); display: flex; align-items: center; gap: 8px; }
        .panel-body { padding: 24px; }

        .info-row { display: flex; padding: 12px 0; border-bottom: 1px solid #fdeef5; font-size: 0.88rem; }
        .info-row:last-child { border-bottom: none; }
        .info-label { width: 170px; font-weight: 600; color: var(--text-muted); flex-shrink: 0; }
        .info-val { font-weight: 600; color: var(--text-heading); }

        .simple-table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
        .simple-table th { text-align: left; padding: 11px 14px; background: #fff7fb; color: var(--text-muted); font-weight: 600; border-bottom: 1px solid var(--card-border); }
        .simple-table td { padding: 12px 14px; border-bottom: 1px solid #fdeef5; }

        .rdf-code-box {
            background: #2d1024; color: #f8dbe8; border-radius: 10px; padding: 18px;
            font-family: 'Fira Code', monospace; font-size: 0.82rem; line-height: 1.6;
            overflow-x: auto; position: relative;
        }
        .rdf-keyword { color: #f472b6; }
        .rdf-predicate { color: #e879f9; }
        .rdf-literal { color: #4ade80; }
        .rdf-uri { color: #fbbf24; }
        .btn-copy {
            position: absolute; top: 10px; right: 10px;
            background: rgba(255,255,255,0.12); color: #ffffff; border: none;
            padding: 4px 10px; border-radius: 6px; font-size: 0.74rem; cursor: pointer;
            transition: var(--transition);
        }
        .btn-copy:hover { background: rgba(255,255,255,0.25); }

        @media (max-width: 992px) {
            .dash-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dash-content-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .dash-stats-grid { grid-template-columns: 1fr; }
            .dash-hero { padding: 24px; }
            .dash-hero-text h1 { font-size: 1.4rem; }
        }

        /* Modal Ubah Data Mahasiswa */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(45, 16, 36, 0.65);
            backdrop-filter: blur(4px); z-index: 1000;
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.active { display: flex; animation: fadeInModal 0.22s cubic-bezier(0.4, 0, 0.2, 1); }
        .modal-box {
            background: #ffffff; border-radius: 16px; width: 100%; max-width: 620px;
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
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
        .form-group-mhs { margin-bottom: 14px; }
        .form-group-mhs label {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 0.82rem; font-weight: 700; color: var(--text-heading); margin-bottom: 6px;
        }
        .form-control-mhs {
            width: 100%; padding: 10px 12px; border: 1px solid #f0c2d6; border-radius: 8px;
            font-family: inherit; font-size: 0.88rem; color: var(--text-body); transition: var(--transition); box-sizing: border-box;
        }
        .form-control-mhs:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.15); }
        .form-control-mhs:disabled, .form-control-mhs[readonly] {
            background: #fdeef5; color: #8a6b7c; cursor: not-allowed; border-color: #f8dbe8;
        }
        .badge-locked {
            font-size: 0.68rem; font-weight: 700; background: #fee2e2; color: #b91c1c;
            padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;
        }
        .badge-editable {
            font-size: 0.68rem; font-weight: 700; background: #dcfce7; color: #15803d;
            padding: 2px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-edit-trigger {
            background: #fce7f3; color: var(--primary); border: 1px solid #fbcfe8;
            padding: 6px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 700;
            cursor: pointer; transition: var(--transition); display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-edit-trigger:hover { background: var(--primary); color: #ffffff; }
        .btn-save-mhs {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #ffffff; border: none; padding: 11px 20px; border-radius: 8px;
            font-weight: 700; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 12px rgba(219, 39, 119, 0.25); transition: var(--transition);
        }
        .btn-save-mhs:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(219, 39, 119, 0.35); }
        .btn-cancel-mhs {
            background: #fdeef5; color: var(--text-body); border: 1px solid #f0c2d6;
            padding: 11px 18px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer;
        }
        .btn-cancel-mhs:hover { background: #f8dbe8; }
        @keyframes fadeInModal { from { opacity: 0; transform: scale(0.96); } to { opacity: 1; transform: scale(1); } }
    
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
                    <i class="fa-solid fa-graduation-cap" style="color: #fcd34d; font-size: 1.15rem;"></i>
                </div>
                <div class="brand-title">
                    <h3>Universitas Muhammadiyah Bengkulu</h3>
                    <p>Ruang Mahasiswa &bull; Web Semantik</p>
                </div>
            </a>
            <div class="nav-user">
                <div class="user-pill">
                    <div class="avatar-circle"><?= strtoupper(substr($nama_mhs, 0, 1)) ?></div>
                    <span style="font-size: 0.88rem; font-weight: 700; color: var(--text-heading);"><?= htmlspecialchars($nama_mhs) ?></span>
                    <span style="font-size: 0.7rem; background: #fce7f3; color: var(--primary); padding: 2px 8px; border-radius: 10px; font-weight: 700;">MAHASISWA</span>
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
            <a href="#biodata" class="nav-tab"><i class="fa-solid fa-address-card"></i> Profil</a>
            <a href="#krs" class="nav-tab"><i class="fa-solid fa-book-open"></i> KRS</a>
            <a href="#rdf" class="nav-tab"><i class="fa-solid fa-code"></i> Data Semantik</a>
            <a href="#rekan" class="nav-tab"><i class="fa-solid fa-users"></i> Teman</a>
        </div>
    </nav>

    <main class="container">

        <?php if (!empty($pesan_mhs)): ?>
            <div style="margin-top: 20px; padding: 14px 18px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; background: <?= ($tipe_pesan === 'success') ? '#dcfce7' : '#fee2e2' ?>; color: <?= ($tipe_pesan === 'success') ? '#166534' : '#991b1b' ?>; border: 1px solid <?= ($tipe_pesan === 'success') ? '#bbf7d0' : '#fecaca' ?>;">
                <i class="fa-solid <?= ($tipe_pesan === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <span><?= htmlspecialchars($pesan_mhs) ?></span>
            </div>
        <?php endif; ?>

        <!-- Hero Mahasiswa -->
        <section class="dash-hero">
            <i class="fa-solid fa-graduation-cap dash-hero-decor"></i>
            <div class="dash-hero-text">
                <h1>Halo, <?= htmlspecialchars($nama_mhs) ?>! Senang melihatmu lagi.</h1>
                <p>Pantau profil, KRS, dan data semantikmu di satu tempat, terhubung langsung ke graph database W3C.</p>
                <div class="meta-tags">
                    <span class="tag-pill"><i class="fa-solid fa-id-card"></i> NPM: <?= htmlspecialchars($npm) ?></span>
                    <span class="tag-pill"><i class="fa-solid fa-laptop-code"></i> <?= htmlspecialchars($prodi_mhs) ?></span>
                    <span class="tag-pill"><i class="fa-solid fa-building-columns"></i> Fakultas <?= htmlspecialchars($fakultas_mhs) ?></span>
                    <span class="tag-pill" style="background: rgba(34, 197, 94, 0.25); color: #86efac;">
                        <i class="fa-solid fa-circle-check"></i> Mahasiswa Aktif
                    </span>
                </div>
            </div>
        </section>

        <!-- Stats Grid Mahasiswa -->
        <section class="dash-stats-grid" id="ringkasan">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Status Kuliah</span>
                    <div class="stat-icon-wrap" style="background: #dcfce7; color: #16a34a;"><i class="fa-solid fa-user-check"></i></div>
                </div>
                <div class="stat-val">Aktif</div>
                <div class="stat-desc" style="color: #16a34a;"><i class="fa-solid fa-check"></i> Semester 3 (2024/2025)</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">IPK Saat Ini</span>
                    <div class="stat-icon-wrap" style="background: #fce7f3; color: #db2777;"><i class="fa-solid fa-award"></i></div>
                </div>
                <div class="stat-val">3.85</div>
                <div class="stat-desc" style="color: #db2777;"><i class="fa-solid fa-arrow-trend-up"></i> Prestasi sangat baik</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">SKS Terselesaikan</span>
                    <div class="stat-icon-wrap" style="background: #fae8ff; color: #a21caf;"><i class="fa-solid fa-book-bookmark"></i></div>
                </div>
                <div class="stat-val">68 SKS</div>
                <div class="stat-desc" style="color: #a21caf;"><i class="fa-solid fa-list-check"></i> 22 mata kuliah lulus</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Sinkronisasi RDF</span>
                    <div class="stat-icon-wrap" style="background: #fce7f3; color: #be185d;"><i class="fa-solid fa-circle-nodes"></i></div>
                </div>
                <div class="stat-val">Valid</div>
                <div class="stat-desc" style="color: #be185d;"><i class="fa-solid fa-diagram-project"></i> Sudah masuk triplestore</div>
            </div>
        </section>

        <!-- Konten Mahasiswa -->
        <section class="dash-content-grid">
            <div>
                <!-- Biodata Pribadi -->
                <div class="panel-card" id="biodata">
                    <div class="panel-header">
                        <h3><i class="fa-solid fa-address-card" style="color: var(--primary);"></i> Profil Saya</h3>
                        <button type="button" class="btn-edit-trigger" onclick="openModalEditMhs()">
                            <i class="fa-solid fa-pen-to-square"></i> Edit Profil
                        </button>
                    </div>
                    <div class="panel-body">
                        <div class="info-row">
                            <span class="info-label">NPM</span>
                            <span class="info-val"><?= htmlspecialchars($npm) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nama Lengkap</span>
                            <span class="info-val"><?= htmlspecialchars($nama_mhs) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Jenis Kelamin</span>
                            <span class="info-val"><?= htmlspecialchars($jk_mhs) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tempat, Tanggal Lahir</span>
                            <span class="info-val"><?= htmlspecialchars($tempat_mhs . ', ' . $tgl_mhs) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Program Studi</span>
                            <span class="info-val" style="color: var(--primary);"><?= htmlspecialchars($prodi_mhs) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Fakultas</span>
                            <span class="info-val"><?= htmlspecialchars($fakultas_mhs) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tanggal Masuk</span>
                            <span class="info-val"><?= htmlspecialchars($tgl_masuk) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Alamat Domisili</span>
                            <span class="info-val"><?= htmlspecialchars($alamat_mhs) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Kartu Rencana Studi (KRS) -->
                <div class="panel-card" id="krs">
                    <div class="panel-header">
                        <h3><i class="fa-solid fa-book-open" style="color: #a21caf;"></i> Kartu Rencana Studi (KRS)</h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted);">Semester Gasal 2024/2025</span>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>Kode MK</th>
                                    <th>Mata Kuliah</th>
                                    <th>SKS</th>
                                    <th>Dosen Pengampu</th>
                                    <th>Presensi Kelas</th>
                                    <th>Nilai Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $krs_list = [
                                    ['kode' => 'IF301', 'nama' => 'Teknologi Web Semantik', 'sks' => '3 SKS', 'dosen' => 'Pak Hary, M.Kom', 'default_grade' => 'A (4.00)'],
                                    ['kode' => 'IF302', 'nama' => 'Pemrograman Web Lanjut', 'sks' => '3 SKS', 'dosen' => 'Tim Dosen TI', 'default_grade' => 'A (4.00)'],
                                    ['kode' => 'IF303', 'nama' => 'Basis Data & Graph Database', 'sks' => '3 SKS', 'dosen' => 'Pak Hary, M.Kom', 'default_grade' => 'B+ (3.50)'],
                                    ['kode' => 'IF304', 'nama' => 'Rekayasa Perangkat Lunak', 'sks' => '3 SKS', 'dosen' => 'Tim Dosen TI', 'default_grade' => 'A (4.00)']
                                ];
                                foreach ($krs_list as $k):
                                    $k_kode = $k['kode'];
                                    $h_count = $presensi_mhs[$k_kode]['H'] ?? 14;
                                    $i_count = $presensi_mhs[$k_kode]['I'] ?? 0;
                                    $s_count = $presensi_mhs[$k_kode]['S'] ?? 0;
                                    $a_count = $presensi_mhs[$k_kode]['A'] ?? 0;
                                    
                                    // Hitung persentase kehadiran
                                    $total_sesi = max(1, $h_count + $i_count + $s_count + $a_count);
                                    $persen_h = round(($h_count / $total_sesi) * 100);

                                    // Nilai dari dosen
                                    $grade_display = isset($nilai_mhs[$k_kode]) ? $nilai_mhs[$k_kode]['grade'] . ' (' . $nilai_mhs[$k_kode]['nilai_akhir'] . ')' : $k['default_grade'];
                                ?>
                                    <tr>
                                        <td><strong><?= $k['kode'] ?></strong></td>
                                        <td><?= $k['nama'] ?></td>
                                        <td><?= $k['sks'] ?></td>
                                        <td><?= $k['dosen'] ?></td>
                                        <td>
                                            <span style="font-size: 0.78rem; background: #dcfce7; color: #166534; font-weight: 700; padding: 2px 8px; border-radius: 4px;">
                                                <i class="fa-solid fa-circle-check"></i> <?= $persen_h ?>% Hadir
                                            </span>
                                        </td>
                                        <td><span style="font-size: 0.8rem; font-weight: 800; color: #16a34a;"><?= $grade_display ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: RDF Turtle & Rekan -->
            <div>
                <!-- RDF Triples -->
                <div class="panel-card" id="rdf">
                    <div class="panel-header">
                        <h3><i class="fa-solid fa-code" style="color: #059669;"></i> Data Semantikku (RDF Turtle)</h3>
                    </div>
                    <div class="panel-body">
                        <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 12px;">
                            Beginilah datamu dituliskan dalam standar Semantic Web W3C:
                        </p>
                        <div class="rdf-code-box">
                            <button class="btn-copy" onclick="navigator.clipboard.writeText(this.parentElement.innerText); alert('Kode RDF berhasil disalin!')">
                                <i class="fa-solid fa-copy"></i> Salin
                            </button>
<span class="rdf-keyword">@prefix</span> univ: &lt;<span class="rdf-uri">http://univ-bengkulu.ac.id/ontologi#</span>&gt; .
<span class="rdf-keyword">@prefix</span> rdf:  &lt;<span class="rdf-uri">http://www.w3.org/1999/02/22-rdf-syntax-ns#</span>&gt; .

<span class="rdf-uri">&lt;http://univ.ac.id/mhs/<?= htmlspecialchars($npm) ?>&gt;</span>
    <span class="rdf-predicate">rdf:type</span> univ:Mahasiswa ;
    <span class="rdf-predicate">univ:nama</span> <span class="rdf-literal">"<?= htmlspecialchars($nama_mhs) ?>"</span> ;
    <span class="rdf-predicate">univ:npm</span> <span class="rdf-literal">"<?= htmlspecialchars($npm) ?>"</span> ;
    <span class="rdf-predicate">univ:jenisKelamin</span> <span class="rdf-literal">"<?= htmlspecialchars($jk_mhs) ?>"</span> ;
    <span class="rdf-predicate">univ:programStudi</span> &lt;<span class="rdf-uri">http://univ.ac.id/prodi/<?= urlencode($prodi_mhs) ?></span>&gt; ;
    <span class="rdf-predicate">univ:fakultas</span> &lt;<span class="rdf-uri">http://univ.ac.id/fakultas/<?= urlencode($fakultas_mhs) ?></span>&gt; ;
    <span class="rdf-predicate">univ:dosenPembimbing</span> &lt;<span class="rdf-uri">http://univ.ac.id/dosen/hary</span>&gt; ;
    <span class="rdf-predicate">univ:mengambilMK</span> &lt;<span class="rdf-uri">http://univ.ac.id/mk/IF301</span>&gt; .
                        </div>
                    </div>
                </div>

                <!-- Rekan Terhubung -->
                <div class="panel-card" id="rekan">
                    <div class="panel-header">
                        <h3><i class="fa-solid fa-users" style="color: #ea580c;"></i> Teman yang Terhubung</h3>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>NPM</th>
                                    <th>Nama</th>
                                    <th>Prodi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rekan as $r): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($r['npm']) ?></strong></td>
                                        <td><?= htmlspecialchars($r['nama_mahasiswa']) ?></td>
                                        <td><span style="font-size: 0.76rem; background: #fdeef5; padding: 2px 8px; border-radius: 4px;"><?= htmlspecialchars($r['nama_prodi'] ?? 'TI') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Modal Ubah Data Mahasiswa -->
    <div class="modal-overlay" id="modalEditMhs" onclick="closeOnBackdrop(event, 'modalEditMhs')">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-pen" style="color: var(--primary);"></i> Edit Profil</h3>
                <button type="button" class="modal-close" onclick="closeModalEditMhs()">&times;</button>
            </div>
            <form action="dashboard_mahasiswa.php" method="POST" class="modal-body">
                <input type="hidden" name="action" value="update_biodata_mhs">

                <!-- Alert Kebijakan Hak Akses Mahasiswa Sesuai Spesifikasi -->
                <div style="background: #fdf2f8; border: 1px solid #fbcfe8; border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; font-size: 0.82rem; color: #9d174d; line-height: 1.5;">
                    <i class="fa-solid fa-circle-info" style="color: #ec4899; margin-right: 4px;"></i>
                    <strong>Ketentuan Hak Akses Mahasiswa:</strong> Anda berhak memperbarui data diri (jenis kelamin, tempat & tanggal lahir, tanggal masuk, alamat domisili, dan kata sandi). Field <strong>Nama Lengkap, NPM, dan Kode Prodi terkunci secara permanen</strong> dan hanya memiliki wewenang diubah oleh Administrator.
                </div>

                <!-- Baris 1: NPM & Nama Lengkap (TERKUNCI / DISABLED) -->
                <div class="form-grid-2">
                    <div class="form-group-mhs">
                        <label>
                            <span><i class="fa-solid fa-id-card"></i> NPM</span>
                            <span class="badge-locked"><i class="fa-solid fa-lock"></i> Terkunci</span>
                        </label>
                        <input type="text" class="form-control-mhs" value="<?= htmlspecialchars($npm) ?>" disabled readonly title="NPM tidak dapat diubah oleh mahasiswa">
                    </div>
                    <div class="form-group-mhs">
                        <label>
                            <span><i class="fa-solid fa-user"></i> Nama Lengkap</span>
                            <span class="badge-locked"><i class="fa-solid fa-lock"></i> Hanya Admin</span>
                        </label>
                        <input type="text" class="form-control-mhs" value="<?= htmlspecialchars($nama_mhs) ?>" disabled readonly title="Nama Mahasiswa hanya dapat diubah oleh Administrator">
                    </div>
                </div>

                <!-- Baris 2: Program Studi (TERKUNCI) & Jenis Kelamin (BISA DIUBAH) -->
                <div class="form-grid-2">
                    <div class="form-group-mhs">
                        <label>
                            <span><i class="fa-solid fa-building-columns"></i> Program Studi</span>
                            <span class="badge-locked"><i class="fa-solid fa-lock"></i> Terkunci</span>
                        </label>
                        <input type="text" class="form-control-mhs" value="<?= htmlspecialchars($prodi_mhs . ' (' . ($mhs_db['kode_prodi'] ?? 'TI') . ')') ?>" disabled readonly title="Kode Prodi terkunci untuk mahasiswa">
                    </div>
                    <div class="form-group-mhs">
                        <label>
                            <span><i class="fa-solid fa-venus-mars"></i> Jenis Kelamin</span>
                            <span class="badge-editable"><i class="fa-solid fa-pen"></i> Bisa Diubah</span>
                        </label>
                        <select name="jenis_kelamin" class="form-control-mhs" required>
                            <option value="L" <?= (($mhs_db['jenis_kelamin'] ?? 'L') === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= (($mhs_db['jenis_kelamin'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 3: Tempat & Tanggal Lahir (BISA DIUBAH) -->
                <div class="form-grid-2">
                    <div class="form-group-mhs">
                        <label>
                            <span><i class="fa-solid fa-location-dot"></i> Tempat Lahir</span>
                            <span class="badge-editable"><i class="fa-solid fa-pen"></i> Bisa Diubah</span>
                        </label>
                        <input type="text" name="tempat_lahir" class="form-control-mhs" value="<?= htmlspecialchars($tempat_mhs) ?>" required>
                    </div>
                    <div class="form-group-mhs">
                        <label>
                            <span><i class="fa-solid fa-calendar"></i> Tanggal Lahir</span>
                            <span class="badge-editable"><i class="fa-solid fa-pen"></i> Bisa Diubah</span>
                        </label>
                        <input type="date" name="tanggal_lahir" class="form-control-mhs" value="<?= htmlspecialchars($tgl_mhs) ?>" required>
                    </div>
                </div>

                <!-- Baris 4: Tanggal Masuk & Password Baru (BISA DIUBAH) -->
                <div class="form-grid-2">
                    <div class="form-group-mhs">
                        <label>
                            <span><i class="fa-solid fa-calendar-check"></i> Tanggal Masuk</span>
                            <span class="badge-editable"><i class="fa-solid fa-pen"></i> Bisa Diubah</span>
                        </label>
                        <input type="date" name="tanggal_masuk" class="form-control-mhs" value="<?= htmlspecialchars($tgl_masuk) ?>" required>
                    </div>
                    <div class="form-group-mhs">
                        <label>
                            <span><i class="fa-solid fa-key"></i> Kata Sandi Baru</span>
                            <span class="badge-editable"><i class="fa-solid fa-pen"></i> Opsional</span>
                        </label>
                        <input type="password" name="password" class="form-control-mhs" placeholder="Kosongkan jika tidak diganti">
                    </div>
                </div>

                <!-- Baris 5: Alamat Domisili (BISA DIUBAH) -->
                <div class="form-group-mhs">
                    <label>
                        <span><i class="fa-solid fa-map-location-dot"></i> Alamat Domisili</span>
                        <span class="badge-editable"><i class="fa-solid fa-pen"></i> Bisa Diubah</span>
                    </label>
                    <textarea name="alamat" rows="2" class="form-control-mhs" required><?= htmlspecialchars($alamat_mhs) ?></textarea>
                </div>

                <!-- Tombol Aksi Modal -->
                <div style="display: flex; gap: 12px; margin-top: 18px; justify-content: flex-end;">
                    <button type="button" class="btn-cancel-mhs" onclick="closeModalEditMhs()">Batal</button>
                    <button type="submit" class="btn-save-mhs">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC MAHASISWA -->
    <script>
        function openModalEditMhs() {
            document.getElementById('modalEditMhs').classList.add('active');
        }

        function closeModalEditMhs() {
            document.getElementById('modalEditMhs').classList.remove('active');
        }

        function closeOnBackdrop(e, modalId) {
            if (e.target.id === modalId) {
                document.getElementById(modalId).classList.remove('active');
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
