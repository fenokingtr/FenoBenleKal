<?php
/**
 * @file admin/index.php
 * @brief Admin paneli ana sayfası.
 * Bu sayfa yönetici yetkisine sahip kullanıcılar için kullanıcıları ekleme, listeleme, arama ve mesajları listeleme imkanı sunar.
 * Kullanıcı ve mesaj listeleri için sayfalama (pagination) ve arama/filtreleme özellikleri içerir.
 */

session_start(); // PHP oturumunu başlat

require_once '../config.php'; // Veritabanı bağlantı ayarlarını içeren dosyayı dahil et

// Admin kontrolü: Kullanıcı oturum açmamışsa veya yönetici değilse, ana giriş sayfasına yönlendir.
if(!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php"); // Ana giriş sayfasına yönlendir
    exit(); // Betiğin çalışmasını durdur
}

// Kullanıcı ekleme işlemi: Form POST edildiğinde çalışır.
if(isset($_POST['add_user'])) {
    // Formdan gelen verileri al
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $login_code = $_POST['login_code'];
    
    // Yeni kullanıcıyı veritabanına ekle
    $stmt = $db->prepare("INSERT INTO users (name, surname, login_code) VALUES (?, ?, ?)");
    $stmt->execute([$name, $surname, $login_code]); // Verileri parametre olarak bağla ve sorguyu çalıştır
    $success = "Kullanıcı başarıyla eklendi!"; // Başarı mesajı ata
}

// Kullanıcı arama ve filtreleme değişkenlerini tanımla
$user_search_query = ''; // Kullanıcı arama sorgusu
$user_where_clause = 'WHERE is_admin = 0'; // Kullanıcı sorgusu için WHERE koşulu (sadece normal kullanıcılar)
$user_params = []; // Kullanıcı sorgusu için parametreler dizisi

// Kullanıcı arama formu POST edildiğinde veya URL'de arama parametreleri varsa
if(isset($_GET['user_search']) && !empty($_GET['user_search_query'])) {
    $user_search_query = $_GET['user_search_query']; // Arama sorgusunu al
    // Arama koşulunu WHERE cümlesine ekle (ad, soyad veya giriş koduna göre arama)
    $user_where_clause .= ' AND (name LIKE ? OR surname LIKE ? OR login_code LIKE ?)';
    // Arama parametrelerini dizine ekle
    $user_params = ["%$user_search_query%", "%$user_search_query%", "%$user_search_query%"];
}

// Kullanıcılar için sayfalama (Pagination) ayarları
$users_per_page = 5; // Her sayfada gösterilecek kullanıcı sayısı
$user_page = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1; // Mevcut kullanıcı sayfası numarası
$user_offset = ($user_page - 1) * $users_per_page; // Veritabanından çekilecek başlangıç ofseti

// Toplam kullanıcı sayısını getir (sayfalama için)
$stmt = $db->prepare("SELECT COUNT(*) FROM users " . $user_where_clause);
$stmt->execute($user_params);
$total_users = $stmt->fetchColumn(); // Toplam kullanıcı sayısını al
$total_user_pages = ceil($total_users / $users_per_page); // Toplam kullanıcı sayfası sayısını hesapla

// Kullanıcıları getir (sayfalama ve arama koşulları ile birlikte)
$stmt = $db->prepare("SELECT * FROM users " . $user_where_clause . " LIMIT " . $users_per_page . " OFFSET " . $user_offset);
$stmt->execute($user_params);
$users = $stmt->fetchAll(); // Kullanıcıları veritabanından çek

// Mesaj arama ve filtreleme değişkenlerini tanımla
$message_search_query = ''; // Mesaj arama sorgusu
$filter_type = 'all'; // Mesaj filtreleme türü (varsayılan: tümü)
$message_where_clause = ''; // Mesaj sorgusu için WHERE koşulu
$message_params = []; // Mesaj sorgusu için parametreler dizisi

// Mesaj arama formu POST edildiğinde veya URL'de arama parametreleri varsa
if(isset($_GET['message_search']) && !empty($_GET['message_search_query'])) {
    $message_search_query = $_GET['message_search_query']; // Arama sorgusunu al
    $filter_type = $_GET['filter_type']; // Filtreleme türünü al
    
    // Filtreleme türüne göre WHERE koşulunu oluştur
    if ($filter_type == 'sender') {
        $message_where_clause .= ' WHERE s.name LIKE ? OR s.surname LIKE ?'; // Gönderen adına/soyadına göre ara
        $message_params = ["%$message_search_query%", "%$message_search_query%"];
    } else if ($filter_type == 'receiver') {
        $message_where_clause .= ' WHERE r.name LIKE ? OR r.surname LIKE ?'; // Alıcı adına/soyadına göre ara
        $message_params = ["%$message_search_query%", "%$message_search_query%"];
    } else if ($filter_type == 'message_content') {
        $message_where_clause .= ' WHERE m.message LIKE ?'; // Mesaj içeriğine göre ara
        $message_params = ["%$message_search_query%"];
    } else { // 'all' - Tüm alanlarda ara
        $message_where_clause .= ' WHERE (s.name LIKE ? OR s.surname LIKE ? OR r.name LIKE ? OR r.surname LIKE ? OR m.message LIKE ?)';
        $message_params = ["%$message_search_query%", "%$message_search_query%", "%$message_search_query%", "%$message_search_query%", "%$message_search_query%"];
    }
}

