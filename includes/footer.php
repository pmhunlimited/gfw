    </main>
    <footer class="bg-[#05070a] border-top border-white border-opacity-5 pt-5 pb-4 mt-5">
        <div class="container-fluid px-5">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h3 class="font-condensed fw-black italic text-white mb-4 fs-4"><?php echo $settings['name'] ?? 'GFW'; ?></h3>
                    <p class="text-white-50 small leading-relaxed max-w-sm mb-4"><?php echo $settings['tagline'] ?? 'The world\'s most advanced football intelligence network. Real-time decryption of global sports data and tactical analysis.'; ?></p>

                    <div class="mt-5">
                        <h4 class="font-condensed fw-black text-electric-red mb-3 small tracking-widest uppercase">STAY UPDATED</h4>
                        <form action="/subscribe" method="POST" class="d-flex gap-2">
                            <input type="email" name="email" placeholder="ENTER EMAIL..." required class="bg-black border border-white/10 rounded-lg px-4 py-2 text-white font-condensed italic small flex-grow-1 focus:border-danger outline-none transition-all">
                            <button type="submit" class="btn btn-danger font-condensed fw-black italic px-4 py-2 uppercase tracking-widest">SUBSCRIBE</button>
                        </form>
                    </div>
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
                                    $footer_pages = $conn->query("SELECT title, slug, is_external, external_url FROM pages WHERE is_visible = 1 AND position = 'footer'")->fetchAll();
                                    foreach ($footer_pages as $fp) {
                                        $url = ($fp['is_external']) ? $fp['external_url'] : '/'.$fp['slug'];
                                        $target = ($fp['is_external']) ? 'target="_blank"' : '';
                                        echo '<li class="mb-2"><a href="'.$url.'" '.$target.' class="text-decoration-none text-reset hover:text-white transition-all">'.$fp['title'].'</a></li>';
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

    <!-- Floating Social Share Icons -->
    <div class="fixed right-0 top-1/2 -translate-y-1/2 z-[999] d-none d-md-flex flex-column gap-2 bg-black/40 backdrop-blur-md p-2 rounded-l-2xl border border-white/10 border-r-0">
        <?php
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $encoded_url = urlencode($current_url);
            $share_title = urlencode($settings['name'] ?? 'GFW');
        ?>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encoded_url; ?>" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-white hover:bg-blue-600 transition-all shadow-lg" title="Share on Facebook">
            <i class="bi bi-facebook"></i>
        </a>
        <a href="https://twitter.com/intent/tweet?url=<?php echo $encoded_url; ?>&text=<?php echo $share_title; ?>" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-white hover:bg-black transition-all shadow-lg" title="Share on X">
            <i class="bi bi-twitter-x"></i>
        </a>
        <a href="https://api.whatsapp.com/send?text=<?php echo $share_title . '%20' . $encoded_url; ?>" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-white hover:bg-green-600 transition-all shadow-lg" title="Share on WhatsApp">
            <i class="bi bi-whatsapp"></i>
        </a>
        <a href="https://t.me/share/url?url=<?php echo $encoded_url; ?>&text=<?php echo $share_title; ?>" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-white hover:bg-sky-500 transition-all shadow-lg" title="Share on Telegram">
            <i class="bi bi-telegram"></i>
        </a>
        <a href="mailto:?subject=<?php echo $share_title; ?>&body=Check this out: <?php echo $encoded_url; ?>" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-white hover:bg-electric-red transition-all shadow-lg" title="Share via Email">
            <i class="bi bi-envelope-fill"></i>
        </a>
    </div>

    <!-- Mobile Bottom Share Bar (Visible only on mobile) -->
    <div class="fixed bottom-0 left-0 w-full z-[998] d-flex d-md-none bg-black/80 backdrop-blur-xl border-top border-white/10 p-3 justify-content-around align-items-center">
        <span class="small font-condensed fw-black italic text-white-50 uppercase me-2">SHARE:</span>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encoded_url; ?>" target="_blank" class="text-white-50 hover:text-white fs-5"><i class="bi bi-facebook"></i></a>
        <a href="https://twitter.com/intent/tweet?url=<?php echo $encoded_url; ?>&text=<?php echo $share_title; ?>" target="_blank" class="text-white-50 hover:text-white fs-5"><i class="bi bi-twitter-x"></i></a>
        <a href="https://api.whatsapp.com/send?text=<?php echo $share_title . '%20' . $encoded_url; ?>" target="_blank" class="text-white-50 hover:text-white fs-5"><i class="bi bi-whatsapp"></i></a>
        <a href="https://t.me/share/url?url=<?php echo $encoded_url; ?>&text=<?php echo $share_title; ?>" target="_blank" class="text-white-50 hover:text-white fs-5"><i class="bi bi-telegram"></i></a>
        <a href="mailto:?subject=<?php echo $share_title; ?>&body=Check this out: <?php echo $encoded_url; ?>" class="text-white-50 hover:text-white fs-5"><i class="bi bi-envelope-fill"></i></a>
    </div>

    <!-- Cookie Consent Banner -->
    <div id="cookie-consent" class="fixed-bottom p-4 bg-black border-top border-electric-red z-[9999] transition-all duration-500 transform translate-y-full mb-16 mb-md-0">
        <div class="container-fluid px-4">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
                <div class="text-white-50 small font-condensed tracking-wide">
                    <span class="text-white fw-black italic me-2 uppercase">Intelligence Protocol:</span>
                    We use cookies and advanced tracking technologies to optimize your intelligence feed and provide <span class="text-electric-red fw-bold">personalized betting odds</span> based on your regional sports data. By continuing, you authorize this data transmission.
                </div>
                <div class="d-flex gap-3">
                    <button id="accept-cookies" class="btn btn-primary font-condensed fw-black italic px-5 py-2 uppercase tracking-widest">Authorize</button>
                    <button id="decline-cookies" class="btn btn-outline-light font-condensed fw-black italic px-5 py-2 uppercase tracking-widest opacity-50">Decline</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (!localStorage.getItem('cookieConsent')) {
                const banner = document.getElementById('cookie-consent');
                setTimeout(() => {
                    banner.classList.remove('translate-y-full');
                }, 1000);

                document.getElementById('accept-cookies').onclick = function() {
                    localStorage.setItem('cookieConsent', 'accepted');
                    banner.classList.add('translate-y-full');
                };

                document.getElementById('decline-cookies').onclick = function() {
                    localStorage.setItem('cookieConsent', 'declined');
                    banner.classList.add('translate-y-full');
                };
            }
        });
    </script>
    <?php if (!empty($settings['footer_code'])): ?>
        <?php echo $settings['footer_code']; ?>
    <?php endif; ?>
</body>
</html>
