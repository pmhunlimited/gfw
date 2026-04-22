<?php
admin_header("Operator");

$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);

    if (!empty($_POST['new_password'])) {
        $pass = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
        $stmt->execute([$username, $email, $pass, $user_id]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->execute([$username, $email, $user_id]);
    }
    $success = "Operator credentials updated.";
    $_SESSION['username'] = $username;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$me = $stmt->fetch();


<h1 class="font-condensed fw-black italic text-white display-5 mb-5">OPERATOR <span class="text-danger">PROFILE</span></h1>

<?php if (isset($success)):
    <div class="alert alert-success bg-green-900 bg-opacity-10 border-green-500 border-opacity-20 text-green-500 font-condensed italic uppercase mb-5"><?php echo $success; ?></div>
<?php endif;

<div class="bg-[#0a0e17] rounded-3xl border border-white/5 p-5 p-md-5 shadow-2xl max-w-2xl">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <div class="mb-4">
            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Operator ID</label>
            <input type="text" name="username" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold" value="<?php echo $me['username']; ?>" required>
        </div>
        <div class="mb-4">
            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Secure Email</label>
            <input type="email" name="email" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold" value="<?php echo $me['email']; ?>" required>
        </div>
        <div class="mb-5">
            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">New Security Cipher (Leave blank to retain current)</label>
            <input type="password" name="new_password" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold">
        </div>
        <button type="submit" class="bg-danger text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest hover:bg-white hover:text-danger transition-all">Update Credentials</button>
    </form>
</div>

<?php admin_footer();
