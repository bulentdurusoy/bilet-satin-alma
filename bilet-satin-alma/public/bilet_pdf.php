<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
requireLogin();
global $db;

// 1️⃣ Giriş kontrolü ve parametre kontrolü
$user = currentUser();
$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    die('Geçersiz bilet ID');
}

// 2️⃣ Bilet detaylarını getir
$stmt = $db->prepare("
    SELECT 
        t.id AS ticket_id,
        t.total_price,
        t.status,
        t.created_at,
        s.departure_city,
        s.arrival_city,
        s.departure_time,
        s.arrival_time,
        s.price AS trip_price,
        s.company_id,
        u.full_name,
        u.email,
        b.name AS company_name
    FROM Tickets t
    JOIN Trips s ON t.trip_id = s.id
    JOIN User u ON t.user_id = u.id
    JOIN Bus_Company b ON s.company_id = b.id
    WHERE t.id = :tid AND t.user_id = :uid
");
$stmt->execute([':tid' => $ticket_id, ':uid' => $user['id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    die('Bilet bulunamadı.');
}

// 3️⃣ Koltukları getir
$stmt2 = $db->prepare("SELECT seat_number FROM Booked_Seats WHERE ticket_id = :tid ORDER BY seat_number ASC");
$stmt2->execute([':tid' => $ticket_id]);
$seats = $stmt2->fetchAll(PDO::FETCH_COLUMN);
$seat_list = implode(', ', $seats);

// 4️⃣ PDF oluştur
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, 'Bilet Bilgisi', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Yolcu: ' . $ticket['full_name'], 0, 1);
$pdf->Cell(0, 8, 'E-posta: ' . $ticket['email'], 0, 1);
$pdf->Cell(0, 8, 'Firma: ' . $ticket['company_name'], 0, 1);
$pdf->Ln(5);

$pdf->Cell(0, 8, 'Kalkış: ' . $ticket['departure_city'] . ' — ' . date('d.m.Y H:i', strtotime($ticket['departure_time'])), 0, 1);
$pdf->Cell(0, 8, 'Varış: ' . $ticket['arrival_city'] . ' — ' . date('d.m.Y H:i', strtotime($ticket['arrival_time'])), 0, 1);
$pdf->Ln(5);

$pdf->Cell(0, 8, 'Koltuk No: ' . $seat_list, 0, 1);
$pdf->Cell(0, 8, 'Toplam Fiyat: ' . number_format($ticket['total_price'], 2) . ' ₺', 0, 1);
$pdf->Cell(0, 8, 'Durum: ' . ucfirst($ticket['status']), 0, 1);
$pdf->Cell(0, 8, 'Satın Alım Tarihi: ' . date('d.m.Y H:i', strtotime($ticket['created_at'])), 0, 1);
$pdf->Ln(10);

$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 8, 'Bu belge, Bilet Satın Alma Platformu tarafından otomatik oluşturulmuştur.', 0, 1, 'C');

// 5️⃣ PDF çıktısı
ob_end_clean(); // Her ihtimale karşı tamponu temizle
$pdf->Output('I', 'bilet_' . $ticket['ticket_id'] . '.pdf');
