<?php
/**
 * @file user/index.php
 * @brief Kullanıcı paneli ana sayfası.
 * Bu sayfa kullanıcılara diğer kullanıcıları listeleme ve gelen mesajlarını görüntüleme imkanı sunar.
 */

session_start(); // PHP oturumunu başlat

require_once '../config.php'; // Veritabanı bağlantı ayarlarını içeren dosyayı dahil et

// Kullanıcı kontrolü: Kullanıcı oturum açmamışsa veya yönetici ise, ana giriş sayfasına yönlendir.
if(!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header("Location: ../index.php"); // Ana giriş sayfasına yönlendir
    exit(); // Betiğin çalışmasını durdur
}

// Tüm kullanıcıları getir (mevcut kullanıcı hariç ve sadece normal kullanıcıları)
$stmt = $db->prepare("SELECT * FROM users WHERE id != ? AND is_admin = 0");
$stmt->execute([$_SESSION['user_id']]); // Mevcut kullanıcı ID'sini parametre olarak bağla
$users = $stmt->fetchAll(); // Sorgu sonucunu al

// Gelen mesajları getir (mevcut kullanıcıya ait olanları, gönderen bilgisiyle birlikte)
$stmt = $db->prepare("
    SELECT m.*, s.name as sender_name, s.surname as sender_surname
    FROM messages m
    JOIN users s ON m.sender_id = s.id
    WHERE m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]); // Mevcut kullanıcı ID'sini parametre olarak bağla
$messages = $stmt->fetchAll(); // Sorgu sonucunu al
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Paneli - Benle Kal</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Özel CSS stilleri -->
    <style>
/* Geniş CSS stilleri burada yer alıyor, her sayfada tekrar eden stiller */
/* Bu CSS, uygulamanın genel görünümünü ve hissini tanımlar. */
/* Navbar, kartlar, butonlar, form elemanları, listeler, başlıklar ve scrollbar stilleri gibi öğeleri içerir. */
/* Tema: Koyu arka plan, gradient vurgular, glassmorphism etkisi */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

