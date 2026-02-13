<?php
admin_header("Post");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $success = "Report decommissioned.";
}

// Handle Manual Post Submission
if (isset($_POST['save_manual'])) {
    $title = sanitize($_POST['title']);
    $cat = sanitize($_POST['cat']);
    $content = $_POST['content'];

    // Auto-generate excerpt: first 150 chars
    $excerpt = sanitize(substr(strip_tags($content), 0, 150)) . '...';

    $image = sanitize($_POST['image']);
    $author = sanitize($_POST['author'] ?? 'STAFF');

    $is_scheduled = !empty($_POST['publish_date']) ? 1 : 0;
    $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : date('Y-m-d H:i:s');

    // Handle Image Upload
    if (!empty($_FILES['image_file']['name'])) {
        $target_dir = __DIR__ . "/../assets/uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_ext = strtolower(pathinfo($_FILES["image_file"]["name"], PATHINFO_EXTENSION));
        $target_file = $target_dir . time() . '.' . $file_ext;
        if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
            $image = "/assets/uploads/" . basename($target_file);
        }
    }

    $slug = strtolower(str_replace(' ', '-', $title)) . '-' . time();
    $stmt = $conn->prepare("INSERT INTO posts (title, slug, excerpt, content, category, author, image, is_scheduled, publish_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$title, $slug, $excerpt, $content, $cat, $author, $image, $is_scheduled, $publish_date])) {
        $post_id = $conn->lastInsertId();
        if (!$is_scheduled || strtotime($publish_date) <= time()) {
            broadcast_to_social($post_id);
            $success = "Intelligence report deployed and broadcasted.";
        } else {
            $success = "Intelligence report scheduled for $publish_date.";
        }
    } else {
        $error = "Failed to deploy report.";
    }
}

// Handle Auto-generation from AI
if (isset($_POST['generate_ai'])) {
    $topic = sanitize($_POST['topic']);
    $cat = sanitize($_POST['cat']);
    $is_scheduled = !empty($_POST['publish_date']) ? 1 : 0;
    $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : date('Y-m-d H:i:s');

    $prompt = "Generate a professional football news article about '$topic' in the category '$cat'.
               Write in a first-person 'fan blogger' perspective.
               Return JSON with 'title', 'content', 'image_keyword' (a specific search term for a football photo).";
    $raw = get_ai_insight($prompt);

    // Improved JSON extraction
    $data = null;
    if (preg_match('/\{.*\}/s', $raw, $matches)) {
        $data = json_decode($matches[0], true);
    }

    if ($data && !empty($data['title']) && !empty($data['content'])) {
        $title = sanitize($data['title']);
        $content = $data['content'];
        $excerpt = sanitize(substr(strip_tags($content), 0, 150)) . '...';
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-' . time();

        // Handle Image
        $keyword = urlencode(($data['image_keyword'] ?? $topic) . " football");
        $image_url = "https://loremflickr.com/1600/900/" . $keyword;
        $img_data = @file_get_contents($image_url);
        $image_filename = "ai_" . time() . ".jpg";
        $db_image = "/assets/uploads/" . $image_filename;
        if ($img_data) {
            file_put_contents(__DIR__ . "/../assets/uploads/" . $image_filename, $img_data);
        } else {
            $db_image = "https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&q=80&w=1600";
        }

        $stmt = $conn->prepare("INSERT INTO posts (title, slug, excerpt, content, category, author, image, is_scheduled, publish_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $slug, $excerpt, $content, $cat, 'AI ANALYST', $db_image, $is_scheduled, $publish_date])) {
            $post_id = $conn->lastInsertId();
            if (!$is_scheduled || strtotime($publish_date) <= time()) {
                broadcast_to_social($post_id);
                $success = "AI Intelligence generated, deployed and broadcasted.";
            } else {
                $success = "AI Intelligence generated and scheduled for $publish_date.";
            }
        } else {
            $error = "Database insertion failed.";
        }
    } else {
        $error = "AI extraction failed or returned invalid format. Raw: " . htmlspecialchars(substr($raw, 0, 100)) . "...";
    }
}

$posts = $conn->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll();
$categories = $conn->query("SELECT * FROM categories")->fetchAll();

?>
<div class="d-flex justify-content-between align-items-center mb-5">
    <h1 class="font-condensed fw-black italic text-white display-5 mb-0">POST <span class="text-danger">REGISTRY</span></h1>
    <div class="d-flex gap-3">
        <button class="btn btn-outline-secondary font-condensed fw-black italic px-4 py-2" data-bs-toggle="modal" data-bs-target="#manualModal">MANUAL ENTRY</button>
        <button class="btn btn-outline-danger font-condensed fw-black italic px-4 py-2" data-bs-toggle="modal" data-bs-target="#generateModal">GENERATE FROM AI</button>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success bg-green-900 bg-opacity-10 border-green-500 border-opacity-20 text-green-500 font-condensed italic uppercase mb-5"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger bg-red-900 bg-opacity-10 border-red-500 border-opacity-20 text-red-500 font-condensed italic uppercase mb-5"><?php echo $error; ?></div>
<?php endif; ?>

