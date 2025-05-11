<?php
session_start();
include 'db_config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$isAdmin = ($_SESSION['role'] === 'admin');

function tambahTransaksi($jenis, $deskripsi, $jumlah, $payment_method = null, $payment_reference = null, $nama = null) {
    global $conn;
    $sql = "INSERT INTO transactions (jenis, deskripsi, jumlah, payment_method, payment_reference, nama) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsss", $jenis, $deskripsi, $jumlah, $payment_method, $payment_reference, $nama);
    $stmt->execute();
    $stmt->close();
}

function hapusTransaksi($id) {
    global $conn;
    $sql = "DELETE FROM transactions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Ambil nilai filter dari $_GET
$periode = isset($_GET['periode']) ? $_GET['periode'] : '';
$jenis_filter = isset($_GET['jenis']) && in_array($_GET['jenis'], ['pemasukan', 'pengeluaran']) ? $_GET['jenis'] : '';
$program = isset($_GET['program']) ? $_GET['program'] : '';

function ambilTransaksi($periode = '', $jenis_filter = '', $program = '') {
    global $conn;
    $sql = "SELECT id, jenis, deskripsi, jumlah, tanggal, payment_method, payment_reference, nama FROM transactions WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($periode)) {
        $sql .= " AND DATE(tanggal) = ?";
        $params[] = $periode;
        $types .= 's';
    }

    if (!empty($jenis_filter)) {
        $sql .= " AND jenis = ?";
        $params[] = $jenis_filter;
        $types .= 's';
    }

    if (!empty($program)) {
        $sql .= " AND deskripsi LIKE ?";
        $params[] = "%$program%";
        $types .= 's';
    }

    $sql .= " ORDER BY tanggal DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fungsi baru untuk menghitung total pemasukan dan pengeluaran dengan filter
function getTotalIncomeExpense($periode = '', $jenis_filter = '', $program = '') {
    global $conn;
    $result = [
        'income' => 0,
        'expense' => 0
    ];
    
    $sql = "SELECT jenis, SUM(jumlah) as total FROM transactions WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($periode)) {
        $sql .= " AND DATE(tanggal) = ?";
        $params[] = $periode;
        $types .= 's';
    }

    if (!empty($jenis_filter)) {
        $sql .= " AND jenis = ?";
        $params[] = $jenis_filter;
        $types .= 's';
    }

    if (!empty($program)) {
        $sql .= " AND deskripsi LIKE ?";
        $params[] = "%$program%";
        $types .= 's';
    }

    $sql .= " GROUP BY jenis";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $query = $stmt->get_result();
    
    while ($row = $query->fetch_assoc()) {
        if ($row['jenis'] === 'pemasukan') {
            $result['income'] = $row['total'] ?? 0;
        } else {
            $result['expense'] = $row['total'] ?? 0;
        }
    }
    
    return $result;
}

// Panggil fungsi dengan parameter filter yang sama
$totals = getTotalIncomeExpense($periode, $jenis_filter, $program);
$total_saldo = $totals['income'] - $totals['expense'];

// Handle tambah transaksi donasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_donasi'])) {
    try {
        // Validasi input
        if (empty($_POST['deskripsi'])) {
            throw new Exception("Deskripsi program harus diisi");
        }
        
        if (empty($_POST['payment_method'])) {
            throw new Exception("Metode pembayaran harus dipilih");
        }
        
        // Bersihkan input
        $deskripsi = htmlspecialchars(trim($_POST['deskripsi']));
        $payment_method = htmlspecialchars(trim($_POST['payment_method']));
        $payment_reference = isset($_POST['payment_reference']) ? htmlspecialchars(trim($_POST['payment_reference'])) : null;
        
        // Tambahkan data ke transaksi
        tambahTransaksi(
            'pemasukan', 
            $deskripsi, 
            0, // Jumlah dihapus, diisi dengan 0
            $payment_method,
            $payment_reference,
            $_POST['nama'] // Menambahkan nama ke parameter
        );
        
        // Set session message berdasarkan metode pembayaran
        if (strtolower($payment_method) === 'tunai') {
            $_SESSION['success'] = "Silahkan menuju ke kantor takmir masjid untuk konfirmasi pembayaran donasi";
        } else {
            $_SESSION['success'] = "Tunggu beberapa saat, admin akan mengkonfirmasi uang donasi Anda";
        }
        
        header("Location: dashboard.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        $_SESSION['form_data'] = $_POST; // Simpan data form untuk prefilling
        header("Location: dashboard.php");
        exit;
    }
}

