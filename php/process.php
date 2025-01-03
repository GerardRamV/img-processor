<?php
require 'helpers.php';

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('CROP_DIR', __DIR__ . '/../processed/');
define('UPLOAD_PUBLIC_DIR', 'uploads/');
define('CROP_PUBLIC_DIR', 'processed/');

function processImage($fileDecoded, $fileName, $aspectRatio) {
    try {
        $fileName = time() . '_' . $fileName;
        $filePath = UPLOAD_DIR . $fileName;

        if (!file_put_contents($filePath, $fileDecoded)) throw new Exception("Error saving file");

        $resultCrop = cropImage($filePath, $fileName, $aspectRatio, CROP_DIR);
        if (!$resultCrop["status"]) throw new Exception("Error: Can't crop image", 1);

        $resultAnalysis = analyzeImage(CROP_DIR . $fileName);
        if (!$resultAnalysis["status"]) throw new Exception("Error: Can't analyze image", 1);

        $resultConvert = convertImage(CROP_DIR . $fileName, CROP_DIR);
        if (!$resultConvert["status"]) throw new Exception("Error: Can't convert image", 1);

        $resultUpload = uploadObject('example-bucket', $resultConvert["fileName"], CROP_DIR . $resultConvert["fileName"], true);
        $urlCropped = $resultUpload["status"] ? $resultUpload["url"] : CROP_PUBLIC_DIR . $resultConvert["fileName"];

        if (!unlink($filePath)) error_log("Could not delete the original file: " . $filePath);

        return [
            "status" => true,
            // "original" => UPLOAD_PUBLIC_DIR . $fileName,
            "cropped" => $urlCropped,
            "analysis" => [
                "labels" => $resultAnalysis["labels"],
                "safeSearch" => $resultAnalysis["safeSearch"],
                "textDetection" => $resultAnalysis["textDetections"]
            ]
        ];
    } catch (Exception $e) {
        error_log($e);
        return ["status" => false, "message" => $e->getMessage()];
    }
}
