<?php
require_once __DIR__ . '/functions.php';
$settings = get_settings();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($custom_meta_title) ? $custom_meta_title : ($settings['name'] ?? 'GFW') . ' | Elite Coverage'; ?></title>
    <?php if (isset($custom_meta_description)): ?>
    <meta name="description" content="<?php echo $custom_meta_description; ?>">
    <?php endif; ?>
    <?php if (isset($custom_meta_keywords)): ?>
    <meta name="keywords" content="<?php echo $custom_meta_keywords; ?>">
    <?php endif; ?>
    <?php if (!empty($settings['favicon'])): ?>
    <link rel="shortcut icon" href="<?php echo $settings['favicon']; ?>" type="image/x-icon">
    <?php endif; ?>
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Barlow+Condensed:wght@600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
      :root {
        --pitch-dark: #05070a;
        --electric-red: #ff3e3e;
      }

      html, body {
        width: 100%;
        overflow-x: hidden;
        font-family: 'Inter', sans-serif;
        background-color: var(--pitch-dark);
        color: #fff;
      }

      h1, h2, h3, h4, h5, h6, .font-condensed {
        font-family: 'Barlow Condensed', sans-serif;
        text-transform: uppercase;
        letter-spacing: -0.01em;
      }

      .text-electric-red { color: var(--electric-red); }
      .bg-electric-red { background-color: var(--electric-red); }
      .border-electric-red { border-color: var(--electric-red); }

      /* Custom Scrollbar */
      ::-webkit-scrollbar { width: 6px; height: 6px; }
      ::-webkit-scrollbar-track { background: #0a0e17; }
      ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
      ::-webkit-scrollbar-thumb:hover { background: var(--electric-red); }

      .no-scrollbar::-webkit-scrollbar { display: none; }
      .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

      .card { background-color: #0a0e17; border: 1px solid rgba(255,255,255,0.05); }
      .btn-primary { background-color: var(--electric-red); border-color: var(--electric-red); }
      .btn-primary:hover { background-color: #d32f2f; border-color: #d32f2f; }

      /* Improved Readability & Sharpness */
      body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility; }
      .bg-white, .bg-white *, .alert-light, .alert-light *, .scoreaxis-widget, .scoreaxis-widget * { color: initial !important; }
      .bg-white, .bg-white p, .bg-white h1, .bg-white h2, .bg-white h3, .bg-white span { color: #000 !important; }
      .alert { border-radius: 15px; border-opacity: 0.2; }

      /* Fix Tailwind/Bootstrap .collapse conflict */
      .navbar-collapse.collapse { visibility: visible !important; }

    </style>
</head>
<body>
    <!-- Main Navbar -->
    <nav class="navbar navbar-dark bg-black border-bottom border-white border-opacity-10 py-2 sticky-top">
        <div class="container-fluid px-4 d-flex align-items-center">
            <a class="navbar-brand font-condensed fw-black italic tracking-tighter fs-3 me-4" href="/">
                <?php if (!empty($settings['logo'])): ?>
                    <img src="<?php echo $settings['logo']; ?>" alt="Logo" style="max-height: 35px;" class="d-inline-block align-middle">
                <?php else: ?>
                    <?php echo explode(' ', $settings['name'] ?? 'GLOBAL FOOTBALL WATCH')[0]; ?> <span class="text-electric-red"><?php echo explode(' ', $settings['name'] ?? 'GLOBAL FOOTBALL WATCH')[1] ?? ''; ?></span>
                <?php endif; ?>
            </a>

            <div class="d-flex align-items-center justify-content-between flex-grow-1">
                <ul class="nav font-condensed fw-bold uppercase small italic align-items-center">
                    <?php $current_path = $_SERVER['REQUEST_URI']; ?>
                    <li class="nav-item"><a class="nav-link px-2 <?php echo ($current_path == '/' || $current_path == '/index.php') ? 'active text-electric-red' : ''; ?>" href="/" style="color: #fff;">Home</a></li>

                    <?php
                    $conn = get_db_connection();
                    if ($conn) {
                        $pages = $conn->query("SELECT title, slug FROM pages WHERE is_visible = 1 AND position = 'main'")->fetchAll();
                        foreach ($pages as $p) {
                            $active = ($current_path == '/'.$p['slug']) ? 'active text-electric-red' : '';
                            echo '<li class="nav-item"><a class="nav-link px-2 '.$active.'" href="/'.$p['slug'].'" style="color: #fff;">'.$p['title'].'</a></li>';
                        }
                    }
                    ?>
                    <li class="nav-item"><a class="nav-link px-2 <?php echo ($current_path == '/watch') ? 'active text-electric-red' : ''; ?>" href="/watch" style="color: #fff;">Live Feed</a></li>
                    <li class="nav-item"><a class="nav-link px-2 <?php echo ($current_path == '/tables' || $current_path == '/standings') ? 'active text-electric-red' : ''; ?>" href="/tables" style="color: #fff;">Standings</a></li>
                </ul>

                <ul class="nav font-condensed fw-bold uppercase small align-items-center no-scrollbar flex-nowrap overflow-x-auto d-none d-lg-flex">
                    <?php
                    $categories = get_categories_with_counts();
                    foreach ($categories as $c) {
                        $cat_url = '/category/' . urlencode($c['name']);
                        $active = (strpos($current_path, '/category/'.urlencode($c['name'])) !== false) ? 'active text-electric-red' : '';
                        echo '<li class="nav-item">
                                <a class="nav-link px-2 '.$active.'" href="'.$cat_url.'" style="color: rgba(255,255,255,0.7);">
                                    '.$c['name'].' <span class="post-count" style="font-size: 8px; background: rgba(255,62,62,0.1); color: #ff3e3e; padding: 1px 4px; border-radius: 3px;">'.$c['post_count'].'</span>
                                </a>
                              </li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>
    <main>
