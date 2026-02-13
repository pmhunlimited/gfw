<?php
include __DIR__ . '/../includes/header.php';

$category = $_GET['category'] ?? null;
$conn = get_db_connection();
$posts = [];
if ($conn) {
    if ($category) {
        $stmt = $conn->prepare("SELECT * FROM posts WHERE category = ? ORDER BY created_at DESC");
        $stmt->execute([$category]);
    } else {
        $stmt = $conn->query("SELECT * FROM posts ORDER BY created_at DESC");
    }
    $posts = $stmt->fetchAll();
}

// Featured posts for SYNDICATED NEXT
$stmt_featured = $conn->query("SELECT * FROM posts WHERE is_top_story = 1 ORDER BY created_at DESC LIMIT 4");
$syndicatedNext = $stmt_featured->fetchAll();
if (count($syndicatedNext) < 4) {
    // Fill with latest if not enough featured
    $latest_ids = array_map(function($p) { return $p['id']; }, $syndicatedNext);
    $placeholders = count($latest_ids) ? implode(',', array_fill(0, count($latest_ids), '?')) : '0';
    $stmt_fill = $conn->prepare("SELECT * FROM posts WHERE id NOT IN ($placeholders) ORDER BY created_at DESC LIMIT " . (4 - count($syndicatedNext)));
    $stmt_fill->execute($latest_ids);
    $syndicatedNext = array_merge($syndicatedNext, $stmt_fill->fetchAll());
}

$latestPost = $posts[0] ?? null;
// ELITE REPORTING - 10 latest posts
$remainingPosts = array_slice($posts, 1, 10);

// Sports data from AI
$activeComp = $_GET['comp'] ?? 'English Premier League';
$activeType = $_GET['type'] ?? 'LIVESCORE';
$today = date('D d M Y');
$sportsData = get_ai_insight("Provide a detailed $activeType report for $activeComp for today, $today. Use Markdown tables for data. Ensure information is current.");

