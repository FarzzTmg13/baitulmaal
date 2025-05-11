<?php
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    
    // Cek apakah username sudah ada
    $check_sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Username sudah digunakan! Pilih username lain.');</script>";
    } else {
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'user')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        if ($stmt->execute()) {
            echo "<script>alert('Pendaftaran berhasil! Silakan login.'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('Pendaftaran gagal, coba lagi!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - Baitul Maal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background: linear-gradient(to bottom right, #e8f5e9, #ffffff);
        background-image: url('https://www.transparenttextures.com/patterns/arabesque.png');
        background-repeat: repeat;
        font-size: 14px;
    }

    .container {
        width: 90%;
        max-width: 420px;
        min-width: 325px;
        margin: 30px auto;
        background: white;
        padding: 25px 20px;
        border-radius: 16px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 10;
    }

    h2 {
        text-align: center;
        color: #27ae60;
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    form {
        display: grid;
        gap: 15px;
    }

    .input-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
        color: #2d3436;
        font-size: 0.9rem;
    }

    .input-group {
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .input-group svg {
        position: absolute;
        top: 34px;
        left: 12px;
        width: 16px;
        height: 16px;
        pointer-events: none;
        fill: #27ae60;
    }

    .input-group input {
        width: 100%;
        padding: 10px 12px 10px 38px;
        font-size: 0.9rem;
        border: 1px solid #ccc;
        border-radius: 8px;
        box-sizing: border-box;
    }

    button {
        width: 100%;
        padding: 12px;
        font-size: 0.95rem;
        font-weight: bold;
        color: white;
        background: #27ae60;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s ease-in-out;
        margin-top: 5px;
    }

    button:hover {
        background: #219150;
    }

    .login-link {
        text-align: center;
        margin-top: 15px;
        font-size: 0.85rem;
    }

    .login-link a {
        color: #27ae60;
        text-decoration: none;
        font-weight: 500;
    }

    .login-link a:hover {
        text-decoration: underline;
    }

    .donation-section {
        margin-top: 30px;
        text-align: center;
        color: #555;
    }

    .donation-section svg {
        width: 100px;
        height: auto;
        margin-bottom: 15px;
    }

    .donation-text {
        padding: 0 5px;
        font-size: 0.8rem;
        color: #444;
        line-height: 1.5;
    }

    .donation-text strong {
        display: block;
        margin-top: 6px;
        color: #27ae60;
        font-size: 0.85rem;
    }

    .donation-text em {
        font-size: 0.75rem;
        display: block;
        margin-top: 5px;
    }

    .ornament {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('https://www.transparenttextures.com/patterns/asfalt-dark.png');
        opacity: 0.05;
        border-radius: 16px;
        pointer-events: none;
        z-index: 1;
    }

    @media (max-width: 420px) {
        .container {
            margin: 20px auto;
            padding: 20px 15px;
        }
        
        h2 {
            font-size: 1.3rem;
        }
        
        .input-group svg {
            top: 32px;
            left: 10px;
        }
        
        .input-group input {
            padding: 8px 10px 8px 34px;
        }
        
        .donation-section svg {
            width: 80px;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="ornament"></div>
        <h2>Daftar Akun</h2>
        <form method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                </svg>
                <input type="text" id="username" name="username" placeholder="Masukkan username" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M17 8h-1V6c0-2.8-2.2-5-5-5S6 3.2 6 6v2H5c-1.1 0-2 .9-2 2v10c0 
                    1.1.9 2 2 2h12c1.1 0 2-.9 
                    2-2V10c0-1.1-.9-2-2-2zM8 6c0-1.7 1.3-3 
                    3-3s3 1.3 3 3v2H8V6zm9 14H5V10h12v10z"/>
                </svg>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit">Daftar</button>
        </form>

        <div class="login-link">
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
        </div>

        <div class="donation-section">
            <svg viewBox="0 0 64 64" fill="#27ae60" xmlns="http://www.w3.org/2000/svg">
                <path d="M32 2C30 6 26 10 20 12c6 2 10 6 12 10 2-4 6-8 12-10-6-2-10-6-12-10zM12 22v6H6v28h52V28h-6v-6h-8v6h-8v-6h-8v6h-8v-6h-8zm2 8h8v6h8v-6h8v6h8v-6h6v20H10V30h4z"/>
            </svg>
            <p class="donation-text">
                "Dengan mendaftar, Anda membuka jalan kebaikan dan amal jariyah. Satu klik, satu langkah menuju keberkahan."
                <strong>"Barang siapa membangun masjid karena Allah, maka Allah akan membangunkan baginya rumah di surga."</strong>
                <em>– HR. Bukhari & Muslim</em>
            </p>
        </div>
    </div>
</body>
</html>