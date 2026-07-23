<?php
// photo.php - serves an image file from hostPath storage, guarding against path traversal
$photoDir = '/data/photos';

$file = basename($_GET['file'] ?? '');
$path = "$photoDir/$file";

if ($file === '' || !is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

$mime = mime_content_type($path);
header("Content-Type: $mime");
header('Cache-Control: public, max-age=86400');
readfile($path);