?>
<div class="container-fluid pt-0 px-0 bg-black overflow-x-hidden">
    <!-- LIVE SCORES WIRE -->
    <section class="bg-[#05070a] border-bottom border-white border-opacity-10 py-3">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="font-condensed fw-black text-white italic small mb-0"><span class="text-danger">●</span> LIVE SCORES</h3>
                <span class="text-white-50 small font-monospace uppercase" style="font-size: 10px;"><?php echo date('D d M Y'); ?></span>
            </div>
            <div id="widget-home-live-scores" class="scoreaxis-widget" style="width: 100%;border: none;overflow: auto;">
                <script src="https://widgets.scoreaxis.com/api/football/live-scores?widgetId=widget-home-live-scores&lang=en&font=heebo&fontSize=12&rowDensity=100&widgetWidth=100%&widgetHeight=auto&bodyColor=%2305070a&textColor=%23ffffff&linkColor=%23ff3e3e&borderColor=%231e293b&tabColor=%231e293b" async></script>
            </div>
        </div>
    </section>

    <!-- HERO -->
    <section class="row g-0 mb-5 border-bottom border-white border-opacity-10 bg-[#05070a]">
        <div class="col-lg-8 border-end border-white border-opacity-10 position-relative hero-height">
            <?php if ($latestPost): ?>
                <a href="/post/<?php echo $latestPost['slug']; ?>" class="text-decoration-none d-block h-100 position-relative overflow-hidden group">
                    <img src="<?php echo $latestPost['image']; ?>" loading="lazy" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover opacity-60 grayscale transition-all duration-1000" style="transition: transform 1s, filter 1s;" onmouseover="this.style.transform='scale(1.05)';this.style.filter='grayscale(0)';" onmouseout="this.style.transform='scale(1)';this.style.filter='grayscale(1)';" alt="">
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
                    <p class="font-condensed italic uppercase tracking-widest">NO REPORTS AVAILABLE</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4 bg-[#0a0e17]">
            <div class="p-4 p-md-5 h-100 d-flex flex-column">
                <h3 class="h6 font-condensed tracking-widest text-electric-red mb-5 d-flex align-items-center fw-black uppercase">
                    <span class="bg-electric-red me-3" style="width: 6px; height: 24px;"></span>
                    SYNDICATED NEXT
                </h3>
                <div class="d-flex flex-column flex-grow-1 gap-4">
                    <?php foreach ($syndicatedNext as $post): ?>
                        <a href="/post/<?php echo $post['slug']; ?>" class="text-decoration-none d-flex gap-4 border-bottom border-white border-opacity-5 pb-4 transition-all hover:translate-x-1">
                            <div class="flex-shrink-0 overflow-hidden border border-white border-opacity-10 rounded-2" style="width: 80px; height: 80px;">
                                <img src="<?php echo $post['image']; ?>" loading="lazy" class="w-100 h-100 object-fit-cover grayscale transition-all duration-700" onmouseover="this.style.filter='grayscale(0)';this.style.transform='scale(1.1)';" onmouseout="this.style.filter='grayscale(1)';this.style.transform='scale(1)';" alt="">
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

    <!-- AI INTELLIGENCE HUB -->
    <section class="mb-5 bg-[#0a0e17] p-4 p-md-5 rounded-4 border border-white border-opacity-5 mx-2 shadow-2xl">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-4">
            <div class="d-flex align-items-center">
                <div class="bg-electric-red me-3" style="width: 6px; height: 40px;"></div>
                <h2 class="h2 font-condensed fw-black italic text-white mb-0 uppercase">AI INTELLIGENCE WIRE</h2>
            </div>
            <div class="d-flex gap-2 overflow-x-auto no-scrollbar pb-1">
                <?php
                $comps = ['UEFA Champions League', 'English Premier League', 'Spanish La Liga', 'Italian Serie A'];
                foreach ($comps as $c):
                ?>
                    <a href="?comp=<?php echo urlencode($c); ?>&type=<?php echo $activeType; ?>" class="btn btn-sm px-4 py-2 rounded-0 font-condensed fw-black transition-all text-nowrap italic <?php echo $activeComp == $c ? 'btn-danger shadow-[0_0_15px_rgba(255,62,62,0.3)]' : 'btn-outline-secondary opacity-50'; ?>">
                        <?php echo strtoupper($c); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4 col-xl-3">
                <div class="d-flex d-md-block overflow-x-auto no-scrollbar gap-2 mb-4">
                    <?php
                    $types = [
                        ['id' => 'LIVESCORE', 'label' => 'LIVE UPDATES', 'icon' => 'bi-broadcast'],
                        ['id' => 'RESULTS', 'label' => 'FULL TIME', 'icon' => 'bi-check-circle'],
                        ['id' => 'STATS', 'label' => 'PERFORMANCE', 'icon' => 'bi-graph-up'],
                        ['id' => 'ODDS', 'label' => 'MARKET PRICES', 'icon' => 'bi-coin']
                    ];
                    foreach ($types as $t):
                    ?>
                        <a href="?comp=<?php echo urlencode($activeComp); ?>&type=<?php echo $t['id']; ?>" class="nav-link text-start rounded-0 py-3 px-4 font-condensed tracking-widest fw-black d-flex align-items-center justify-content-between transition-all mb-3 w-100 <?php echo $activeType == $t['id'] ? 'active bg-electric-red text-white' : 'text-secondary bg-white bg-opacity-5'; ?>">
                            <span class="h6 mb-0 italic"><?php echo $t['label']; ?></span>
                            <i class="bi <?php echo $t['icon']; ?> fs-5 opacity-50"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-8 col-xl-9">
                <div class="bg-black bg-opacity-60 p-4 p-md-5 rounded-4 border border-white border-opacity-5 min-vh-60 shadow-inner">
                    <div class="intelligence-content text-white opacity-90 fs-6">
                        <?php echo parse_markdown($sportsData); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- GRID -->
    <section class="p-4 p-md-5 bg-black">
        <h2 class="display-5 font-condensed fw-black italic text-white mb-5 border-bottom border-white border-opacity-5 pb-3">ELITE REPORTING</h2>
        <div class="row g-4">
            <?php foreach ($remainingPosts as $post): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="/post/<?php echo $post['slug']; ?>" class="card h-100 bg-transparent border-0 group text-decoration-none">
                        <div class="ratio ratio-1x1 mb-4 overflow-hidden rounded-4 border border-white border-opacity-10 bg-dark shadow-lg">
                            <img src="<?php echo $post['image']; ?>" loading="lazy" class="object-fit-cover grayscale transition-all duration-700" onmouseover="this.style.filter='grayscale(0)';this.style.transform='scale(1.1)';" onmouseout="this.style.filter='grayscale(1)';this.style.transform='scale(1)';" alt="">
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
    </section>
</div>
<style>
    .hero-height { min-height: 400px; }
    @media (min-width: 992px) {
        .hero-height { min-height: 600px; }
    }
</style>
<?php include __DIR__ . '/../includes/footer.php'; ?>
