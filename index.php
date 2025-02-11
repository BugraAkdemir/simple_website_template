<?php
include 'mainTools.php';
//include '';

$names = "Buğra Akdemir";
$web_page = "bigra.com.tr";
$mail = "info@bigra.com.tr";
$number = "+62 852-9516-0657";
$age = "16";
$sehir = "Kastamonu, Türkiye";
$ddd = $databaseVerisi;

// Ban Veritabanı bağlantısı
$ban_dsn = "mysql:host=localhost;dbname={$ddd}banlist;charset=utf8";
$ban_username = 'bigracom_bugra';
$ban_password = 'bugra2005bugra';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
];

try {
    $ban_pdo = new PDO($ban_dsn, $ban_username, $ban_password, $options);
} catch (PDOException $e) {
    die("Ban veritabanı bağlantısı hatası: " . $e->getMessage());
}

// Ziyaretçi Veritabanı bağlantısı
$visit_dsn = "mysql:host=localhost;dbname={$ddd}visits;charset=utf8";
$visit_username = 'bigracom_bugra';
$visit_password = 'bugra2005bugra';

try {
    $visit_pdo = new PDO($visit_dsn, $visit_username, $visit_password, $options);
} catch (PDOException $e) {
    die("Ziyaretçi veritabanı bağlantısı hatası: " . $e->getMessage());
}

// Kullanıcının IP adresini öğren
function get_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

$user_ip = get_ip_address();

// IP adresinin banlı olup olmadığını kontrol et
function is_ip_banned($ban_pdo, $ip_address) {
    $stmt = $ban_pdo->prepare("SELECT COUNT(*) FROM banned_ips WHERE ip_address = :ip_address");
    $stmt->execute(['ip_address' => $ip_address]);
    return $stmt->fetchColumn() > 0;
}

if (is_ip_banned($ban_pdo, $user_ip)) {
    header("Location: ban.php");
    exit;
}

// Ziyaretçi verisini veritabanına ekle
function add_visitor_data($visit_pdo, $ip_address) {
    $stmt = $visit_pdo->prepare("INSERT INTO visitor_data (ip_address, visit_time) VALUES (:ip_address, NOW())");
    $stmt->execute(['ip_address' => $ip_address]);
}

add_visitor_data($visit_pdo, $user_ip);

// Site yönlendirme ayarlarını JSON dosyasından oku
$settings_file = 'admin/json/settings.json';
$settings = ['site_mode' => 'home'];

if (file_exists($settings_file)) {
    $settings_contents = file_get_contents($settings_file);
    if ($settings_contents) {
        $settings = json_decode($settings_contents, true);
        if (!is_array($settings) || !isset($settings['site_mode'])) {
            $settings['site_mode'] = 'home';
        }
    }
}

// Site moduna göre yönlendirme yap
if ($settings['site_mode'] === 'maintenance') {
    header("Location: bakim.php");
    exit;
}

//Token Generator

include "mainTools.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$ddd = $databaseVerisi;
$servername = "localhost"; // Veritabanı sunucusu
$username = "bigracom_bugra"; // Veritabanı kullanıcı adı
$password = "bugra2005bugra"; // Veritabanı şifresi
$dbname = "{$ddd}spams"; // Veritabanı adı



