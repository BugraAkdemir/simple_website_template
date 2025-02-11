<?php
// Dosya yolları
$settings_file = '../admin/json/settings.json';
?>

<?php
include '../mainTools.php';
session_start();
$ddd = $databaseVerisi;

// Veritabanı bağlantısı
$dsn = "mysql:host=localhost;dbname={$ddd}adminpassword;charset=utf8";
$username = 'bigracom_bugra'; // Kullanıcı adını buraya girin
$password = 'bugra2005bugra'; // Şifreyi buraya girin
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Veritabanı bağlantısı hatası: " . $e->getMessage());
}

// Ban veritabanı bağlantısı
$ban_dsn = "mysql:host=localhost;dbname={$ddd}banlist;charset=utf8";
$ban_username = 'bigracom_bugra';
$ban_password = 'bugra2005bugra';
$ban_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $ban_pdo = new PDO($ban_dsn, $ban_username, $ban_password, $ban_options);
} catch (PDOException $e) {
    die("Ban veritabanı bağlantısı hatası: " . $e->getMessage());
}

// Ziyaretçi veritabanı bağlantısı
$visit_dsn = "mysql:host=localhost;dbname={$ddd}visits;charset=utf8";
$visit_username = 'bigracom_bugra';
$visit_password = 'bugra2005bugra';
$visit_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $visit_pdo = new PDO($visit_dsn, $visit_username, $visit_password, $visit_options);
} catch (PDOException $e) {
    die("Ziyaretçi veritabanı bağlantı hatası: " . $e->getMessage());
}

// IP adresinin banlı olup olmadığını kontrol etmek için fonksiyon
function is_ip_banned($ban_pdo, $ip_address) {
    $stmt = $ban_pdo->prepare("SELECT COUNT(*) FROM banned_ips WHERE ip_address = :ip_address");
    $stmt->execute(['ip_address' => $ip_address]);
    return $stmt->fetchColumn() > 0;
}

// IP adresini banlamak için fonksiyon
function ban_ip($ban_pdo, $ip_address) {
    if (!is_ip_banned($ban_pdo, $ip_address)) {
        $stmt = $ban_pdo->prepare("INSERT INTO banned_ips (ip_address) VALUES (:ip_address)");
        $stmt->execute(['ip_address' => $ip_address]);
    }
}

// IP adresi banını kaldırmak için fonksiyon
function unban_ip($ban_pdo, $ip_address) {
    $stmt = $ban_pdo->prepare("DELETE FROM banned_ips WHERE ip_address = :ip_address");
    $stmt->execute(['ip_address' => $ip_address]);
}

// Banlanan IP adreslerini getirmek için fonksiyon
function get_banned_ips($ban_pdo) {
    $stmt = $ban_pdo->query("SELECT * FROM banned_ips");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Şifre kontrolü
function get_stored_password($pdo, $username) {
    $stmt = $pdo->prepare("SELECT password FROM admin_credentials WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['password'] : '';
}

// Ziyaretçi verilerini DB'den çekmek için fonksiyon
function get_visitor_data($visit_pdo) {
    $stmt = $visit_pdo->query("SELECT ip_address, visit_time FROM visitor_data ORDER BY visit_time DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Giriş doğrulaması
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $stored_password = get_stored_password($pdo, $username);

        if ($stored_password && password_verify($password, $stored_password)) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $username;
            header("Location: admin.php");
            exit;
        } else {
            $login_error = "Hatalı kullanıcı adı veya şifre. Tekrar deneyin.";
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
                <label for="username">Kullanıcı Adı:</label>
                <input type="text" id="username" name="username" required>

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

// Admin paneli içerikleri (oturum açıldıktan sonra)

// Şifre değiştirme işlemi
$password_change_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    $stored_password = get_stored_password($pdo, $_SESSION['username']); // Oturumda kullanıcı adını almayı unutmayın

    if (password_verify($old_password, $stored_password)) {
        if (!empty($new_password)) {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_credentials SET password = :password WHERE username = :username");
            $stmt->execute(['password' => $new_password_hash, 'username' => $_SESSION['username']]);
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

// Ziyaretçi verilerini al
$visitor_data = get_visitor_data($visit_pdo);
$total_visitors = count($visitor_data);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <link rel="icon" href="../favicon.ico">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- jQuery'yi dahil et -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $("#banListToggle").click(function() {
            $("#banList").toggle();
        });
    });
    </script>
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
                            foreach (array_slice($visitor_data, 0, 10) as $index => $visitor) {
                                echo "<tr>
                                        <td>" . ($total_visitors - $index) . "</td>
                                        <td>{$visitor['ip_address']}</td>
                                        <td>{$visitor['visit_time']}</td>
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

            <?php
// Ban ve Unban mesajları için değişkenler
$ban_message = "";
$unban_message = "";

// IP adresi banlama işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_ip'])) {
    $ip_address = $_POST['ip_address'];
    ban_ip($ban_pdo, $ip_address);
    $ban_message = "IP adresi başarıyla banlandı.";
}

// IP adresi banını kaldırma işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unban_ip'])) {
    $ip_address = $_POST['ip_address'];
    unban_ip($ban_pdo, $ip_address);
    $unban_message = "IP adresi banı başarıyla kaldırıldı.";
}

// Banlanan IP adreslerini getirme
$banned_ips = get_banned_ips($ban_pdo);
?>

<div id="ban-management">
    <h2>IP Ban Management</h2>
    <?php if (!empty($ban_message)) echo "<p class='success'>$ban_message</p>"; ?>
    <?php if (!empty($unban_message)) echo "<p class='success'>$unban_message</p>"; ?>

    <form method="POST" action="">
        <h3>Kullanıcı Banla:</h3>
        <label for="ip_address">IP Adresi:</label>
        <input type="text" id="ip_address" name="ip_address" required>
        <button type="submit" name="ban_ip">Banla</button>
    </form>

    <?php if (!empty($banned_ips)): ?>
        <h3>Banlı Kullanıcılar:</h3>
        <button id="banListToggle">Banlı Kullanıcı Listesini Göster</button>
        <div id="banList" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th>IP Adresi</th>
                        <th>Banı Kaldır</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($banned_ips as $banned_ip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($banned_ip['ip_address'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="ip_address" value="<?php echo htmlspecialchars($banned_ip['ip_address'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" name="unban_ip">Banı Kaldır</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
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