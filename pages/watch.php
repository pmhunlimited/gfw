<?php
include __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <h1 class="font-condensed fw-black italic text-white display-3 mb-5 border-bottom border-white border-opacity-10 pb-3">LIVE <span class="text-danger">CENTRE</span></h1>

    <div class="row g-4">
        <?php
        $data = fetch_sportmonks("livescores/inplay", "participants;scores;events.type;league");
        if ($data):
            foreach ($data as $f):
                $home = $f['participants'][0]['name'] ?? 'Home';
                $away = $f['participants'][1]['name'] ?? 'Away';
                $home_logo = $f['participants'][0]['image_path'] ?? '';
                $away_logo = $f['participants'][1]['image_path'] ?? '';
                $score_h = $f['scores'][0]['score']['goals'] ?? 0;
                $score_a = $f['scores'][1]['score']['goals'] ?? 0;
                $status = $f['state']['name'] ?? 'Live';
                $league = $f['league']['name'] ?? 'Football';
        ?>
        <div class="col-lg-6">
            <div class="bg-[#0a0e17] p-4 rounded-4 border border-white border-opacity-5 shadow-2xl">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="badge bg-danger font-condensed italic px-3 py-1"><?php echo strtoupper($status); ?></span>
                    <span class="text-white-50 small uppercase font-black tracking-widest"><?php echo $league; ?></span>
                </div>

                <div class="d-flex justify-content-between align-items-center px-3">
                    <div class="text-center" style="width: 40%;">
                        <img src="<?php echo $home_logo; ?>" class="mb-3" style="width: 60px; height: 60px;">
                        <h4 class="h6 font-condensed text-white uppercase fw-black mb-0"><?php echo $home; ?></h4>
                    </div>

                    <div class="text-center">
                        <div class="display-5 font-condensed fw-black text-white italic"><?php echo $score_h; ?> - <?php echo $score_a; ?></div>
                    </div>

                    <div class="text-center" style="width: 40%;">
                        <img src="<?php echo $away_logo; ?>" class="mb-3" style="width: 60px; height: 60px;">
                        <h4 class="h6 font-condensed text-white uppercase fw-black mb-0"><?php echo $away; ?></h4>
                    </div>
                </div>

                <hr class="border-white border-opacity-5 my-4">

                <div class="events-log small">
                    <?php foreach (array_reverse($f['events'] ?? []) as $e): ?>
                        <div class="d-flex align-items-center gap-3 mb-2 opacity-75">
                            <span class="text-danger fw-bold font-monospace"><?php echo $e['minute']; ?>'</span>
                            <span class="text-white-50 uppercase font-black" style="font-size: 9px;"><?php echo $e['type']['name'] ?? ''; ?></span>
                            <span class="text-white"><?php echo $e['player_name'] ?? ''; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
            endforeach;
        else:
        ?>
            <div class="col-12 text-center py-5">
                <div class="bg-[#0a0e17] p-5 rounded-4 border border-white border-opacity-5">
                    <i class="bi bi-broadcast text-white-50 display-1 mb-4 opacity-20"></i>
                    <h3 class="font-condensed text-white-50 uppercase tracking-widest italic">No matches currently in-play</h3>
                    <p class="text-white-50 small mt-3">The live tactical feed will resume when the next mission begins.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
