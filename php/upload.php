<?php
date_default_timezone_set('America/Mexico_City');
require 'process.php';
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed", 1);
    }

    $width = filter_input(INPUT_POST, 'width', FILTER_VALIDATE_INT);
    $height = filter_input(INPUT_POST, 'height', FILTER_VALIDATE_INT);
    $fileBase64 = $_POST['file'] ?? null;
    $fileName = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

    if (!$width || !$height || !$fileBase64 || !$fileName) {
        throw new Exception("Missing parameters", 1);
    }

    if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $fileBase64, $type)) {
        throw new Exception("Invalid image type", 1);
    }

    $fileData = base64_decode(preg_replace('/^data:image\/(png|jpeg|jpg|webp);base64,/', '', $fileBase64));
    if ($fileData === false) {
        throw new Exception("Base64 decode failed", 1);
    }

    $maxFileSize = 25 * 1024 * 1024; // 25MB
    if (strlen($fileData) > $maxFileSize) {
        throw new Exception("File size exceeds the maximum limit of 25MB", 1);
    }

    $aspectRatio = $width / $height;
    if ($aspectRatio <= 0) {
        throw new Exception("Invalid aspect ratio", 1);
    }

    $response = processImage($fileData, $fileName, $aspectRatio);
    if (!$response["status"]) {
        throw new Exception($response["message"], 1);
    }

    echo json_encode($response);
} catch (Exception $exception) {
    error_log($exception);
    echo json_encode(["status" => false, "message" => $exception->getMessage()]);
}
