<?php
// upload.php - handles photo upload and writes a metadata JSON file to hostPath storage

$photoDir    = '/data/photos';
$metadataDir = '/data/metadata';

// Make sure the directories exist (hostPath dir is created by kubelet, but
// subfolders inside it are our app's responsibility)
foreach ([$photoDir, $metadataDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload error code: " . $file['error'];
    } else {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = mime_content_type($file['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $message = "Rejected: unsupported file type ($mime).";
        } else {
            $id = bin2hex(random_bytes(8));
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $storedName = "$id.$ext";

            if (move_uploaded_file($file['tmp_name'], "$photoDir/$storedName")) {
                $metadata = [
                    'id'            => $id,
                    'original_name' => $file['name'],
                    'stored_name'   => $storedName,
                    'size_bytes'    => $file['size'],
                    'mime_type'     => $mime,
                    'uploader'      => trim($_POST['uploader'] ?? 'anonymous'),
                    'uploaded_at'   => date('c'),
                    // Handy for the class discussion on hostPath + node affinity:
                    'served_by_pod'  => gethostname(),
                    'served_by_node' => getenv('NODE_NAME') ?: 'unknown',
                ];

                file_put_contents(
                    "$metadataDir/$id.json",
                    json_encode($metadata, JSON_PRETTY_PRINT)
                );

                $message = "Uploaded successfully. <a href='index.php'>View gallery</a>";
            } else {
                $message = "Failed to move uploaded file into place.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Photo</title>
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 40px auto; }
        .msg { padding: 10px; background: #eef; border-radius: 6px; margin-bottom: 15px; }
        input, button { padding: 8px; margin: 5px 0; width: 100%; box-sizing: border-box; }
    </style>
</head>
<body>
    <h1>Upload a Photo</h1>
    <?php if ($message): ?>
        <div class="msg"><?= $message /* already safe, built server-side */ ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <label>Your name:</label>
        <input type="text" name="uploader" placeholder="e.g. student1">
        <label>Photo:</label>
        <input type="file" name="photo" accept="image/*" required>
        <button type="submit">Upload</button>
    </form>
    <p><a href="index.php">&larr; Back to gallery</a></p>
</body>
</html>
