<?php
/**
 * @file config.php
 * @brief Veritabanı bağlantı ayarlarını ve PDO bağlantısını içerir.
 * Bu dosya, uygulamanın farklı bölümlerinden veritabanına güvenli bir şekilde bağlanmak için kullanılır.
 */

// Veritabanı bağlantı bilgileri
$host = 'localhost'; // Veritabanı sunucusu adresi
$dbname = 'gizlikal'; // Kullanılacak veritabanının adı
$username = 'root'; // Veritabanı kullanıcı adı
$password = ''; // Veritabanı şifresi

// PDO ile veritabanı bağlantısı kurma
try {
    // PDO nesnesi oluşturma: MySQL veritabanına UTF-8 karakter setiyle bağlanır.
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Hata modunu ayarlama: Hataların istisna olarak fırlatılmasını sağlar.
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Bağlantı hatası durumunda hata mesajını ekrana yazdır ve betiği durdur.
    echo "Bağlantı hatası: " . $e->getMessage();
    die();
}
?> 