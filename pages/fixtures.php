<?php
include __DIR__ . '/../includes/header.php';

$leagues = [
    ['id' => '7', 'name' => 'Premier League'],
    ['id' => '572', 'name' => 'Champions League'],
    ['id' => '11', 'name' => 'La Liga'],
    ['id' => '17', 'name' => 'Serie A'],
    ['id' => '9', 'name' => 'Bundesliga'],
    ['id' => '8', 'name' => 'Ligue 1'],
    ['id' => '25', 'name' => 'Eredivisie'],
    ['id' => '597', 'name' => 'Europa League'],
];
?>

<div class="container-fluid py-5 px-4 bg-black">
    <div class="mb-5 d-flex align-items-center">
        <div class="bg-electric-red me-4" style="width: 8px; height: 50px;"></div>
        <div>
            <h1 class="display-4 font-condensed fw-black italic text-white mb-0 uppercase tracking-tighter">MATCH <span class="text-electric-red">FIXTURES</span></h1>
            <p class="text-white-50 font-monospace small uppercase tracking-widest">Real-time intelligence on global match deployments</p>
        </div>
    </div>

    <div class="row g-5">
        <div class="col-lg-12">
            <!-- 365Scores Widget Hub -->
            <div class="elite-tabs-container">
                <!-- Tabs Header -->
                <div class="elite-tabs-nav no-scrollbar mb-5 d-flex gap-2 overflow-x-auto pb-3 border-bottom border-white border-opacity-5">
                    <?php foreach ($leagues as $index => $league): ?>
                        <button class="elite-tab-btn flex-shrink-0 <?php echo $index === 0 ? 'active' : ''; ?>" data-target="#fixture-league-<?php echo $league['id']; ?>">
                            <?php echo $league['name']; ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Tabs Content -->
                <div class="elite-tabs-content">
                    <?php foreach ($leagues as $index => $league): ?>
                        <div class="elite-tab-pane <?php echo $index === 0 ? 'active' : ''; ?>" id="fixture-league-<?php echo $league['id']; ?>">
                            <div class="bg-[#0a0e17] p-4 p-md-5 rounded-4 border border-white border-opacity-5 shadow-2xl">
                                <div class="mb-4 d-flex justify-content-between align-items-center">
                                    <h3 class="h4 font-condensed fw-black italic text-white mb-0 uppercase"><?php echo $league['name']; ?> SCHEDULE</h3>
                                    <span class="badge bg-danger bg-opacity-10 text-danger font-monospace px-3 py-1">LIVE UPDATES ACTIVE</span>
                                </div>

                                <div data-widget-type="entityScores"
                                     data-entity-type="league"
                                     data-entity-id="<?php echo $league['id']; ?>"
                                     data-lang="en"
                                     data-widget-id="fixture-scores-<?php echo $league['id']; ?>"
                                     data-theme="dark"
                                     data-auto-height="true">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .elite-tabs-nav::-webkit-scrollbar { display: none; }
    .elite-tab-btn {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.05);
        color: #94a3b8;
        padding: 12px 24px;
        border-radius: 12px;
        font-family: 'Barlow Condensed', sans-serif;
        font-weight: 800;
        text-transform: uppercase;
        font-style: italic;
        letter-spacing: 1px;
        white-space: nowrap;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 13px;
    }
    .elite-tab-btn:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
        transform: translateY(-2px);
    }
    .elite-tab-btn.active {
        background: var(--electric-red);
        border-color: var(--electric-red);
        color: #fff;
        box-shadow: 0 10px 30px rgba(255,62,62,0.3);
    }
    .elite-tab-pane {
        display: none;
        animation: eliteFadeSlide 0.5s ease-out;
    }
    .elite-tab-pane.active {
        display: block;
    }
    @keyframes eliteFadeSlide {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script src="https://widgets.365scores.com/main.js"></script>
<script>
    document.querySelectorAll('.elite-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');
            const container = btn.closest('.elite-tabs-container');

            container.querySelectorAll('.elite-tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            container.querySelectorAll('.elite-tab-pane').forEach(p => p.classList.remove('active'));
            container.querySelector(target).classList.add('active');
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
