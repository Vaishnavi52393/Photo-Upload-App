<?php
// index.php - reads every *.json metadata file from hostPath storage
// and renders the matching photo + its metadata

$photoDir    = '/data/photos';
$metadataDir = '/data/metadata';

$entries = [];
if (is_dir($metadataDir)) {
    foreach (glob("$metadataDir/*.json") as $jsonFile) {
        $data = json_decode(file_get_contents($jsonFile), true);
        if ($data) {
            $entries[] = $data;
        }
    }
}

// newest first
usort($entries, fn($a, $b) => strcmp($b['uploaded_at'], $a['uploaded_at']));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Photo Gallery (hostPath demo)</title>
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 40px auto; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 10px; }
        .card img { width: 100%; height: 160px; object-fit: cover; border-radius: 4px; }
        .meta { font-size: 12px; color: #555; margin-top: 6px; }
        .banner { background: #fffbe6; border: 1px solid #f0e0a0; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
        a.button { display: inline-block; background: #2563eb; color: white; padding: 8px 14px;
                   border-radius: 6px; text-decoration: none; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Photo Gallery</h1>
    <div class="banner">
        Served by pod: <strong><?= htmlspecialchars(gethostname()) ?></strong>
        on node: <strong><?= htmlspecialchars(getenv('NODE_NAME') ?: 'unknown') ?></strong>
        &mdash; try deleting this pod and see what happens after it reschedules!
    </div>
    <a class="button" href="upload.php">Upload a new photo</a>

    <?php if (empty($entries)): ?>
        <p>No photos yet. Be the first to upload one!</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($entries as $e): ?>
                <div class="card">
                    <img src="photo.php?file=<?= urlencode($e['stored_name']) ?>" alt="">
                    <div class="meta">
                        <strong><?= htmlspecialchars($e['original_name']) ?></strong><br>
                        by <?= htmlspecialchars($e['uploader']) ?><br>
                        <?= number_format($e['size_bytes'] / 1024, 1) ?> KB<br>
                        <?= htmlspecialchars($e['uploaded_at']) ?><br>
                        node: <?= htmlspecialchars($e['served_by_node']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>