// Mesajlar için sayfalama (Pagination) ayarları
$messages_per_page = 5; // Her sayfada gösterilecek mesaj sayısı
$message_page = isset($_GET['message_page']) ? (int)$_GET['message_page'] : 1; // Mevcut mesaj sayfası numarası
$message_offset = ($message_page - 1) * $messages_per_page; // Veritabanından çekilecek başlangıç ofseti

// Toplam mesaj sayısını getir (sayfalama için)
$stmt = $db->prepare("SELECT COUNT(*) FROM messages m JOIN users s ON m.sender_id = s.id JOIN users r ON m.receiver_id = r.id " . $message_where_clause);
$stmt->execute($message_params);
$total_messages = $stmt->fetchColumn(); // Toplam mesaj sayısını al
$total_message_pages = ceil($total_messages / $messages_per_page); // Toplam mesaj sayfası sayısını hesapla

// Tüm mesajları getir (sayfalama, birleşim ve arama/filtreleme koşulları ile birlikte)
$sql_messages = "
    SELECT m.*, 
           s.name as sender_name, s.surname as sender_surname,
           r.name as receiver_name, r.surname as receiver_surname
    FROM messages m
    JOIN users s ON m.sender_id = s.id
    JOIN users r ON m.receiver_id = r.id
    " . $message_where_clause . "
    ORDER BY m.created_at DESC
    LIMIT " . $messages_per_page . " OFFSET " . $message_offset;
