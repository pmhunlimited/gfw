<?php
admin_header("Taxonomy");

if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $success = "Taxonomy decommissioned.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed");
    }
    $name = sanitize($_POST['name']);
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$name, (int)$_POST['id']]);
        $success = "Taxonomy updated.";
    } else {
        $stmt = $conn->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        $success = "New taxonomy registered.";
    }
}

$categories = get_categories_with_counts();
?>

<div class="d-flex justify-content-between align-items-center mb-5">
    <h1 class="font-condensed fw-black italic text-white display-5 mb-0">TAXONOMY <span class="text-danger">REGISTRY</span></h1>
    <button class="btn btn-outline-danger font-condensed fw-black italic px-4 py-2" data-bs-toggle="modal" data-bs-target="#categoryModal">NEW CATEGORY</button>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success bg-green-900 bg-opacity-10 border-green-500 border-opacity-20 text-green-500 font-condensed italic uppercase mb-5"><?php echo $success; ?></div>
<?php endif; ?>

<div class="bg-[#0a0e17] rounded-3xl border border-white/5 overflow-hidden shadow-2xl">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead class="bg-black">
                <tr>
                    <th class="px-5 py-4 text-[10px] font-black uppercase text-secondary tracking-widest border-0">ID</th>
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-secondary tracking-widest border-0">Taxonomy Name</th>
                    <th class="px-4 py-4 text-[10px] font-black uppercase text-secondary tracking-widest border-0 text-center">Posts</th>
                    <th class="px-5 py-4 text-[10px] font-black uppercase text-secondary tracking-widest border-0 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td class="px-5 py-4 border-white border-opacity-5">
                        <span class="text-white-50 font-monospace small">#<?php echo ($cat['id'] ?? '?'); ?></span>
                    </td>
                    <td class="px-4 py-4 border-white border-opacity-5">
                        <div class="text-white font-bold small uppercase italic"><?php echo $cat['name']; ?></div>
                    </td>
                    <td class="px-4 py-4 border-white border-opacity-5 text-center">
                        <span class="badge bg-danger bg-opacity-10 text-danger font-condensed px-3 py-1"><?php echo $cat['post_count']; ?></span>
                    </td>
                    <td class="px-5 py-4 border-white border-opacity-5 text-end">
                        <button class="btn btn-link text-white-50 hover:text-white p-0 me-3 edit-cat" data-id="<?php echo $cat['id']; ?>" data-name="<?php echo htmlspecialchars($cat['name']); ?>" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="bi bi-pencil-square fs-5"></i></button>
                        <a href="?delete=<?php echo $cat['id']; ?>" class="text-danger hover:text-white transition-all" onclick="return confirm('Decommission this taxonomy permanently?')"><i class="bi bi-trash fs-5"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary rounded-4">
            <div class="modal-header border-white border-opacity-10">
                <h5 class="modal-title font-condensed fw-black italic text-white uppercase" id="catModalLabel">Register Taxonomy</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="id" id="cat_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-white-50 small uppercase font-black">Category Name</label>
                        <input type="text" name="name" id="cat_name" class="form-control bg-black border-white border-opacity-10 text-white rounded-xl" placeholder="e.g. Premier League" required>
                    </div>
                </div>
                <div class="modal-footer border-white border-opacity-10">
                    <button type="submit" name="save_category" class="btn btn-danger w-100 py-3 rounded-xl font-condensed italic fw-black">COMMIT REGISTRY</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-cat').forEach(button => {
    button.addEventListener('click', function() {
        document.getElementById('cat_id').value = this.dataset.id;
        document.getElementById('cat_name').value = this.dataset.name;
        document.getElementById('catModalLabel').innerText = 'Update Taxonomy';
    });
});
document.getElementById('categoryModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_name').value = '';
    document.getElementById('catModalLabel').innerText = 'Register Taxonomy';
});
</script>

<?php admin_footer(); ?>
