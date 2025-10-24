# 🚌 Bilet Satın Alma Sistemi

PHP & SQLite tabanlı otobüs bileti satış, firma ve kupon yönetim sistemi.

## ⚙️ Kurulum (XAMPP)

1. [XAMPP](https://www.apachefriends.org/tr/index.html) indir, **Apache**’yi başlat.  
2. Projeyi şu dizine kopyala:
C:\xampp\htdocs\bilet-satin-alma
3. Tarayıcıdan şu adrese git:
http://localhost/bilet-satin-alma/public/index.php

## 🗄️ Veritabanı (SQLite)

- Veritabanı dosyası: `database/bilet.db`  
- Eğer yoksa `database` klasörüne boş `bilet.db` dosyası oluştur.  
- `config/database.php` içinde şu satır olmalı:
```php
$db = new PDO('sqlite:' . __DIR__ . '/../database/bilet.db');

🔐 Roller

| Rol               | Yetki                   |
| ----------------- | ----------------------- |
| **admin**         | Tüm sistemi yönetir     |
| **company_admin** | Kendi firmasını yönetir |
| **user**          | Bilet satın alır        |
Rol	Yetki
admin	Tüm sistemi yönetir
company_admin	Kendi firmasını yönetir
user	Bilet satın alır
🚀 Özellikler

Kullanıcı kaydı ve giriş

Firma & sefer yönetimi

Kupon sistemi

Bakiye yükleme

PDF bilet oluşturma

⚠️ Notlar

PHP 8+ önerilir

display_errors = On (geliştirme için)

URL /public/ ile bitmeli

database/bilet.db yazma izni gerekebilir (chmod 777)