<?php
include __DIR__ . '/../includes/header.php';
?>
<div class="container py-5 text-center">
    <h1 class="font-condensed fw-black italic text-white display-3 mb-4">LIVE <span class="text-danger">STREAM</span></h1>
    <div class="bg-[#0a0e17] p-5 rounded-4 border border-white border-opacity-5 shadow-2xl">
        <div class="ratio ratio-16x9 bg-black mb-4">
            <div class="d-flex align-items-center justify-content-center flex-column">
                <div class="spinner-grow text-danger mb-4"></div>
                <p class="font-condensed italic uppercase tracking-widest text-white-50">Synchronizing Global Feed...</p>
            </div>
        </div>
        <p class="text-white-50 italic">The live tactical broadcast is currently encrypted. Please check back during major fixtures.</p>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
