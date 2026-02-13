<?php
include __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <h1 class="font-condensed fw-black italic text-white display-3 mb-5">LEAGUE <span class="text-danger">STANDINGS</span></h1>
    <div class="bg-[#0a0e17] p-4 p-md-5 rounded-4 border border-white border-opacity-5 shadow-2xl min-vh-60">
        <!-- ScoreAxis League Table Widget -->
        <div id="scoreaxis-widget-league-table" style="width:100%; border: none;">
            <script src="https://widgets.scoreaxis.com/api/football/league-table/8?lang=en&font=heebo&fontSize=14&rowDensity=100&widgetWidth=100%&widgetHeight=auto&bodyColor=%230a0e17&textColor=%23ffffff&linkColor=%23ff3e3e&borderColor=%231e293b&tabColor=%231e293b" async></script>
            <div style="font-size: 12px; color: #64748b; text-align: center; margin-top: 10px;">League data by <a href="https://www.scoreaxis.com/" target="_blank" style="color: #ff3e3e; text-decoration: none;">ScoreAxis</a></div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
