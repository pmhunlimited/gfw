<?php
admin_header("Parameters");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_general'])) {
    $stmt = $conn->prepare("UPDATE site_settings SET name = ?, tagline = ?, admin_email = ?, whatsapp_number = ? WHERE id = 1");
    $stmt->execute([
        sanitize($_POST['name']),
        sanitize($_POST['tagline']),
        sanitize($_POST['admin_email']),
        sanitize($_POST['whatsapp_number'])
    ]);
    $success = "General parameters synchronized.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_ai'])) {
    $stmt = $conn->prepare("UPDATE site_settings SET gemini_api_key = ?, deepseek_api_key = ?, news_api_key = ?, sportmonks_api_key = ?, selected_model = ? WHERE id = 1");
    $stmt->execute([
        $_POST['gemini_api_key'],
        $_POST['deepseek_api_key'],
        $_POST['news_api_key'],
        $_POST['sportmonks_api_key'],
        $_POST['selected_model']
    ]);
    $success = "AI logic updated.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_smtp'])) {
    $stmt = $conn->prepare("UPDATE site_settings SET smtp_host = ?, smtp_port = ?, smtp_user = ?, smtp_pass = ?, smtp_sender_email = ?, smtp_sender_name = ? WHERE id = 1");
    $stmt->execute([
        sanitize($_POST['smtp_host']),
        sanitize($_POST['smtp_port']),
        sanitize($_POST['smtp_user']),
        $_POST['smtp_pass'],
        sanitize($_POST['smtp_sender_email']),
        sanitize($_POST['smtp_sender_name'])
    ]);
    $success = "SMTP cluster reconfigured.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_security'])) {
    $pin_enabled = isset($_POST['pin_enabled']) ? 1 : 0;
    $new_pin = $_POST['admin_pin'];

    if ($pin_enabled && !empty($new_pin)) {
        $hashed_pin = password_hash($new_pin, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE site_settings SET pin_enabled = ?, admin_pin = ? WHERE id = 1");
        $stmt->execute([$pin_enabled, $hashed_pin]);
    } else {
        $stmt = $conn->prepare("UPDATE site_settings SET pin_enabled = ? WHERE id = 1");
        $stmt->execute([$pin_enabled]);
    }
    $success = "Security protocols updated.";
}

$settings = get_settings();
$activeTab = $_GET['tab'] ?? 'general';

?>
<h1 class="font-condensed fw-black italic text-white display-5 mb-5">SYSTEM <span class="text-danger">CONTROL</span></h1>

<?php if (isset($success)): ?>
    <div class="alert alert-success bg-green-900 bg-opacity-10 border-green-500 border-opacity-20 text-green-500 font-condensed italic uppercase mb-5"><?php echo $success; ?></div>
<?php endif; ?>

