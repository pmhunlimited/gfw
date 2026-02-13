<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (is_admin()) {
    redirect('/admin/');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF TOKEN INVALID.";
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];

        $conn = get_db_connection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                log_activity("Admin login successful: " . $username . " from " . $_SERVER['REMOTE_ADDR']);

                redirect('/admin/');
            } else {
                $error = "INVALID CREDENTIALS. SYSTEM SECURE.";
                log_activity("Failed admin login attempt: " . $username);
            }
        } else {
            $error = "DATABASE CONNECTION ERROR.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>GFW | CORE ACCESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #05070a; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
        .login-card { background: #0a0e17; border: 1px solid rgba(255,255,255,0.05); padding: 50px; border-radius: 30px; width: 100%; max-width: 450px; }
        .font-condensed { font-family: 'Barlow Condensed'; text-transform: uppercase; }
        .btn-primary { background: #ff3e3e; border: none; font-weight: 900; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="login-card shadow-2xl">
        <h1 class="font-condensed italic text-center text-white mb-2">CORE <span class="text-danger">ACCESS</span></h1>
        <p class="text-center text-white-50 small uppercase tracking-widest mb-5">Identify yourself to the network</p>

        <?php if ($error): ?>
            <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-20 text-danger small font-bold italic"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="mb-4">
                <label class="form-label text-white-50 small uppercase font-black">Operator ID</label>
                <input type="text" name="username" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl py-3" required>
            </div>
            <div class="mb-5">
                <label class="form-label text-white-50 small uppercase font-black">Security Cipher</label>
                <input type="password" name="password" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl py-3" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 rounded-xl font-condensed italic">AUTHORIZE ACCESS</button>
        </form>
        <div class="mt-4 text-center">
            <a href="/" class="text-white-50 small text-decoration-none hover:text-white transition-all uppercase font-black italic tracking-widest">Back to Broadcast</a>
        </div>
    </div>
</body>
</html>