// Veritabanına bağlanma
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['cname']);
    $email = htmlspecialchars($_POST['cemail']);
    $subject = htmlspecialchars($_POST['csubject']);
    $message = htmlspecialchars($_POST['cmessage']);
    $user_ip = $_SERVER['REMOTE_ADDR']; // Kullanıcının IP adresini al

    // Kullanıcının son gönderim tarihini kontrol et
    $sql = "SELECT last_sent FROM message_log WHERE ip_address = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_ip);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($last_sent);

    // Eğer kayıt varsa ve 24 saat dolmamışsa yönlendirme yap
    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        $current_time = new DateTime();
        $last_sent_time = new DateTime($last_sent);
        $interval = $current_time->diff($last_sent_time);

        if ($interval->h < 24) {
            header("Location: /sg.html");
            exit;
        }
    }

    // PHPMailer Başlat
    $mail = new PHPMailer(true);

    try {
        // SMTP Ayarları
        $mail->isSMTP();
        $mail->Host = 'mail.bugraa.com'; // SMTP sunucusu
        $mail->SMTPAuth = true;
        $mail->Username = 'info@bugraa.com'; // SMTP kullanıcı adı
        $mail->Password = 'bugra2005'; // SMTP şifresi
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // E-posta Ayarları
        $mail->setFrom($email, $name);
        $mail->addAddress('dev@bugraa.com'); // Alıcı e-posta adresi

        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = "<h1>Yeni İletişim Formu Mesajı</h1>
                       <p><strong>Adı:</strong> $name</p>
                       <p><strong>Email:</strong> $email</p>
                       <p><strong>Konu:</strong> $subject</p>
                       <p><strong>Mesaj:</strong> $message</p>";
        $mail->AltBody = "Adı: $name\nEmail: $email\nKonu: $subject\nMesaj: $message";

        $mail->send();

        // Mesaj gönderildiyse veritabanına yeni tarih ekle/güncelle
        $current_time = date('Y-m-d H:i:s');
        $sql = "INSERT INTO message_log (ip_address, last_sent) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE last_sent = VALUES(last_sent)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $user_ip, $current_time);
        $stmt->execute();

        $sendmsg = "Message Sent Successfully Thank You";
    } catch (Exception $e) {
        $erormsg = "Eror";
    }
    exit;
}

$conn->close();


?>

<!-- Mail İşlemleri -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Your description">
    <meta name="author" content="Your name">

    <!-- OG Meta Tags to improve the way the post looks when you share the page on Facebook, Twitter, LinkedIn -->
	<!-- <meta property="og:site_name" content="" /> 
	<meta property="og:site" content="" /> 
	<meta property="og:title" content=""/> 
	<meta property="og:description" content="" /> 
	<meta property="og:image" content="" /> 
	<meta property="og:url" content="" /> 
	<meta name="twitter:card" content="summary_large_image">  -->

    <!-- Webpage Title -->
    <title>Bugra Akdemir</title>
    
    <!-- Styles -->
    <!-- <link rel="preconnect" href="https://fonts.gstatic.com"> -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400&family=Poppins:wght@600&display=swap" rel="stylesheet">
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/fontawesome-all.css" rel="stylesheet">
	<link href="css/styles.css" rel="stylesheet">
	
	<!-- Favicon  -->
    <link rel="icon" href="images/favicon.ico">