function getTotalSaldo() {
    global $conn;
    $sql = "SELECT SUM(CASE WHEN jenis='pemasukan' THEN jumlah ELSE -jumlah END) AS saldo FROM transactions";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['saldo'] ?? 0;
}

function editTransaksi($id, $jenis, $deskripsi, $jumlah, $payment_method = null, $payment_reference = null) {
    global $conn;
    $sql = "UPDATE transactions SET jenis = ?, deskripsi = ?, jumlah = ?, payment_method = ?, payment_reference = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdssi", $jenis, $deskripsi, $jumlah, $payment_method, $payment_reference, $id);
    $stmt->execute();
    $stmt->close();
}

// Aksi hanya bisa dilakukan oleh admin
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle tambah transaksi baru
    if (isset($_POST['jenis']) && isset($_POST['deskripsi']) && isset($_POST['jumlah']) && !isset($_POST['simpan_edit'])) {
        try {
            // Validasi input dasar
            if (empty($_POST['deskripsi'])) {
                throw new Exception("Deskripsi transaksi harus diisi");
            }
            
            if (!is_numeric($_POST['jumlah']) || (float)$_POST['jumlah'] <= 0) {
                throw new Exception("Jumlah harus berupa angka positif");
            }
            
            $jenis = $_POST['jenis'];
            $deskripsi = htmlspecialchars(trim($_POST['deskripsi']));
            $jumlah = (float)$_POST['jumlah'];
            $payment_method = null;
            $payment_reference = null;
            $nama = htmlspecialchars(trim($_POST['nama'])); // Ambil input nama
            
            // Validasi khusus untuk pengeluaran
            if ($jenis === 'pengeluaran') {
                if (empty($_POST['payment_method'])) {
                    throw new Exception("Metode pembayaran harus dipilih untuk pengeluaran");
                }
                
                $payment_method = htmlspecialchars(trim($_POST['payment_method']));
                
                // Validasi referensi untuk non-tunai
                if ($payment_method !== 'Tunai') {
                    if (empty($_POST['payment_reference'])) {
                        throw new Exception("Nomor referensi harus diisi untuk pembayaran non-tunai");
                    }
                    $payment_reference = htmlspecialchars(trim($_POST['payment_reference']));
                }
            }

            // Simpan ke database
            tambahTransaksi(
                $jenis,
                $deskripsi,
                $jumlah,
                $payment_method,
                $payment_reference,
                $nama
            );

            $_SESSION['success'] = "Transaksi berhasil dicatat!";
            header("Location: dashboard.php");
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            header("Location: dashboard.php");
            exit;
        }
    } 
    // Handle hapus transaksi
    elseif (isset($_POST['delete_id'])) {
        hapusTransaksi($_POST['delete_id']);
        $_SESSION['success'] = "Transaksi berhasil dihapus!";
        header("Location: dashboard.php");
        exit;
    } 
    // Handle edit transaksi
    elseif (isset($_POST['simpan_edit'])) {
        try {
            // Kontrol input
            $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
            $jenis = isset($_POST['jenis']) && in_array($_POST['jenis'], ['pemasukan', 'pengeluaran']) ? $_POST['jenis'] : '';
            $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
            $jumlah = isset($_POST['jumlah']) ? (float)$_POST['jumlah'] : 0;
            $payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : null;
            $payment_reference = isset($_POST['payment_reference']) ? trim($_POST['payment_reference']) : null;
            $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    
            // Validasi input
            if (empty($jenis)) throw new Exception("Jenis transaksi harus dipilih");
            if (empty($deskripsi)) throw new Exception("Deskripsi tidak boleh kosong");
            if ($jumlah <= 0) throw new Exception("Jumlah harus lebih dari 0");
            
            if ($jenis === 'pengeluaran') {
                if (empty($payment_method)) throw new Exception("Metode pembayaran harus dipilih untuk pengeluaran");
                if ($payment_method !== 'Tunai' && empty($payment_reference)) throw new Exception("Nomor referensi harus diisi untuk pembayaran non-tunai");
            }
    
            // Update transaksi
            editTransaksi(
                $edit_id,
                $jenis,
                $deskripsi,
                $jumlah,
                $payment_method,
                $payment_reference
            );
    
            $_SESSION['success'] = "Perubahan transaksi berhasil disimpan!";
            header("Location: dashboard.php");
            exit;
    
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: dashboard.php");
            exit;
        }
}
}

