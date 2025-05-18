<?php
session_start();

/* Database Configuration */
define('DB_HOST', 'localhost');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('DB_NAME', 'database_name');

/* 1. Database Singleton Class */
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}

/* 2. Base Model Class */
abstract class BaseModel {
    protected $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    abstract public function save();
    abstract public function delete();
}

/* 3. Transaction Class (Inherits BaseModel) */
class Transaction extends BaseModel {
    private $id;
    private $jenis;
    private $deskripsi;
    private $jumlah;
    private $tanggal;
    private $payment_method;
    private $payment_reference;
    private $nama;

    public function __construct($data = []) {
        parent::__construct();
        if (!empty($data)) {
            $this->id = $data['id'] ?? null;
            $this->jenis = $data['jenis'] ?? '';
            $this->deskripsi = $data['deskripsi'] ?? '';
            $this->jumlah = $data['jumlah'] ?? 0;
            $this->tanggal = $data['tanggal'] ?? date('Y-m-d H:i:s');
            $this->payment_method = $data['payment_method'] ?? null;
            $this->payment_reference = $data['payment_reference'] ?? null;
            $this->nama = $data['nama'] ?? '';
        }
    }

    // Getters
    public function getId() { return $this->id; }
    public function getJenis() { return $this->jenis; }
    public function getDeskripsi() { return $this->deskripsi; }
    public function getJumlah() { return $this->jumlah; }
    public function getTanggal() { return $this->tanggal; }
    public function getPaymentMethod() { return $this->payment_method; }
    public function getPaymentReference() { return $this->payment_reference; }
    public function getNama() { return $this->nama; }

    // Setters
    public function setJenis($jenis) { $this->jenis = $jenis; }
    public function setDeskripsi($deskripsi) { $this->deskripsi = $deskripsi; }
    public function setJumlah($jumlah) { $this->jumlah = $jumlah; }
    public function setPaymentMethod($method) { $this->payment_method = $method; }
    public function setPaymentReference($ref) { $this->payment_reference = $ref; }
    public function setNama($nama) { $this->nama = $nama; }

    public function save() {
        if ($this->id) {
            $stmt = $this->db->prepare("UPDATE transactions SET 
                jenis=?, deskripsi=?, jumlah=?, payment_method=?, payment_reference=?, nama=? 
                WHERE id=?");
            $stmt->bind_param("ssdsssi", 
                $this->jenis, $this->deskripsi, $this->jumlah, 
                $this->payment_method, $this->payment_reference, $this->nama, $this->id);
        } else {
            $stmt = $this->db->prepare("INSERT INTO transactions 
                (jenis, deskripsi, jumlah, payment_method, payment_reference, nama) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsss", 
                $this->jenis, $this->deskripsi, $this->jumlah, 
                $this->payment_method, $this->payment_reference, $this->nama);
        }
        $stmt->execute();
        if (!$this->id) {
            $this->id = $stmt->insert_id;
        }
        $stmt->close();
    }

    public function delete() {
        if ($this->id) {
            $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = ?");
            $stmt->bind_param("i", $this->id);
            $stmt->execute();
            $stmt->close();
        }
    }

    public static function getAll($periode = '', $jenis_filter = '', $program = '') {
        $db = Database::getInstance();
        $sql = "SELECT * FROM transactions WHERE 1=1";
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
        $stmt = $db->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $transactions = [];
        
        while ($row = $result->fetch_assoc()) {
            $transactions[] = new Transaction($row);
        }
        
        return $transactions;
    }

    public static function getTotals($periode = '', $jenis_filter = '', $program = '') {
        $db = Database::getInstance();
        $result = ['income' => 0, 'expense' => 0];
        
        $sql = "SELECT jenis, SUM(jumlah) as total FROM transactions WHERE 1=1";
        $params = [];
        $types = '';

        // ... (same filter logic as before)
        
        return $result;
    }
}

/* 4. User Session Class */
class UserSession {
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function checkLogin() {
        if (!self::isLoggedIn()) {
            header("Location: login.php");
            exit;
        }
    }
}

/* 5. Session Flash Messages */
class FlashMessage {
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return $value;
    }
}

// ==============================
// Application Logic
// ==============================
UserSession::checkLogin();
$isAdmin = UserSession::isAdmin();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['simpan_donasi'])) {
            $transaction = new Transaction();
            $transaction->setJenis('pemasukan');
            $transaction->setDeskripsi($_POST['deskripsi']);
            $transaction->setJumlah(0);
            $transaction->setPaymentMethod($_POST['payment_method'] ?? null);
            $transaction->setPaymentReference($_POST['payment_reference'] ?? null);
            $transaction->setNama($_POST['nama']);
            $transaction->save();

            $message = ($transaction->getPaymentMethod() === 'Tunai') ?
                "Silahkan menuju ke kantor takmir masjid untuk konfirmasi pembayaran donasi" :
                "Tunggu beberapa saat, admin akan mengkonfirmasi uang donasi Anda";
            
            FlashMessage::set('success', $message);
            header("Location: dashboard.php");
            exit;
        }

        if ($isAdmin) {
            // Handle admin actions...
        }
    } catch (Exception $e) {
        FlashMessage::set('error', $e->getMessage());
        $_SESSION['form_data'] = $_POST;
        header("Location: dashboard.php");
        exit;
    }
}

// Get Filter Parameters
$periode = $_GET['periode'] ?? '';
$jenis_filter = $_GET['jenis'] ?? '';
$program = $_GET['program'] ?? '';

// Get Data
$transactions = Transaction::getAll($periode, $jenis_filter, $program);
$totals = Transaction::getTotals($periode, $jenis_filter, $program);
$total_saldo = $totals['income'] - $totals['expense'];

// HTML Template (di bagian tabel transaksi)
foreach ($transactions as $t):
    $paymentRef = $t->getPaymentReference() ? 
        '<div class="payment-reference">'.$t->getPaymentReference().'</div>' : '';
    ?>
    <tr>
        <td><?= date('d M Y', strtotime($t->getTanggal())) ?></td>
        <td><?= htmlspecialchars($t->getNama()) ?></td>
        <td><?= $t->getJenis() === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran' ?></td>
        <td><?= htmlspecialchars($t->getDeskripsi()) ?></td>
        <td>
            <?php if ($t->getPaymentMethod()): ?>
                <span class="payment-method-badge">
                    <?= htmlspecialchars($t->getPaymentMethod()) ?>
                </span>
                <?= $paymentRef ?>
            <?php endif; ?>
        </td>
        <td class="<?= $t->getJenis() === 'pemasukan' ? 'income' : 'expense' ?>">
            <?= $t->getJenis() === 'pemasukan' ? '+' : '-' ?> 
            Rp <?= number_format($t->getJumlah(), 2, ',', '.') ?>
        </td>
        <?php if ($isAdmin): ?>
            <!-- Action buttons -->
        <?php endif; ?>
    </tr>
<?php endforeach; ?>