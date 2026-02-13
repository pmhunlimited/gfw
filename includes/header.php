<?php
require_once __DIR__ . '/functions.php';
$settings = get_settings();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $settings['name'] ?? 'GFW'; ?> | Elite Coverage</title>
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

      .markdown-content table { width: 100%; border-collapse: collapse; margin: 20px 0; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: rgba(255,255,255,0.02); }
      .markdown-content th { background: rgba(255,62,62,0.15); color: #ff3e3e; padding: 14px 18px; border-bottom: 2px solid rgba(255,62,62,0.3); text-align: left; }
      .markdown-content td { padding: 12px 18px; border-bottom: 1px solid rgba(255,255,255,0.05); color: rgba(255,255,255,0.9); }

      /* Improved Readability & Sharpness */
      body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility; }
      .bg-white, .bg-white *, .alert-light, .alert-light *, .scoreaxis-widget, .scoreaxis-widget * { color: initial !important; }
      .bg-white, .bg-white p, .bg-white h1, .bg-white h2, .bg-white h3, .bg-white span { color: #000 !important; }
      .alert { border-radius: 15px; border-opacity: 0.2; }
      .text-sharp { text-shadow: 0 0 1px rgba(255,255,255,0.1); }
      ::placeholder { color: rgba(255,255,255,0.3) !important; }

      .top-menu { background: #000; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 10px; overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch; }
      .top-menu::-webkit-scrollbar { display: none; }
      .top-menu .nav { flex-wrap: nowrap; }
      .top-menu .nav-link { color: #64748b; font-weight: 700; text-transform: uppercase; padding: 10px 15px; letter-spacing: 1px; display: inline-block; }
      .top-menu .nav-link:hover, .top-menu .nav-link.active { color: var(--electric-red); }
      .top-menu .post-count { background: rgba(255,62,62,0.1); color: var(--electric-red); padding: 2px 6px; border-radius: 4px; margin-left: 5px; font-size: 9px; }
    </style>
</head>
<body>
    <!-- Top Menu (Categories) -->
    <div class="top-menu">
        <div class="container-fluid px-4">
            <ul class="nav">
                <?php
                $current_path = $_SERVER['REQUEST_URI'];
                $categories = get_categories_with_counts();
                foreach ($categories as $c) {
                    $cat_url = '/category/' . urlencode($c['name']);
                    $active = (strpos($current_path, '/category/'.urlencode($c['name'])) !== false) ? 'active' : '';
                    echo '<li class="nav-item">
                            <a class="nav-link '.$active.'" href="'.$cat_url.'">
                                '.$c['name'].' <span class="post-count">'.$c['post_count'].'</span>
                            </a>
                          </li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-white border-opacity-10 py-3 sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand font-condensed fw-black italic tracking-tighter fs-3" href="/">
                <?php echo explode(' ', $settings['name'] ?? 'GLOBAL FOOTBALL WATCH')[0]; ?> <span class="text-electric-red"><?php echo explode(' ', $settings['name'] ?? 'GLOBAL FOOTBALL WATCH')[1] ?? ''; ?></span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto font-condensed fw-bold uppercase tracking-widest small italic">
                    <?php
                    $conn = get_db_connection();
                    if ($conn) {
                        // Dynamic Pages
                        $pages = $conn->query("SELECT title, slug FROM pages WHERE is_visible = 1")->fetchAll();
                        foreach ($pages as $p) {
                            $active = ($current_path == '/'.$p['slug']) ? 'active text-electric-red' : '';
                            echo '<li class="nav-item"><a class="nav-link px-3 '.$active.'" href="/'.$p['slug'].'">'.$p['title'].'</a></li>';
                        }
                    }
                    ?>

                    <li class="nav-item"><a class="nav-link px-3 <?php echo ($current_path == '/' || $current_path == '/index.php') ? 'active text-electric-red' : ''; ?>" href="/">Home</a></li>
                    <li class="nav-item"><a class="nav-link px-3 <?php echo ($current_path == '/watch') ? 'active text-electric-red' : ''; ?>" href="/watch">Live Feed</a></li>
                    <li class="nav-item"><a class="nav-link px-3 <?php echo ($current_path == '/tables' || $current_path == '/standings') ? 'active text-electric-red' : ''; ?>" href="/tables">Standings</a></li>
                    <li class="nav-item"><a class="nav-link px-3 <?php echo ($current_path == '/betting') ? 'active text-electric-red' : ''; ?>" href="/betting">Betting</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main>
