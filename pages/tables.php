<?php
include __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <h1 class="font-condensed fw-black italic text-white display-3 mb-5 border-bottom border-white border-opacity-10 pb-3">LEAGUE <span class="text-danger">STANDINGS</span></h1>

    <div class="row g-5">
        <!-- Premier League -->
        <div class="col-lg-6">
            <h2 class="h4 font-condensed fw-black text-white italic mb-4 uppercase">Premier League</h2>
            <div id="widget-6tvwmll51mwx" class="scoreaxis-widget" style="width: auto;height: auto;font-size: 14px;background-color: #f5f5f5;color: #0c0c0d;border: 1px solid;border-color: #ecf1f7;overflow: auto;">
                <script src="https://widgets.scoreaxis.com/api/football/league-table/6232265abf1fa71a672159ec?widgetId=6tvwmll51mwx&lang=en&teamLogo=1&tableLines=1&homeAway=1&header=1&position=1&goals=1&gamesCount=1&diff=1&winCount=1&drawCount=1&loseCount=1&lastGames=1&points=1&teamsLimit=all&links=1&font=heebo&fontSize=14&rowDensity=100&widgetWidth=auto&widgetHeight=auto&bodyColor=%23f5f5f5&textColor=%230c0c0d&linkColor=%23151313&borderColor=%23ecf1f7&tabColor=%2322dda5" async></script>
                <div class="widget-main-link" style="padding: 6px 12px;font-weight: 500;">Live data by <a href="https://www.scoreaxis.com/" style="color: inherit;">Scoreaxis</a></div>
            </div>
        </div>

        <!-- Champions League -->
        <div class="col-lg-6">
            <h2 class="h4 font-condensed fw-black text-white italic mb-4 uppercase">Champions League</h2>
            <div id="widget-0hh9mll529yu" class="scoreaxis-widget" style="width: auto;height: auto;font-size: 14px;background-color: #f5f5f5;color: #0c0c0d;border: 1px solid;border-color: #ecf1f7;overflow: auto;">
                <script src="https://widgets.scoreaxis.com/api/football/league-table/6232267fbf1fa71a67215dfc?widgetId=0hh9mll529yu&lang=en&teamLogo=1&tableLines=1&homeAway=1&header=1&position=1&goals=1&gamesCount=1&diff=1&winCount=1&drawCount=1&loseCount=1&lastGames=1&points=1&teamsLimit=all&links=1&font=heebo&fontSize=14&rowDensity=100&widgetWidth=auto&widgetHeight=auto&bodyColor=%23f5f5f5&textColor=%230c0c0d&linkColor=%23151313&borderColor=%23ecf1f7&tabColor=%2322dda5" async></script>
                <div class="widget-main-link" style="padding: 6px 12px;font-weight: 500;">Live data by <a href="https://www.scoreaxis.com/" style="color: inherit;">Scoreaxis</a></div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
