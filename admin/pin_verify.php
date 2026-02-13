<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    redirect('/admin/login');
}

$settings = get_settings();
if (!$settings['pin_enabled']) {
    $_SESSION['pin_verified'] = true;
    $_SESSION['pin_verified_at'] = time();
    redirect('/admin/');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF TOKEN INVALID.";
    } else {
        $pin = $_POST['pin'];
        if (password_verify($pin, $settings['admin_pin'])) {
            $_SESSION['pin_verified'] = true;
            $_SESSION['pin_verified_at'] = time();
            redirect('/admin/');
        } else {
            $error = "INVALID SECURITY PIN. ACCESS DENIED.";
            log_activity("Failed admin PIN attempt.");
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>GFW | SECURITY VERIFICATION</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #05070a; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
        .pin-card { background: #0a0e17; border: 1px solid rgba(255,255,255,0.05); padding: 50px; border-radius: 30px; width: 100%; max-width: 400px; }
        .font-condensed { font-family: 'Barlow Condensed'; text-transform: uppercase; }
        .btn-primary { background: #ff3e3e; border: none; font-weight: 900; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="pin-card shadow-2xl text-center">
        <div class="mb-4">
            <i class="bi bi-shield-lock-fill text-danger display-4"></i>
        </div>
        <h1 class="font-condensed italic text-white mb-2">SECURITY <span class="text-danger">PIN</span></h1>
        <p class="text-white-50 small uppercase tracking-widest mb-5">Enter your secondary authorization cipher</p>

        <?php if ($error): ?>
            <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-20 text-danger small font-bold italic"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="mb-5">
                <input type="password" name="pin" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl py-3 text-center tracking-[1em] fs-4" placeholder="••••" required autofocus maxlength="10">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 rounded-xl font-condensed italic">VERIFY IDENTITY</button>
        </form>
    </div>
</body>
</html>