body {
    min-height: 100vh;
    background: linear-gradient(120deg, #0a0a13 0%, #181824 100%);
    font-family: 'Inter', Arial, sans-serif;
    color: #f8fafc;
}

.navbar {
    background: linear-gradient(90deg, #2563eb 0%, #fc575e 100%);
    box-shadow: 0 2px 12px rgba(44,62,80,0.13);
    border-bottom: none;
    padding: 0.7rem 0;
}

.navbar-brand {
    font-weight: bold;
    font-size: 1.7rem;
    background: linear-gradient(90deg, #f7b42c 0%, #fc575e 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-fill-color: transparent;
    letter-spacing: 2px;
    text-shadow: 0 2px 8px rgba(252,87,94,0.15);
}

.navbar .btn {
    margin-left: 0.5rem;
    font-weight: 600;
    letter-spacing: 1px;
}

/* Glassmorphism Card */
.card {
    background: rgba(20, 22, 34, 0.98);
    border-radius: 22px;
    box-shadow: 0 0 12px 1px #fff3, 0 0 24px 0 #00f6ff22;
    border: 1.5px solid #fff3;
    margin-bottom: 2rem;
    overflow: hidden;
    transition: box-shadow 0.3s, border 0.3s;
}
.card:hover {
    box-shadow: 0 0 18px 3px #00f6ff55, 0 0 32px 0 #fff2;
    border: 1.5px solid #00f6ff88;
}

.card-header {
    background: none;
    border-bottom: 1.5px solid #fff2;
    font-weight: 700;
    font-size: 1.18rem;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: flex-start;
    text-align: left;
    color: #fff;
    text-shadow: 0 0 4px #fff2, 0 0 8px #00f6ff33;
}
.card-header::before {
    content: '';
    display: inline-block;
    width: 10px;
    height: 30px;
    border-radius: 8px;
    background: linear-gradient(180deg, #00f6ff 0%, #7f5af0 100%);
    box-shadow: 0 0 6px 1px #00f6ff55;
    margin-right: 12px;
}

.card-body {
    background: none;
    border-radius: 0 0 22px 22px;
}

.btn-primary {
    background: linear-gradient(90deg, #00f6ff 0%, #7f5af0 100%);
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.08rem;
    color: #fff;
    box-shadow: 0 0 6px 1px #00f6ff55;
    transition: background 0.2s, box-shadow 0.2s;
    text-shadow: 0 0 4px #fff2, 0 0 8px #00f6ff33;
}
.btn-primary:hover {
    background: linear-gradient(90deg, #7f5af0 0%, #00f6ff 100%);
    color: #fff;
    box-shadow: 0 0 12px 2px #7f5af088;
}

.btn-danger {
    border-radius: 12px;
    background: linear-gradient(90deg, #ff3c6a 0%, #ffb86c 100%);
    border: none;
    font-weight: 700;
    color: #fff;
    box-shadow: 0 0 6px 1px #ff3c6a55;
    text-shadow: 0 0 4px #fff2, 0 0 8px #ff3c6a33;
}
.btn-danger:hover {
    background: linear-gradient(90deg, #ffb86c 0%, #ff3c6a 100%);
    color: #fff;
    box-shadow: 0 0 12px 2px #ff3c6a88;
}

.btn-warning {
    border-radius: 12px;
    background: linear-gradient(90deg, #ffe066 0%, #ff3c6a 100%);
    border: none;
    font-weight: 700;
    color: #222;
    box-shadow: 0 0 6px 1px #ffe06655;
    text-shadow: 0 0 4px #fff2, 0 0 8px #ffe06633;
}
.btn-warning:hover {
    background: linear-gradient(90deg, #ff3c6a 0%, #ffe066 100%);
    color: #fff;
    box-shadow: 0 0 12px 2px #ffe06688;
}

.btn-secondary {
    border-radius: 12px;
    background: linear-gradient(90deg, #232946 0%, #00f6ff 100%);
    border: none;
    font-weight: 700;
    color: #fff;
    box-shadow: 0 0 6px 1px #00f6ff55;
    text-shadow: 0 0 4px #fff2, 0 0 8px #00f6ff33;
}
.btn-secondary:hover {
    background: linear-gradient(90deg, #00f6ff 0%, #232946 100%);
    color: #fff;
    box-shadow: 0 0 12px 2px #00f6ff88;
}

.form-control, textarea {
    border-radius: 12px;
    border: 1.5px solid #fff3;
    box-shadow: 0 0 4px 1px #fff2;
    font-size: 1.05rem;
    background: rgba(20,22,34,0.98);
    color: #fff;
    transition: border 0.2s, box-shadow 0.2s;
}
.form-control:focus, textarea:focus {
    border-color: #00f6ff88;
    outline: none;
    background: rgba(20,22,34,1);
    color: #fff;
    box-shadow: 0 0 8px 2px #00f6ff55;
}

.list-group-item {
    border: none;
    border-radius: 12px !important;
    margin-bottom: 0.5rem;
    background: rgba(20,22,34,0.98);
    transition: background 0.2s, box-shadow 0.2s;
    font-weight: 600;
    color: #fff;
    box-shadow: 0 0 4px 1px #fff2;
}
.list-group-item:hover, .list-group-item.active {
    background: linear-gradient(90deg, #00f6ff 0%, #7f5af0 100%);
    color: #181824;
    box-shadow: 0 0 8px 2px #00f6ff55;
}

.main-title {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(90deg, #fff 0%, #00f6ff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-fill-color: transparent;
    margin-bottom: 0.2rem;
    letter-spacing: 1.5px;
    text-shadow: 0 0 8px #fff2, 0 0 16px #00f6ff33;
}
.sub-title {
    font-size: 1.2rem;
    color: #fff;
    margin-bottom: 1.5rem;
    text-shadow: 0 0 4px #fff2, 0 0 8px #00f6ff33;
}

.card .card-subtitle {
    color: #00f6ff !important;
    font-weight: 700;
    text-shadow: 0 0 4px #fff2, 0 0 8px #00f6ff33;
}

::-webkit-scrollbar {
    width: 8px;
    background: #181824;
}
::-webkit-scrollbar-thumb {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 0 4px 1px #fff2;
}

@media (max-width: 991px) {
    .main-title { font-size: 2rem; }
    .sub-title { font-size: 1.1rem; }
    .card-header::before { height: 18px; }
}

.card, .card-body, .list-group, .list-group-item, form, .form-label, .form-control, textarea {
    text-align: left !important;
}

.card table, .card th, .card td {
    color: #f8fafc !important;
}

.card th {
    color: #fff !important;
    opacity: 0.8;
}
</style>
</head>
<body class="bg-light">
    <div class="container mt-4">

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3>Benle Kal - Hoş Geldin, <?php echo htmlspecialchars(ucwords(strtolower($_SESSION['name']))); ?></h3>
                        <!-- Çıkış Yap butonu: Oturumu sonlandırır ve ana giriş sayfasına yönlendirir -->
                        <a href="../logout.php" class="btn btn-danger">Çıkış Yap</a>
                    </div>
                </div>
            </div>
            
            <!-- Kullanıcı Listesi Kartı -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Kullanıcılar</h4>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach($users as $user): ?>
                            <!-- Her bir kullanıcı için mesaj gönderme bağlantısı -->
                            <a href="message.php?user=<?php echo $user['id']; ?>" class="list-group-item list-group-item-action">
                                <?php echo htmlspecialchars(ucwords(strtolower($user['name'] . ' ' . $user['surname']))); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gelen Mesajlar Kartı -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Gelen Mesajlar</h4>
                    </div>
                    <div class="card-body">
                        <?php if(empty($messages)): ?>
                            <!-- Mesaj yoksa bilgi mesajı -->
                            <p class="text-muted">Henüz mesajınız bulunmuyor.</p>
                        <?php else: ?>
                            <?php foreach($messages as $message): ?>
                            <!-- Her bir gelen mesaj için kart -->
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Anonim</h6>
                                    <p class="card-text"><?php echo htmlspecialchars($message['message']); ?></p>
                                    <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 