// Inisialisasi variabel untuk edit modal
$edit_id = '';
$edit_jenis = '';
$edit_deskripsi = '';
$edit_jumlah = '';

if ($isAdmin && isset($_POST['edit_button'])) {
    // Ketika tombol edit ditekan, ambil data transaksi yang ingin diedit
    $edit_id = $_POST['edit_id'];
    
    // Ambil data transaksi dari database berdasarkan ID
    $sql = "SELECT jenis, deskripsi, jumlah, payment_method, payment_reference, nama FROM transactions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($edit_jenis, $edit_deskripsi, $edit_jumlah, $edit_payment_method, $edit_payment_reference, $edit_nama);
        $stmt->fetch();
    }
    $stmt->close();
}

$transaksi = ambilTransaksi($periode, $jenis_filter, $program);
$total_saldo = getTotalSaldo();
$labels = array_map(fn($t) => $t['tanggal'], $transaksi);
$amounts = array_map(fn($t) => $t['jenis'] === 'pemasukan' ? $t['jumlah'] : -$t['jumlah'], $transaksi);

// Proses data untuk chart sebelum bagian HTML
$formatted_labels = array_map(function($date) {
    $dateObj = new DateTime($date);
    return $dateObj->format('d M');
}, $labels);

$background_colors = array_map(function($v) {
    return $v > 0 ? '#10b981' : '#ef4444';
}, $amounts);

$border_colors = array_map(function($v) {
    return $v > 0 ? '#059669' : '#dc2626';
}, $amounts);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Keuangan Masjid</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tambahkan sebelum script lainnya -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #219150;
            --secondary: #34d399;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light-bg: #f8fafc;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #1a7a43;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .income {
            color: #219150;
            font-weight: 500;
        }

        .expense {
            color: var(--danger);
            font-weight: 500;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        th {
            background-color: var(--primary);
            color: white;
            position: sticky;
            top: 0;
        }
        
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .saldo-card {
            background: linear-gradient(135deg, #219150 0%, #34d399 100%);
            color: white;
            border-radius: 12px;
        }
        
        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(33, 145, 80, 0.2);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: #e6f2eb;
            color: #1a7a43;
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .modal {
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }

        /* Payment method styles */
        .payment-method-badge {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            display: inline-block;
        }

        .payment-reference {
            font-size: 11px;
            color: #1e40af;
            display: block;
            margin-top: 2px;
        }

        /* Payment reference field animation */
        #paymentReferenceField {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: all 0.3s ease;
        }

        #paymentReferenceField.show {
            max-height: 100px;
            opacity: 1;
            margin-top: 1rem;
        }

        /* Table column alignment */
        th:nth-child(1), td:nth-child(1) { width: 15%; } /* Tanggal */
        th:nth-child(2), td:nth-child(2) { width: 12%; } /* Jenis */
        th:nth-child(3), td:nth-child(3) { width: 25%; } /* Keterangan */
        th:nth-child(4), td:nth-child(4) { width: 20%; } /* Metode Pembayaran */
        th:nth-child(5), td:nth-child(5) { width: 15%; text-align: right; } /* Jumlah */
        <?php if ($isAdmin): ?>
        th:nth-child(6), td:nth-child(6) { width: 13%; text-align: center; } /* Aksi */
        <?php endif; ?>

        /* Tambahkan ini ke bagian CSS */
#paymentReferenceField.hidden {
    display: none;
}

#paymentReferenceField {
    transition: all 0.3s ease;
    overflow: hidden;
}

/* Style untuk error message */
.error-message {
    color: #dc2626;
    background-color: #fee2e2;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.error-message i {
    margin-right: 0.5rem;
}

