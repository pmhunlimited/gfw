    </main>
    <footer class="bg-[#05070a] border-top border-white border-opacity-5 pt-5 pb-4 mt-5">
        <div class="container-fluid px-5">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h3 class="font-condensed fw-black italic text-white mb-4 fs-4"><?php echo $settings['name'] ?? 'GFW'; ?></h3>
                    <p class="text-white-50 small leading-relaxed max-w-sm">The world's most advanced football intelligence network. Real-time decryption of global sports data and tactical analysis.</p>
                </div>
                <div class="col-lg-8">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <h4 class="font-condensed fw-black text-electric-red mb-3 small tracking-widest">CHANNELS</h4>
                            <ul class="list-unstyled small font-bold text-white-50 uppercase">
                                <li class="mb-2"><a href="/" class="text-decoration-none text-reset hover:text-white transition-all">Latest Reports</a></li>
                                <li class="mb-2"><a href="/watch" class="text-decoration-none text-reset hover:text-white transition-all">Live Feed</a></li>
                                <li class="mb-2"><a href="/tables" class="text-decoration-none text-reset hover:text-white transition-all">Standings</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h4 class="font-condensed fw-black text-electric-red mb-3 small tracking-widest">RESOURCES</h4>
                            <ul class="list-unstyled small font-bold text-white-50 uppercase">
                                <?php
                                $conn = get_db_connection();
                                if ($conn) {
                                    $footer_pages = $conn->query("SELECT title, slug FROM pages WHERE is_visible = 1 AND position = 'footer'")->fetchAll();
                                    foreach ($footer_pages as $fp) {
                                        echo '<li class="mb-2"><a href="/'.$fp['slug'].'" class="text-decoration-none text-reset hover:text-white transition-all">'.$fp['title'].'</a></li>';
                                    }
                                }
                                ?>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h4 class="font-condensed fw-black text-electric-red mb-3 small tracking-widest">NETWORK</h4>
                            <div class="d-flex gap-3">
                                <a href="<?php echo $settings['tw_url'] ?? '#'; ?>" class="text-white-50 hover:text-[#ff3e3e] transition-all fs-5"><i class="bi bi-twitter-x"></i></a>
                                <a href="<?php echo $settings['fb_url'] ?? '#'; ?>" class="text-white-50 hover:text-[#ff3e3e] transition-all fs-5"><i class="bi bi-facebook"></i></a>
                                <a href="<?php echo $settings['ig_url'] ?? '#'; ?>" class="text-white-50 hover:text-[#ff3e3e] transition-all fs-5"><i class="bi bi-instagram"></i></a>
                                <a href="<?php echo $settings['yt_url'] ?? '#'; ?>" class="text-white-50 hover:text-[#ff3e3e] transition-all fs-5"><i class="bi bi-youtube"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-top border-white border-opacity-5 mt-5 pt-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <p class="text-white-50 small mb-0 font-monospace uppercase opacity-50">&copy; <?php echo date('Y'); ?> <?php echo $settings['name'] ?? 'GFW'; ?>. SYSTEM SECURE.</p>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
