<?php
include __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <h1 class="font-condensed fw-black italic text-white display-3 mb-5 border-bottom border-white border-opacity-10 pb-3">LEAGUE <span class="text-danger">STANDINGS</span></h1>

    <div class="row g-5">
        <!-- England Premier League -->
        <div class="col-lg-6 mb-5">
            <h2 class="h4 font-condensed fw-black text-white italic mb-4 uppercase">Premier League</h2>
            <div class="bg-[#0a0e17] p-2 rounded-4 border border-white border-opacity-5 shadow-2xl overflow-hidden">
                <div data-widget-type="entityStandings" data-entity-type="league" data-entity-id="7" data-lang="en" data-widget-id="42b3999a-d4fb-4336-9b3f-1469423e17f0" data-theme="dark"></div>
            </div>
        </div>

        <!-- UEFA Champions League -->
        <div class="col-lg-6 mb-5">
            <h2 class="h4 font-condensed fw-black text-white italic mb-4 uppercase">Champions League</h2>
            <div class="bg-[#0a0e17] p-2 rounded-4 border border-white border-opacity-5 shadow-2xl overflow-hidden">
                <div data-widget-type="entityStandings" data-entity-type="league" data-entity-id="572" data-lang="en" data-widget-id="7173ab0e-04b2-4e6c-97c9-59828b7946a1" data-theme="dark"></div>
            </div>
        </div>

        <!-- Seria A Italy -->
        <div class="col-lg-6 mb-5">
            <h2 class="h4 font-condensed fw-black text-white italic mb-4 uppercase">Serie A Italy</h2>
            <div class="bg-[#0a0e17] p-2 rounded-4 border border-white border-opacity-5 shadow-2xl overflow-hidden">
                <div data-widget-type="entityStandings" data-entity-type="league" data-entity-id="17" data-lang="en" data-widget-id="5667665a-1214-4178-80ce-be0638948306" data-theme="dark"></div>
            </div>
        </div>

        <!-- La Liga -->
        <div class="col-lg-6 mb-5">
            <h2 class="h4 font-condensed fw-black text-white italic mb-4 uppercase">La Liga</h2>
            <div class="bg-[#0a0e17] p-2 rounded-4 border border-white border-opacity-5 shadow-2xl overflow-hidden">
                <div data-widget-type="entityStandings" data-entity-type="league" data-entity-id="11" data-lang="en" data-widget-id="6cef89c8-7066-4338-8e41-97ab7335f1f8" data-theme="dark"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://widgets.365scores.com/main.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