<div class="bg-[#0a0e17] rounded-3xl border border-white/5 overflow-hidden shadow-2xl">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead class="bg-black">
                <tr>
                    <th class="px-5 py-4 text-[10px] font-black uppercase text-secondary tracking-widest border-0">Report</th>
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-secondary tracking-widest border-0">Taxonomy</th>
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-secondary tracking-widest border-0">Operator</th>
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-secondary tracking-widest border-0">Timestamp</th>
                    <th class="px-5 py-4 text-[10px] font-black uppercase text-secondary tracking-widest border-0 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                <tr>
                    <td class="px-5 py-4 border-white border-opacity-5">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo $post['image']; ?>" class="rounded-2 me-3" style="width: 40px; height: 40px; object-fit: cover;">
                            <div>
                                <div class="text-white font-bold small uppercase italic"><?php echo $post['title']; ?></div>
                                <div class="text-[9px] text-white-50 font-monospace opacity-50">/<?php echo $post['slug']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 border-white border-opacity-5">
                        <span class="badge bg-white bg-opacity-5 text-white-50 font-condensed italic px-2 py-1"><?php echo strtoupper($post['category']); ?></span>
                    </td>
                    <td class="px-4 py-4 border-white border-opacity-5">
                        <span class="text-white-50 small font-bold italic"><?php echo $post['author']; ?></span>
                    </td>
                    <td class="px-4 py-4 border-white border-opacity-5">
                        <span class="text-white-50 font-monospace small"><?php echo date('Y-m-d', strtotime($post['publish_date'] ?: $post['created_at'])); ?></span>
                        <?php if ($post['is_scheduled'] && strtotime($post['publish_date']) > time()): ?>
                            <div class="text-danger font-black uppercase italic" style="font-size: 8px;">SCHEDULED</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-4 border-white border-opacity-5 text-end">
                        <a href="?delete=<?php echo $post['id']; ?>" class="text-danger hover:text-white transition-all" onclick="return confirm('Decommission this report permanently?')"><i class="bi bi-trash fs-5"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary rounded-4">
            <div class="modal-header border-white border-opacity-10">
                <h5 class="modal-title font-condensed fw-black italic text-white uppercase">Manual Intelligence Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-8">
                            <label class="form-label text-white-50 small uppercase font-black">Title</label>
                            <input type="text" name="title" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50 small uppercase font-black">Category</label>
                            <select name="cat" class="form-select bg-black border-white border-opacity-10 text-white rounded-xl">
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['name']; ?>"><?php echo $c['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50 small uppercase font-black">Operator/Author</label>
                            <input type="text" name="author" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl" placeholder="STAFF">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small uppercase font-black">Image URL</label>
                            <input type="text" name="image" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl" placeholder="https://unsplash.com/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small uppercase font-black">OR Upload Image</label>
                            <input type="file" name="image_file" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50 small uppercase font-black">Schedule Deployment (Leave blank for immediate broadcast)</label>
                            <input type="datetime-local" name="publish_date" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small uppercase font-black">Content (Markdown supported)</label>
                            <textarea name="content" rows="10" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-white border-opacity-10">
                    <button type="submit" name="save_manual" class="btn btn-danger w-100 py-3 rounded-xl font-condensed italic fw-black">DEPLOY REPORT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- AI Modal -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary rounded-4">
            <div class="modal-header border-white border-opacity-10">
                    <h5 class="modal-title font-condensed fw-black italic text-white uppercase">AI Post Generator</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="aiForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label text-white-50 small uppercase font-black d-flex justify-content-between">
                            Trending Subjects
                            <button type="button" id="refreshTopics" class="btn btn-link p-0 text-danger small text-decoration-none">REFRESH</button>
                        </label>
                        <div id="suggestedTopics" class="d-flex flex-wrap gap-2">
                            <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-white-50 small uppercase font-black">Intelligence Subject</label>
                        <input type="text" name="topic" id="topicInput" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl" placeholder="Select a topic above or type here..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50 small uppercase font-black">Taxonomy Classification</label>
                        <select name="cat" class="form-select bg-black border-white border-opacity-10 text-white rounded-xl">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['name']; ?>"><?php echo $c['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50 small uppercase font-black">Schedule Deployment (Optional)</label>
                        <input type="datetime-local" name="publish_date" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl">
                    </div>
                </div>
                <div class="modal-footer border-white border-opacity-10">
                    <button type="submit" name="generate_ai" class="btn btn-danger w-100 py-3 rounded-xl font-condensed italic fw-black">DECRYPT & DEPLOY</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function loadTopics() {
    const container = document.getElementById('suggestedTopics');
    container.innerHTML = '<div class="spinner-border spinner-border-sm text-danger"></div>';
    try {
        const response = await fetch('/admin/ajax_suggest.php');
        const topics = await response.json();
        container.innerHTML = '';
        topics.forEach(topic => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-secondary text-[10px] uppercase font-bold py-1 px-2 rounded-lg text-start';
            btn.innerText = topic;
            btn.onclick = () => document.getElementById('topicInput').value = topic;
            container.appendChild(btn);
        });
    } catch (e) {
        container.innerHTML = '<span class="text-danger small">Failed to load subjects.</span>';
    }
}

document.getElementById('refreshTopics').onclick = loadTopics;
document.getElementById('generateModal').addEventListener('shown.bs.modal', loadTopics);

document.getElementById('aiForm').onsubmit = function() {
    this.querySelector('button[type="submit"]').disabled = true;
    this.querySelector('button[type="submit"]').innerHTML = '<span class="spinner-grow spinner-grow-sm me-2"></span>DECRYPTING...';
};
</script>

<?php admin_footer(); ?>