$stmt = $db->prepare($sql_messages);
$stmt->execute($message_params);
$messages = $stmt->fetchAll(); // Mesajları veritabanından çek
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Benle Kal</title>
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
    color: #f8fafc;
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
        <div class="row align-items-center mb-4">
            <div class="col-8 mx-auto text-center">
                <h1 class="main-title" style="margin-bottom:0;">Benle Kal - Admin Paneli</h1>
            </div>
            <div class="col text-end">
                <!-- Çıkış Yap butonu: Oturumu sonlandırır ve ana giriş sayfasına yönlendirir -->
                <a href="../logout.php" class="btn btn-danger">Çıkış Yap</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 mb-4">
                <!-- Admin Panel Header (now removed as per previous request to remove navbar, but keeping the title) -->
            </div>
            
            <!-- Kullanıcı Ekleme Formu Kartı -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Yeni Kullanıcı Ekle</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($success)): ?>
                            <!-- Başarı mesajını göster -->
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Ad</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Soyad</label>
                                <input type="text" name="surname" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Giriş Kodu</label>
                                <input type="text" name="login_code" class="form-control" required>
                            </div>
                            <!-- Kullanıcı Ekle butonu -->
                            <button type="submit" name="add_user" class="btn btn-primary">Kullanıcı Ekle</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Kullanıcı Listesi Kartı -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Kullanıcı Listesi</h4>
                    </div>
                    <div class="card-body">
                        <!-- Kullanıcı Arama Formu -->
                        <form method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="user_search_query" class="form-control" placeholder="Kullanıcı ara..." value="<?php echo htmlspecialchars($user_search_query); ?>">
                                <!-- Arama butonu -->
                                <button class="btn btn-primary" type="submit" name="user_search">Ara</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Ad Soyad</th>
                                        <th>Giriş Kodu</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($users)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Kullanıcı bulunamadı.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(ucwords(strtolower($user['name'] . ' ' . $user['surname']))); ?></td>
                                            <td><?php echo htmlspecialchars($user['login_code']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <!-- Kullanıcı Düzenle butonu -->
                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">Düzenle</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Kullanıcı Sayfalama (Pagination) -->
                        <?php if($total_user_pages > 1): ?>
                        <nav aria-label="User pagination" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <!-- İlk Sayfa butonu -->
                                <li class="page-item <?php echo $user_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?user_page=1<?php echo !empty($user_search_query) ? '&user_search_query='.urlencode($user_search_query).'&user_search=1' : ''; ?>">İlk</a>
                                </li>
                                <!-- Önceki Sayfa butonu -->
                                <li class="page-item <?php echo $user_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?user_page=<?php echo $user_page - 1; ?><?php echo !empty($user_search_query) ? '&user_search_query='.urlencode($user_search_query).'&user_search=1' : ''; ?>">Önceki</a>
                                </li>
                                <?php
                                    // Sayfa numaralarını dinamik olarak göster (mevcut sayfanın etrafında 5 sayfa)
                                    $start_page = max(1, $user_page - 2);
                                    $end_page = min($total_user_pages, $user_page + 2);
                                    for($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <!-- Sayfa numarası butonu -->
                                    <li class="page-item <?php echo $i == $user_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?user_page=<?php echo $i; ?><?php echo !empty($user_search_query) ? '&user_search_query='.urlencode($user_search_query).'&user_search=1' : ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <!-- Sonraki Sayfa butonu -->
                                <li class="page-item <?php echo $user_page >= $total_user_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?user_page=<?php echo $user_page + 1; ?><?php echo !empty($user_search_query) ? '&user_search_query='.urlencode($user_search_query).'&user_search=1' : ''; ?>">Sonraki</a>
                                </li>
                                <!-- Son Sayfa butonu -->
                                <li class="page-item <?php echo $user_page >= $total_user_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?user_page=<?php echo $total_user_pages; ?><?php echo !empty($user_search_query) ? '&user_search_query='.urlencode($user_search_query).'&user_search=1' : ''; ?>">Son</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Mesaj Listesi Kartı -->
            <div class="col-md-12 mt-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Tüm Mesajlar</h4>
                    </div>
                    <div class="card-body">
                        <!-- Mesaj Arama ve Filtreleme Formu -->
                        <form method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="message_search_query" class="form-control" placeholder="Mesaj ara..." value="<?php echo htmlspecialchars($message_search_query); ?>">
                                <select name="filter_type" class="form-select" style="max-width: 150px;">
                                    <option value="all" <?php echo ($filter_type == 'all') ? 'selected' : ''; ?>>Tümü</option>
                                    <option value="sender" <?php echo ($filter_type == 'sender') ? 'selected' : ''; ?>>Gönderen</option>
                                    <option value="receiver" <?php echo ($filter_type == 'receiver') ? 'selected' : ''; ?>>Alıcı</option>
                                    <option value="message_content" <?php echo ($filter_type == 'message_content') ? 'selected' : ''; ?>>Mesaj</option>
                                </select>
                                <!-- Arama butonu -->
                                <button class="btn btn-primary" type="submit" name="message_search">Ara</button>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Gönderen</th>
                                        <th>Alıcı</th>
                                        <th>Mesaj</th>
                                        <th>Tarih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($messages)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Mesaj bulunamadı.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($messages as $message): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(ucwords(strtolower($message['sender_name'] . ' ' . $message['sender_surname']))); ?></td>
                                            <td><?php echo htmlspecialchars(ucwords(strtolower($message['receiver_name'] . ' ' . $message['receiver_surname']))); ?></td>
                                            <td><?php echo htmlspecialchars($message['message']); ?></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Mesaj Sayfalama (Pagination) -->
                        <?php if($total_message_pages > 1): ?>
                        <nav aria-label="Message pagination" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <!-- İlk Sayfa butonu -->
                                <li class="page-item <?php echo $message_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?message_page=1<?php echo !empty($message_search_query) ? '&message_search_query='.urlencode($message_search_query).'&filter_type='.$filter_type.'&message_search=1' : ''; ?>">İlk</a>
                                </li>
                                <!-- Önceki Sayfa butonu -->
                                <li class="page-item <?php echo $message_page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?message_page=<?php echo $message_page - 1; ?><?php echo !empty($message_search_query) ? '&message_search_query='.urlencode($message_search_query).'&filter_type='.$filter_type.'&message_search=1' : ''; ?>">Önceki</a>
                                </li>
                                <?php
                                    // Sayfa numaralarını dinamik olarak göster (mevcut sayfanın etrafında 5 sayfa)
                                    $start_page = max(1, $message_page - 2);
                                    $end_page = min($total_message_pages, $message_page + 2);
                                    for($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <!-- Sayfa numarası butonu -->
                                    <li class="page-item <?php echo $i == $message_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?message_page=<?php echo $i; ?><?php echo !empty($message_search_query) ? '&message_search_query='.urlencode($message_search_query).'&filter_type='.$filter_type.'&message_search=1' : ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <!-- Sonraki Sayfa butonu -->
                                <li class="page-item <?php echo $message_page >= $total_message_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?message_page=<?php echo $message_page + 1; ?><?php echo !empty($message_search_query) ? '&message_search_query='.urlencode($message_search_query).'&filter_type='.$filter_type.'&message_search=1' : ''; ?>">Sonraki</a>
                                </li>
                                <!-- Son Sayfa butonu -->
                                <li class="page-item <?php echo $message_page >= $total_message_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?message_page=<?php echo $total_message_pages; ?><?php echo !empty($message_search_query) ? '&message_search_query='.urlencode($message_search_query).'&filter_type='.$filter_type.'&message_search=1' : ''; ?>">Son</a>
                                </li>
                            </ul>
                        </nav>
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