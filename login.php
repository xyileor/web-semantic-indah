<?php
/**
 * =========================================================================
 * PORTAL AKADEMIK UNIVERSITAS - HALAMAN & SISTEM LOGIN LENGKAP
 * Mendukung Login Multi-Role (Mahasiswa, Program Studi, Administrator)
 * & Registrasi Mahasiswa Baru ke Database MySQL 'universitassemantik'
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

// Jangan izinkan browser/proxy menyimpan halaman login lama setelah logout/login.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
error_reporting(0);

// Hubungkan database jika tersedia
$koneksi = null;
if (file_exists(__DIR__ . '/koneksi.php')) {
    @include_once __DIR__ . '/koneksi.php';
}

// Jika user sudah login dan mengakses login.php via GET, langsung arahkan ke dashboard
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['user']) && !isset($_GET['action'])) {
    header("Location: dashboard.php");
    exit;
}

// =========================================================================
// 1. PROSES REGISTRASI MAHASISWA BARU (POST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $npm           = trim($_POST['npm'] ?? '');
    $nama          = trim($_POST['nama_mahasiswa'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? 'L');
    $tempat_lahir  = trim($_POST['tempat_lahir'] ?? 'Bengkulu');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? date('Y-m-d'));
    $tanggal_masuk = trim($_POST['tanggal_masuk'] ?? date('Y-m-d'));
    $alamat        = trim($_POST['alamat'] ?? '');
    $kode_prodi    = trim($_POST['kode_prodi'] ?? 'TI');
    $password      = trim($_POST['password'] ?? '12345678');

    if (empty($npm) || empty($nama) || empty($password)) {
        header("Location: login.php?error=empty_reg&tab=register");
        exit;
    }

    if ($koneksi && !mysqli_connect_errno()) {
        $safe_npm = mysqli_real_escape_string($koneksi, $npm);
        // Cek duplikasi NPM
        $cek = mysqli_query($koneksi, "SELECT npm FROM mahasiswa WHERE npm = '$safe_npm' LIMIT 1");
        if ($cek && mysqli_num_rows($cek) > 0) {
            header("Location: login.php?error=npm_exists&tab=register");
            exit;
        }

        $safe_nama         = mysqli_real_escape_string($koneksi, $nama);
        $safe_jk           = mysqli_real_escape_string($koneksi, $jenis_kelamin);
        $safe_tempat       = mysqli_real_escape_string($koneksi, $tempat_lahir);
        $safe_tgl_lahir    = mysqli_real_escape_string($koneksi, $tanggal_lahir);
        $safe_tgl_masuk    = mysqli_real_escape_string($koneksi, $tanggal_masuk);
        $safe_alamat       = mysqli_real_escape_string($koneksi, $alamat);
        $safe_prodi        = mysqli_real_escape_string($koneksi, $kode_prodi);
        $safe_password     = mysqli_real_escape_string($koneksi, $password);

        $sql = "INSERT INTO mahasiswa 
                (npm, nama_mahasiswa, jenis_kelamin, tempat_lahir, tanggal_lahir, tanggal_masuk, alamat, password, kode_prodi)
                VALUES 
                ('$safe_npm', '$safe_nama', '$safe_jk', '$safe_tempat', '$safe_tgl_lahir', '$safe_tgl_masuk', '$safe_alamat', '$safe_password', '$safe_prodi')";

        if (mysqli_query($koneksi, $sql)) {
            header("Location: login.php?pesan=register_sukses&npm=" . urlencode($npm));
            exit;
        } else {
            header("Location: login.php?error=db_error&tab=register");
            exit;
        }
    } else {
        // Fallback jika database belum aktif
        header("Location: login.php?pesan=register_sukses&npm=" . urlencode($npm));
        exit;
    }
}

// =========================================================================
// 2. PROSES AUTENTIKASI LOGIN (POST)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role        = trim($_POST['role'] ?? 'mahasiswa');
    $username    = trim($_POST['username'] ?? '');
    $password    = trim($_POST['password'] ?? '');
    $remember_me = isset($_POST['remember_me']);

    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty&role=" . urlencode($role));
        exit;
    }

    $authenticated_user = null;
    $clean_user = strtolower(trim($username));

    // A. CEK AKUN MAHASISWA DARI DATABASE
    // Mahasiswa WAJIB login menggunakan NPM, bukan nama mahasiswa.
    if ($koneksi && !mysqli_connect_errno() && $role === 'mahasiswa') {
        $safe_npm_login = mysqli_real_escape_string($koneksi, $username);

        // Login mahasiswa hanya berdasarkan NPM eksak.
        // Nama mahasiswa tidak lagi dapat digunakan sebagai username login.
        $query_mhs = "
            SELECT *
            FROM mahasiswa
            WHERE npm = '$safe_npm_login'
            LIMIT 1
        ";
        $res_mhs = mysqli_query($koneksi, $query_mhs);

        if ($res_mhs && mysqli_num_rows($res_mhs) > 0) {
            $row = mysqli_fetch_assoc($res_mhs);
            // Validasi kecocokan password: password di database, hash, atau sandi default
            if ($row['password'] === $password 
                || password_verify($password, $row['password']) 
                || empty($row['password']) 
                || $password === 'pass123' 
                || $password === '12345678') {
                $authenticated_user = [
                    'npm'            => $row['npm'],
                    'nama'           => $row['nama_mahasiswa'],
                    'jenis_kelamin'  => ($row['jenis_kelamin'] === 'L') ? 'Laki-laki' : 'Perempuan',
                    'tempat_lahir'   => $row['tempat_lahir'] ?? 'Bengkulu',
                    'tanggal_lahir'  => $row['tanggal_lahir'] ?? '2004-05-10',
                    'tanggal_masuk'  => $row['tanggal_masuk'] ?? '2023-08-01',
                    'alamat'         => $row['alamat'] ?? 'Kota Bengkulu',
                    'prodi'          => $row['nama_prodi'] ?? $row['kode_prodi'] ?? 'Teknik Informatika',
                    'fakultas'       => $row['nama_fakultas'] ?? 'Teknik',
                    'role'           => 'Mahasiswa',
                    'status'         => 'Aktif',
                    'ipk'            => '3.82',
                    'sks'            => '68'
                ];
            }
        }
    }

    // B. CEK APAKAH AKUN ADALAH PROGRAM STUDI (PRODI)
    if (!$authenticated_user && ($role === 'prodi' || $role === 'dosen' || in_array($clean_user, ['prodi', 'ti', 'si', 'mj', 'prodi_ti', 'prodi_si', 'prodi_mj', 'teknik informatika', 'sistem informasi', 'manajemen']))) {
        // Cek ke database tabel program_studi jika terkoneksi
        if ($koneksi && !mysqli_connect_errno()) {
            $clean_safe = mysqli_real_escape_string($koneksi, $clean_user);
            $q_prodi = mysqli_query($koneksi, "
                SELECT p.*, f.nama_fakultas 
                FROM program_studi p 
                LEFT JOIN fakultas f ON p.kode_fakultas = f.kode_fakultas 
                WHERE LOWER(p.kode_prodi) = LOWER('$clean_safe') 
                   OR LOWER(p.nama_prodi) = LOWER('$clean_safe') 
                   OR LOWER(p.kode_prodi) = REPLACE(LOWER('$clean_safe'), 'prodi_', '')
                   OR ('$clean_safe' = 'prodi' AND p.kode_prodi = 'TI')
                LIMIT 1
            ");

            if ($q_prodi && mysqli_num_rows($q_prodi) > 0) {
                $row_p = mysqli_fetch_assoc($q_prodi);
                $p_pass = $row_p['password'] ?? 'prodi123';
                if ($password === $p_pass || $password === 'prodi123' || $password === '12345678' || $password === 'pass123' || $password === 'dosen123') {
                    $authenticated_user = [
                        'npm'            => $row_p['kode_prodi'],
                        'kode_prodi'     => $row_p['kode_prodi'],
                        'nama'           => 'Pengelola Program Studi ' . $row_p['nama_prodi'],
                        'nama_prodi'     => $row_p['nama_prodi'],
                        'fakultas'       => $row_p['nama_fakultas'] ?? 'Teknik',
                        'role'           => 'Prodi',
                        'status'         => 'Pengelola Program Studi',
                        'jabatan'        => 'Koordinator Program Studi ' . $row_p['nama_prodi'],
                        'ipk'            => '-',
                        'sks'            => '-'
                    ];
                }
            }
        }

        // Fallback jika database belum aktif atau belum sinkron
        if (!$authenticated_user) {
            $mock_prodi = [
                'ti' => ['kode' => 'TI', 'nama' => 'Teknik Informatika', 'fakultas' => 'Teknik'],
                'si' => ['kode' => 'SI', 'nama' => 'Sistem Informasi', 'fakultas' => 'Teknik'],
                'mj' => ['kode' => 'MJ', 'nama' => 'Manajemen', 'fakultas' => 'Ekonomi'],
                'prodi' => ['kode' => 'TI', 'nama' => 'Teknik Informatika', 'fakultas' => 'Teknik'],
                'prodi_ti' => ['kode' => 'TI', 'nama' => 'Teknik Informatika', 'fakultas' => 'Teknik'],
                'prodi_si' => ['kode' => 'SI', 'nama' => 'Sistem Informasi', 'fakultas' => 'Teknik'],
                'prodi_mj' => ['kode' => 'MJ', 'nama' => 'Manajemen', 'fakultas' => 'Ekonomi']
            ];
            $key = str_replace('prodi_', '', $clean_user);
            if (isset($mock_prodi[$clean_user]) || isset($mock_prodi[$key])) {
                $p_info = $mock_prodi[$clean_user] ?? $mock_prodi[$key];
                if ($password === 'prodi123' || $password === '12345678' || $password === 'pass123' || $password === 'dosen123') {
                    $authenticated_user = [
                        'npm'            => $p_info['kode'],
                        'kode_prodi'     => $p_info['kode'],
                        'nama'           => 'Pengelola Program Studi ' . $p_info['nama'],
                        'nama_prodi'     => $p_info['nama'],
                        'fakultas'       => $p_info['fakultas'],
                        'role'           => 'Prodi',
                        'status'         => 'Pengelola Program Studi',
                        'jabatan'        => 'Koordinator Program Studi ' . $p_info['nama'],
                        'ipk'            => '-',
                        'sks'            => '-'
                    ];
                }
            }
        }
    }

    // C. CEK APAKAH AKUN ADALAH ADMINISTRATOR
    if (!$authenticated_user && ($role === 'admin' || in_array($clean_user, ['admin', 'administrator', 'root']))) {
        if ($clean_user === 'admin' || $clean_user === 'administrator' || $clean_user === 'root') {
            if ($password === 'admin123' || $password === 'admin' || $password === '12345678' || $password === 'pass123') {
                $authenticated_user = [
                    'npm'            => 'ADM-001',
                    'nama'           => 'Administrator Semantik',
                    'jenis_kelamin'  => 'Laki-laki',
                    'tempat_lahir'   => 'Bengkulu',
                    'tanggal_lahir'  => '1995-10-20',
                    'tanggal_masuk'  => '2020-01-01',
                    'alamat'         => 'Pusat Data & Sistem Informasi Universitas',
                    'prodi'          => 'Pusat Komputer & Data',
                    'fakultas'       => 'Universitas Muhammadiyah Bengkulu',
                    'role'           => 'Administrator',
                    'status'         => 'Super Admin',
                    'jabatan'        => 'Pengelola Database & Server Web Semantik',
                    'ipk'            => '-',
                    'sks'            => '-'
                ];
            }
        }
    }

    // D. FALLBACK MAHASISWA (Jika database offline / tidak terkoneksi)
    // Mahasiswa tetap WAJIB menggunakan NPM.
    if (!$authenticated_user && $role === 'mahasiswa') {
        $mock_students = [
            '2023010001' => ['nama' => 'Raka Pratama', 'pass' => 'raka2023', 'prodi' => 'Teknik Informatika', 'fakultas' => 'Teknik'],
            '2023010002' => ['nama' => 'Nadia Putri', 'pass' => 'nadia456', 'prodi' => 'Teknik Informatika', 'fakultas' => 'Teknik'],
            '2023010003' => ['nama' => 'Fikri Hakim', 'pass' => 'fikri789', 'prodi' => 'Sistem Informasi', 'fakultas' => 'Teknik'],
            '2023010004' => ['nama' => 'Salsabila Azzahra', 'pass' => 'salsa101', 'prodi' => 'Manajemen', 'fakultas' => 'Ekonomi'],
        ];
        foreach ($mock_students as $m_npm => $m_data) {
            if ($clean_user === strtolower($m_npm)) {
                if ($password === $m_data['pass'] || $password === 'pass123' || $password === '12345678') {
                    $authenticated_user = [
                        'npm'            => $m_npm,
                        'nama'           => $m_data['nama'],
                        'jenis_kelamin'  => 'Laki-laki',
                        'tempat_lahir'   => 'Bengkulu',
                        'tanggal_lahir'  => '2005-03-12',
                        'tanggal_masuk'  => '2023-08-01',
                        'alamat'         => 'Kota Bengkulu',
                        'prodi'          => $m_data['prodi'],
                        'fakultas'       => $m_data['fakultas'],
                        'role'           => 'Mahasiswa',
                        'status'         => 'Aktif',
                        'ipk'            => '3.82',
                        'sks'            => '68'
                    ];
                    break;
                }
            }
        }
    }

    // Validasi Hasil
    if ($authenticated_user) {
        // Ganti ID sesi setelah autentikasi untuk mencegah session fixation.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user'] = $authenticated_user;
        if ($remember_me) {
            setcookie('portal_remember_user', $username, time() + (86400 * 30), "/");
        }
        header("Location: dashboard.php");
        exit;
    } else {
        header("Location: login.php?error=invalid&role=" . urlencode($role));
        exit;
    }
}

// =========================================================================
// 3. TAMPILAN HALAMAN LOGIN MANDIRI (GET)
// =========================================================================
$current_role = htmlspecialchars($_GET['role'] ?? 'mahasiswa');
$current_tab  = htmlspecialchars($_GET['tab'] ?? 'login');
$error_code   = htmlspecialchars($_GET['error'] ?? '');
$pesan_code   = htmlspecialchars($_GET['pesan'] ?? '');
$prefill_npm  = htmlspecialchars($_GET['npm'] ?? ($_COOKIE['portal_remember_user'] ?? ''));

// Ambil seluruh data mahasiswa dari MySQL untuk keperluan demo login cepat & bantuan
$all_students_db = [];
if ($koneksi && !mysqli_connect_errno()) {
    $q_all = mysqli_query($koneksi, "
        SELECT m.npm, m.nama_mahasiswa, m.password, p.nama_prodi 
        FROM mahasiswa m 
        LEFT JOIN program_studi p ON m.kode_prodi = p.kode_prodi 
        ORDER BY m.npm ASC
    ");
    if ($q_all) {
        while ($row_m = mysqli_fetch_assoc($q_all)) {
            $all_students_db[] = $row_m;
        }
    }
}
if (empty($all_students_db)) {
    $all_students_db = [
        ['npm' => '2023010001', 'nama_mahasiswa' => 'Raka Pratama', 'password' => 'raka2023', 'nama_prodi' => 'Teknik Informatika'],
        ['npm' => '2023010002', 'nama_mahasiswa' => 'Nadia Putri', 'password' => 'nadia456', 'nama_prodi' => 'Teknik Informatika'],
        ['npm' => '2023010003', 'nama_mahasiswa' => 'Fikri Hakim', 'password' => 'fikri789', 'nama_prodi' => 'Sistem Informasi'],
        ['npm' => '2023010004', 'nama_mahasiswa' => 'Salsabila Azzahra', 'password' => 'salsa101', 'nama_prodi' => 'Manajemen']
    ];
}

// Ambil seluruh Program Studi dari MySQL untuk opsi Akun Demo Cepat
$all_prodi_db = [];
if ($koneksi && !mysqli_connect_errno()) {
    $qp_all = mysqli_query($koneksi, "
        SELECT p.*, f.nama_fakultas 
        FROM program_studi p 
        LEFT JOIN fakultas f ON p.kode_fakultas = f.kode_fakultas 
        ORDER BY p.kode_prodi ASC
    ");
    if ($qp_all) {
        while ($row_p = mysqli_fetch_assoc($qp_all)) {
            $all_prodi_db[] = $row_p;
        }
    }
}
if (empty($all_prodi_db)) {
    $all_prodi_db = [
        ['kode_prodi' => 'TI', 'nama_prodi' => 'Teknik Informatika', 'password' => 'prodi123'],
        ['kode_prodi' => 'SI', 'nama_prodi' => 'Sistem Informasi', 'password' => 'prodi123'],
        ['kode_prodi' => 'MJ', 'nama_prodi' => 'Manajemen', 'password' => 'prodi123']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal Akademik & Web Semantik | Universitas Muhammadiyah Bengkulu</title>
    
    <!-- Google Fonts & Font Awesome Icons -->
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
            --accent-amber: #f59e0b;
            --accent-green: #10b981;
            --accent-purple: #a21caf;
            --bg-page: #fdeef5;
            --text-heading: #2d1024;
            --text-body: #4a3341;
            --text-muted: #8a6b7c;
            --card-border: #f8dbe8;
            --radius-lg: 16px;
            --radius-md: 10px;
            --radius-sm: 6px;
            --shadow-card: 0 20px 40px -15px rgba(11, 30, 59, 0.12);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #500724 0%, #500724 40%, #be185d 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-body);
            position: relative;
            overflow-x: hidden;
        }

        /* Background Animated Geometric Elements */
        .bg-pattern {
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(219, 39, 119, 0.25) 0%, transparent 40%),
                radial-gradient(circle at 85% 75%, rgba(124, 58, 237, 0.2) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(245, 158, 11, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }

        .bg-grid {
            position: absolute;
            inset: 0;
            background-size: 40px 40px;
            background-image: 
                linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            pointer-events: none;
            z-index: 1;
        }

        /* Top Navbar */
        .login-topbar {
            position: relative;
            z-index: 10;
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #ffffff;
        }

        .brand-emblem {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fcd34d 0%, #f59e0b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
        }

        .brand-title h2 {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .brand-title p {
            font-size: 0.75rem;
            color: #b59aa8;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            color: #ffffff;
            font-size: 0.86rem;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 30px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: var(--transition);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-3px);
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* Main Container */
        .login-main {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px 50px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 520px;
        }

        /* Main Card */
        .login-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(255, 255, 255, 0.8);
            overflow: hidden;
            transition: var(--transition);
        }

        .card-header-banner {
            background: linear-gradient(135deg, #db2777, #be185d);
            padding: 28px 30px 24px;
            color: #ffffff;
            text-align: center;
            position: relative;
        }

        .card-header-banner h1 {
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .card-header-banner p {
            font-size: 0.85rem;
            color: #fbcfe8;
        }

        /* Navigation Mode Tabs (Masuk vs Daftar) */
        .main-tabs {
            display: flex;
            background: #fdeef5;
            border-bottom: 1px solid var(--card-border);
            padding: 4px;
        }

        .main-tab-btn {
            flex: 1;
            padding: 12px 16px;
            text-align: center;
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--text-muted);
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .main-tab-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        /* Role Selector Pills */
        .role-selector-wrap {
            padding: 20px 28px 10px;
        }

        .role-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .role-pills {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .role-pill-btn {
            padding: 10px 8px;
            border: 2px solid #f8dbe8;
            background: #fff7fb;
            border-radius: var(--radius-md);
            cursor: pointer;
            text-align: center;
            transition: var(--transition);
        }

        .role-pill-btn:hover {
            border-color: #f0c2d6;
            background: #ffffff;
        }

        .role-pill-btn.active {
            border-color: var(--primary);
            background: var(--primary-light);
            color: var(--primary);
        }

        .role-pill-btn i {
            display: block;
            font-size: 1.25rem;
            margin-bottom: 4px;
        }

        .role-pill-btn span {
            font-size: 0.78rem;
            font-weight: 700;
        }

        /* Form Body */
        .card-body {
            padding: 20px 28px 28px;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.86rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 6px;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: #b59aa8;
            font-size: 0.95rem;
            pointer-events: none;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px 14px 12px 42px;
            font-size: 0.92rem;
            font-family: inherit;
            color: var(--text-heading);
            background: #ffffff;
            border: 1.5px solid #f0c2d6;
            border-radius: var(--radius-md);
            outline: none;
            transition: var(--transition);
        }

        .form-input:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.15);
        }

        .toggle-pwd-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #b59aa8;
            cursor: pointer;
            font-size: 0.95rem;
            padding: 4px;
            transition: var(--transition);
        }

        .toggle-pwd-btn:hover {
            color: var(--primary);
        }

        /* Checkbox & Forgot */
        .form-aux {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.84rem;
            margin-bottom: 22px;
        }

        .remember-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--text-body);
        }

        .remember-wrap input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .forgot-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Buttons */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.96rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(219, 39, 119, 0.35);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(219, 39, 119, 0.45);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Quick Demo Account Buttons */
        .demo-box {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--card-border);
        }

        .demo-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .demo-buttons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .btn-demo-quick {
            background: #fff7fb;
            border: 1px solid #f8dbe8;
            border-radius: var(--radius-sm);
            padding: 8px 6px;
            font-size: 0.74rem;
            font-weight: 600;
            color: var(--text-body);
            cursor: pointer;
            text-align: center;
            transition: var(--transition);
            line-height: 1.3;
        }

        .btn-demo-quick:hover {
            background: #ffffff;
            border-color: var(--primary);
            color: var(--primary);
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }

        .btn-demo-quick strong {
            display: block;
            color: var(--text-heading);
            font-size: 0.78rem;
        }

        /* Alert notifications */
        .alert-message {
            border-radius: var(--radius-md);
            padding: 12px 16px;
            font-size: 0.86rem;
            font-weight: 500;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Register Tab Grid */
        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        /* Semantic Footnote */
        .login-footer-info {
            text-align: center;
            margin-top: 20px;
            color: #b59aa8;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .login-footer-info a {
            color: #f472b6;
            text-decoration: none;
            font-weight: 600;
        }

        /* Modal Panduan Lupa Password */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 30, 59, 0.75);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-dialog {
            background: #ffffff;
            width: 100%;
            max-width: 460px;
            border-radius: var(--radius-lg);
            padding: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
        }

        .modal-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #fdeef5;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 1rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .modal-close-btn:hover {
            background: #f8dbe8;
            color: var(--text-heading);
        }

        @media (max-width: 576px) {
            .login-topbar {
                padding: 16px 20px;
            }
            .role-pills {
                grid-template-columns: 1fr;
            }
            .demo-buttons-grid {
                grid-template-columns: 1fr;
            }
            .form-row-2 {
                grid-template-columns: 1fr;
            }
            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="bg-pattern"></div>
    <div class="bg-grid"></div>

    <!-- Header Atas -->
    <header class="login-topbar">
        <a href="index.php" class="brand-link">
            <div class="brand-emblem">
                <i class="fa-solid fa-graduation-cap" style="color: #9d174d; font-size: 1.25rem;"></i>
            </div>
            <div class="brand-title">
                <h2>Portal Akademik</h2>
                <p>Universitas Muhammadiyah Bengkulu</p>
            </div>
        </a>
        <a href="index.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
        </a>
    </header>

    <!-- Konten Form Login & Register -->
    <main class="login-main">
        <div class="login-wrapper">

            <!-- Card Utama -->
            <div class="login-card">
                <!-- Header Card -->
                <div class="card-header-banner">
                    <h1>Sistem Informasi Akademik</h1>
                    <p>Autentikasi Terintegrasi Berbasis Web Semantik & RDF</p>
                </div>

                <!-- Tab Masuk / Pendaftaran Baru -->
                <div class="main-tabs">
                    <button type="button" class="main-tab-btn <?= ($current_tab !== 'register') ? 'active' : '' ?>" id="tabBtnLogin" onclick="switchMainTab('login')">
                        <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Akun
                    </button>
                    <button type="button" class="main-tab-btn <?= ($current_tab === 'register') ? 'active' : '' ?>" id="tabBtnRegister" onclick="switchMainTab('register')">
                        <i class="fa-solid fa-user-plus"></i> Pendaftaran Mahasiswa
                    </button>
                </div>

                <!-- ========================================================
                     TAB KONTEN 1: FORM LOGIN
                     ======================================================== -->
                <div id="contentLogin" style="display: <?= ($current_tab !== 'register') ? 'block' : 'none' ?>;">
                    
                    <!-- Alert Notifikasi -->
                    <div style="padding: 20px 28px 0;">
                        <?php if ($error_code === 'invalid'): ?>
                            <div class="alert-message alert-danger">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>Akun atau kata sandi tidak cocok. Silakan periksa kembali atau gunakan akun demo di bawah.</span>
                            </div>
                        <?php elseif ($error_code === 'empty'): ?>
                            <div class="alert-message alert-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Harap masukkan NPM dan kata sandi Anda.</span>
                            </div>
                        <?php elseif ($error_code === 'unauthorized'): ?>
                            <div class="alert-message alert-danger">
                                <i class="fa-solid fa-lock"></i>
                                <span>Sesi Anda belum aktif. Silakan masuk terlebih dahulu untuk mengakses dashboard.</span>
                            </div>
                        <?php elseif ($pesan_code === 'logout'): ?>
                            <div class="alert-message alert-success">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Anda telah berhasil keluar dari sistem. Sampai jumpa kembali!</span>
                            </div>
                        <?php elseif ($pesan_code === 'register_sukses'): ?>
                            <div class="alert-message alert-success">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Pendaftaran berhasil! Silakan masuk dengan NPM: <strong><?= $prefill_npm ?></strong></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pilihan Peran (Role Selector) -->
                    <div class="role-selector-wrap">
                        <span class="role-label"><i class="fa-solid fa-user-shield"></i> Pilih Peran Akses:</span>
                        <div class="role-pills">
                            <button type="button" class="role-pill-btn <?= ($current_role === 'mahasiswa') ? 'active' : '' ?>" id="roleBtnMhs" onclick="setRole('mahasiswa')">
                                <i class="fa-solid fa-user-graduate"></i>
                                <span>Mahasiswa</span>
                            </button>
                            <button type="button" class="role-pill-btn <?= ($current_role === 'prodi' || $current_role === 'dosen') ? 'active' : '' ?>" id="roleBtnProdi" onclick="setRole('prodi')">
                                <i class="fa-solid fa-building-columns"></i>
                                <span>Program Studi</span>
                            </button>
                            <button type="button" class="role-pill-btn <?= ($current_role === 'admin') ? 'active' : '' ?>" id="roleBtnAdmin" onclick="setRole('admin')">
                                <i class="fa-solid fa-gear"></i>
                                <span>Administrator</span>
                            </button>
                        </div>
                    </div>

                    <!-- Form Login POST -->
                    <form action="login.php" method="POST" class="card-body" id="formLogin">
                        <input type="hidden" name="role" id="inputRole" value="<?= ($current_role === 'dosen') ? 'prodi' : $current_role ?>">

                        <!-- Input NPM / Nomor Pokok Mahasiswa -->
                        <div class="form-group">
                            <label class="form-label" for="inputUsername">
                                <span id="labelUser"><i class="fa-solid fa-id-card"></i> NPM / Nomor Pokok Mahasiswa</span>
                            </label>
                            <div class="input-group">
                                <i class="fa-solid fa-user input-icon"></i>
                                <input type="text" name="username" id="inputUsername" class="form-input" 
                                       placeholder="Contoh: 2023010001" 
                                       value="<?= $prefill_npm ?>" required autofocus>
                            </div>
                        </div>

                        <!-- Input Password -->
                        <div class="form-group">
                            <div class="form-label">
                                <span><i class="fa-solid fa-lock"></i> Kata Sandi</span>
                            </div>
                            <div class="input-group">
                                <i class="fa-solid fa-key input-icon"></i>
                                <input type="password" name="password" id="inputPassword" class="form-input" 
                                       placeholder="Masukkan kata sandi akun" required>
                                <button type="button" class="toggle-pwd-btn" onclick="togglePassword('inputPassword', this)" title="Tampilkan / Sembunyikan Kata Sandi">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Opsi Bantuan & Ingat Saya -->
                        <div class="form-aux">
                            <label class="remember-wrap">
                                <input type="checkbox" name="remember_me" value="1" <?= !empty($prefill_npm) ? 'checked' : '' ?>>
                                <span>Ingat Akun Saya</span>
                            </label>
                            <a href="javascript:void(0)" class="forgot-link" onclick="openForgotModal()">
                                <i class="fa-solid fa-circle-question"></i> Bantuan Masuk?
                            </a>
                        </div>

                        <!-- Tombol Masuk Submit -->
                        <button type="submit" class="btn-submit" id="btnSubmitLogin">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Masuk ke Portal Akademik</span>
                        </button>

                        <!-- Pilihan Akun Demo Cepat (Quick-Fill) -->
                        <div class="demo-box">
                            <div class="demo-title">
                                <i class="fa-solid fa-user-graduate" style="color: #db2777;"></i> Akun Mahasiswa Terdaftar (Klik untuk Isi & Masuk):
                            </div>
                            <div class="demo-buttons-grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));">
                                <?php foreach ($all_students_db as $sm): ?>
                                    <button type="button" class="btn-demo-quick" onclick="quickFill('mahasiswa', '<?= htmlspecialchars($sm['npm']) ?>', '<?= htmlspecialchars($sm['password'] ?? 'pass123') ?>')">
                                        <strong><?= htmlspecialchars($sm['nama_mahasiswa']) ?></strong>
                                        <span style="font-size: 0.72rem; color: #8a6b7c;">NPM: <?= htmlspecialchars($sm['npm']) ?></span>
                                        <span style="font-size: 0.68rem; color: #be185d; display: block;">Sandi: <?= htmlspecialchars($sm['password'] ?? 'pass123') ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <div class="demo-title" style="margin-top: 14px;">
                                <i class="fa-solid fa-building-columns" style="color: #a21caf;"></i> Akun Program Studi & Administrator:
                            </div>
                            <div class="demo-buttons-grid" style="grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));">
                                <?php foreach ($all_prodi_db as $pr): ?>
                                    <button type="button" class="btn-demo-quick" onclick="quickFill('prodi', '<?= htmlspecialchars($pr['kode_prodi']) ?>', '<?= htmlspecialchars($pr['password'] ?? 'prodi123') ?>')">
                                        <strong>Prodi <?= htmlspecialchars($pr['kode_prodi']) ?></strong>
                                        <span style="font-size: 0.71rem; color: #8a6b7c;"><?= htmlspecialchars($pr['nama_prodi']) ?></span>
                                        <span style="font-size: 0.68rem; color: #a21caf; display: block;">Sandi: <?= htmlspecialchars($pr['password'] ?? 'prodi123') ?></span>
                                    </button>
                                <?php endforeach; ?>
                                <button type="button" class="btn-demo-quick" onclick="quickFill('admin', 'admin', 'admin123')">
                                    <strong>Administrator</strong>
                                    <span style="font-size: 0.71rem; color: #8a6b7c;">Pusat Sistem</span>
                                    <span style="font-size: 0.68rem; color: #dc2626; display: block;">Sandi: admin123</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ========================================================
                     TAB KONTEN 2: PENDAFTARAN MAHASISWA BARU (REGISTER)
                     ======================================================== -->
                <div id="contentRegister" style="display: <?= ($current_tab === 'register') ? 'block' : 'none' ?>;">
                    
                    <div style="padding: 20px 28px 0;">
                        <?php if ($error_code === 'npm_exists'): ?>
                            <div class="alert-message alert-danger">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>NPM tersebut sudah terdaftar di sistem. Silakan gunakan NPM lain atau login langsung.</span>
                            </div>
                        <?php elseif ($error_code === 'empty_reg'): ?>
                            <div class="alert-message alert-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Mohon lengkapi semua data wajib pada formulir pendaftaran.</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form action="login.php" method="POST" class="card-body">
                        <input type="hidden" name="action" value="register">

                        <!-- NPM & Nama Lengkap -->
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label" for="regNpm"><i class="fa-solid fa-id-badge"></i> NPM *</label>
                                <input type="text" name="npm" id="regNpm" class="form-input" style="padding-left: 14px;" placeholder="Contoh: 2023010005" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="regNama"><i class="fa-solid fa-user"></i> Nama Lengkap *</label>
                                <input type="text" name="nama_mahasiswa" id="regNama" class="form-input" style="padding-left: 14px;" placeholder="Nama mahasiswa..." required>
                            </div>
                        </div>

                        <!-- Program Studi & Jenis Kelamin -->
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label" for="regProdi"><i class="fa-solid fa-graduation-cap"></i> Program Studi *</label>
                                <select name="kode_prodi" id="regProdi" class="form-select" style="padding-left: 14px;" required>
                                    <option value="TI">Teknik Informatika (Fakultas Teknik)</option>
                                    <option value="SI">Sistem Informasi (Fakultas Teknik)</option>
                                    <option value="MJ">Manajemen (Fakultas Ekonomi)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="regJk"><i class="fa-solid fa-venus-mars"></i> Jenis Kelamin</label>
                                <select name="jenis_kelamin" id="regJk" class="form-select" style="padding-left: 14px;">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tempat & Tanggal Lahir -->
                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label" for="regTempat"><i class="fa-solid fa-location-dot"></i> Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" id="regTempat" class="form-input" style="padding-left: 14px;" value="Bengkulu">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="regTgl"><i class="fa-solid fa-calendar"></i> Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" id="regTgl" class="form-input" style="padding-left: 14px;" value="2005-01-01">
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div class="form-group">
                            <label class="form-label" for="regAlamat"><i class="fa-solid fa-map-pin"></i> Alamat Domisili</label>
                            <input type="text" name="alamat" id="regAlamat" class="form-input" style="padding-left: 14px;" placeholder="Jl. Raya No. 123, Bengkulu">
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <label class="form-label" for="regPass"><i class="fa-solid fa-key"></i> Kata Sandi Akun *</label>
                            <div class="input-group">
                                <input type="password" name="password" id="regPass" class="form-input" style="padding-left: 14px;" placeholder="Buat kata sandi minimal 6 karakter" required>
                                <button type="button" class="toggle-pwd-btn" onclick="togglePassword('regPass', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Tombol Submit Registrasi -->
                        <button type="submit" class="btn-submit" style="background: linear-gradient(135deg, #059669 0%, #be185d 100%);">
                            <i class="fa-solid fa-user-plus"></i>
                            <span>Daftar Mahasiswa Baru</span>
                        </button>
                    </form>
                </div>

            </div>

            <!-- Footer Teks Semantik -->
            <div class="login-footer-info">
                <p>Data tersimpan di basis data relasional & terpetakan secara otomatis ke <br><strong>W3C RDF Semantic Graph Database</strong>.</p>
                <p style="margin-top: 6px;">&copy; <?= date('Y') ?> Universitas Muhammadiyah Bengkulu &bull; Tugas Pemrograman Web Semantik</p>
            </div>

        </div>
    </main>

    <!-- ========================================================
         MODAL PANDUAN LUPA PASSWORD / BANTUAN MASUK
         ======================================================== -->
    <div class="modal-overlay" id="forgotModal" onclick="closeOnBackdrop(event, 'forgotModal')">
        <div class="modal-dialog">
            <button class="modal-close-btn" onclick="closeForgotModal()">&times;</button>
            <div style="text-align: center; margin-bottom: 18px;">
                <div style="width: 52px; height: 52px; background: #fce7f3; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 12px;">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-heading);">Bantuan Akun & Kata Sandi</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Panduan autentikasi Portal Akademik Web Semantik</p>
            </div>

            <div style="background: #fff7fb; border: 1px solid var(--card-border); border-radius: 10px; padding: 16px; margin-bottom: 20px; font-size: 0.86rem; line-height: 1.6;">
                <p style="margin-bottom: 8px;"><strong>1. Seluruh Akun Mahasiswa Terdaftar (<?= count($all_students_db) ?> Mahasiswa):</strong></p>
                <ul style="padding-left: 20px; color: var(--text-body); margin-bottom: 12px; max-height: 180px; overflow-y: auto;">
                    <?php foreach ($all_students_db as $sm): ?>
                        <li>NPM: <code><?= htmlspecialchars($sm['npm']) ?></code> (<?= htmlspecialchars($sm['nama_mahasiswa']) ?>) | Sandi: <code><?= htmlspecialchars($sm['password'] ?? 'pass123') ?></code></li>
                    <?php endforeach; ?>
                </ul>

                <p style="margin-bottom: 8px;"><strong>2. Akun Program Studi (Prodi):</strong></p>
                <ul style="padding-left: 20px; color: var(--text-body); margin-bottom: 12px;">
                    <?php foreach ($all_prodi_db as $pr): ?>
                        <li>Kode Prodi: <code><?= htmlspecialchars($pr['kode_prodi']) ?></code> (<?= htmlspecialchars($pr['nama_prodi']) ?>) | Sandi: <code><?= htmlspecialchars($pr['password'] ?? 'prodi123') ?></code></li>
                    <?php endforeach; ?>
                </ul>

                <p style="margin-bottom: 8px;"><strong>3. Akun Administrator:</strong></p>
                <ul style="padding-left: 20px; color: var(--text-body);">
                    <li>Username: <code>admin</code> | Sandi: <code>admin123</code></li>
                </ul>
            </div>

            <button type="button" class="btn-submit" onclick="closeForgotModal()">
                Saya Mengerti
            </button>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC LENGKAP -->
    <script>
        // Toggle Masuk vs Daftar Mahasiswa
        function switchMainTab(tab) {
            const btnLogin = document.getElementById('tabBtnLogin');
            const btnReg = document.getElementById('tabBtnRegister');
            const cLogin = document.getElementById('contentLogin');
            const cReg = document.getElementById('contentRegister');

            if (tab === 'register') {
                btnLogin.classList.remove('active');
                btnReg.classList.add('active');
                cLogin.style.display = 'none';
                cReg.style.display = 'block';
            } else {
                btnReg.classList.remove('active');
                btnLogin.classList.add('active');
                cReg.style.display = 'none';
                cLogin.style.display = 'block';
            }
        }

        // Set Role
        function setRole(role) {
            document.getElementById('inputRole').value = role;
            
            const btnMhs = document.getElementById('roleBtnMhs');
            const btnProdi = document.getElementById('roleBtnProdi');
            const btnAdmin = document.getElementById('roleBtnAdmin');
            const labelUser = document.getElementById('labelUser');
            const inputUser = document.getElementById('inputUsername');
            const btnSubmit = document.getElementById('btnSubmitLogin');

            if (btnMhs) btnMhs.classList.remove('active');
            if (btnProdi) btnProdi.classList.remove('active');
            if (btnAdmin) btnAdmin.classList.remove('active');

            if (role === 'mahasiswa') {
                if (btnMhs) btnMhs.classList.add('active');
                labelUser.innerHTML = '<i class="fa-solid fa-id-card"></i> NPM / Nomor Pokok Mahasiswa';
                inputUser.placeholder = 'Contoh: 2023010001';
                btnSubmit.querySelector('span').textContent = 'Masuk sebagai Mahasiswa';
            } else if (role === 'prodi' || role === 'dosen') {
                if (btnProdi) btnProdi.classList.add('active');
                labelUser.innerHTML = '<i class="fa-solid fa-building-columns"></i> Kode / Akun Program Studi (Prodi)';
                inputUser.placeholder = 'Contoh: TI, SI, MJ atau prodi';
                btnSubmit.querySelector('span').textContent = 'Masuk sebagai Program Studi (Prodi)';
            } else if (role === 'admin') {
                if (btnAdmin) btnAdmin.classList.add('active');
                labelUser.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Username Administrator';
                inputUser.placeholder = 'Contoh: admin';
                btnSubmit.querySelector('span').textContent = 'Masuk sebagai Administrator';
            }
        }

        // Quick Fill Demo Credentials
        function quickFill(role, user, pass) {
            switchMainTab('login');
            setRole(role);
            document.getElementById('inputUsername').value = user;
            document.getElementById('inputPassword').value = pass;
            
            // Efek visual highlight sesaat
            const inputU = document.getElementById('inputUsername');
            const inputP = document.getElementById('inputPassword');
            inputU.style.borderColor = '#16a34a';
            inputP.style.borderColor = '#16a34a';
            setTimeout(() => {
                inputU.style.borderColor = '';
                inputP.style.borderColor = '';
            }, 600);
        }

        // Toggle Password Show/Hide
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Modal Bantuan
        function openForgotModal() {
            document.getElementById('forgotModal').classList.add('active');
        }

        function closeForgotModal() {
            document.getElementById('forgotModal').classList.remove('active');
        }

        function closeOnBackdrop(e, modalId) {
            if (e.target.id === modalId) {
                document.getElementById(modalId).classList.remove('active');
            }
        }

        // Inisialisasi awal role
        window.addEventListener('DOMContentLoaded', () => {
            const currentRole = '<?= $current_role ?>';
            if (currentRole) {
                setRole(currentRole);
            }
        });
    </script>
</body>
</html>
