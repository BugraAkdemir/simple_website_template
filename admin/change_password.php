<?php
session_start();

// Şifre ve kullanıcı adı dosyası yolu
$password_file = 'password.log';

// Şifre değiştirme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    $credentials = file($password_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($credentials as &$line) {
        list($stored_username, $stored_password) = explode(':', $line);
        if (password_verify($current_password, trim($stored_password))) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $line = "$stored_username:$new_hashed_password";
            file_put_contents($password_file, implode(PHP_EOL, $credentials));
            header("Location: admin.php?password_changed=true");
            exit;
        }
    }
    header("Location: admin.php?password_changed=false");
    exit;
}
?>