</head>
<body data-spy="scroll" data-target=".fixed-top">
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top navbar-dark">
        <div class="container">
            
            <!-- Image Logo -->
            <!-- <a class="navbar-brand logo-image" href="index.html"><img src="images/logo (3).svg" alt="alternative"></a>   -->

             <!-- Text Logo - Use this if you don't have a graphic logo -->
            <a class="navbar-brand logo-text page-scroll" href="index.html">Bugra</a>

            <button class="navbar-toggler p-0 border-0" type="button" data-toggle="offcanvas">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="navbar-collapse offcanvas-collapse" id="navbarsExampleDefault">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link page-scroll" href="#header">Home <span class="sr-only">(current)</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link page-scroll" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link page-scroll" href="#services">Services</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Drop</a>
                        <div class="dropdown-menu" aria-labelledby="dropdown01">
                            <a class="dropdown-item page-scroll" href="https://projects.bugraa.com">Projects</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item page-scroll" href="terms.html">Terms Conditions</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item page-scroll" href="privacy.html">Privacy Policy</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link page-scroll" href="#contact">Contact</a>
                    </li>
                </ul>
                <span class="nav-item social-icons">
                    <span class="fa-stack">
                        <a href="https://instagram.com/bigra.xc">
                            <i class="fas fa-circle fa-stack-2x"></i>
                            <i class="fab fa-instagram -f fa-stack-1x"></i>
                        </a>
                    </span>
                    <span class="fa-stack">
                        <a href="https://github.com/BugraAkdemir">
                            <i class="fas fa-circle fa-stack-2x"></i>
                            <i class="fab fa-github fa-stack-1x"></i>
                        </a>
                    </span>
                    <span class="fa-stack">
                        <a href="https://youtube.com/BugraAkdemir">
                            <i class="fas fa-circle fa-stack-2x"></i>
                            <i class="fab fa-youtube fa-stack-1x"></i>
                        </a>
                    </span>
                </span>
            </div> <!-- end of navbar-collapse -->
        </div> <!-- end of container -->
    </nav> <!-- end of navbar -->
    <!-- end of navigation -->


    <!-- Header -->
    <header id="header" class="header">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="text-container">
                        <h1 class="h1-large">Bugra Akdemir</h1>
                        <h2 style="color: white;">WEB &amp; MOBILE Developer</h2>
                        <a class="btn-solid-lg page-scroll" href="#about">Discover</a>
                        <a class="btn-outline-lg page-scroll" href="#contact"><i class="fas fa-user"></i>Contact Me</a>
                    </div> <!-- end of text-container -->
                </div> <!-- end of col -->
            </div> <!-- end of row -->
        </div> <!-- end of container -->
    </header> <!-- end of header -->
    <!-- end of header -->


    <!-- About-->
    <div id="about" class="basic-1 bg-gray">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="text-container first">
                        <h2>Hi there I'm Bugra,</h2>
                        <p>Started my software life in my middle school years. Now it has been exactly 3 years since I started software and I still continue to develop like the first day. I'm Ma king Progress in Mobile Development and Web Development</p>
                    </div> <!-- end of text-container -->
                </div> <!-- end of col -->
    </div> <!-- end of basic-1 -->
    <!-- end of about -->


    <!-- Services -->
    <div id="services" class="basic-2">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h2 class="h2-heading">Offered services</h2>
                    <p class="p-heading">I have been working as a web developer for over 3 years.</p>
                </div> <!-- end of col -->
            </div> <!-- end of row -->
            <div class="row">
                <div class="col-lg-4">
                    <div class="text-box">
                        <i class="far fa-gem"></i>
                        <h4>DESIGN</h4>
                        <p>Successful online projects start with good design. It establishes a solid foundation for future development and allows for long term growth</p>
                    </div> <!-- end of text-box -->
                </div> <!-- end of col -->
                <div class="col-lg-4">
                    <div class="text-box">
                        <i class="fas fa-code"></i>
                        <h4>DEVELOPMENT</h4>
                        <p>I can code my own designs or even use the customer's design as base. My focus is to generate clean code that's well structured for reliability</p>
                    </div> <!-- end of text-box -->
                </div> <!-- end of col -->
                <div class="col-lg-4">
                    <div class="text-box">
                        <i class="fas fa-tv"></i>
                        <h4>BASIC SEO</h4>
                        <p>i can setup your project to use basic SEO principles which will push your project to the first page on search engines and save you ads money</p>
                    </div> <!-- end of text-box -->
                </div> <!-- end of col -->
            </div> <!-- end of row -->
        </div> <!-- end of container -->
    </div> <!-- end of basic-2 -->
    <!-- end of services -->


    <!-- Details -->
	<div class="split">
		<div class="area-1">
		</div><!-- end of area-1 on same line and no space between comments to eliminate margin white space --><div class="area-2 bg-gray">
            <div class="container">    
                <div class="row">
                    <div class="col-lg-12">     
                        
                        <!-- Text Container -->
                        <div class="text-container">
                            <h2>Why Work With Me</h2>
                            <p>I am a great communicator and love to invest the necessary time to understand the customer's problem very well</p>
                            <h5>DESIGN TOOLS</h5>
                            <p>My favorite design tools are Photoshop and Illustrator but I can create designs in Figma, Sketch and Adobe XD too</p>
                            <h5>DEVELOPMENT SKILLS</h5>
                            <p>I am familiar and work on a daily basis with HTML, CSS, JavaScript, Bootstrap, Python, Php, Dart & Flutter and other modern frameworks</p>
                            
                            <div class="icons-container">
                                <img src="images/dartPRG.png" alt="">
                                <img src="images/php.png" alt="">
                                <img src="images/python.png" alt="">
                                <img src="images/details-icon-html.png" alt="alternative">
                                <img src="images/details-icon-css.png" alt="alternative">
                                <img src="images/details-icon-bootstrap.png" alt="alternative">
                                <img src="images/details-icon-javascript.png" alt="alternative">
                            </div> <!-- end of icons-container -->
                        </div> <!-- end of text-container -->
                        <!-- end of text container -->

                    </div> <!-- end of col -->
                </div> <!-- end of row -->
            </div> <!-- end of container -->
		</div> <!-- end of area-2 -->
    </div> <!-- end of split -->
    <!-- end of details -->


    


    


   


    <!-- Section Divider -->
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <hr class="section-divider">
            </div> <!-- end of col -->
        </div> <!-- end of row -->
    </div> <!-- end of container -->
    <!-- end of section divider -->


    <!-- Questions -->
    <div class="accordion-1">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h2 class="h2-heading">Frequent questions</h2>
                </div> <!-- end of col -->
            </div> <!-- end of row -->
            <div class="row">
                <div class="col-lg-12">

                    <div class="accordion" id="accordionExample">
                        <div class="card">
                            <div class="card-header" id="headingOne">
                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How can I contact you and quickly get a quote for my online project?
                                </button>
                            </div>
                            <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
                                <div class="card-body">
                                    You can reach me in the best way through my social media accounts.
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingTwo">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Do you create designs from the ground up or you are using themes?
                                </button>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
                                <div class="card-body">
                                    We Can Use It From Scratch Or In Theme If You Want, Depending On Option And Fee
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingThree">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Will I receive any included maintenance or warranty after project delivery?
                                </button>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                                <div class="card-body">
                                    There is a 1-Year Warranty and Maintenance, But Please Read the Warranty Terms <a href="terms.html">Warranty Conditions</a>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingFour">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    If something goes wrong with the project can I have my money back?
                                </button>
                            </div>
                            <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordionExample">
                                <div class="card-body">
                                    Yes, as long as the return conditions are met. <a href="terms.html">Return Conditions</a>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header" id="headingFive">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    What's your preferred method of payment and do you need an advance?
                                </button>
                            </div>
                            <div id="collapseFive" class="collapse" aria-labelledby="headingFive" data-parent="#accordionExample">
                                <div class="card-body">
                                    We Receive Payments Via Shopify or Bitcoin
                                </div>
                            </div>
                        </div>
                    </div> <!-- end of accordion -->

                </div> <!-- end of col -->
            </div> <!-- end of row -->
        </div> <!-- end of container -->
    </div> <!-- end of accordion-1 -->
    <!-- end of questions -->

    <script>
        let isSubmitting = false; // Gönderim kontrolü için bayrak

        function msgKonrolBtn(event) {
            event.preventDefault(); // Formun varsayılan davranışını engeller

            if (isSubmitting) {
                alert("Form zaten gönderiliyor! Lütfen bekleyin."); // Spam tıklama mesajı
                return;
            }

            isSubmitting = true; // Gönderim işlemi başladı

            const form = event.target; // Form referansı
            const formData = new FormData(form); // Form verilerini al

            // AJAX isteği gönder
            fetch(form.action, {
                method: form.method, // POST veya GET
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    return response.text(); // Başarılı yanıt alındı
                }
                throw new Error('Form gönderiminde hata oluştu.');
            })
            .then(data => {
                const div = document.getElementById('konroldiv');
                div.style.display = 'block'; // Div görünür yapılıyor
                div.innerText = 'Message Sent Successfully Thank You'; // Başarılı metin
            })
            .catch(error => {
                console.error(error);
                const div = document.getElementById('konroldiv');
                div.style.display = 'block'; // Div görünür yapılıyor
                div.innerText = 'Bir hata oluştu!'; // Hata metni
            })
            .finally(() => {
                isSubmitting = false; // Gönderim tamamlandı
            });
        }
    </script>

    <!-- Contact -->
    <div id="contact" class="form-1 bg-gray">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <h2>Contact details</h2>
                    <p class="p-heading">For any type of online project please don't hesitate to get in touch with me. The fastest way is to send me your message using the following email <a class="blue no-line" href="mailTo:dev@bugraa.com">dev@bugraa.com</a></p>
                </div> <!-- end of col -->
            </div> <!-- end of row -->
            <div class="row">
                <div class="col-lg-12">
                    
                    <!-- Contact Form -->
                    <form id="contactForm" action="" method="POST" onsubmit="msgKonrolBtn(event)">
                        <div class="form-group">
                            <input type="text" class="form-control-input" id="cname" name="cname" required>
                            <label class="label-control" for="cname">Name</label>
                        </div>
                        <div class="form-group">
                            <input type="email" class="form-control-input" id="cemail" name="cemail" required>
                            <label class="label-control" for="cemail">Email</label>

                        </div>

                        <div class="form-group">
                            <input type="text" class="form-control-input" id="csubject" name="csubject" required>
                            <label class="label-control" for="csubject">Konu</label>
                        </div>


                        <div class="form-group">
                            <textarea class="form-control-textarea" id="cmessage" name="cmessage" required></textarea>
                            <label class="label-control" for="cmessage">Project details</label>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="form-control-submit-button" onclick="msgKonrolBtn()">Submit</button>
                        </div>
                    </form>

                    <div id="konroldiv"></div>
                    
                    

                    <!-- end of contact form -->

                </div> <!-- end of col -->
            </div> <!-- end of row -->
        </div> <!-- end of container -->
    </div> <!-- end of form-1 -->  
    <!-- end of contact -->

    

    <!-- Footer -->
    <div class="footer bg-gray">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="social-container">

                        <span class="fa-stack">
                            <a href="https://instagram.com/bigra.xc">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fab fa-instagram fa-stack-1x"></i>
                            </a>
                        </span>
                        <span class="fa-stack">
                            <a href="https://youtube.com/@BugraAkdemir">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fab fa-youtube fa-stack-1x"></i>
                            </a>
                        </span>
                        <span class="fa-stack">
                            <a href="https://github.com/BugraAkdemir">
                                <i class="fas fa-circle fa-stack-2x"></i>
                                <i class="fab fa-github fa-stack-1x"></i>
                            </a>
                        </span>
                    </div> <!-- end of social-container -->
                </div> <!-- end of col -->
            </div> <!-- end of row -->
        </div> <!-- end of container -->
    </div> <!-- end of footer -->  
    <!-- end of footer -->


    <!-- Copyright -->
    <div class="copyright bg-gray">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <p class="p-small">Copyright © <a class="no-line" href="https://bugraa.com">Bugra Akdemir</a></p>
                </div> <!-- end of col -->
            </div> <!-- enf of row -->
        </div> <!-- end of container -->

        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <p class="p-small">Distributed By <a class="no-line" href="https://bugraa.com/">AKDBT</a></p>
                </div> <!-- end of col -->
            </div> <!-- enf of row -->
        </div> <!-- end of container -->
        
    </div> <!-- end of copyright --> 
    <!-- end of copyright -->
    
    	
    <!-- Scripts -->
    <script src="js/jquery.min.js"></script> <!-- jQuery for Bootstrap's JavaScript plugins -->
    <script src="js/bootstrap.min.js"></script> <!-- Bootstrap framework -->
    <script src="js/jquery.easing.min.js"></script> <!-- jQuery Easing for smooth scrolling between anchors -->
    <script src="js/scripts.js"></script> <!-- Custom scripts -->


    


    <script> 

    // Çerez kontrolü  
    

    

    //Token Genaratör
    // var i = 0;
    // i++;
    // localStorage.setItem("userCou", i);

    var length = 16;
    // Karakter setimizi burada tanımlıyoruz.
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
 
    // Rastgele karakter seçimi
    var str = '';
    for (let i = 0; i < length; i++) {
      str += chars.charAt(Math.floor(Math.random() * chars.length));
    }


    const soru = localStorage.getItem("token"); 
    if (!soru){
      localStorage.setItem("token", str);
    }else if(soru){
      //pass
    }else{
		localStorage.setItem("token", "notTokenFaildet")
	}
    
    

    

    // Sayfa yüklendiğinde çerez izni kontrolü  
    
</script>


</body>
</html>