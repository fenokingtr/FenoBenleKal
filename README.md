# Benle Kal - Gizli Mesajlaşma Uygulaması

## 🌟 Proje Hakkında

"Benle Kal", kullanıcıların birbirlerine anonim veya gizli mesajlar göndermesini sağlayan, web tabanlı basit bir mesajlaşma uygulamasıdır. Uygulama, hem yönetici paneli (kullanıcı yönetimi ve tüm mesajları görüntüleme) hem de kullanıcı paneli (mesaj gönderme ve gelen mesajları görüntüleme) içerir.

### Amaç
Bu projenin temel amacı, kullanıcıların güvenli bir şekilde gizli mesajlar paylaşmasına olanak tanımak ve bir yöneticinin bu mesajları ve kullanıcıları denetleyebileceği bir sistem sunmaktır.

## 🚀 Gereksinimler

Bu projeyi yerel makinenizde çalıştırmak için aşağıdaki yazılımlara ihtiyacınız vardır:

*   **PHP 7.4 veya üzeri**: Sunucu tarafı betik dili.
*   **MySQL / MariaDB**: Veritabanı sistemi.
*   **Web Sunucusu** (örn. Apache, Nginx): PHP dosyalarını çalıştırmak için.
    *   **XAMPP** veya **WAMP** gibi paketler, PHP, MySQL ve Apache'yi tek bir pakette sunar ve hızlı kurulum için idealdir.

## 🛠️ Kurulum

1.  **Projeyi İndirin/Klonlayın:**
    `git clone [proje_reposu_adresi]` veya ZIP olarak indirin.
2.  **Dosyaları Yerleştirin:**
    İndirdiğiniz dosyaları web sunucunuzun belge kök dizinine (örneğin, XAMPP için `htdocs` klasörüne) kopyalayın.
3.  **Veritabanını Oluşturun:**
    *   `config.php` dosyasında veritabanı bağlantı bilgilerini kendi ayarlarınıza göre güncelleyin.
    *   `database.sql` dosyasını kullanarak bir MySQL/MariaDB veritabanı oluşturun ve içe aktarın. Bu dosya gerekli tabloları ve varsayılan bir yönetici kullanıcısını içerir.
4.  **Uygulamayı Çalıştırın:**
    Web tarayıcınızda `http://localhost/gizlikal` (veya projenizi yerleştirdiğiniz dizine göre) adresine giderek uygulamayı başlatın.

## ⚙️ Kullanılan Teknolojiler

*   **PHP**: Sunucu tarafı programlama dili.
    *   **PDO**: Veritabanı işlemleri için güvenli ve esnek bir arayüz sağlar.
*   **MySQL / MariaDB**: İlişkisel veritabanı.
*   **HTML5**: Sayfa yapısı.
*   **CSS3**: Sayfa stilizasyonu.
*   **Bootstrap 5.1.3**: Duyarlı ve modern kullanıcı arayüzü için CSS çerçevesi.
*   **Mermaid.js (diyagramlar için)**: Geliştirme sürecinde kullanılan görselleştirme aracı (bu projede doğrudan kullanılmıyor, ancak diyagramlar oluşturmak için notlarda belirtilebilir).

## 📂 Dosya Yapısı ve Modüller

*   `index.php`: Ana giriş sayfası.
*   `config.php`: Veritabanı bağlantı ayarları.
*   `logout.php`: Oturumu sonlandırma sayfası.
*   `database.sql`: Veritabanı şeması ve başlangıç verileri.
*   `admin/`: Yönetici paneli dosyaları
    *   `index.php`: Yönetici ana paneli (kullanıcı ekleme, listeleme, mesajları görüntüleme).
    *   `edit_user.php`: Kullanıcı bilgilerini düzenleme sayfası.
    *   `bot.php`, `bot2.php`: Gelecekteki geliştirmeler veya testler için örnek dosyalar olabilir (mevcut projede aktif kullanılmıyor).
*   `user/`: Normal kullanıcı paneli dosyaları
    *   `index.php`: Kullanıcı ana paneli (kullanıcıları listeleme, gelen mesajları görüntüleme).
    *   `message.php`: Belirli bir kullanıcıya mesaj gönderme ve ilgili mesajlaşma geçmişini görüntüleme sayfası.

## 📝 Önemli Geliştirme Notları

### CSS Stilleri
Proje genelinde aynı CSS stillerini kullanmak için, her PHP dosyasının `<head>` bölümünde `<style>` etiketleri arasına tüm stil kodları doğrudan dahil edilmiştir. **Bu, stil dosyalarının ayrı bir klasörde (örn. `css/style.css`) tutulmaması anlamına gelir.**

**Bu yaklaşımın nedeni ve dikkat edilmesi gerekenler:**
*   **Değişikliklerin Yansımaması Durumu:** Eğer CSS kodlarını harici bir dosyaya taşır ve bu dosyayı herhangi bir PHP sayfasında dahil etmeyi unutursanız veya yolunu yanlış belirtirseniz, o sayfadaki stil değişiklikleri yansımayacaktır.
*   **Bakım Kolaylığı:** Şu anki haliyle, genel stil değişiklikleri her dosya içinde yapılmalıdır. Büyük projelerde bu durum zorluğa neden olabilir. Gelecekteki geliştirmeler için bu durum göz önünde bulundurulmalıdır.

### Veritabanı ve Güvenlik
*   Veritabanı bağlantısı `PDO` kullanılarak hazırlanmıştır. Bu, SQL enjeksiyonu gibi temel güvenlik açıklarına karşı koruma sağlar.
*   Şifreler açık metin olarak saklanmaktadır (mevcut `database.sql` yapısına göre). Gerçek bir uygulamada şifreler mutlaka hash'lenerek saklanmalıdır (`password_hash()` fonksiyonu gibi).

### Sayfalama (Pagination)
*   Admin panelindeki kullanıcı ve mesaj listelerinde sayfalama özelliği eklenmiştir. Her sayfada 5 kayıt gösterilir.
*   Sayfalama butonları (`İlk`, `Önceki`, `Sonraki`, `Son`) ve dinamik olarak gösterilen sayfa numaraları (mevcut sayfanın etrafında belirli bir aralıkta) bulunmaktadır.

### Kod Yorumları
*   Tüm PHP dosyalarına (admin/, user/ ve ana dizindeki) kapsamlı yorum satırları eklenmiştir. Bu yorumlar, kodun işlevselliğini, değişkenlerin ve önemli blokların ne işe yaradığını açıklar, böylece kodun okunabilirliği ve anlaşılırlığı artırılmıştır.

---

## 📜 Lisans

Bu proje MIT Lisansı altında lisanslanmıştır. Daha fazla bilgi için `LICENSE` dosyasına bakınız.

---

Bu README dosyası, projenizin hızlıca anlaşılması, kurulması ve bakımı için önemli bilgiler içermektedir. İyi geliştirmeler!
