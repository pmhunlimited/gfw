<?php
include __DIR__ . '/../includes/header.php';

$category = $_GET['category'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$conn = get_db_connection();
$posts = [];
$featuredNews = [];
$topNews = [];
$watchLive = [];
$categories = [];
$totalPosts = 0;
$totalPages = 0;

if ($conn) {
    if ($category) {
        // CATEGORY PAGE: Display all posts in this category
        $stmt_count = $conn->prepare("SELECT COUNT(*) FROM posts WHERE LOWER(category) = LOWER(?) AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP)");
        $stmt_count->execute([$category]);
        $totalPosts = (int)$stmt_count->fetchColumn();
        $totalPages = ceil($totalPosts / $limit);

        $stmt = $conn->prepare("SELECT * FROM posts WHERE LOWER(category) = LOWER(?) AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP) ORDER BY publish_date DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $category, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll();
    } else {
        // HOME PAGE: Magazine Layout
        // 1. Featured News (Latest top stories for Hero/Slider)
        $stmt_featured = $conn->query("SELECT * FROM posts WHERE is_top_story = 1 AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP) ORDER BY publish_date DESC LIMIT 5");
        $featuredNews = $stmt_featured->fetchAll();

        // 2. Top News (Latest news excluding featured in hero)
        $featured_ids = array_map(function($p) { return $p['id']; }, $featuredNews);
        $placeholders = count($featured_ids) ? implode(',', array_fill(0, count($featured_ids), '?')) : '0';
        $stmt_top = $conn->prepare("SELECT * FROM posts WHERE id NOT IN ($placeholders) AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP) ORDER BY publish_date DESC LIMIT 6");
        $stmt_top->execute($featured_ids);
        $topNews = $stmt_top->fetchAll();

        // 3. Watch Live (YouTube videos)
        $stmt_watch = $conn->query("SELECT * FROM posts WHERE video_url IS NOT NULL AND video_url != '' AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP) ORDER BY publish_date DESC LIMIT 4");
        $watchLive = $stmt_watch->fetchAll();

        // 4. Category Breakdown
        $categories = $conn->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    }
}

if ($category) {
    $custom_meta_title = "Category: " . htmlspecialchars($category) . " | GFW";
}
?>

<div class="container-fluid pt-0 px-0 bg-black overflow-x-hidden">
    <?php if (!$category): ?>
        <!-- HERO SECTION -->
        <?php if (!empty($featuredNews)): $hero = $featuredNews[0]; ?>
        <section class="row g-0 border-bottom border-white border-opacity-10 bg-[#05070a]">
            <div class="col-lg-8 border-end border-white border-opacity-10 position-relative hero-height">
                <a href="/post/<?php echo $hero['slug']; ?>" class="text-decoration-none d-block h-100 position-relative overflow-hidden group">
                    <img src="<?php echo $hero['image']; ?>" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover opacity-80 transition-all duration-1000 group-hover:scale-105" alt="">
                    <div class="position-absolute bottom-0 start-0 w-100 p-4 p-md-5 bg-gradient-to-t from-black via-black/70 to-transparent z-20">
                        <span class="badge bg-electric-red rounded-0 px-4 py-2 italic font-condensed fw-black shadow-2xl mb-4">FEATURED REPORT</span>
                        <h1 class="display-4 display-md-2 font-condensed fw-black text-white italic text-uppercase lh-1 mb-4"><?php echo $hero['title']; ?></h1>
                        <p class="lead text-white text-opacity-70 fw-bold text-uppercase fs-4 mb-0 d-none d-md-block"><?php echo $hero['excerpt']; ?></p>
                    </div>
                </a>
            </div>
            <div class="col-lg-4 bg-[#0a0e17]">
                <div class="p-4 p-md-5 h-100">
                    <h3 class="h6 font-condensed tracking-widest text-electric-red mb-5 d-flex align-items-center fw-black uppercase">
                        <span class="bg-electric-red me-3" style="width: 6px; height: 24px;"></span>
                        TOP STORIES
                    </h3>
                    <div class="d-flex flex-column gap-4">
                        <?php for($i=1; $i<min(5, count($featuredNews)); $i++): $fn = $featuredNews[$i]; ?>
                            <a href="/post/<?php echo $fn['slug']; ?>" class="text-decoration-none d-flex gap-4 border-bottom border-white border-opacity-5 pb-4 transition-all hover:translate-x-1">
                                <div class="flex-shrink-0 overflow-hidden border border-white border-opacity-10 rounded-2" style="width: 80px; height: 80px;">
                                    <img src="<?php echo $fn['image']; ?>" class="w-100 h-100 object-fit-cover">
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <span class="text-electric-red fw-black italic font-condensed mb-2 d-block" style="font-size: 10px;"><?php echo strtoupper($fn['category']); ?></span>
                                    <h4 class="text-white font-condensed fw-black italic text-uppercase fs-6 line-clamp-2"><?php echo $fn['title']; ?></h4>
                                </div>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- TOP NEWS SECTION -->
        <section class="py-10 bg-black">
            <div class="container-fluid px-4 px-md-6">
                <div class="d-flex align-items-center mb-8">
                    <div class="bg-electric-red me-3" style="width: 6px; height: 40px;"></div>
                    <h2 class="h2 font-condensed fw-black italic text-white mb-0 uppercase">TOP NEWS</h2>
                </div>
                <div class="row g-4">
                    <?php foreach ($topNews as $post): ?>
                        <div class="col-md-4">
                            <a href="/post/<?php echo $post['slug']; ?>" class="card h-100 bg-transparent border-0 group text-decoration-none">
                                <div class="ratio ratio-16x9 mb-4 overflow-hidden rounded-4 border border-white border-opacity-10 bg-dark shadow-lg">
                                    <img src="<?php echo $post['image']; ?>" class="object-fit-cover transition-all duration-700 group-hover:scale-110">
                                </div>
                                <span class="text-electric-red font-condensed italic fw-black small uppercase mb-2"><?php echo $post['category']; ?></span>
                                <h3 class="h5 text-white fw-black text-uppercase italic tracking-tight lh-1-2"><?php echo $post['title']; ?></h3>
                                <p class="text-white-50 text-xs line-clamp-2 opacity-60"><?php echo $post['excerpt']; ?></p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- WATCH LIVE SECTION -->
        <?php if (!empty($watchLive)): ?>
        <section class="py-10 bg-[#0a0e17] border-y border-white/5">
            <div class="container-fluid px-4 px-md-6">
                <div class="d-flex align-items-center mb-8">
                    <div class="bg-electric-red me-3" style="width: 6px; height: 40px;"></div>
                    <h2 class="h2 font-condensed fw-black italic text-white mb-0 uppercase">WATCH LIVE <span class="text-danger ms-2">●</span></h2>
                </div>
                <div class="row g-4">
                    <?php foreach ($watchLive as $post): ?>
                        <div class="col-md-3">
                            <a href="/post/<?php echo $post['slug']; ?>" class="card h-100 bg-transparent border-0 group text-decoration-none">
                                <div class="ratio ratio-16x9 mb-3 overflow-hidden rounded-3 border border-white/10 position-relative">
                                    <img src="<?php echo $post['image']; ?>" class="object-fit-cover">
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <div class="bg-electric-red text-white rounded-full w-12 h-12 flex items-center justify-center shadow-2xl">
                                            <i class="bi bi-play-fill fs-2"></i>
                                        </div>
                                    </div>
                                </div>
                                <h4 class="text-white font-condensed fw-black italic uppercase fs-6 leading-tight group-hover:text-electric-red transition-all"><?php echo $post['title']; ?></h4>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- CATEGORY BREAKDOWN -->
        <section class="py-10 bg-black">
            <div class="container-fluid px-4 px-md-6">
                <?php foreach ($categories as $cat):
                    $stmt_cat_posts = $conn->prepare("SELECT * FROM posts WHERE LOWER(category) = LOWER(?) AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP) ORDER BY publish_date DESC LIMIT 2");
                    $stmt_cat_posts->execute([$cat['name']]);
                    $catPosts = $stmt_cat_posts->fetchAll();
                    if (empty($catPosts)) continue;
                ?>
                <div class="mb-10">
                    <div class="d-flex justify-content-between align-items-center mb-5 border-bottom border-white/5 pb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-electric-red me-2" style="width: 4px; height: 20px;"></div>
                            <h4 class="font-condensed fw-black italic text-white mb-0 uppercase tracking-widest"><?php echo $cat['name']; ?></h4>
                        </div>
                        <a href="/category/<?php echo urlencode($cat['name']); ?>" class="text-electric-red font-condensed fw-black italic uppercase small text-decoration-none">View All →</a>
                    </div>
                    <div class="row g-4">
                        <?php foreach ($catPosts as $cp): ?>
                            <div class="col-md-6">
                                <a href="/post/<?php echo $cp['slug']; ?>" class="text-decoration-none d-flex gap-4 group">
                                    <div class="flex-shrink-0 overflow-hidden rounded-3 border border-white/10" style="width: 150px; height: 100px;">
                                        <img src="<?php echo $cp['image']; ?>" class="w-100 h-100 object-fit-cover transition-all duration-700 group-hover:scale-110">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h4 class="text-white font-condensed fw-black italic text-uppercase fs-5 mb-2 group-hover:text-electric-red transition-all"><?php echo $cp['title']; ?></h4>
                                        <p class="text-white-50 text-xs line-clamp-2 opacity-60 mb-0"><?php echo $cp['excerpt']; ?></p>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

    <?php else: ?>
        <!-- CATEGORY PAGE CONTENT -->
        <section class="p-4 p-md-10 bg-black min-h-screen">
            <div class="d-flex align-items-center mb-10">
                <div class="bg-electric-red me-3" style="width: 6px; height: 40px;"></div>
                <h1 class="display-5 font-condensed fw-black italic text-white mb-0 uppercase"><?php echo $category; ?></h1>
            </div>

            <?php if (!empty($posts)): ?>
                <div class="row g-5">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-4 col-lg-3">
                            <a href="/post/<?php echo $post['slug']; ?>" class="card h-100 bg-transparent border-0 group text-decoration-none">
                                <div class="ratio ratio-1x1 mb-4 overflow-hidden rounded-4 border border-white border-opacity-10 bg-dark shadow-lg">
                                    <img src="<?php echo $post['image']; ?>" class="object-fit-cover transition-all duration-700 group-hover:scale-110">
                                </div>
                                <h3 class="h5 text-white fw-black text-uppercase italic tracking-tight lh-1-2 mb-2 group-hover:text-electric-red transition-all"><?php echo $post['title']; ?></h3>
                                <p class="text-white-50 text-xs line-clamp-3 opacity-60"><?php echo $post['excerpt']; ?></p>
                                <span class="text-white-50 font-monospace uppercase mt-auto" style="font-size: 9px;"><?php echo date('M d, Y', strtotime($post['publish_date'])); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div class="mt-16 flex flex-wrap justify-center gap-2">
                    <?php $base_url = "/category/" . urlencode($category) . "?"; ?>
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>" class="px-3 py-2 md:px-5 md:py-3 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-electric-red transition-all font-condensed italic fw-black text-xs md:text-base">PREV</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>" class="px-3 py-2 md:px-5 md:py-3 <?php echo $page == $i ? 'bg-electric-red border-electric-red' : 'bg-white/5 border-white/10'; ?> border rounded-lg text-white hover:bg-electric-red transition-all font-condensed italic fw-black text-xs md:text-base"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>" class="px-3 py-2 md:px-5 md:py-3 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-electric-red transition-all font-condensed italic fw-black text-xs md:text-base">NEXT</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="py-20 text-center">
                    <p class="font-condensed italic uppercase tracking-widest text-white-50 fs-4">NO REPORTS DISCOVERED IN THIS TAXONOMY</p>
                    <a href="/" class="btn btn-outline-danger mt-5 px-10 py-3 font-black italic">RETURN TO BASE</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<style>
    .hero-height { min-height: 400px; }
    @media (min-width: 992px) {
        .hero-height { height: 70vh; }
    }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
