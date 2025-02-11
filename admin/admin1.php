<link rel="icon" href="../favicon.ico">
<?php
session_start();

// Dosya yolları
$password_file = 'assets/logs/password.log';
$settings_file = '../admin/json/settings.json';
$visitor_counter_file = '../admin/json/sayac.json';

// Şifre kontrolü
function get_stored_password() {
    global $password_file;
    if (file_exists($password_file)) {
        $credentials = file_get_contents($password_file);
        list(, $stored_password) = explode(':', trim($credentials));
        return $stored_password;
    }
    return '';
}

// Giriş doğrulaması
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        $stored_password = get_stored_password();
        if ($_POST['password'] === $stored_password) {
            $_SESSION['authenticated'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $login_error = "Hatalı şifre. Tekrar deneyin.";
        }
    }

    // Giriş formu (oturum açılmamışsa)
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Girişi</title>
        <link rel="stylesheet" href="assets/css/styles.css">
    </head>
    <body>
        <div class="container">
            <h1>Admin Girişi</h1>
            <?php if (isset($login_error)) echo "<p class='error'>$login_error</p>"; ?>
            <form method="POST" action="">
                <label for="password">Şifre:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Giriş Yap</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Admin paneli içerikleri
// Şifre değiştirme işlemi
$password_change_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    $stored_password = get_stored_password();

    if ($old_password === $stored_password) {
        if (!empty($new_password)) {
            file_put_contents($password_file, "admin:$new_password");
            $password_change_message = "Şifre başarıyla değiştirildi.";
        } else {
            $password_change_message = "Yeni şifre boş olamaz.";
        }
    } else {
        $password_change_message = "Eski şifre yanlış.";
    }
}

// Site yönlendirme ayarlarını JSON dosyasından oku
$settings = ['site_mode' => 'home'];
if (file_exists($settings_file)) {
    $settings_contents = file_get_contents($settings_file);
    if ($settings_contents) {
        $settings = json_decode($settings_contents, true);
        if (!is_array($settings) || !isset($settings['site_mode'])) {
            $settings['site_mode'] = 'home';
        }
    }
} else {
    $settings['site_mode'] = 'home';
}

// Site modunu güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    if (isset($_POST['site_mode'])) {
        $settings['site_mode'] = $_POST['site_mode'];
        file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));
        $page_selection_message = "Ana sayfa başarıyla değiştirildi.";
    }
}

// Kullanıcı sayaçlarını JSON dosyasından oku
$counter_file = 'json/sayac.json';
$visitor_data = [];
if (file_exists($counter_file)) {
    $visitor_contents = file_get_contents($counter_file);
    if ($visitor_contents) {
        $visitor_data = json_decode($visitor_contents, true);
        if (is_array($visitor_data)) {
            foreach ($visitor_data as &$visitor) {
                if (!isset($visitor['ip'])) {
                    $visitor['ip'] = 'Bilinmeyen';
                }
            }
        }
    }
}
$total_visitors = count($visitor_data);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Admin Paneli</h1>

        <div class="admin-container">
            <div id="visitor-info">
                <h2>Son 5 Kullanıcı</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Sıra</th>
                            <th>IP Adresi</th>
                            <th>Giriş Zamanı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($total_visitors > 0) {
                            foreach (array_slice(array_reverse($visitor_data), 0, 5) as $index => $visitor) {
                                echo "<tr>
                                        <td>" . ($total_visitors - $index) . "</td>
                                        <td>{$visitor['ip']}</td>
                                        <td>{$visitor['time']}</td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3'>Kullanıcı verisi yok.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <p><strong>Toplam Kullanıcı:</strong> <?php echo $total_visitors; ?></p>
            </div>

            <div id="change-credentials">
                <h2>Şifre Değişikliği</h2>
                <?php if (!empty($password_change_message)) echo "<p class='success'>$password_change_message</p>"; ?>
                <form method="POST" action="">
                    <label for="current_password">Mevcut Şifre:</label>
                    <input type="password" id="current_password" name="old_password" required>
                    
                    <label for="new_password">Yeni Şifre:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    
                    <button type="submit" name="change_password">Şifreyi Değiştir</button>
                </form>
            </div>

            <div id="site-settings">
                <h2>Site Ayarları</h2>
                <?php if (!empty($page_selection_message)) echo "<p class='success'>$page_selection_message</p>"; ?>
                <form method="POST" action="">
                    <label for="site_mode">Site Modu Seçimi:</label>
                    <select name="site_mode" id="site_mode">
                        <option value="home" <?php if ($settings['site_mode'] === 'home') echo 'selected'; ?>>Ana Sayfa</option>
                        <option value="maintenance" <?php if ($settings['site_mode'] === 'maintenance') echo 'selected'; ?>>Bakım Sayfası</option>
                    </select>
                    <button type="submit" name="update_settings">Ayarları Güncelle</button>
                </form>
            </div>
            <div class="cikisyapd">
                <button>
                    <a href="logout.php" id="cikisyap" styles="color:#fff;">Çıkış Yap</a>
                </button>
            </div>
            
        </div>
    </div>

</body>
</html>
