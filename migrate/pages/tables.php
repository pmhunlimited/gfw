<?php
include __DIR__ . '/../includes/header.php';
$standingsData = get_ai_insight("Provide detailed league standings for Premier League and La Liga. Use Markdown tables.");
?>
<div class="container py-5">
    <h1 class="font-condensed fw-black italic text-white display-3 mb-5">LEAGUE <span class="text-danger">STANDINGS</span></h1>
    <div class="bg-[#0a0e17] p-5 rounded-4 border border-white border-opacity-5 shadow-2xl min-vh-60">
        <div class="intelligence-content text-white opacity-90 fs-6">
            <?php echo parse_markdown($standingsData); ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
