<?php
// Veritabanı dosya yolu
$dbPath = __DIR__ . '/database.sqlite';

try {
    // PDO ile bağlantı aç
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // === Company tablosu ===
    $db->exec("
        CREATE TABLE IF NOT EXISTS Company (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT,
            email TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // === User tablosu ===
    $db->exec("
        CREATE TABLE IF NOT EXISTS User (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            role TEXT NOT NULL,
            password TEXT NOT NULL,
            company_id INTEGER,
            balance REAL DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            salt TEXT,
            FOREIGN KEY (company_id) REFERENCES Company(id)
        );
    ");

   // === Trip tablosu ===
$db->exec("
    CREATE TABLE IF NOT EXISTS Trip (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        company_id INTEGER,
        from_city TEXT NOT NULL,
        to_city TEXT NOT NULL,
        departure_time TEXT NOT NULL,
        arrival_time TEXT NOT NULL,
        price REAL NOT NULL,
        capacity INTEGER DEFAULT 40,   -- 💡 yeni sütun
        status TEXT DEFAULT 'active',
        FOREIGN KEY (company_id) REFERENCES Company(id)
    );
");
// === Coupon tablosu ===
$db->exec("
    CREATE TABLE IF NOT EXISTS Coupon (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT UNIQUE NOT NULL,
        discount_percent REAL NOT NULL,
        valid_from TEXT,
        valid_to TEXT,
        usage_limit INTEGER DEFAULT 1,
        used_count INTEGER DEFAULT 0,
        company_id INTEGER, -- ✅ Eklendi
        status TEXT DEFAULT 'active',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES Company(id)
    );
");

// === User_Coupon tablosu ===
$db->exec("
    CREATE TABLE IF NOT EXISTS User_Coupon (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        coupon_id INTEGER NOT NULL,
        used_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES User(id),
        FOREIGN KEY (coupon_id) REFERENCES Coupon(id)
    );
");


    // === Ticket tablosu ===
    $db->exec("
        CREATE TABLE IF NOT EXISTS Ticket (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            trip_id INTEGER NOT NULL,
            total_price REAL NOT NULL,
            status TEXT DEFAULT 'active',
            balance REAL DEFAULT 1000, -- örnek bakiye
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (trip_id) REFERENCES Trip(id),
            FOREIGN KEY (user_id) REFERENCES User(id)
        );
    ");

    // === Booked_Seats tablosu ===
    $db->exec("
        CREATE TABLE IF NOT EXISTS Booked_Seats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id INTEGER NOT NULL,
            seat_number INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES Ticket(id)
        );
    ");

    echo "<h3 style='color:green'>Tüm tablolar başarıyla oluşturuldu ✅</h3>";

} catch (PDOException $e) {
    die("<h3 style='color:red'>Veritabanı hatası: " . $e->getMessage() . "</h3>");
}
?>
