<?php
/**
 * @file admin/edit_user.php
 * @brief Admin panelinde kullanıcı bilgilerini düzenleme sayfası.
 * Bu sayfa sadece yönetici yetkisine sahip kullanıcılar tarafından erişilebilir.
 * Belirtilen kullanıcı ID'sine göre kullanıcının bilgilerini görüntüler ve güncelleme imkanı sunar.
 */

session_start(); // PHP oturumunu başlat

require_once '../config.php'; // Veritabanı bağlantı ayarlarını içeren dosyayı dahil et

// Admin kontrolü: Kullanıcı oturum açmamışsa veya yönetici değilse, ana sayfaya yönlendir.
if(!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../index.php"); // Ana giriş sayfasına yönlendir
    exit(); // Betiğin çalışmasını durdur
}

// Kullanıcı ID kontrolü: URL'de 'id' parametresi yoksa veya boşsa, admin ana sayfasına yönlendir.
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php"); // Admin ana sayfasına yönlendir
    exit(); // Betiğin çalışmasını durdur
}

$user_id = $_GET['id']; // URL'den gelen kullanıcı ID'sini al

// Kullanıcı bilgilerini veritabanından getir: is_admin alanı 0 (normal kullanıcı) olan kayıtları getirir.
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 0");
$stmt->execute([$user_id]); // Kullanıcı ID'sini parametre olarak bağla ve sorguyu çalıştır
$user = $stmt->fetch(); // Sorgu sonucunu tek bir satır olarak al

// Kullanıcı bulunamazsa veya yönetici ise, admin ana sayfasına yönlendir.
if(!$user) {
    header("Location: index.php"); // Admin ana sayfasına yönlendir
    exit(); // Betiğin çalışmasını durdur
}

// Kullanıcı güncelleme işlemi: Form POST edildiğinde çalışır.
if(isset($_POST['update_user'])) {
    // Formdan gelen verileri al
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $login_code = $_POST['login_code'];
    
    // Giriş kodunun benzersiz olduğunu kontrol et: Mevcut kullanıcı hariç başka bir kullanıcıda aynı giriş kodu var mı?
    $stmt = $db->prepare("SELECT id FROM users WHERE login_code = ? AND id != ?");
    $stmt->execute([$login_code, $user_id]); // Giriş kodunu ve mevcut kullanıcı ID'sini parametre olarak bağla
    $existing_user = $stmt->fetch(); // Sorgu sonucunu al
    
    if($existing_user) {
        $error = "Bu giriş kodu başka bir kullanıcı tarafından kullanılıyor!"; // Hata mesajı ata
    } else {
        // Kullanıcı bilgilerini güncelle
        $stmt = $db->prepare("UPDATE users SET name = ?, surname = ?, login_code = ? WHERE id = ?");
        $stmt->execute([$name, $surname, $login_code, $user_id]); // Yeni bilgileri ve kullanıcı ID'sini parametre olarak bağla
        $success = "Kullanıcı başarıyla güncellendi!"; // Başarı mesajı ata
        
        // Güncel kullanıcı bilgilerini sayfada göstermek için tekrar getir (eğer düzenleme sonrası bilgiler değiştiyse)
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Düzenle - Benle Kal</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Özel CSS stilleri -->
    <style>
/* Geniş CSS stilleri burada yer alıyor, her sayfada tekrar eden stiller */
/* Bu CSS, uygulamanın genel görünümünü ve hissini tanımlar. */
/* Navbar, kartlar, butonlar, form elemanları ve scrollbar stilleri gibi öğeleri içerir. */
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
                        <h3>Benle Kal - Kullanıcı Düzenle</h3>
                        <div>
                            <!-- Geri Dön butonu: Admin ana paneline döner -->
                            <a href="index.php" class="btn btn-secondary">Geri Dön</a>
                            <!-- Çıkış Yap butonu: Oturumu sonlandırır ve ana giriş sayfasına yönlendirir -->
                            <a href="../logout.php" class="btn btn-danger">Çıkış Yap</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4>Kullanıcı Bilgilerini Düzenle</h4>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <!-- Hata mesajını göster -->
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if(isset($success)): ?>
                            <!-- Başarı mesajını göster -->
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Ad</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars(ucwords(strtolower($user['name']))); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Soyad</label>
                                <input type="text" name="surname" class="form-control" value="<?php echo htmlspecialchars(ucwords(strtolower($user['surname']))); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Giriş Kodu</label>
                                <input type="text" name="login_code" class="form-control" value="<?php echo htmlspecialchars($user['login_code']); ?>" required>
                            </div>
                            <!-- Güncelle butonu -->
                            <button type="submit" name="update_user" class="btn btn-primary">Güncelle</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 