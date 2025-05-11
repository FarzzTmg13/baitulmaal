<?php
include 'db_config.php';

$password = 'user123'; // Ganti dengan password yang ingin digunakan
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = ? WHERE username = 'user'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed_password);
if ($stmt->execute()) {
    echo "Password user berhasil diperbarui!";
} else {
    echo "Gagal memperbarui password user.";
}
?>