<?php
include __DIR__ . '/../includes/header.php';

$category = $_GET['category'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

$conn = get_db_connection();
$posts = [];
$totalPosts = 0;

if ($conn) {
    if ($category) {
        $stmt_count = $conn->prepare("SELECT COUNT(*) FROM posts WHERE category = ? AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP)");
        $stmt_count->execute([$category]);
        $totalPosts = $stmt_count->fetchColumn();

        $stmt = $conn->prepare("SELECT * FROM posts WHERE category = ? AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP) ORDER BY publish_date DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $category, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt_count = $conn->query("SELECT COUNT(*) FROM posts WHERE (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP)");
        $totalPosts = $stmt_count->fetchColumn();

        $stmt = $conn->prepare("SELECT * FROM posts WHERE (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP) ORDER BY publish_date DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    $posts = $stmt->fetchAll();
} else {
    // Mock posts for verification
    $posts = [
        [
            'id' => 1,
            'title' => 'MOCK REPORT: MANCHESTER CITY SECURES VICTORY',
            'slug' => 'mock-report-1',
            'excerpt' => 'Manchester City continued their dominant run with a convincing win over their rivals.',
            'content' => 'Full report content here...',
            'category' => 'PREMIER LEAGUE',
            'author' => 'GFW',
            'image' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&q=80&w=1600',
            'created_at' => date('Y-m-d H:i:s'),
            'publish_date' => date('Y-m-d H:i:s'),
            'is_top_story' => 1
        ]
    ];
    $totalPosts = 1;
}

$totalPages = ceil($totalPosts / $limit);

// Featured posts for SYNDICATED NEXT (Only on home page or top of category)
if ($conn) {
    $stmt_featured = $conn->query("SELECT * FROM posts WHERE is_top_story = 1 AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP) ORDER BY publish_date DESC LIMIT 4");
    $syndicatedNext = $stmt_featured->fetchAll();
} else {
    $syndicatedNext = $posts;
}

if (count($syndicatedNext) < 4 && $conn) {
    // Fill with latest if not enough featured
    $latest_ids = array_map(function($p) { return $p['id']; }, $syndicatedNext);
    $placeholders = count($latest_ids) ? implode(',', array_fill(0, count($latest_ids), '?')) : '0';
    $stmt_fill = $conn->prepare("SELECT * FROM posts WHERE id NOT IN ($placeholders) AND (is_scheduled = 0 OR publish_date <= CURRENT_TIMESTAMP) ORDER BY publish_date DESC LIMIT " . (4 - count($syndicatedNext)));
    $stmt_fill->execute($latest_ids);
    $syndicatedNext = array_merge($syndicatedNext, $stmt_fill->fetchAll());
}

$latestPost = ($page == 1) ? ($posts[0] ?? null) : null;
// ELITE REPORTING - posts for current page
$remainingPosts = ($page == 1) ? array_slice($posts, 1) : $posts;

$activeComp = $_GET['comp'] ?? 'English Premier League';

$leagues = [
    ['id' => '7', 'name' => 'Premier League'],
    ['id' => '572', 'name' => 'Champions League'],
    ['id' => '11', 'name' => 'La Liga'],
    ['id' => '17', 'name' => 'Serie A'],
    ['id' => '9', 'name' => 'Bundesliga'],
    ['id' => '8', 'name' => 'Ligue 1']
];

if ($category) {
    $custom_meta_title = "Category: $category | GFW";
}
?>
<div class="container-fluid pt-0 px-0 bg-black overflow-x-hidden">
    <!-- MAGAZINE HERO (Only on Page 1) -->
    <?php if ($page == 1): ?>
    <section class="row g-0 mb-5 border-bottom border-white border-opacity-10 bg-[#05070a]">
        <div class="col-lg-8 border-end border-white border-opacity-10 position-relative hero-height">
            <?php if ($latestPost): ?>
                <a href="/post/<?php echo $latestPost['slug']; ?>" class="text-decoration-none d-block h-100 position-relative overflow-hidden group">
                    <img src="<?php echo $latestPost['image']; ?>" loading="lazy" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover opacity-80 transition-all duration-1000" style="transition: transform 1s;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';" alt="">
                    <div class="position-absolute bottom-0 start-0 w-100 p-4 p-md-5 bg-gradient-to-t from-black via-black/70 to-transparent z-20">
                        <div class="mb-4">
                            <span class="badge bg-electric-red rounded-0 px-4 py-2 italic font-condensed fw-black shadow-2xl">GLOBAL EXCLUSIVE</span>
                        </div>
                        <h1 class="display-2 font-condensed fw-black text-white italic text-uppercase lh-1 mb-4"><?php echo $latestPost['title']; ?></h1>
                        <p class="lead text-white text-opacity-70 fw-bold text-uppercase fs-4 mb-0 d-none d-md-block"><?php echo $latestPost['excerpt']; ?></p>
                        <div class="mt-5">
                            <span class="btn btn-outline-light rounded-0 px-5 py-3 font-condensed fw-black italic tracking-widest">DECRYPT FULL REPORT →</span>
                        </div>
                    </div>
                </a>
            <?php else: ?>
                <div class="h-100 d-flex align-items-center justify-content-center bg-black/40">
                    <p class="font-condensed italic uppercase tracking-widest">NO REPORTS AVAILABLE IN THIS CATEGORY</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4 bg-[#0a0e17]">
            <div class="p-4 p-md-5 h-100 d-flex flex-column">
                <h3 class="h6 font-condensed tracking-widest text-electric-red mb-5 d-flex align-items-center fw-black uppercase">
                    <span class="bg-electric-red me-3" style="width: 6px; height: 24px;"></span>
                    LATEST UPDATE
                </h3>
                <div class="d-flex flex-column flex-grow-1 gap-4">
                    <?php foreach ($syndicatedNext as $post): ?>
                        <a href="/post/<?php echo $post['slug']; ?>" class="text-decoration-none d-flex gap-4 border-bottom border-white border-opacity-5 pb-4 transition-all hover:translate-x-1">
                            <div class="flex-shrink-0 overflow-hidden border border-white border-opacity-10 rounded-2" style="width: 80px; height: 80px;">
                                <img src="<?php echo $post['image']; ?>" loading="lazy" class="w-100 h-100 object-fit-cover transition-all duration-700" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';" alt="">
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-electric-red fw-black italic font-condensed mb-2 d-block" style="font-size: 10px;"><?php echo strtoupper($post['category']); ?></span>
                                <h4 class="text-white font-condensed fw-black italic text-uppercase fs-5 line-clamp-2"><?php echo $post['title']; ?></h4>
                                <p class="text-white-50 text-[10px] font-monospace mt-2 uppercase opacity-40"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <!-- GRID -->
    <?php if (count($remainingPosts) > 0): ?>
    <section class="p-4 p-md-5 bg-black">
        <h2 class="display-5 font-condensed fw-black italic text-white mb-5 border-bottom border-white border-opacity-5 pb-3">
            <?php echo $category ? strtoupper($category) : 'ELITE REPORTING'; ?>
        </h2>
        <div class="row g-4">
            <?php foreach ($remainingPosts as $post): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="/post/<?php echo $post['slug']; ?>" class="card h-100 bg-transparent border-0 group text-decoration-none">
                        <div class="ratio ratio-1x1 mb-4 overflow-hidden rounded-4 border border-white border-opacity-10 bg-dark shadow-lg">
                            <img src="<?php echo $post['image']; ?>" loading="lazy" class="object-fit-cover transition-all duration-700" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';" alt="">
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-electric-red font-condensed italic fw-black px-3 py-2 uppercase shadow-lg" style="font-size: 9px;"><?php echo $post['category']; ?></span>
                            </div>
                        </div>
                        <h3 class="h5 text-white fw-black text-uppercase italic tracking-tight lh-1-2"><?php echo $post['title']; ?></h3>
                        <p class="text-white-50 text-xs line-clamp-2 opacity-60"><?php echo $post['excerpt']; ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-12 flex justify-center gap-2">
            <?php
            $base_url = $category ? "/category/" . urlencode($category) . "?" : "/?";
            ?>
            <?php if ($page > 1): ?>
                <a href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-electric-red transition-all font-condensed italic fw-black">PREV</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>" class="px-4 py-2 <?php echo $page == $i ? 'bg-electric-red border-electric-red' : 'bg-white/5 border-white/10'; ?> border rounded-lg text-white hover:bg-electric-red transition-all font-condensed italic fw-black"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>" class="px-4 py-2 bg-white/5 border border-white/10 rounded-lg text-white hover:bg-electric-red transition-all font-condensed italic fw-black">NEXT</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>
<style>
    .hero-height { min-height: 400px; }
    @media (min-width: 992px) {
        .hero-height { min-height: 600px; }
    }
</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
