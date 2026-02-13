<?php
include __DIR__ . '/../includes/header.php';
?>
<div class="container py-5 text-center">
    <h1 class="font-condensed fw-black italic text-white display-3 mb-4">LIVE <span class="text-danger">CENTRE</span></h1>
    <div class="bg-[#0a0e17] p-2 p-md-4 rounded-4 border border-white border-opacity-5 shadow-2xl overflow-hidden mx-auto" style="max-width: 1000px;">
        <!-- ScoreAxis Live Scores Widget - Displays today's matches dynamically -->
        <div id="widget-live-centre-scores" class="scoreaxis-widget" style="width: auto;height: auto;font-size: 14px;background-color: #0a0e17;color: #ffffff;border: 1px solid;border-color: #1e293b;overflow: auto;">
            <script src="https://widgets.scoreaxis.com/api/football/live-scores?widgetId=widget-live-centre-scores&lang=en&font=heebo&fontSize=14&rowDensity=100&widgetWidth=auto&widgetHeight=auto&bodyColor=%230a0e17&textColor=%23ffffff&linkColor=%23ff3e3e&borderColor=%231e293b&tabColor=%231e293b" async></script>
            <div class="widget-main-link" style="padding: 6px 12px;font-weight: 500; font-size: 12px; color: #64748b;">Live data by <a href="https://www.scoreaxis.com/" target="_blank" style="color: #ff3e3e; text-decoration: none;">Scoreaxis</a></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