<div class="bg-[#0a0e17] rounded-3xl border border-white/5 overflow-hidden shadow-2xl">
    <div class="d-flex flex-wrap border-bottom border-white/5 bg-black">
        <a href="?tab=general" class="flex-grow-1 text-center py-4 text-[10px] font-black uppercase tracking-widest text-decoration-none border-bottom-2 <?php echo $activeTab == 'general' ? 'text-danger border-danger' : 'text-secondary border-transparent'; ?>">General</a>
        <a href="?tab=ai" class="flex-grow-1 text-center py-4 text-[10px] font-black uppercase tracking-widest text-decoration-none border-bottom-2 <?php echo $activeTab == 'ai' ? 'text-danger border-danger' : 'text-secondary border-transparent'; ?>">AI Core</a>
        <a href="?tab=smtp" class="flex-grow-1 text-center py-4 text-[10px] font-black uppercase tracking-widest text-decoration-none border-bottom-2 <?php echo $activeTab == 'smtp' ? 'text-danger border-danger' : 'text-secondary border-transparent'; ?>">SMTP</a>
        <a href="?tab=security" class="flex-grow-1 text-center py-4 text-[10px] font-black uppercase tracking-widest text-decoration-none border-bottom-2 <?php echo $activeTab == 'security' ? 'text-danger border-danger' : 'text-secondary border-transparent'; ?>">Security</a>
        <a href="?tab=automation" class="flex-grow-1 text-center py-4 text-[10px] font-black uppercase tracking-widest text-decoration-none border-bottom-2 <?php echo $activeTab == 'automation' ? 'text-danger border-danger' : 'text-secondary border-transparent'; ?>">Automation</a>
        <a href="?tab=social" class="flex-grow-1 text-center py-4 text-[10px] font-black uppercase tracking-widest text-decoration-none border-bottom-2 <?php echo $activeTab == 'social' ? 'text-danger border-danger' : 'text-secondary border-transparent'; ?>">Syndication</a>
    </div>

    <div class="p-5 p-md-5">
        <?php if ($activeTab == 'general'): ?>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Broadcaster Identity</label>
                        <input type="text" name="name" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold" value="<?php echo $settings['name']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Tagline</label>
                        <input type="text" name="tagline" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold" value="<?php echo $settings['tagline']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Admin Email</label>
                        <input type="email" name="admin_email" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold" value="<?php echo $settings['admin_email']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white font-bold" value="<?php echo $settings['whatsapp_number']; ?>">
                    </div>
                </div>
                <button type="submit" name="save_general" class="mt-8 bg-danger text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest hover:bg-white hover:text-danger transition-all">Commit Identity</button>
            </form>

        <?php elseif ($activeTab == 'ai'): ?>
            <div class="alert alert-info bg-blue-900 bg-opacity-10 border-blue-500 border-opacity-20 text-info font-condensed italic uppercase mb-5 p-4 rounded-3xl">
                <h5 class="fw-black mb-3">Intelligence Acquisition Guide</h5>
                <div class="row g-4 small">
                    <div class="col-md-6 border-end border-white border-opacity-10">
                        <p class="mb-2"><strong>Google Gemini API:</strong></p>
                        <ol class="ps-3 opacity-75">
                            <li>Visit <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-info">Google AI Studio</a>.</li>
                            <li>Sign in with your Google Account.</li>
                            <li>Click "Create API key" and copy the value.</li>
                            <li>Ensure billing is active if you expect high traffic.</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>DeepSeek API:</strong></p>
                        <ol class="ps-3 opacity-75">
                            <li>Visit <a href="https://platform.deepseek.com/" target="_blank" class="text-info">DeepSeek Platform</a>.</li>
                            <li>Navigate to the "API Keys" section.</li>
                            <li>Generate a new key and add balance to your account.</li>
                            <li>Copy and secure the key.</li>
                        </ol>
                    </div>
                </div>
            </div>

            <form method="POST" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="bg-white/5 p-6 rounded-2xl border border-white/10 shadow-inner">
                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Gemini API Key</span>
                            <input type="password" name="gemini_api_key" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-xs text-white" value="<?php echo $settings['gemini_api_key']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white/5 p-6 rounded-2xl border border-white/10 shadow-inner">
                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3 block">DeepSeek API Key</span>
                            <input type="password" name="deepseek_api_key" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-xs text-white" value="<?php echo $settings['deepseek_api_key']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white/5 p-6 rounded-2xl border border-white/10 shadow-inner">
                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3 block">NewsAPI.org Key</span>
                            <input type="password" name="news_api_key" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-xs text-white" value="<?php echo $settings['news_api_key'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-white/5 p-6 rounded-2xl border border-white/10 shadow-inner">
                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest mb-3 block">Sportmonks.com API Key</span>
                            <input type="password" name="sportmonks_api_key" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-xs text-white" value="<?php echo $settings['sportmonks_api_key'] ?? ''; ?>">
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <span class="text-[10px] font-black uppercase text-gray-500 tracking-widest block mb-4">Central Intelligence Model</span>
                    <?php
                    $models = [
                        ['id' => 'gemini-3-flash-preview', 'name' => 'Gemini 3 Flash (Prime)', 'provider' => 'Google'],
                        ['id' => 'gemini-flash-latest', 'name' => 'Gemini 1.5 Flash (Stable)', 'provider' => 'Google'],
                        ['id' => 'deepseek-chat', 'name' => 'DeepSeek-V3', 'provider' => 'DeepSeek'],
                    ];
                    foreach ($models as $m):
                    ?>
                        <label class="d-flex align-items-center justify-content-between p-4 rounded-2xl border w-full mb-3 cursor-pointer transition-all <?php echo $settings['selected_model'] == $m['id'] ? 'border-danger bg-danger bg-opacity-5' : 'border-white/5 bg-white/5'; ?>">
                            <div class="text-start">
                                <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase bg-white/10 text-gray-400 mr-2"><?php echo $m['provider']; ?></span>
                                <span class="text-sm font-black text-white uppercase italic"><?php echo $m['name']; ?></span>
                            </div>
                            <input type="radio" name="selected_model" value="<?php echo $m['id']; ?>" <?php echo $settings['selected_model'] == $m['id'] ? 'checked' : ''; ?> class="form-check-input bg-danger border-0">
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="save_ai" class="bg-danger text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest hover:bg-white hover:text-danger transition-all">Update AI Logic</button>
            </form>

        <?php elseif ($activeTab == 'security'): ?>
            <form method="POST" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="bg-white/5 p-8 rounded-3xl border border-white/5">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h4 class="text-white font-black uppercase italic mb-1">Security PIN (2FA)</h4>
                            <p class="text-gray-500 text-[10px] uppercase font-bold tracking-widest">Enhanced biometric-style cipher protection</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="pin_enabled" class="sr-only peer" <?php echo ($settings['pin_enabled'] ?? false) ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-danger after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>

                    <div class="mb-4">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Set New Security PIN</label>
                        <input type="password" name="admin_pin" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-mono tracking-[1em]" placeholder="••••">
                        <p class="text-[9px] text-white-50 mt-2 italic opacity-40">Only required if enabling for the first time or changing the PIN.</p>
                    </div>
                </div>
                <button type="submit" name="save_security" class="bg-danger text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest hover:bg-white hover:text-danger transition-all">Apply Security Logic</button>
            </form>

        <?php elseif ($activeTab == 'automation'): ?>
            <div class="space-y-8">
                <div class="alert alert-warning bg-orange-900 bg-opacity-10 border-orange-500 border-opacity-20 text-orange-500 font-condensed italic uppercase p-4 rounded-3xl">
                    <h5 class="fw-black mb-3">Cron Job Configuration</h5>
                    <p class="small mb-4 opacity-75">To enable daily news automation, you must configure a cron job on your server (cPanel/VPS). This job should trigger the news engine every 24 hours.</p>

                    <div class="bg-black/50 p-4 rounded-2xl mb-4 border border-white/10">
                        <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest block mb-2">CRON JOB COMMAND</span>
                        <code class="text-white small">0 6 * * * /usr/bin/php <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/automation/news_engine.php</code>
                    </div>

                    <h6 class="fw-black small uppercase mb-2">Instructions for cPanel:</h6>
                    <ol class="small ps-3 opacity-75">
                        <li>Log in to your <strong>cPanel</strong> account.</li>
                        <li>Search for <strong>"Cron Jobs"</strong> in the search bar.</li>
                        <li>Under "Add New Cron Job", select <strong>"Once Per Day"</strong> from Common Settings.</li>
                        <li>In the "Command" field, paste the command shown above.</li>
                        <li>Click <strong>"Add New Cron Job"</strong> to finalize.</li>
                    </ol>
                </div>

                <div class="bg-white/5 p-8 rounded-3xl border border-white/5">
                    <h4 class="text-white font-black uppercase italic mb-3 small">Operational Status</h4>
                    <div class="d-flex align-items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-success shadow-[0_0_10px_#198754]"></div>
                        <span class="text-[10px] font-black text-white uppercase tracking-widest">Automation Engine Ready</span>
                    </div>
                    <p class="text-gray-500 text-[9px] uppercase font-bold mt-4">Note: Ensure your Gemini and NewsAPI keys are configured in the "AI Core" tab.</p>
                </div>
            </div>

        <?php elseif ($activeTab == 'smtp'): ?>
            <form method="POST" class="space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Host Address</label>
                        <input type="text" name="smtp_host" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-mono" value="<?php echo $settings['smtp_host']; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Port</label>
                        <input type="text" name="smtp_port" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white font-mono" value="<?php echo $settings['smtp_port']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Auth Username</label>
                        <input type="text" name="smtp_user" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white" value="<?php echo $settings['smtp_user']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Auth Password</label>
                        <input type="password" name="smtp_pass" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white" value="<?php echo $settings['smtp_pass']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Sender Email</label>
                        <input type="email" name="smtp_sender_email" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white" value="<?php echo $settings['smtp_sender_email']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Sender Name</label>
                        <input type="text" name="smtp_sender_name" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white" value="<?php echo $settings['smtp_sender_name']; ?>">
                    </div>
                </div>
                <button type="submit" name="save_smtp" class="mt-8 bg-danger text-white px-10 py-3 rounded-2xl font-black uppercase italic tracking-widest hover:bg-white hover:text-danger transition-all">Apply SMTP Config</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php admin_footer(); ?>