/* Tambahkan di bagian CSS */
.income-card {
    background-color: #f0fdf4;
    border-left: 4px solid #10b981;
}

.expense-card {
    background-color: #fef2f2;
    border-left: 4px solid #ef4444;
}

/* Tambahkan di bagian CSS */
#adminPaymentMethod, #adminPaymentReference {
    display: none;
}

#adminPaymentMethod.show, #adminPaymentReference.show {
    display: block;
}

/* Tambahkan di bagian CSS */
#adminPaymentMethod, #adminPaymentReference {
    transition: all 0.3s ease;
    overflow: hidden;
}

#adminPaymentMethod.hidden, #adminPaymentReference.hidden {
    display: none;
}

#adminPaymentMethod.show, #adminPaymentReference.show {
    display: block;
}
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
       <!-- Header Section -->
<header class="flex flex-col md:flex-row justify-between items-center mb-8">
    <div class="mb-4 md:mb-0">
        <a href="index.php" class="flex items-center"> <!-- Added link here -->
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-mosque text-[#219150] mr-2"></i> Baitulmaal Dashboard
            </h1>
        </a>
        <p class="text-gray-600">Manajemen keuangan masjid yang transparan</p>
    </div>
    
    <div class="flex items-center space-x-4">
        <div class="text-right">
            <p class="text-sm text-gray-500">Halo,</p>
            <p class="font-medium text-blue-600"><?= $_SESSION['username'] ?></p>
        </div>
        <a href="logout.php" class="flex items-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
    </div>
</header>
        
        <!-- Saldo Card -->
        <div class="saldo-card p-6 mb-8 shadow-lg">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-medium">Total Saldo Tersedia</p>
                    <h2 class="text-4xl font-bold">Rp <?= number_format($total_saldo, 2, ',', '.') ?></h2>
                </div>
                <div class="bg-white bg-opacity-20 p-3 rounded-full">
                    <i class="fas fa-wallet text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Income & Expense Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Income Card -->
            <div class="card p-6 bg-green-50 border border-green-100">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-green-600">Total Pemasukan</p>
                        <h2 class="text-2xl font-bold text-green-700">Rp <?= number_format($totals['income'], 2, ',', '.') ?></h2>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-arrow-down text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Expense Card -->
            <div class="card p-6 bg-red-50 border border-red-100">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-red-600">Total Pengeluaran</p>
                        <h2 class="text-2xl font-bold text-red-700">Rp <?= number_format($totals['expense'], 2, ',', '.') ?></h2>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-arrow-up text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <?php if ($isAdmin): ?>
        <!-- Admin Form -->
        <div class="card p-6">
            <div class="flex items-center mb-4">
                <div class="bg-[#e6f2eb] p-2 rounded-full mr-3">
                    <i class="fas fa-plus-circle text-[#219150]"></i>
                </div>
                <h3 class="text-lg font-semibold">Tambah Transaksi</h3>
            </div>
            <form method="POST" class="space-y-4" id="adminTransactionForm">
                <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama <span class="text-red-500">*</span></label>
                <input type="text" name="nama" placeholder="Nama Pengguna" class="form-control w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                    <select name="jenis" id="jenisTransaksi" class="form-control w-full" required>
                        <option value="pemasukan">Pemasukan</option>
                        <option value="pengeluaran">Pengeluaran</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <input type="text" name="deskripsi" placeholder="Contoh: Donasi Jumat / Pembelian Perlengkapan" class="form-control w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp)</label>
                    <input type="number" name="jumlah" placeholder="500000" class="form-control w-full" required min="1">
                </div>

                <!-- Metode Pembayaran untuk Pengeluaran -->
                <div id="adminPaymentMethod" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Metode Pembayaran</label>
                    <select name="payment_method" id="adminPaymentMethodSelect" class="form-control w-full">
                        <option value="Tunai">Tunai</option>
                        <?php
                        $methods = $conn->query("SELECT * FROM payment_methods WHERE is_active = TRUE AND type IN ('bank', 'digital')");
                        while ($method = $methods->fetch_assoc()):
                        ?>
                        <option value="<?= htmlspecialchars($method['name']) ?>">
                            <?= htmlspecialchars($method['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Referensi Pembayaran untuk Non-Tunai -->
                <div id="adminPaymentReference" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Rekening Tujuan</label>
                    <input type="text" name="payment_reference" id="adminPaymentReferenceInput" placeholder="No. Rekening / Telephone" class="form-control w-full">
                </div>
                
                <button type="submit" class="btn-primary w-full py-3 rounded-lg font-medium flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Simpan Transaksi
                </button>
            </form>
        </div>
    <?php endif; ?>
    
    <?php if (!$isAdmin): ?>
                <!-- User Donation Form -->
                <div class="card p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 p-2 rounded-full mr-3">
                            <i class="fas fa-hand-holding-heart text-green-500"></i>
                        </div>
                        <h3 class="text-lg font-semibold">Form Donasi</h3>
                    </div>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="error-message mb-4">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= $_SESSION['error'] ?>
                        </div>
                    <?php unset($_SESSION['error']); endif; ?>

                    <?php $form_data = $_SESSION['form_data'] ?? []; ?>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="jenis" value="pemasukan">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Asli / Samaran<span class="text-red-500">*</span></label>
                            <input type="text" name="nama" placeholder="Nama Pengguna" 
                                   class="form-control w-full" required
                                   value="<?= htmlspecialchars($form_data['nama'] ?? '') ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Untuk Program <span class="text-red-500">*</span></label>
                            <input type="text" name="deskripsi" placeholder="Contoh: Pembangunan Masjid" 
                                   class="form-control w-full" required
                                   value="<?= htmlspecialchars($form_data['deskripsi'] ?? '') ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Metode Pembayaran <span class="text-red-500">*</span></label>
                            <select name="payment_method" id="userPaymentMethod" class="form-control w-full" required>
                                <option value="">Pilih Metode Pembayaran</option>
                                <?php
                                $methods = $conn->query("SELECT * FROM payment_methods WHERE is_active = TRUE");
                                while ($method = $methods->fetch_assoc()):
                                ?>
                                <option value="<?= htmlspecialchars($method['name']) ?>"
                                    <?= (isset($form_data['payment_method']) && $form_data['payment_method'] === $method['name']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($method['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div id="paymentReferenceField" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Rekening Anda</label>
                            <input type="text" name="payment_reference" placeholder="Contoh: No. rekening / Telephone" 
                                   class="form-control w-full"
                                   value="<?= htmlspecialchars($form_data['payment_reference'] ?? '') ?>">
                            <p class="text-xs text-gray-500 mt-1">Silakan ditransfer lewat 023417654980 / 089661832244</p>
                        </div>
                        
                        <button type="submit" name="simpan_donasi" class="btn-success w-full py-3 rounded-lg font-medium flex items-center justify-center">
                            <i class="fas fa-donate mr-2"></i> Donasi Sekarang
                        </button>
                    </form>
                    
                    <?php unset($_SESSION['form_data']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Filter Section -->
            <div class="card p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-purple-100 p-2 rounded-full mr-3">
                        <i class="fas fa-filter text-purple-500"></i>
                    </div>
                    <h3 class="text-lg font-semibold">Filter Transaksi</h3>
                </div>
                <form method="GET" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                        <input type="date" id="periode" name="periode" class="form-control w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                        <select name="jenis" id="jenis" class="form-control w-full">
                            <option value="">Semua Jenis</option>
                            <option value="pemasukan">Pemasukan</option>
                            <option value="pengeluaran">Pengeluaran</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Program/Kegiatan</label>
                        <input type="text" name="program" placeholder="Cari program..." class="form-control w-full">
                    </div>
                    <button type="submit" class="bg-purple-500 text-white w-full py-3 rounded-lg font-medium hover:bg-purple-600 transition flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i> Terapkan Filter
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Chart Section -->
        <div class="card p-6 mb-8">
            <div class="flex items-center mb-4">
                <div class="bg-orange-100 p-2 rounded-full mr-3">
                    <i class="fas fa-chart-line text-orange-500"></i>
                </div>
                <h3 class="text-lg font-semibold">Grafik Keuangan</h3>
            </div>
            <canvas id="chartKeuangan" height="300"></canvas>
        </div>
        
        <!-- Transaction History -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div class="bg-indigo-100 p-2 rounded-full mr-3">
                    <i class="fas fa-history text-indigo-500"></i>
                </div>
                <h3 class="text-lg font-semibold">Riwayat Transaksi</h3>
            </div>
            <div class="text-sm text-gray-500">
                Total: <?= count($transaksi) ?> transaksi
            </div>
        </div>
        
        <div class="table-responsive">
    <table class="w-full">
        <thead>
            <tr>
                <th class="py-3 px-4 text-left rounded-tl-lg">Tanggal</th>
                <th class="py-3 px-4 text-left">Nama</th>
                <th class="py-3 px-4 text-left">Jenis</th>
                <th class="py-3 px-4 text-left">Keterangan</th>
                <th class="py-3 px-4 text-left">Metode Pembayaran</th>
                <th class="py-3 px-4 text-right">Jumlah</th>
                <?php if ($isAdmin): ?>
                    <th class="py-3 px-4 text-center rounded-tr-lg">No. Rekening</th> <!-- Tambahkan kolom ini untuk admin -->
                    <th class="py-3 px-4 text-center rounded-tr-lg">Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($transaksi)): ?>
            <tr>
                <td colspan="<?= $isAdmin ? 8 : 6 ?>" class="py-4 px-4 text-center text-gray-500">
                    <i class="fas fa-info-circle mr-2"></i> Tidak ada data transaksi
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($transaksi as $t): ?>
                <tr class="border-t border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4"><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                    <td class="py-3 px-4"><?= htmlspecialchars($t['nama']) ?></td>
                    <td class="py-3 px-4">
                        <?php if ($t['jenis'] === 'pemasukan'): ?>
                            <span class="badge badge-success">
                                <i class="fas fa-arrow-down mr-1"></i> Pemasukan
                            </span>
                        <?php else: ?>
                            <span class="badge badge-danger">
                                <i class="fas fa-arrow-up mr-1"></i> Pengeluaran
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4"><?= htmlspecialchars($t['deskripsi']) ?></td>
                    <td class="py-3 px-4">
                        <?php if (!empty($t['payment_method'])): ?>
                            <span class="payment-method-badge">
                                <?= htmlspecialchars($t['payment_method']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-right font-medium <?= $t['jenis'] === 'pemasukan' ? 'income' : 'expense' ?>">
                        <?= $t['jenis'] === 'pemasukan' ? '+' : '-' ?> Rp <?= number_format($t['jumlah'], 2, ',', '.') ?>
                    </td>
                    <?php if ($isAdmin): ?>
                        <td class="py-3 px-4"><?= htmlspecialchars($t['payment_reference'] ?? '') ?></td> <!-- Menampilkan nomor rekening untuk admin -->
                        <td class="py-3 px-4 text-center">
                            <div class="flex justify-center space-x-2">
                                <form method="POST">
                                    <input type="hidden" name="edit_id" value="<?= $t['id'] ?>">
                                    <button type="submit" name="edit_button" class="text-blue-500 hover:text-blue-700 p-2 rounded-full hover:bg-blue-50">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="delete_id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
    </div>
    </div>
    
        <!-- Edit Modal -->
<?php if ($isAdmin && isset($_POST['edit_button'])): ?>
<div class="modal active" id="editModal">
    <div class="modal-content">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Edit Transaksi</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="edit_id" value="<?= htmlspecialchars($edit_id) ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($edit_nama) ?>" required class="form-control w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                    <select name="jenis" id="editJenisTransaksi" required class="form-control w-full">
                        <option value="pemasukan" <?= $edit_jenis === 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                        <option value="pengeluaran" <?= $edit_jenis === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <input type="text" name="deskripsi" value="<?= htmlspecialchars($edit_deskripsi) ?>" required class="form-control w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp)</label>
                    <input type="number" name="jumlah" value="<?= htmlspecialchars($edit_jumlah) ?>" required class="form-control w-full">
                </div>

                <!-- Metode Pembayaran (ditampilkan untuk semua jenis transaksi) -->
                <div id="editPaymentMethod">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Metode Pembayaran</label>
                    <select name="payment_method" id="editPaymentMethodSelect" class="form-control w-full">
                        <option value="Tunai" <?= (isset($edit_payment_method) && $edit_payment_method === 'Tunai' ? 'selected' : '') ?>>Tunai</option>
                        <?php
                        $methods = $conn->query("SELECT * FROM payment_methods WHERE is_active = TRUE");
                        while ($method = $methods->fetch_assoc()):
                        ?>
                        <option value="<?= htmlspecialchars($method['name']) ?>"
                            <?= (isset($edit_payment_method) && $edit_payment_method === $method['name'] ? 'selected' : '') ?>>
                            <?= htmlspecialchars($method['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Referensi Pembayaran (ditampilkan untuk non-tunai baik pemasukan maupun pengeluaran) -->
                <div id="editPaymentReference" style="<?= (isset($edit_payment_method) && $edit_payment_method !== 'Tunai' ? '' : 'display: none;') ?>">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <?= ($edit_jenis === 'pengeluaran' ? 'Nomor Rekening Tujuan' : 'Nomor Rekening Pengirim') ?>
                    </label>
                    <input type="text" name="payment_reference" id="editPaymentReferenceInput" 
                           value="<?= isset($edit_payment_reference) ? htmlspecialchars($edit_payment_reference) : '' ?>" 
                           placeholder="<?= ($edit_jenis === 'pengeluaran' ? 'No. Rekening Tujuan' : 'No. Rekening Pengirim') ?>" 
                           class="form-control w-full">
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" name="simpan_edit" class="btn-primary px-4 py-2 rounded-lg">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
    
    <script>
        // Chart Configuration
        const ctx = document.getElementById('chartKeuangan').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($formatted_labels) ?>,
                datasets: [{
                    label: 'Transaksi Keuangan',
                    data: <?= json_encode($amounts) ?>,
                    backgroundColor: <?= json_encode($background_colors) ?>,
                    borderColor: <?= json_encode($border_colors) ?>,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rp ' + Math.abs(context.raw).toLocaleString('id-ID');
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + Math.abs(value).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
        
        // Modal Functions
        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }
        
        // Set today's date as default filter date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('periode').value = today;
        });

        // Payment method reference field handler
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
    const paymentReferenceField = document.getElementById('paymentReferenceField');
    
    if (paymentMethodSelect && paymentReferenceField) {
        paymentMethodSelect.addEventListener('change', function() {
            const selectedMethod = this.value.toLowerCase();
            const referenceInput = paymentReferenceField.querySelector('input[name="payment_reference"]');
            
            if (selectedMethod && selectedMethod !== 'tunai') {
                paymentReferenceField.classList.remove('hidden');
                paymentReferenceField.classList.add('show');
                referenceInput.setAttribute('required', 'required');
            } else {
                paymentReferenceField.classList.remove('show');
                paymentReferenceField.classList.add('hidden');
                referenceInput.removeAttribute('required');
            }
        });
        
        // Trigger change event on load
        paymentMethodSelect.dispatchEvent(new Event('change'));
    }
    
    // Tampilkan pesan sukses/error jika ada
<?php if (isset($_SESSION['success'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?= $_SESSION['success'] ?>',
        confirmButtonText: 'OK',
        confirmButtonColor: '#219150'
    });
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: '<?= $_SESSION['error'] ?>',
        confirmButtonText: 'OK',
        confirmButtonColor: '#ef4444'
    });
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
});

// Admin form handler
document.addEventListener('DOMContentLoaded', function() {
    const jenisTransaksi = document.getElementById('jenisTransaksi');
    const adminPaymentMethod = document.getElementById('adminPaymentMethod');
    const adminPaymentReference = document.getElementById('adminPaymentReference');
    const adminPaymentMethodSelect = document.getElementById('adminPaymentMethodSelect');
    const adminPaymentReferenceInput = document.getElementById('adminPaymentReferenceInput');
    const adminForm = document.getElementById('adminTransactionForm');

    if (jenisTransaksi && adminPaymentMethod && adminPaymentReference) {
        jenisTransaksi.addEventListener('change', function() {
            if (this.value === 'pengeluaran') {
                adminPaymentMethod.style.display = 'block';
                adminPaymentReference.style.display = 'none'; // Sembunyikan dulu
            } else {
                adminPaymentMethod.style.display = 'none';
                adminPaymentReference.style.display = 'none';
                adminPaymentMethodSelect.removeAttribute('required');
                adminPaymentReferenceInput.removeAttribute('required');
            }
        });

        // Handle payment method change untuk admin
        adminPaymentMethodSelect.addEventListener('change', function() {
            if (this.value && this.value !== 'Tunai') {
                adminPaymentReference.style.display = 'block';
                adminPaymentReferenceInput.setAttribute('required', 'required');
            } else {
                adminPaymentReference.style.display = 'none';
                adminPaymentReferenceInput.removeAttribute('required');
            }
        });

        // Trigger initial state
        jenisTransaksi.dispatchEvent(new Event('change'));
        adminPaymentMethodSelect.dispatchEvent(new Event('change'));
    }

    // Form validation
    if (adminForm) {
        adminForm.addEventListener('submit', function(e) {
            if (jenisTransaksi.value === 'pengeluaran') {
                if (!adminPaymentMethodSelect.value) {
                    alert('Mohon pilih metode pembayaran untuk pengeluaran');
                    e.preventDefault();
                    return;
                }
                
                if (adminPaymentMethodSelect.value !== 'Tunai' && !adminPaymentReferenceInput.value) {
                    alert('Mohon isi nomor referensi pembayaran');
                    e.preventDefault();
                    return;
                }
            }
        });
    }

    // Payment method reference field handler untuk user
    const userPaymentMethod = document.getElementById('userPaymentMethod');
    const paymentReferenceField = document.getElementById('paymentReferenceField');
    
    if (userPaymentMethod && paymentReferenceField) {
        userPaymentMethod.addEventListener('change', function() {
            const selectedMethod = this.value.toLowerCase();
            const referenceInput = paymentReferenceField.querySelector('input[name="payment_reference"]');
            
            if (selectedMethod && selectedMethod !== 'tunai') {
                paymentReferenceField.classList.remove('hidden');
                paymentReferenceField.classList.add('show');
                referenceInput.setAttribute('required', 'required');
            } else {
                paymentReferenceField.classList.remove('show');
                paymentReferenceField.classList.add('hidden');
                referenceInput.removeAttribute('required');
            }
        });
        
        // Trigger change event on load
        userPaymentMethod.dispatchEvent(new Event('change'));
    }
});

// Edit modal handler
document.addEventListener('DOMContentLoaded', function() {
    const editJenisTransaksi = document.getElementById('editJenisTransaksi');
    const editPaymentMethod = document.getElementById('editPaymentMethod');
    const editPaymentReference = document.getElementById('editPaymentReference');
    const editPaymentMethodSelect = document.getElementById('editPaymentMethodSelect');
    
    if (editJenisTransaksi && editPaymentMethod && editPaymentReference) {
        editJenisTransaksi.addEventListener('change', function() {
            // Tampilkan metode pembayaran untuk semua jenis transaksi
            editPaymentMethod.style.display = 'block';
            
            // Untuk pemasukan, tampilkan referensi jika metode bukan tunai
            if (this.value === 'pengeluaran') {
                editPaymentMethodSelect.setAttribute('required', 'required');
            } else {
                editPaymentMethodSelect.removeAttribute('required');
            }
            
            // Periksa metode pembayaran saat jenis transaksi berubah
            checkPaymentMethod();
        });

        editPaymentMethodSelect.addEventListener('change', function() {
            checkPaymentMethod();
        });
        
        function checkPaymentMethod() {
            if ((editJenisTransaksi.value === 'pengeluaran' || editJenisTransaksi.value === 'pemasukan') && 
                editPaymentMethodSelect.value && editPaymentMethodSelect.value !== 'Tunai') {
                editPaymentReference.style.display = 'block';
                document.getElementById('editPaymentReferenceInput').setAttribute('required', 'required');
            } else {
                editPaymentReference.style.display = 'none';
                document.getElementById('editPaymentReferenceInput').removeAttribute('required');
            }
        }
        
        // Trigger initial state
        editJenisTransaksi.dispatchEvent(new Event('change'));
        editPaymentMethodSelect.dispatchEvent(new Event('change'));
    }
});
    </script>
</body>
</html>