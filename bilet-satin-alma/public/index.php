<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/config/database.php';

require_once __DIR__ . '/../app/helpers/auth.php';


$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'home':
        include __DIR__ . '/../app/views/home.php';
        break;

    case 'login':
        include __DIR__ . '/../app/views/auth/login.php';
        break;

    case 'register':
        include __DIR__ . '/../app/views/auth/register.php';
        break;

    // User sayfaları
    case 'tickets':
    requireRole(['user']);
    require_once __DIR__ . '/../app/controllers/BiletController.php';
    BiletController::myTickets();
    break;

    case 'select_seat':
    require_once __DIR__ . '/../app/controllers/BiletController.php';
    BiletController::buy();
    break;
    case 'ticket_pdf':
    requireRole(['user']);
    require_once __DIR__ . '/../app/controllers/BiletController.php';
    BiletController::pdf();
    break;
    
    case 'company_coupons':
    requireRole(['company_admin']);
    include __DIR__ . '/../app/views/company/kuponlar.php';
    break;

    case 'cancel_ticket':
    requireRole(['user']);
    require_once __DIR__ . '/../app/controllers/BiletController.php';
    BiletController::cancel();
    break;

    // Admin
    case 'admin':
        requireRole(['admin']);
        include __DIR__ . '/../app/views/admin/dashboard.php';
        break;

    case 'admin_companies':
        requireRole(['admin']);
        require_once __DIR__ . '/../app/controllers/AdminController.php';
        AdminController::companies();
        break;

    case 'admin_coupons':
        requireRole(['admin']);
        include __DIR__ . '/../app/views/admin/coupons.php';
        break;
    
    case 'company_dashboard': 
        requireRole(['company_admin']);
        include __DIR__ . '/../app/views/company/dashboard.php';
        break;
    
    case 'admin_company_admins':
        requireRole(['admin']);
        include __DIR__ . '/../app/views/admin/firma_adminler.php';
        break;


    case 'admin_coupons':
        requireRole(['admin']);
        include __DIR__ . '/../app/views/admin/kuponlar.php';
        break;

    case 'admin_dashboard':
        requireRole(['admin']);
        include __DIR__ . '/../app/views/admin/dashboard.php';
        break;

    case 'firma_adminler':
        requireRole(['admin']);
        include __DIR__ . '/../app/views/admin/firma_adminler.php';
        break;
        
    case 'admin_coupons':
        requireRole(['admin']);
        include '../app/views/admin/coupons.php';
        break;

    case 'logout':
    // Session başlat ve tamamen temizle
    session_start();
    $_SESSION = [];             // tüm session verilerini temizle
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    session_destroy(); // oturumu tamamen sonlandır

    // Login sayfasına yönlendir
    header("Location: /bilet-satin-alma/public/index.php?page=login");
    exit;



    case 'company_dashboard':
        requireRole(['company_admin']);
        include __DIR__ . '/../app/views/company/dashboard.php';
        break;

    
        

    default:
        echo "404 Not Found";

}


