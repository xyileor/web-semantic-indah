<?php
/**
 * =========================================================================
 * DASHBOARD ADMINISTRATOR - PORTAL AKADEMIK & WEB SEMANTIK
 * Pusat Manajemen Data Mahasiswa (Full CRUD MySQL), Graph Ontologi, & Server
 * Desain: Premium, Elegan, Modern, & Profesional
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

// 2. Proteksi Hak Akses (Hanya Administrator yang diizinkan mengakses halaman ini)
$user_role = strtolower($_SESSION['user']['role'] ?? '');
if ($user_role !== 'administrator' && $user_role !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// 3. Hubungkan Database
$koneksi = null;
if (file_exists(__DIR__ . '/koneksi.php')) {
    @include_once __DIR__ . '/koneksi.php';
}

$pesan_admin = '';
$tipe_pesan  = '';

// =========================================================================
// PROSES A: TAMBAH MAHASISWA BARU (POST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_mahasiswa') {
    $npm        = trim($_POST['npm'] ?? '');
    $nama       = trim($_POST['nama_mahasiswa'] ?? '');
    $jk         = trim($_POST['jenis_kelamin'] ?? 'L');
    $tempat     = trim($_POST['tempat_lahir'] ?? 'Bengkulu');
    $tgl_lahir  = trim($_POST['tanggal_lahir'] ?? '2005-01-01');
    $tgl_masuk  = trim($_POST['tanggal_masuk'] ?? date('Y-m-d'));
    $alamat     = trim($_POST['alamat'] ?? 'Kota Bengkulu');
    $password   = trim($_POST['password'] ?? 'pass123');
    $prodi      = trim($_POST['kode_prodi'] ?? 'TI');

    if (empty($npm) || empty($nama)) {
        $pesan_admin = "NPM dan Nama Mahasiswa tidak boleh kosong!";
        $tipe_pesan  = "danger";
    } elseif ($koneksi && !mysqli_connect_errno()) {
        $safe_npm = mysqli_real_escape_string($koneksi, $npm);

        // Cek apakah NPM sudah terdaftar
        $cek = mysqli_query($koneksi, "SELECT npm FROM mahasiswa WHERE npm = '$safe_npm' LIMIT 1");
        if ($cek && mysqli_num_rows($cek) > 0) {
            $pesan_admin = "Gagal! Mahasiswa dengan NPM $npm sudah terdaftar di database.";
            $tipe_pesan  = "danger";
        } else {
            $safe_nama  = mysqli_real_escape_string($koneksi, $nama);
            $safe_jk    = mysqli_real_escape_string($koneksi, $jk);
            $safe_tmp   = mysqli_real_escape_string($koneksi, $tempat);
            $safe_tgll  = mysqli_real_escape_string($koneksi, $tgl_lahir);
            $safe_tglm  = mysqli_real_escape_string($koneksi, $tgl_masuk);
            $safe_alm   = mysqli_real_escape_string($koneksi, $alamat);
            $safe_pwd   = mysqli_real_escape_string($koneksi, $password);
            $safe_prd   = mysqli_real_escape_string($koneksi, $prodi);

            $sql_insert = "INSERT INTO mahasiswa 
                    (npm, nama_mahasiswa, jenis_kelamin, tempat_lahir, tanggal_lahir, tanggal_masuk, alamat, password, kode_prodi)
                    VALUES 
                    ('$safe_npm', '$safe_nama', '$safe_jk', '$safe_tmp', '$safe_tgll', '$safe_tglm', '$safe_alm', '$safe_pwd', '$safe_prd')";

            if (mysqli_query($koneksi, $sql_insert)) {
                $pesan_admin = "Mahasiswa '$nama' (NPM: $npm) berhasil ditambahkan! Akun ini bisa langsung login dengan NPM: $npm & Sandi: $password";
                $tipe_pesan  = "success";
            } else {
                $pesan_admin = "Gagal menambahkan mahasiswa: " . mysqli_error($koneksi);
                $tipe_pesan  = "danger";
            }
        }
    }
}

// =========================================================================
// PROSES B: EDIT / PERBARUI DATA MAHASISWA (POST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_mahasiswa') {
    $npm_target = trim($_POST['npm_target'] ?? '');
    $nama       = trim($_POST['nama_mahasiswa'] ?? '');
    $jk         = trim($_POST['jenis_kelamin'] ?? 'L');
    $tempat     = trim($_POST['tempat_lahir'] ?? 'Bengkulu');
    $tgl_lahir  = trim($_POST['tanggal_lahir'] ?? '2005-01-01');
    $tgl_masuk  = trim($_POST['tanggal_masuk'] ?? date('Y-m-d'));
    $alamat     = trim($_POST['alamat'] ?? 'Kota Bengkulu');
    $password   = trim($_POST['password'] ?? 'pass123');
    $prodi      = trim($_POST['kode_prodi'] ?? 'TI');

    if (empty($npm_target) || empty($nama)) {
        $pesan_admin = "NPM dan Nama Mahasiswa tidak boleh kosong!";
        $tipe_pesan  = "danger";
    } elseif ($koneksi && !mysqli_connect_errno()) {
        $safe_target = mysqli_real_escape_string($koneksi, $npm_target);
        $safe_nama   = mysqli_real_escape_string($koneksi, $nama);
        $safe_jk     = mysqli_real_escape_string($koneksi, $jk);
        $safe_tmp    = mysqli_real_escape_string($koneksi, $tempat);
        $safe_tgll   = mysqli_real_escape_string($koneksi, $tgl_lahir);
        $safe_tglm   = mysqli_real_escape_string($koneksi, $tgl_masuk);
        $safe_alm    = mysqli_real_escape_string($koneksi, $alamat);
        $safe_pwd    = mysqli_real_escape_string($koneksi, $password);
        $safe_prd    = mysqli_real_escape_string($koneksi, $prodi);

        $sql_update = "UPDATE mahasiswa SET 
            nama_mahasiswa = '$safe_nama',
            jenis_kelamin  = '$safe_jk',
            tempat_lahir   = '$safe_tmp',
            tanggal_lahir  = '$safe_tgll',
            tanggal_masuk  = '$safe_tglm',
            alamat         = '$safe_alm',
            password       = '$safe_pwd',
            kode_prodi     = '$safe_prd'
            WHERE npm      = '$safe_target'";

        if (mysqli_query($koneksi, $sql_update)) {
            $pesan_admin = "Berhasil! Data mahasiswa '$nama' (NPM: $npm_target) telah diperbarui di database.";
            $tipe_pesan  = "success";
        } else {
            $pesan_admin = "Gagal memperbarui data mahasiswa: " . mysqli_error($koneksi);
            $tipe_pesan  = "danger";
        }
    }
}

// =========================================================================
// PROSES C: HAPUS MAHASISWA (GET)
// =========================================================================
if (isset($_GET['hapus_npm'])) {
    $del_npm = trim($_GET['hapus_npm']);
    if ($koneksi && !empty($del_npm)) {
        $safe_del = mysqli_real_escape_string($koneksi, $del_npm);
        if (mysqli_query($koneksi, "DELETE FROM mahasiswa WHERE npm = '$safe_del'")) {
            $pesan_admin = "Data mahasiswa dengan NPM $del_npm berhasil dihapus dari sistem.";
            $tipe_pesan  = "success";
        } else {
            $pesan_admin = "Gagal menghapus mahasiswa: " . mysqli_error($koneksi);
            $tipe_pesan  = "danger";
        }
    }
}

// =========================================================================
// PROSES D: UBAH NAMA PROGRAM STUDI OLEH ADMIN (POST)
// Sesuai aturan: "admin = bisa akses prodi dan data mahasiswa"
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah_nama_prodi_admin') {
    $target_kode_prodi = trim($_POST['kode_prodi'] ?? '');
    $nama_prodi_baru   = trim($_POST['nama_prodi'] ?? '');

    if (!empty($target_kode_prodi) && !empty($nama_prodi_baru) && $koneksi) {
        $safe_kp = mysqli_real_escape_string($koneksi, $target_kode_prodi);
        $safe_np = mysqli_real_escape_string($koneksi, $nama_prodi_baru);

        if (mysqli_query($koneksi, "UPDATE program_studi SET nama_prodi = '$safe_np' WHERE kode_prodi = '$safe_kp'")) {
            $pesan_admin = "Nama Program Studi $target_kode_prodi berhasil diperbarui menjadi '$nama_prodi_baru'!";
            $tipe_pesan  = "success";
        } else {
            $pesan_admin = "Gagal mengubah nama prodi: " . mysqli_error($koneksi);
            $tipe_pesan  = "danger";
        }
    }
}

// =========================================================================
// PROSES E: AMBIL DATA DARI DATABASE
// =========================================================================
$mahasiswa_list = [];
$total_laki = 0;
$total_perempuan = 0;

if ($koneksi && !mysqli_connect_errno()) {
    $q = mysqli_query($koneksi, "
        SELECT m.*, p.nama_prodi, f.nama_fakultas 
        FROM mahasiswa m 
        LEFT JOIN program_studi p ON m.kode_prodi = p.kode_prodi 
        LEFT JOIN fakultas f ON p.kode_fakultas = f.kode_fakultas
        ORDER BY m.npm ASC
    ");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $mahasiswa_list[] = $r;
            if ($r['jenis_kelamin'] === 'L') {
                $total_laki++;
            } else {
                $total_perempuan++;
            }
        }
    }
}

// Ambil Program Studi & Hitung Mahasiswa per Prodi
$prodi_list = [];
if ($koneksi && !mysqli_connect_errno()) {
    $pq = mysqli_query($koneksi, "
        SELECT p.*, f.nama_fakultas, COUNT(m.npm) as total_mhs 
        FROM program_studi p 
        LEFT JOIN fakultas f ON p.kode_fakultas = f.kode_fakultas
        LEFT JOIN mahasiswa m ON p.kode_prodi = m.kode_prodi
        GROUP BY p.kode_prodi, p.nama_prodi, p.kode_fakultas, f.nama_fakultas
        ORDER BY p.kode_prodi ASC
    ");
    if ($pq) {
        while ($pr = mysqli_fetch_assoc($pq)) {
            $prodi_list[] = $pr;
        }
    }
}

$total_mhs = count($mahasiswa_list);
$total_prodi = count($prodi_list) ?: 3;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrator &bull; Portal Akademik & Web Semantik</title>
    
    <!-- Google Fonts & Font Awesome Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=Fira+Code:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #db2777;
            --primary-dark: #be185d;
            --primary-light: #fce7f3;
            --primary-subtle: #fdf2f8;
            --secondary: #2d1024;
            --accent-blue: #ec4899;
            --accent-purple: #a21caf;
            --accent-amber: #d97706;
            --accent-emerald: #059669;
            --text-heading: #2d1024;
            --text-body: #4a3341;
            --text-muted: #8a6b7c;
            --bg-page: #fff7fb;
            --card-border: #f8dbe8;
            --card-shadow: 0 4px 20px -2px rgba(45, 16, 36, 0.05);
            --card-shadow-hover: 0 12px 30px -4px rgba(45, 16, 36, 0.08);
            --radius-xl: 18px;
            --radius-lg: 14px;
            --radius-md: 10px;
            --radius-sm: 6px;
            --transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-body);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            width: 100%;
        }

        /* Top Header Navbar */
        .dash-nav {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(12px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dash-nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .emblem {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #db2777 0%, #be185d 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(219, 39, 119, 0.25);
            color: #ffffff;
            font-size: 1.25rem;
        }

        .brand-title h3 {
            font-size: 1.08rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.01em;
        }

        .brand-title p {
            font-size: 0.74rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .user-menu-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-ghost-nav {
            font-size: 0.84rem;
            font-weight: 600;
            color: #5b4756;
            padding: 8px 14px;
            background: #fdeef5;
            border-radius: 8px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-ghost-nav:hover {
            background: #f8dbe8;
            color: var(--text-heading);
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid var(--card-border);
            padding: 6px 14px 6px 8px;
            border-radius: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #db2777, #9d174d);
            color: #ffffff;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        .badge-role {
            font-size: 0.68rem;
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .btn-logout {
            background: #fee2e2;
            color: #dc2626;
            font-weight: 600;
            font-size: 0.84rem;
            padding: 8px 16px;
            border-radius: 8px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #fecaca;
        }

        .btn-logout:hover {
            background: #fca5a5;
            color: #991b1b;
        }

        /* Hero Banner Premium */
        .dash-hero {
            background: linear-gradient(135deg, #2d1024 0%, #831843 55%, #be185d 100%);
            border-radius: var(--radius-xl);
            padding: 34px 38px;
            color: #ffffff;
            margin: 24px 0 28px;
            box-shadow: 0 16px 36px -10px rgba(15, 118, 110, 0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .dash-hero-decor {
            position: absolute;
            right: -25px;
            bottom: -35px;
            font-size: 13rem;
            color: rgba(255, 255, 255, 0.04);
            pointer-events: none;
        }

        .dash-hero-text h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .dash-hero-text p {
            color: #f9a8d4;
            font-size: 0.94rem;
            margin-bottom: 20px;
            max-width: 650px;
            line-height: 1.55;
        }

        .meta-tags {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tag-pill {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .tag-pill.live {
            background: rgba(16, 185, 129, 0.25);
            color: #6ee7b7;
            border-color: rgba(52, 211, 153, 0.4);
        }

        /* Stats Cards Grid */
        .dash-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 22px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
            border-color: #f9a8d4;
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .stat-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-icon-wrap {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .stat-val {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--text-heading);
            line-height: 1.15;
        }

        .stat-desc {
            font-size: 0.78rem;
            font-weight: 600;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Alert Toast */
        .alert-bar {
            padding: 14px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 22px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #dcfce7;
            color: #14532d;
            border: 1px solid #86efac;
        }

        .alert-danger {
            background: #fee2e2;
            color: #7f1d1d;
            border: 1px solid #fca5a5;
        }

        /* Main Content Layout */
        .dash-content-grid {
            display: grid;
            grid-template-columns: 1.6fr 0.85fr;
            gap: 24px;
            margin-bottom: 50px;
        }

        .panel-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .panel-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            gap: 12px;
            flex-wrap: wrap;
        }

        .panel-header h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-heading);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-action {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 3px 10px rgba(219, 39, 119, 0.25);
            transition: var(--transition);
        }

        .btn-primary-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(219, 39, 119, 0.35);
        }

        .btn-outline-action {
            background: #ffffff;
            color: #5b4756;
            border: 1px solid #f0c2d6;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        .btn-outline-action:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-subtle);
        }

        /* Filter & Search Bar */
        .table-toolbar {
            padding: 14px 24px;
            background: #fff7fb;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            min-width: 220px;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #b59aa8;
            font-size: 0.9rem;
        }

        .search-input {
            width: 100%;
            padding: 8px 12px 8px 36px;
            border: 1px solid #f0c2d6;
            border-radius: 8px;
            font-size: 0.86rem;
            outline: none;
            background: #ffffff;
            transition: var(--transition);
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.12);
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid #f0c2d6;
            border-radius: 8px;
            font-size: 0.86rem;
            background: #ffffff;
            color: var(--text-heading);
            outline: none;
            cursor: pointer;
        }

        /* Table Styling */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .simple-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        .simple-table th {
            text-align: left;
            padding: 12px 16px;
            background: #fff7fb;
            color: var(--text-muted);
            font-weight: 700;
            border-bottom: 1px solid var(--card-border);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .simple-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #fdeef5;
            vertical-align: middle;
        }

        .simple-table tr:hover td {
            background-color: #fffafd;
        }

        .prodi-badge {
            display: inline-block;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            background: #fdeef5;
            color: #4a3341;
            border: 1px solid #f8dbe8;
        }

        .prodi-badge.ti {
            background: #fce7f3;
            color: #9d174d;
            border-color: #fbcfe8;
        }

        .prodi-badge.si {
            background: #fae8ff;
            color: #86198f;
            border-color: #f5d0fe;
        }

        .prodi-badge.mj {
            background: #fef3c7;
            color: #b45309;
            border-color: #fde68a;
        }

        .pwd-tag {
            font-family: 'Fira Code', monospace;
            background: #fff7fb;
            border: 1px solid #f8dbe8;
            color: #be185d;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-edit-row {
            background: #fce7f3;
            color: #be185d;
            border: 1px solid #fbcfe8;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: var(--transition);
        }

        .btn-edit-row:hover {
            background: #be185d;
            color: #ffffff;
        }

        .btn-del-row {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: var(--transition);
        }

        .btn-del-row:hover {
            background: #dc2626;
            color: #ffffff;
        }

        /* Form Inside Panels */
        .panel-body {
            padding: 24px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }

        .form-group-admin {
            margin-bottom: 14px;
        }

        .form-group-admin label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-heading);
        }

        .form-group-admin input, .form-group-admin select, .form-group-admin textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #f0c2d6;
            border-radius: 8px;
            font-size: 0.88rem;
            font-family: inherit;
            color: var(--text-heading);
            background: #ffffff;
            transition: var(--transition);
            outline: none;
        }

        .form-group-admin input:focus, .form-group-admin select:focus, .form-group-admin textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.15);
        }

        /* RDF Code Box */
        .rdf-code-box {
            background: #3b0a24;
            color: #f8dbe8;
            border-radius: 10px;
            padding: 18px;
            font-family: 'Fira Code', monospace;
            font-size: 0.82rem;
            line-height: 1.65;
            overflow-x: auto;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .rdf-keyword { color: #f472b6; font-weight: 600; }
        .rdf-predicate { color: #e879f9; }
        .rdf-literal { color: #4ade80; }
        .rdf-uri { color: #fbbf24; }

        .btn-copy-rdf {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.74rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-copy-rdf:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Modal Dialog Styling */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(45, 16, 36, 0.65);
            backdrop-filter: blur(6px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-dialog-custom {
            background: #ffffff;
            width: 100%;
            max-width: 620px;
            border-radius: var(--radius-xl);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            animation: zoomIn 0.22s ease;
        }

        @keyframes zoomIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header-custom {
            padding: 20px 24px;
            background: linear-gradient(135deg, #db2777 0%, #be185d 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header-custom h3 {
            font-size: 1.15rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header-custom p {
            font-size: 0.8rem;
            color: #f9a8d4;
            margin-top: 2px;
        }

        .modal-close-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            font-size: 1.1rem;
            cursor: pointer;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .modal-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-body-custom {
            padding: 24px;
            max-height: 78vh;
            overflow-y: auto;
        }

        /* Footer */
        .admin-footer {
            margin-top: auto;
            border-top: 1px solid var(--card-border);
            background: #ffffff;
            padding: 20px 0;
            font-size: 0.82rem;
            color: var(--text-muted);
            text-align: center;
        }

        @media (max-width: 992px) {
            .dash-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .dash-content-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .dash-stats-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .dash-hero { padding: 24px; }
            .dash-hero-text h1 { font-size: 1.4rem; }
            .table-toolbar { flex-direction: column; align-items: stretch; }
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
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="brand-title">
                    <h3>Universitas Muhammadiyah Bengkulu</h3>
                    <p>Panel Administrator &bull; Web Semantik</p>
                </div>
            </a>
            <div class="nav-user">
                <div class="user-pill">
                    <div class="avatar-circle">A</div>
                    <div>
                        <div style="font-size: 0.86rem; font-weight: 700; color: var(--text-heading); line-height: 1.2;">Admin Semantik</div>
                        <span class="badge-role">ADMINISTRATOR UTAMA</span>
                    </div>
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
            <a href="#data-mahasiswa" class="nav-tab"><i class="fa-solid fa-user-graduate"></i> Data Mahasiswa</a>
            <a href="#formTambahMhs" class="nav-tab"><i class="fa-solid fa-user-plus"></i> Tambah Mahasiswa</a>
            <a href="#prodi-fakultas" class="nav-tab"><i class="fa-solid fa-sitemap"></i> Prodi & Fakultas</a>
            <a href="#rdf" class="nav-tab"><i class="fa-solid fa-code"></i> Graph Semantik</a>
        </div>
    </nav>

    <main class="container">

        <!-- Notifikasi Pesan Aksi Admin -->
        <?php if (!empty($pesan_admin)): ?>
            <div class="alert-bar alert-<?= $tipe_pesan ?>" style="margin-top: 22px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-<?= ($tipe_pesan === 'success') ? 'circle-check' : 'circle-exclamation' ?>" style="font-size: 1.15rem;"></i>
                    <span><?= htmlspecialchars($pesan_admin) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; font-size: 1rem; cursor:pointer;">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Hero Admin Elegan -->
        <section class="dash-hero">
            <i class="fa-solid fa-shield-halved dash-hero-decor"></i>
            <div class="dash-hero-text">
                <h1>Panel Kendali Data Mahasiswa</h1>
                <p>Tambah, ubah, dan hapus data mahasiswa di MySQL. Setiap perubahan tersinkron ke RDF graph W3C, sementara status server bisa Anda pantau dari sini.</p>
                <div class="meta-tags">
                    <span class="tag-pill live"><i class="fa-solid fa-circle-check"></i> Akses penuh: tambah, ubah, hapus</span>
                    <span class="tag-pill"><i class="fa-solid fa-database"></i> Database: universitassemantik</span>
                    <span class="tag-pill"><i class="fa-solid fa-server"></i> Apache & MySQL aktif</span>
                    <span class="tag-pill"><i class="fa-solid fa-diagram-project"></i> W3C RDF Graph Engine</span>
                </div>
            </div>
        </section>

        <!-- Stats Grid Cards -->
        <section class="dash-stats-grid" id="ringkasan">
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Jumlah Mahasiswa</span>
                    <div class="stat-icon-wrap" style="background: #fce7f3; color: #be185d;"><i class="fa-solid fa-users"></i></div>
                </div>
                <div class="stat-val"><?= $total_mhs ?> Orang</div>
                <div class="stat-desc" style="color: #be185d;">
                    <i class="fa-solid fa-mars"></i> <?= $total_laki ?> Laki-laki &bull; <i class="fa-solid fa-venus"></i> <?= $total_perempuan ?> Perempuan
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Program Studi</span>
                    <div class="stat-icon-wrap" style="background: #fce7f3; color: #db2777;"><i class="fa-solid fa-graduation-cap"></i></div>
                </div>
                <div class="stat-val"><?= $total_prodi ?> Prodi</div>
                <div class="stat-desc" style="color: #db2777;">
                    <i class="fa-solid fa-check-circle"></i> TI, SI, & Manajemen
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Fakultas</span>
                    <div class="stat-icon-wrap" style="background: #fae8ff; color: #a21caf;"><i class="fa-solid fa-building-columns"></i></div>
                </div>
                <div class="stat-val">3 Fakultas</div>
                <div class="stat-desc" style="color: #a21caf;">
                    <i class="fa-solid fa-layer-group"></i> Teknik, Ekonomi, Hukum
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <span class="stat-title">Status Server</span>
                    <div class="stat-icon-wrap" style="background: #dcfce7; color: #15803d;"><i class="fa-solid fa-server"></i></div>
                </div>
                <div class="stat-val" style="color: #15803d;">Online</div>
                <div class="stat-desc" style="color: #15803d;">
                    <i class="fa-solid fa-bolt"></i> Apache dan MySQL tersambung
                </div>
            </div>
        </section>

        <!-- Konten Utama Admin -->
        <section class="dash-content-grid">
            
            <!-- Kolom Kiri: Tabel Mahasiswa & Formulir Tambah -->
            <div>
                <!-- Panel Data Mahasiswa Lengkap -->
                <div class="panel-card" id="data-mahasiswa">
                    <div class="panel-header">
                        <h3>
                            <i class="fa-solid fa-user-graduate" style="color: var(--primary);"></i>
                            <span>Data Mahasiswa</span>
                        </h3>
                        <div class="panel-actions">
                            <button type="button" class="btn-outline-action" onclick="exportTableToCSV('data_mahasiswa.csv')">
                                <i class="fa-solid fa-file-csv"></i> Unduh CSV
                            </button>
                            <button type="button" class="btn-primary-action" onclick="openModalTambah()">
                                <i class="fa-solid fa-plus"></i> Tambah Mahasiswa
                            </button>
                        </div>
                    </div>

                    <!-- Toolbar Pencarian & Filter -->
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="searchInput" class="search-input" placeholder="Cari berdasarkan NPM, nama, atau alamat..." onkeyup="filterMahasiswaTable()">
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label for="filterProdi" style="font-size: 0.82rem; font-weight: 600; color: #8a6b7c;">Filter Prodi:</label>
                            <select id="filterProdi" class="filter-select" onchange="filterMahasiswaTable()">
                                <option value="">Semua Prodi</option>
                                <option value="TI">Teknik Informatika</option>
                                <option value="SI">Sistem Informasi</option>
                                <option value="MJ">Manajemen</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabel Data Mahasiswa -->
                    <div class="table-responsive">
                        <table class="simple-table" id="mahasiswaTable">
                            <thead>
                                <tr>
                                    <th>NPM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Prodi</th>
                                    <th>Domisili</th>
                                    <th>Sandi Login</th>
                                    <th style="text-align: right;">Aksi Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mahasiswa_list)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                            <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                                            Belum ada data mahasiswa di tabel MySQL. Silakan gunakan tombol Tambah Mahasiswa di atas.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mahasiswa_list as $m): 
                                        $prodi_code = strtoupper($m['kode_prodi']);
                                        $badge_class = strtolower($prodi_code);
                                    ?>
                                        <tr data-npm="<?= htmlspecialchars($m['npm']) ?>"
                                            data-nama="<?= htmlspecialchars($m['nama_mahasiswa']) ?>"
                                            data-prodi="<?= htmlspecialchars($m['kode_prodi']) ?>"
                                            data-jk="<?= htmlspecialchars($m['jenis_kelamin']) ?>"
                                            data-tempat="<?= htmlspecialchars($m['tempat_lahir']) ?>"
                                            data-tgllahir="<?= htmlspecialchars($m['tanggal_lahir']) ?>"
                                            data-tglmasuk="<?= htmlspecialchars($m['tanggal_masuk']) ?>"
                                            data-alamat="<?= htmlspecialchars($m['alamat']) ?>"
                                            data-password="<?= htmlspecialchars($m['password'] ?? 'pass123') ?>">
                                            <td>
                                                <strong style="color: var(--text-heading); font-family: 'Fira Code', monospace;"><?= htmlspecialchars($m['npm']) ?></strong>
                                            </td>
                                            <td>
                                                <div style="font-weight: 700; color: var(--text-heading);"><?= htmlspecialchars($m['nama_mahasiswa']) ?></div>
                                                <small style="color: #8a6b7c;"><?= ($m['jenis_kelamin'] === 'L') ? '<i class="fa-solid fa-mars" style="color: #ec4899;"></i> Laki-laki' : '<i class="fa-solid fa-venus" style="color: #ec4899;"></i> Perempuan' ?></small>
                                            </td>
                                            <td>
                                                <span class="prodi-badge <?= $badge_class ?>">
                                                    <?= htmlspecialchars($m['nama_prodi'] ?? $m['kode_prodi']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small style="color: #5b4756; display: block; max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($m['alamat'] ?? 'Bengkulu') ?>">
                                                    <i class="fa-solid fa-location-dot" style="color: #b59aa8;"></i> <?= htmlspecialchars($m['alamat'] ?? 'Bengkulu') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <code class="pwd-tag"><?= htmlspecialchars($m['password'] ?? 'pass123') ?></code>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="action-btns" style="justify-content: flex-end;">
                                                    <!-- Tombol Edit Data -->
                                                    <button type="button" class="btn-edit-row" onclick="openModalEdit(this.closest('tr'))" title="Edit Data Mahasiswa Ini">
                                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                                    </button>
                                                    <!-- Tombol Hapus Data -->
                                                    <a href="dashboard_admin.php?hapus_npm=<?= urlencode($m['npm']) ?>" 
                                                       class="btn-del-row" 
                                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data mahasiswa:\n\nNPM: <?= $m['npm'] ?>\nNama: <?= htmlspecialchars($m['nama_mahasiswa']) ?>?\n\nTindakan ini tidak dapat dibatalkan.')"
                                                       title="Hapus Mahasiswa">
                                                        <i class="fa-solid fa-trash"></i> Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form Cepat Tambah Mahasiswa Baru -->
                <div class="panel-card" id="formTambahMhs">
                    <div class="panel-header">
                        <h3><i class="fa-solid fa-user-plus" style="color: var(--primary);"></i> Tambah Mahasiswa Baru</h3>
                    </div>
                    <div class="panel-body">
                        <form action="dashboard_admin.php" method="POST">
                            <input type="hidden" name="action" value="tambah_mahasiswa">
                            
                            <div class="form-row">
                                <div class="form-group-admin">
                                    <label><i class="fa-solid fa-id-badge"></i> NPM Mahasiswa *</label>
                                    <input type="text" name="npm" placeholder="Contoh: 2023010005" required>
                                </div>
                                <div class="form-group-admin">
                                    <label><i class="fa-solid fa-user"></i> Nama Lengkap Mahasiswa *</label>
                                    <input type="text" name="nama_mahasiswa" placeholder="Nama mahasiswa lengkap..." required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group-admin">
                                    <label><i class="fa-solid fa-graduation-cap"></i> Program Studi *</label>
                                    <select name="kode_prodi" required>
                                        <option value="TI">Teknik Informatika (Fakultas Teknik)</option>
                                        <option value="SI">Sistem Informasi (Fakultas Teknik)</option>
                                        <option value="MJ">Manajemen (Fakultas Ekonomi)</option>
                                    </select>
                                </div>
                                <div class="form-group-admin">
                                    <label><i class="fa-solid fa-venus-mars"></i> Jenis Kelamin</label>
                                    <select name="jenis_kelamin">
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group-admin">
                                    <label><i class="fa-solid fa-location-dot"></i> Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" value="Bengkulu">
                                </div>
                                <div class="form-group-admin">
                                    <label><i class="fa-solid fa-calendar"></i> Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" value="2005-01-01">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group-admin">
                                    <label><i class="fa-solid fa-calendar-check"></i> Tanggal Masuk Kuliah</label>
                                    <input type="date" name="tanggal_masuk" value="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="form-group-admin">
                                    <label><i class="fa-solid fa-key"></i> Kata Sandi Akun Login Mahasiswa *</label>
                                    <input type="text" name="password" value="pass123" placeholder="Buat sandi login..." required>
                                </div>
                            </div>

                            <div class="form-group-admin">
                                <label><i class="fa-solid fa-map-location-dot"></i> Alamat Domisili Mahasiswa</label>
                                <input type="text" name="alamat" placeholder="Jl. Merdeka No. 123, Kota Bengkulu" value="Kota Bengkulu">
                            </div>

                            <button type="submit" class="btn-primary-action" style="width: 100%; justify-content: center; padding: 13px; font-size: 0.95rem; margin-top: 8px;">
                                <i class="fa-solid fa-floppy-disk"></i> Simpan & Aktifkan Mahasiswa Baru ke Database
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Kolom Kanan: Prodi, Ringkasan, & Ontologi RDF -->
            <div>
                <!-- Panel Data Program Studi & Fakultas -->
                <div class="panel-card" id="prodi-fakultas">
                    <div class="panel-header">
                        <h3><i class="fa-solid fa-sitemap" style="color: #db2777;"></i> Program Studi & Fakultas</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Program Studi</th>
                                    <th>Fakultas</th>
                                    <th style="text-align: center;">Mahasiswa</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prodi_list as $p): ?>
                                    <tr>
                                        <td><strong style="color: #db2777; font-family:'Fira Code', monospace;"><?= htmlspecialchars($p['kode_prodi']) ?></strong></td>
                                        <td><strong><?= htmlspecialchars($p['nama_prodi']) ?></strong></td>
                                        <td><span class="prodi-badge"><?= htmlspecialchars($p['nama_fakultas'] ?? $p['kode_fakultas']) ?></span></td>
                                        <td style="text-align: center;">
                                            <span style="font-size: 0.78rem; font-weight: 700; background: #fce7f3; color: #db2777; padding: 2px 8px; border-radius: 6px;">
                                                <?= intval($p['total_mhs'] ?? 0) ?> Mhs
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="dashboard_prodi.php?p=<?= urlencode($p['kode_prodi']) ?>" class="btn-edit-row" style="font-size: 0.74rem; padding: 4px 8px;" title="Akses Dashboard Prodi">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Akses
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Panel Ontologi Turtle W3C Semantic Web -->
                <div class="panel-card" id="rdf">
                    <div class="panel-header">
                        <h3><i class="fa-solid fa-code" style="color: var(--primary);"></i> Graph Semantik (RDF Turtle)</h3>
                    </div>
                    <div class="panel-body">
                        <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 12px;">
                            Representasi entitas Administrator & Basis Data terhubung standar RDF W3C:
                        </p>
                        <div class="rdf-code-box" id="rdfAdminCode">
                            <button type="button" class="btn-copy-rdf" onclick="copyRDF('rdfAdminCode')">
                                <i class="fa-solid fa-copy"></i> Salin
                            </button>
<span class="rdf-keyword">@prefix</span> univ: &lt;<span class="rdf-uri">http://univ-bengkulu.ac.id/ontologi#</span>&gt; .
<span class="rdf-keyword">@prefix</span> rdf:  &lt;<span class="rdf-uri">http://www.w3.org/1999/02/22-rdf-syntax-ns#</span>&gt; .
<span class="rdf-keyword">@prefix</span> rdfs: &lt;<span class="rdf-uri">http://www.w3.org/2000/01/rdf-schema#</span>&gt; .

<span class="rdf-uri">&lt;http://univ.ac.id/admin/root&gt;</span>
    <span class="rdf-predicate">rdf:type</span> univ:AdministratorSistem ;
    <span class="rdf-predicate">univ:nama</span> <span class="rdf-literal">"Administrator Semantik"</span> ;
    <span class="rdf-predicate">univ:totalMahasiswa</span> <span class="rdf-literal">"<?= $total_mhs ?>"</span> ;
    <span class="rdf-predicate">univ:totalLakiLaki</span> <span class="rdf-literal">"<?= $total_laki ?>"</span> ;
    <span class="rdf-predicate">univ:totalPerempuan</span> <span class="rdf-literal">"<?= $total_perempuan ?>"</span> ;
    <span class="rdf-predicate">univ:basisData</span> &lt;<span class="rdf-uri">http://univ.ac.id/db/universitassemantik</span>&gt; ;
    <span class="rdf-predicate">univ:serverApache</span> <span class="rdf-literal">"Port 80 (HTTP)"</span> ;
    <span class="rdf-predicate">univ:serverMySQL</span> <span class="rdf-literal">"Port 3306 (Active)"</span> ;
    <span class="rdf-predicate">univ:statusOperasional</span> <span class="rdf-literal">"Full CRUD Authorized"</span> .
                        </div>
                    </div>
                </div>

                <!-- Panduan Cepat Administrator -->
                <div class="panel-card" style="background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%); border-color: #f9a8d4;">
                    <div class="panel-header" style="background: transparent; border-bottom: 1px solid rgba(219, 39, 119, 0.15);">
                        <h3 style="color: #be185d;"><i class="fa-solid fa-lightbulb"></i> Tips Cepat untuk Admin</h3>
                    </div>
                    <div class="panel-body" style="font-size: 0.85rem; color: #4a3341; line-height: 1.6;">
                        <ul style="padding-left: 20px;">
                            <li style="margin-bottom: 6px;"><strong>Edit Data:</strong> Klik tombol <em>Edit</em> pada baris tabel untuk mengubah nama, prodi, tanggal lahir, alamat, maupun password mahasiswa.</li>
                            <li style="margin-bottom: 6px;"><strong>Pencarian Instan:</strong> Gunakan kotak pencarian untuk menyaring data siswa secara langsung tanpa memuat ulang halaman.</li>
                            <li style="margin-bottom: 6px;"><strong>Login Instan:</strong> Setiap siswa baru yang ditambahkan langsung aktif dan dapat login via halaman Login resmi.</li>
                        </ul>
                    </div>
                </div>

            </div>

        </section>

    </main>

    <!-- ========================================================
         MODAL 1: EDIT DATA MAHASISWA (POPUP MODAL ELEGAN)
         ======================================================== -->
    <div class="modal-overlay" id="modalEditMahasiswa" onclick="closeOnBackdrop(event, 'modalEditMahasiswa')">
        <div class="modal-dialog-custom">
            <div class="modal-header-custom">
                <div>
                    <h3><i class="fa-solid fa-pen-to-square"></i> Edit Data Mahasiswa</h3>
                    <p>Perbarui informasi biodata & kata sandi mahasiswa di database MySQL</p>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeModalEdit()">&times;</button>
            </div>
            
            <form action="dashboard_admin.php" method="POST" class="modal-body-custom">
                <input type="hidden" name="action" value="edit_mahasiswa">
                <input type="hidden" name="npm_target" id="editNpmTarget">

                <div style="background: #fdeef5; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.84rem; font-weight: 600; color: #5b4756;">NPM Mahasiswa (Kunci Unik):</span>
                    <span id="editNpmDisplay" style="font-family: 'Fira Code', monospace; font-size: 0.95rem; font-weight: 700; color: var(--primary-dark); background: #ffffff; padding: 3px 10px; border-radius: 6px; border: 1px solid #f0c2d6;"></span>
                </div>

                <div class="form-row">
                    <div class="form-group-admin" style="grid-column: span 2;">
                        <label style="display: flex; align-items: center; justify-content: space-between;">
                            <span><i class="fa-solid fa-user"></i> Nama Lengkap Mahasiswa *</span>
                            <span style="font-size: 0.72rem; color: #059669; font-weight: 700; background: #dcfce7; padding: 2px 8px; border-radius: 6px;"><i class="fa-solid fa-check"></i> Wewenang Admin: Bisa Ubah Nama</span>
                        </label>
                        <input type="text" name="nama_mahasiswa" id="editNama" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-admin">
                        <label><i class="fa-solid fa-graduation-cap"></i> Program Studi *</label>
                        <select name="kode_prodi" id="editProdi" required>
                            <option value="TI">Teknik Informatika (Fakultas Teknik)</option>
                            <option value="SI">Sistem Informasi (Fakultas Teknik)</option>
                            <option value="MJ">Manajemen (Fakultas Ekonomi)</option>
                        </select>
                    </div>
                    <div class="form-group-admin">
                        <label><i class="fa-solid fa-venus-mars"></i> Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="editJk">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-admin">
                        <label><i class="fa-solid fa-location-dot"></i> Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" id="editTempat">
                    </div>
                    <div class="form-group-admin">
                        <label><i class="fa-solid fa-calendar"></i> Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="editTglLahir">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-admin">
                        <label><i class="fa-solid fa-calendar-check"></i> Tanggal Masuk Kuliah</label>
                        <input type="date" name="tanggal_masuk" id="editTglMasuk">
                    </div>
                    <div class="form-group-admin">
                        <label><i class="fa-solid fa-key"></i> Kata Sandi Login *</label>
                        <input type="text" name="password" id="editPassword" required>
                    </div>
                </div>

                <div class="form-group-admin">
                    <label><i class="fa-solid fa-map-location-dot"></i> Alamat Domisili</label>
                    <textarea name="alamat" id="editAlamat" rows="2" style="resize: vertical;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 18px;">
                    <button type="button" class="btn-outline-action" onclick="closeModalEdit()" style="flex: 1; justify-content: center; padding: 11px;">
                        Batal
                    </button>
                    <button type="submit" class="btn-primary-action" style="flex: 2; justify-content: center; padding: 11px;">
                        <i class="fa-solid fa-check"></i> Simpan Perubahan Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer Elegan -->
    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Universitas Muhammadiyah Bengkulu &bull; Sistem Informasi Akademik Berbasis Teknologi Web Semantik</p>
        </div>
    </footer>

    <!-- JAVASCRIPT LOGIC INTERAKTIF -->
    <script>
        // Buka Modal Edit dengan data prefilled dari baris tabel
        function openModalEdit(row) {
            const npm       = row.getAttribute('data-npm') || '';
            const nama      = row.getAttribute('data-nama') || '';
            const prodi     = row.getAttribute('data-prodi') || 'TI';
            const jk        = row.getAttribute('data-jk') || 'L';
            const tempat    = row.getAttribute('data-tempat') || 'Bengkulu';
            const tglLahir  = row.getAttribute('data-tgllahir') || '';
            const tglMasuk  = row.getAttribute('data-tglmasuk') || '';
            const alamat    = row.getAttribute('data-alamat') || '';
            const password  = row.getAttribute('data-password') || 'pass123';

            document.getElementById('editNpmTarget').value      = npm;
            document.getElementById('editNpmDisplay').textContent = npm;
            document.getElementById('editNama').value           = nama;
            document.getElementById('editProdi').value          = prodi;
            document.getElementById('editJk').value             = jk;
            document.getElementById('editTempat').value         = tempat;
            document.getElementById('editTglLahir').value       = tglLahir;
            document.getElementById('editTglMasuk').value       = tglMasuk;
            document.getElementById('editAlamat').value         = alamat;
            document.getElementById('editPassword').value       = password;

            document.getElementById('modalEditMahasiswa').classList.add('active');
        }

        function closeModalEdit() {
            document.getElementById('modalEditMahasiswa').classList.remove('active');
        }

        // Scroll cepat ke form tambah
        function openModalTambah() {
            const formCard = document.getElementById('formTambahMhs');
            if (formCard) {
                formCard.scrollIntoView({ behavior: 'smooth' });
                const inputNpm = formCard.querySelector('input[name="npm"]');
                if (inputNpm) {
                    inputNpm.focus();
                    inputNpm.style.borderColor = '#db2777';
                    setTimeout(() => inputNpm.style.borderColor = '', 1000);
                }
            }
        }

        // Tutup modal jika klik backdrop gelap
        function closeOnBackdrop(e, modalId) {
            if (e.target.id === modalId) {
                document.getElementById(modalId).classList.remove('active');
            }
        }

        // Filter & Cari Live di Tabel Mahasiswa
        function filterMahasiswaTable() {
            const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
            const prodiVal  = document.getElementById('filterProdi').value.toLowerCase().trim();
            const table     = document.getElementById('mahasiswaTable');
            const rows      = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                if (row.cells.length < 6) return; // Skip baris kosong

                const npm       = (row.getAttribute('data-npm') || '').toLowerCase();
                const nama      = (row.getAttribute('data-nama') || '').toLowerCase();
                const alamat    = (row.getAttribute('data-alamat') || '').toLowerCase();
                const rowProdi  = (row.getAttribute('data-prodi') || '').toLowerCase();

                const matchSearch = (npm.includes(searchVal) || nama.includes(searchVal) || alamat.includes(searchVal));
                const matchProdi  = (!prodiVal || rowProdi === prodiVal);

                if (matchSearch && matchProdi) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Salin Triples RDF
        function copyRDF(boxId) {
            const el = document.getElementById(boxId);
            if (el) {
                navigator.clipboard.writeText(el.innerText).then(() => {
                    alert('Ontologi RDF (Turtle) berhasil disalin ke clipboard!');
                });
            }
        }

        // Ekspor Tabel ke File CSV
        function exportTableToCSV(filename) {
            const rows = document.querySelectorAll('#mahasiswaTable tr');
            let csv = [];
            
            rows.forEach(row => {
                const cols = row.querySelectorAll('th, td');
                let rowData = [];
                // Abaikan kolom aksi (kolom terakhir)
                for (let i = 0; i < cols.length - 1; i++) {
                    let text = cols[i].innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim();
                    text = text.replace(/"/g, '""');
                    rowData.push('"' + text + '"');
                }
                if (rowData.length > 0) {
                    csv.push(rowData.join(','));
                }
            });

            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
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
