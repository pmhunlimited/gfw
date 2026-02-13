<?php
include __DIR__ . '/../includes/header.php';

$slug = $_GET['slug'] ?? null;
$conn = get_db_connection();
$post = null;
if ($conn && $slug) {
    $stmt = $conn->prepare("SELECT * FROM posts WHERE slug = ?");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
}

if (!$post) {
    echo '<div class="container py-5 text-center"><h1>Post Not Found</h1><a href="/" class="btn btn-primary mt-4">Back to Broadcast</a></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle Comment Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_text'])) {
    $author = sanitize($_POST['comment_author']);
    $text = sanitize($_POST['comment_text']);
    $stmt = $conn->prepare("INSERT INTO comments (post_id, author, text, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$post['id'], $author, $text]);
    $comment_msg = "Comment submitted for review.";
}

// Get Approved Comments
$stmt = $conn->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
$stmt->execute([$post['id']]);
$comments = $stmt->fetchAll();

?>
<div class="bg-black text-white min-h-screen">
    <!-- Hero Header -->
    <div class="relative h-[60vh] md:h-[80vh] overflow-hidden">
        <img src="<?php echo $post['image']; ?>" class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
        <div class="absolute bottom-0 left-0 w-full p-6 md:p-12">
            <div class="container mx-auto">
                <span class="bg-electric-red text-white px-4 py-2 font-condensed fw-black italic uppercase text-xs mb-4 inline-block tracking-widest shadow-2xl">GLOBAL EXCLUSIVE</span>
                <h1 class="display-2 font-condensed fw-black italic text-white uppercase tracking-tighter leading-none mb-6"><?php echo $post['title']; ?></h1>
                <div class="flex items-center gap-6 text-white-50 font-monospace text-xs uppercase tracking-widest">
                    <span>BY <span class="text-white fw-bold"><?php echo $post['author']; ?></span></span>
                    <span class="w-1 h-1 bg-white/20 rounded-full"></span>
                    <span><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-6 py-12">
        <div class="row g-5">
            <div class="col-lg-8">
                <article class="prose prose-invert prose-red max-w-none">
                    <p class="lead text-xl text-white-50 font-medium italic mb-8 border-l-4 border-electric-red pl-6"><?php echo $post['excerpt']; ?></p>
                    <div class="markdown-content text-lg leading-relaxed text-white-90 opacity-90">
                        <?php echo nl2br($post['content']); ?>
                    </div>
                </article>

                <!-- Comments Section -->
                <div class="mt-16 border-t border-white/10 pt-12">
                    <h3 class="font-condensed fw-black italic text-white text-3xl mb-8 uppercase">Intelligence Feedback</h3>

                    <?php if (isset($comment_msg)): ?>
                        <div class="alert alert-success bg-green-900/20 border-green-500/50 text-green-500 rounded-0 font-condensed italic uppercase"><?php echo $comment_msg; ?></div>
                    <?php endif; ?>

                    <form method="POST" class="mb-12 bg-[#0a0e17] p-8 border border-white/5 rounded-2xl shadow-2xl">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Author ID</label>
                                <input type="text" name="comment_author" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-bold" required>
                            </div>
                            <div class="col-12">
                                <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Message Payload</label>
                                <textarea name="comment_text" rows="4" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white" required></textarea>
                            </div>
                        </div>
                        <button type="submit" class="mt-6 bg-electric-red text-white px-10 py-3 rounded-xl font-black uppercase italic tracking-widest hover:bg-white hover:text-electric-red transition-all">Transmit Message</button>
                    </form>

                    <div class="space-y-6">
                        <?php foreach ($comments as $comment): ?>
                            <div class="bg-white/5 p-6 border-l-2 border-electric-red">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-condensed fw-black italic text-white uppercase"><?php echo $comment['author']; ?></span>
                                    <span class="text-[9px] text-white-50 font-monospace uppercase"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></span>
                                </div>
                                <p class="text-white-50 mb-0"><?php echo nl2br($comment['text']); ?></p>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($comments)): ?>
                            <p class="text-white-50 italic opacity-40 font-condensed uppercase tracking-widest text-center py-10">Waiting for intelligence input...</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <div class="bg-[#0a0e17] p-8 border border-white/5 rounded-3xl mb-8">
                        <h4 class="font-condensed fw-black italic text-electric-red text-xl mb-4 uppercase">Newsletter Syndication</h4>
                        <p class="text-white-50 small mb-6">Receive real-time intelligence directly to your secure inbox.</p>
                        <form action="/subscribe" method="POST">
                            <input type="email" name="email" placeholder="SECURE EMAIL ADDRESS" class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white font-monospace text-xs mb-4" required>
                            <button type="submit" class="w-full bg-white text-black px-6 py-3 rounded-xl font-black uppercase italic tracking-widest hover:bg-electric-red hover:text-white transition-all">Secure Subscription</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
