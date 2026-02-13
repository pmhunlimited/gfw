<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <title>GFW | SYSTEM CONTROL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Barlow+Condensed:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #05070a; color: #fff; font-family: 'Inter', sans-serif; }
        .font-condensed { font-family: 'Barlow Condensed'; text-transform: uppercase; }
        .sidebar { background: #0a0e17; border-right: 1px solid rgba(255,255,255,0.05); min-height: 100vh; }
        .nav-link { color: #64748b; font-weight: 900; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; padding: 15px 25px; border-left: 3px solid transparent; }
        .nav-link.active { color: #ff3e3e; border-left-color: #ff3e3e; background: rgba(255,62,62,0.05); }
        .nav-link:hover { color: #fff; }

        /* Improved Readability & Sharpness */
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility; }
        .bg-white, .modal-content, .alert-light { color: #000 !important; }
        .bg-white h1, .bg-white h2, .bg-white h3, .bg-white p { color: #000 !important; }
        .text-sharp { text-shadow: 0 0 1px rgba(255,255,255,0.1); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0 sidebar d-none d-md-block fixed-top h-100">
            <div class="p-4 mb-4">
                <a href="/" class="font-condensed italic fw-black text-white text-decoration-none fs-4">GFW <span class="text-danger">CORE</span></a>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link <?php echo ($path == '' || $path == '/') ? 'active' : ''; ?>" href="/admin/"><i class="bi bi-grid-fill me-3"></i> Intelligence</a>
                <a class="nav-link <?php echo $path == '/comments' ? 'active' : ''; ?>" href="/admin/comments"><i class="bi bi-chat-dots-fill me-3"></i> Feedback</a>
                <a class="nav-link <?php echo $path == '/subscribers' ? 'active' : ''; ?>" href="/admin/subscribers"><i class="bi bi-people-fill me-3"></i> Network</a>
                <a class="nav-link <?php echo $path == '/categories' ? 'active' : ''; ?>" href="/admin/categories"><i class="bi bi-tags-fill me-3"></i> Taxonomy</a>
                <a class="nav-link <?php echo $path == '/settings' ? 'active' : ''; ?>" href="/admin/settings"><i class="bi bi-sliders me-3"></i> Parameters</a>
                <a class="nav-link <?php echo $path == '/profile' ? 'active' : ''; ?>" href="/admin/profile"><i class="bi bi-person-badge-fill me-3"></i> Operator</a>
                <hr class="border-white border-opacity-5 mx-4 my-4">
                <a class="nav-link text-danger" href="/admin/logout"><i class="bi bi-power me-3"></i> Deauthorize</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-5">
