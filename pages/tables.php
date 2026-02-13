<?php
include __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <h1 class="font-condensed fw-black italic text-white display-3 mb-5 border-bottom border-white border-opacity-10 pb-3">LEAGUE <span class="text-danger">STANDINGS</span></h1>

    <div class="row g-5">
        <?php
        $leagues = [
            ['id' => 8, 'name' => 'Premier League'],
            ['id' => 564, 'name' => 'La Liga'],
            ['id' => 384, 'name' => 'Serie A'],
            ['id' => 82, 'name' => 'Bundesliga']
        ];

        foreach ($leagues as $l):
            $data = fetch_sportmonks("standings/live/leagues/" . $l['id'], "participant");
        ?>
        <div class="col-lg-6 mb-5">
            <h2 class="h4 font-condensed fw-black text-white italic mb-4 uppercase"><?php echo $l['name']; ?></h2>
            <div class="bg-[#0a0e17] p-0 rounded-4 border border-white border-opacity-5 shadow-2xl overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 small">
                        <thead>
                            <tr class="text-white-50 font-black uppercase tracking-widest" style="font-size: 10px;">
                                <th class="ps-4 py-3">Pos</th>
                                <th class="py-3">Team</th>
                                <th class="py-3 text-center">P</th>
                                <th class="py-3 text-center">W</th>
                                <th class="py-3 text-center">D</th>
                                <th class="py-3 text-center">L</th>
                                <th class="py-3 text-center">GD</th>
                                <th class="pe-4 py-3 text-center text-danger">Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($data && isset($data[0]['standings'])):
                                foreach ($data[0]['standings'] as $s):
                                    $team = $s['participant']['name'] ?? 'Unknown';
                                    $logo = $s['participant']['image_path'] ?? '';
                            ?>
                                <tr>
                                    <td class="ps-4 py-3 align-middle font-monospace"><?php echo $s['position']; ?></td>
                                    <td class="py-3 align-middle">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $logo; ?>" class="me-2" style="width: 20px; height: 20px;">
                                            <span class="fw-bold"><?php echo $team; ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 align-middle text-center"><?php echo $s['overall']['games_played']; ?></td>
                                    <td class="py-3 align-middle text-center text-white-50"><?php echo $s['overall']['won']; ?></td>
                                    <td class="py-3 align-middle text-center text-white-50"><?php echo $s['overall']['draw']; ?></td>
                                    <td class="py-3 align-middle text-center text-white-50"><?php echo $s['overall']['lost']; ?></td>
                                    <td class="py-3 align-middle text-center text-white-50"><?php echo $s['overall']['goals_diff']; ?></td>
                                    <td class="pe-4 py-3 align-middle text-center fw-black text-danger"><?php echo $s['points']; ?></td>
                                </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <tr><td colspan="8" class="py-5 text-center text-white-50 italic">Standings data currently restricted. Verify API permissions.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
