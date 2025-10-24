<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';

class BiletController {

    /** 🎟️ Bilet Satın Alma */
    public static function buy() {
        requireLogin();
        global $db;

        $tripId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$tripId) die('Sefer bulunamadı.');

        // Seferi getir
        $stmt = $db->prepare("
            SELECT t.*, c.name AS company_name
            FROM Trip t 
            JOIN Company c ON c.id = t.company_id
            WHERE t.id = :id
        ");
        $stmt->execute([':id' => $tripId]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$trip) die('Sefer mevcut değil.');

        // Dolu koltuklar
        $bookedStmt = $db->prepare("
            SELECT bs.seat_number
            FROM Booked_Seats bs
            JOIN Ticket tk ON tk.id = bs.ticket_id
            WHERE tk.trip_id = :tid AND tk.status = 'active'
        ");
        $bookedStmt->execute([':tid' => $tripId]);
        $bookedSeats = array_map(fn($r) => (int)$r['seat_number'], $bookedStmt->fetchAll(PDO::FETCH_ASSOC));

        $message = null;
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = currentUser();

            // Koltuk seçimi
            $selected = $_POST['seats'] ?? [];
            if (!is_array($selected) || count($selected) === 0) {
                $error = 'En az bir koltuk seçmelisin.';
            } else {
                $validSeats = [];
                foreach ($selected as $s) {
                    $n = (int)$s;
                    if ($n < 1 || $n > (int)$trip['capacity']) {
                        $error = 'Geçersiz koltuk numarası: ' . htmlspecialchars($s);
                        break;
                    }
                    if (in_array($n, $bookedSeats, true)) {
                        $error = 'Seçilen koltuklardan bazıları dolu.';
                        break;
                    }
                    $validSeats[] = $n;
                }
            }

            // Kupon kontrolü
            $couponCode = trim($_POST['coupon'] ?? '');
            $coupon = null;
            if (!$error && $couponCode !== '') {
                $cStmt = $db->prepare("
                    SELECT * FROM Coupon
                    WHERE code = :code
                      AND datetime(valid_to) > datetime('now')
                      AND (company_id IS NULL OR company_id = :cid)
                ");
                $cStmt->execute([':code' => $couponCode, ':cid' => $trip['company_id']]);
                $coupon = $cStmt->fetch(PDO::FETCH_ASSOC);

                if (!$coupon) {
                    $error = 'Kupon geçersiz veya süresi dolmuş.';
                } else {
                    // Kupon limit kontrolü
                    if ((int)$coupon['used_count'] >= (int)$coupon['usage_limit']) {
                        $error = 'Kupon kullanım limiti dolmuş.';
                    }
                }
            }

            if (!$error) {
                $seatCount    = count($validSeats);
                $baseTotal    = (float)$trip['price'] * $seatCount;
                $discountRate = $coupon ? (float)$coupon['discount_percent'] : 0.0;
                $total        = round($baseTotal * (1 - $discountRate));

                // Bakiye kontrol
                $balStmt = $db->prepare("SELECT balance FROM User WHERE id = :uid");
                $balStmt->execute([':uid' => (int)$user['id']]);
                $balance = (float)$balStmt->fetchColumn();

                if ($balance < $total) {
                    $error = "Bakiyen yetersiz. Toplam: {$total} ₺, Bakiyen: {$balance} ₺";
                } else {
                    try {
                        $db->beginTransaction();

                        // Koltukların doluluk kontrolü
                        $placeholders = implode(',', array_fill(0, $seatCount, '?'));
                        $reCheck = $db->prepare("
                            SELECT bs.seat_number
                            FROM Booked_Seats bs
                            JOIN Ticket tk ON tk.id = bs.ticket_id
                            WHERE tk.trip_id = ? AND tk.status = 'active' AND bs.seat_number IN ($placeholders)
                        ");
                        $reCheck->execute(array_merge([$tripId], $validSeats));
                        if ($reCheck->fetch()) {
                            throw new Exception('Seçilen koltuklardan biri az önce doldu. Lütfen tekrar deneyin.');
                        }

                        // Bakiyeden düş
                        $upd = $db->prepare("UPDATE User SET balance = balance - :amt WHERE id = :uid");
                        $upd->execute([':amt' => $total, ':uid' => (int)$user['id']]);

                        // Ticket oluştur
                        $insT = $db->prepare("
                            INSERT INTO Ticket (trip_id, user_id, status, total_price)
                            VALUES (:trip, :user, 'active', :price)
                        ");
                        $insT->execute([
                            ':trip'  => $tripId,
                            ':user'  => (int)$user['id'],
                            ':price' => $total
                        ]);
                        $ticketId = (int)$db->lastInsertId();

                        // Koltukları ekle
                        $insSeat = $db->prepare("
                            INSERT INTO Booked_Seats (ticket_id, seat_number)
                            VALUES (:tid, :sn)
                        ");
                        foreach ($validSeats as $sn) {
                            $insSeat->execute([
                                ':tid' => $ticketId,
                                ':sn'  => (int)$sn
                            ]);
                        }

                        // Kupon kullanımı kaydet
                        if ($coupon) {
                            // Kullanım sayısını artır
                            $db->prepare("
                                UPDATE Coupon SET used_count = used_count + 1 WHERE id = :cid
                            ")->execute([':cid' => (int)$coupon['id']]);

                            // Kullanıcı - kupon ilişkisi ekle
                            $insUC = $db->prepare("
                                INSERT INTO User_Coupon (user_id, coupon_id)
                                VALUES (:uid, :cid)
                            ");
                            $insUC->execute([
                                ':uid' => (int)$user['id'],
                                ':cid' => (int)$coupon['id']
                            ]);
                        }

                        $db->commit();

                        $_SESSION['user']['balance'] = $balance - $total;
                        $message = "Bilet oluşturuldu! Toplam: {$total} ₺ — Bilet ID: {$ticketId}";

                        // Yeni koltuk listesi
                        $bookedStmt->execute([':tid' => $tripId]);
                        $bookedSeats = array_map(fn($r) => (int)$r['seat_number'], $bookedStmt->fetchAll(PDO::FETCH_ASSOC));
                    } catch (Exception $ex) {
                        $db->rollBack();
                        $error = 'Satın alma başarısız: ' . $ex->getMessage();
                    }
                }
            }
        }

        include __DIR__ . '/../views/ticket/buy.php';
    }


    /** 🎫 Kullanıcının Biletleri */
    public static function myTickets() {
        requireLogin();
        global $db;

        $uid = (int)currentUser()['id'];
        $stmt = $db->prepare("
            SELECT 
                tk.id,
                tk.total_price,
                tk.status,
                tk.created_at,
                t.from_city AS departure_city,
                t.to_city AS destination_city,
                t.departure_time,
                t.arrival_time,
                c.name AS company_name
            FROM Ticket tk
            JOIN Trip t ON t.id = tk.trip_id
            JOIN Company c ON c.id = t.company_id
            WHERE tk.user_id = :uid
            ORDER BY tk.created_at DESC
        ");
        $stmt->execute([':uid' => $uid]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Koltuk numaraları
        $seatStmt = $db->prepare("SELECT seat_number FROM Booked_Seats WHERE ticket_id = :tid ORDER BY seat_number ASC");
        foreach ($tickets as &$tk) {
            $seatStmt->execute([':tid' => (int)$tk['id']]);
            $tk['seats'] = array_map(fn($r) => (int)$r['seat_number'], $seatStmt->fetchAll(PDO::FETCH_ASSOC));
        }

        include __DIR__ . '/../views/ticket/my_tickets.php';
    }


    /** ❌ Bilet İptali */
    public static function cancel() {
        requireLogin();
        global $db;

        $uid = (int)currentUser()['id'];
        $ticketId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$ticketId) die('Bilet bulunamadı');

        $stmt = $db->prepare("
            SELECT tk.*, t.departure_time
            FROM Ticket tk
            JOIN Trip t ON t.id = tk.trip_id
            WHERE tk.id = :tid AND tk.user_id = :uid
        ");
        $stmt->execute([':tid' => $ticketId, ':uid' => $uid]);
        $tk = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tk) die('Bilet bulunamadı.');
        if ($tk['status'] !== 'active') die('Bu bilet zaten iptal edilmiş.');

        $now   = new DateTime('now');
        $dep   = new DateTime($tk['departure_time']);
        $limit = (clone $dep)->modify('-1 hour');

        if ($now >= $limit) {
            header("Location: index.php?page=my_tickets&error=too_late");
            exit;
        }

        try {
            $db->beginTransaction();

            $u1 = $db->prepare("UPDATE Ticket SET status = 'canceled' WHERE id = :tid");
            $u1->execute([':tid' => $ticketId]);

            $u2 = $db->prepare("UPDATE User SET balance = balance + :amt WHERE id = :uid");
            $u2->execute([':amt' => (float)$tk['total_price'], ':uid' => $uid]);

            $db->commit();
            $_SESSION['user']['balance'] = ((float)$_SESSION['user']['balance']) + (float)$tk['total_price'];

            header("Location: index.php?page=tickets&canceled=1");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            die('İptal başarısız: ' . $e->getMessage());
        }
    }


    /** 📄 PDF Bilet */
    public static function pdf() {
        $tr = fn(string $s) => mb_convert_encoding($s, 'CP1254', 'UTF-8');
        requireLogin();
        global $db;

        $user = currentUser();
        $ticketId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        if (!$ticketId) die("Bilet ID eksik.");

        $stmt = $db->prepare("
            SELECT 
                t.id AS ticket_id,
                t.total_price,
                t.status,
                t.created_at,
                tr.from_city,
                tr.to_city,
                tr.departure_time,
                tr.arrival_time,
                tr.price AS trip_price,
                c.name AS company_name
            FROM Ticket t
            JOIN Trip tr ON t.trip_id = tr.id
            JOIN Company c ON tr.company_id = c.id
            WHERE t.id = :tid AND t.user_id = :uid
        ");
        $stmt->execute([':tid' => $ticketId, ':uid' => (int)$user['id']]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) die("Bilet bulunamadı veya size ait değil.");

        $st = $db->prepare("SELECT seat_number FROM Booked_Seats WHERE ticket_id = :tid ORDER BY seat_number");
        $st->execute([':tid' => $ticketId]);
        $seatStr = implode(', ', $st->fetchAll(PDO::FETCH_COLUMN));

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->AddFont('Roboto', '', 'Roboto-Regular.php');
        $pdf->AddFont('Roboto', 'B', 'Roboto-SemiBold.php');
        $pdf->AddFont('Roboto', 'I', 'Roboto-Italic.php');
        $pdf->SetFont('Roboto', '', 12);

        $pdf->SetFont('Roboto', 'B', 16);
        $pdf->Cell(0, 10, $tr('E-Bilet'), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('Roboto', '', 12);
        $pdf->Cell(50, 10, $tr('Bilet No:'), 0, 0);
        $pdf->Cell(100, 10, $tr($ticket['ticket_id']), 0, 1);

        $pdf->Cell(50, 10, $tr('Firma:'), 0, 0);
        $pdf->Cell(100, 10, $tr($ticket['company_name']), 0, 1);

        $pdf->Cell(50, 10, $tr('Güzergah:'), 0, 0);
        $pdf->Cell(100, 10, $tr($ticket['from_city'].' -> '.$ticket['to_city']), 0, 1);

        $pdf->Cell(50, 10, $tr('Kalkış:'), 0, 0);
        $pdf->Cell(100, 10, $tr(date('H:i d.m.Y', strtotime($ticket['departure_time']))), 0, 1);

        $pdf->Cell(50, 10, $tr('Varış:'), 0, 0);
        $pdf->Cell(100, 10, $tr(date('H:i d.m.Y', strtotime($ticket['arrival_time']))), 0, 1);

        $pdf->Cell(50, 10, $tr('Koltuklar:'), 0, 0);
        $pdf->Cell(100, 10, $tr($seatStr ?: '-'), 0, 1);

        $pdf->Cell(50, 10, $tr('Tutar:'), 0, 0);
        $pdf->Cell(100, 10, $tr(number_format((float)$ticket['total_price'], 2, ',', '.') . ' TL'), 0, 1);

        $statusTr = $ticket['status'] === 'active' ? 'Aktif' : ($ticket['status'] === 'canceled' ? 'İptal' : strtoupper($ticket['status']));
        $pdf->Cell(50, 10, $tr('Durum:'), 0, 0);
        $pdf->Cell(100, 10, $tr($statusTr), 0, 1);

        $pdf->Ln(15);
        $pdf->SetFont('Roboto', 'I', 10);
        $pdf->Cell(0, 10, $tr('Bu bilet dijital olarak oluşturulmuştur. Geçerli bir e-bilettir.'), 0, 1, 'C');

        if (ob_get_length()) ob_end_clean();
        $pdf->Output('I', 'bilet_' . $ticketId . '.pdf');
    }
}
