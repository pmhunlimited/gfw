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

    <!-- Analytics & AdSense Integration -->
    <?php if (!empty($settings['analytics_code'])): ?>
        <?php echo $settings['analytics_code']; ?>
    <?php endif; ?>
    <?php if (!empty($settings['adsense_code'])): ?>
        <?php echo $settings['adsense_code']; ?>
    <?php endif; ?>
    <?php if (!empty($settings['header_scripts'])): ?>
        <?php echo $settings['header_scripts']; ?>
    <?php endif; ?>

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

      .top-menu { background: #000; border-bottom: 1px solid rgba(255,255,255,0.1); }
      .top-menu .nav-link {
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        padding: 8px 15px;
        letter-spacing: 0.5px;
        transition: color 0.2s;
      }
      .top-menu .nav-link:hover { color: var(--electric-red); }
      .top-menu .post-count { color: var(--electric-red); font-size: 9px; vertical-align: middle; margin-left: 4px; }

      .navbar { background: #000 !important; }
      .navbar-brand { font-size: 24px; letter-spacing: -1px; }
      .main-nav .nav-link {
        font-family: 'Barlow Condensed', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        color: #fff !important;
        padding: 10px 15px !important;
        letter-spacing: 1px;
        font-style: italic;
      }
      .main-nav .nav-link:hover, .main-nav .nav-link.active {
        color: var(--electric-red) !important;
      }

      @media (max-width: 991.98px) {
        .navbar-collapse {
          background: #000;
          position: absolute;
          top: 100%;
          left: 0;
          right: 0;
          z-index: 1000;
          padding: 20px;
          border-bottom: 1px solid rgba(255,255,255,0.1);
        }
      }

      /* Fix conflict between Bootstrap and Tailwind 'collapse' class */
      .navbar-collapse.collapse {
        visibility: visible !important;
      }
    </style>
</head>
<body>
    <!-- Top Bar for Categories -->
    <div class="top-menu no-scrollbar overflow-x-auto">
        <div class="container-fluid px-4">
            <ul class="nav flex-nowrap">
                <?php
                $categories = get_categories_with_counts();
                foreach ($categories as $c) {
                    $cat_url = '/category/' . urlencode($c['name']);
                    echo '<li class="nav-item">
                            <a class="nav-link" href="'.$cat_url.'">
                                '.$c['name'].' <span class="post-count">'.$c['post_count'].'</span>
                            </a>
                          </li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top border-bottom border-white border-opacity-10 py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand font-condensed fw-black italic tracking-tighter" href="/">
                <?php if (!empty($settings['logo'])): ?>
                    <img src="<?php echo $settings['logo']; ?>" alt="Logo" style="max-height: 40px;">
                <?php else: ?>
                    GLOBAL <span class="text-electric-red">FOOTBALL</span>
                <?php endif; ?>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto main-nav">
                    <?php
                    $current_path = $_SERVER['REQUEST_URI'];
                    ?>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_path == '/' || $current_path == '/index.php') ? 'active' : ''; ?>" href="/">Home</a></li>

                    <?php
                    $pages = get_pages('main');
                    foreach ($pages as $p) {
                        $active = ($current_path == '/'.$p['slug']) ? 'active' : '';
                        echo '<li class="nav-item"><a class="nav-link '.$active.'" href="/'.$p['slug'].'">'.$p['title'].'</a></li>';
                    }
                    ?>

                    <li class="nav-item"><a class="nav-link <?php echo ($current_path == '/watch') ? 'active' : ''; ?>" href="/watch">Live Feed</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_path == '/tables' || $current_path == '/standings') ? 'active' : ''; ?>" href="/tables">Standings</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main>
