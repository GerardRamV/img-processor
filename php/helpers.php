<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Google\ApiCore\ApiException;
use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageContext;
use Google\Cloud\Vision\V1\CropHintsParams;
use Google\Cloud\Translate\V2\TranslateClient;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Storage\StorageClient;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Path to google key
putenv("GOOGLE_APPLICATION_CREDENTIALS=" . $_ENV['GOOGLE_APPLICATION_CREDENTIALS']);

function cropImage($file, $name, $aspectRatio, $uploadDir) {
    $response = [
        "status" => false,
        "message" => "",
        "fileName" => ""
    ];

    $imageAnnotator = new ImageAnnotatorClient();

    try {
        if (empty($file) || empty($name) || empty($aspectRatio)) throw new Exception("The 'file', 'name' and 'aspectRatio' parameters are required.");

        if (!file_exists($file)) throw new Exception("File does not exist - " . $file, 1);
        if (!is_readable($file)) throw new Exception("Cannot read file - " . $file);

        $image = file_get_contents($file);
        if ($image === false) throw new Exception("Cannot get content of image - " . $file, 1);

        $imageContext = new ImageContext();
        $paramsCrop = new CropHintsParams();

        // Iniciar los parámetros
        $paramsCrop->setAspectRatios([(float)$aspectRatio]);
        $imageContext->setCropHintsParams($paramsCrop);
        $params = ["imageContext" => $imageContext];

        // Llamada a la API de Google Vision para obtener las sugerencias de recorte
        $resCrop = $imageAnnotator->cropHintsDetection($image, $params);
        $cropHints = $resCrop->getCropHintsAnnotation();
        if ($cropHints == null) throw new Exception("Cannot get crop hints", 1);
        if (empty($cropHints->getCropHints())) throw new Exception("No cropping suggestions found on the image.");
        if (!$cropHints->getCropHints()->offsetExists(0)) throw new Exception("No crop hints on result", 1);

        // Obtener los parámetros de recorte
        $rect = $cropHints->getCropHints()->offsetGet(0)->getBoundingPoly()->getVertices();
        $coordX = $rect->offsetGet(0)->getX();
        $coordY = $rect->offsetGet(0)->getY();
        $width = $rect->offsetGet(2)->getX() - $coordX;
        $height = $rect->offsetGet(2)->getY() - $coordY;

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        // Crear la imagen desde el archivo
        $img = null;
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $img = imagecreatefromjpeg($file);
                break;
            case 'png':
                $img = imagecreatefrompng($file);
                break;
            case 'webp':
                $img = imagecreatefromwebp($file);
                break;
            default:
                throw new Exception("Image format not supported: " . $extension);
        }
        if (!$img) throw new Exception("Cannot not create image from file");

        // Recortar la imagen
        $area = ["x" => $coordX, "y" => $coordY, "width" => $width, "height" => $height];
        $crop = imagecrop($img, $area);
        if (!$crop) throw new Exception("Cannot crop image", 1);

        // Guardar la imagen recortada
        $resultCrop = null;
        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $resultCrop = imagejpeg($crop, $uploadDir . $name, 100);
                break;
            case 'png':
                $resultCrop = imagepng($crop, $uploadDir . $name, 9);
                break;
            case 'webp':
                $resultCrop = imagewebp($crop, $uploadDir . $name, 100);
                break;
        }

        // Liberar la memoria
        imagedestroy($img);
        imagedestroy($crop);

        // Validar el resultado de guardar la imagen
        if (!$resultCrop) throw new Exception("Cannot create image crop", 1);

        // Preparar la respuesta
        $response["status"] = true;
        $response["message"] = "Image crop success";
        $response["fileName"] = $name;
    } catch (ApiException $e) {
        error_log("API Error: " . $e->getMessage());
        $response["message"] = "API Error: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $response["message"] = "Error: " . $e->getMessage();
    } finally {
        $imageAnnotator->close();
        return $response;
    }
}

function analyzeImage($file) {
    $response = [
        "status" => false,
        "labels" => [],
        "safeSearch" => [],
        "textDetections" => [],
        "message" => ""
    ];

    $imageAnnotator = new ImageAnnotatorClient();
    $translate = new TranslateClient();

    try {
        // Validación de entrada
        if (!file_exists($file)) throw new Exception("File does not exist - " . $file, 1);
        if (!is_readable($file)) throw new Exception("Cannot read file - " . $file);

        // Obtener el contenido de la imagen
        $image = file_get_contents($file);
        if (!$image) throw new Exception("Cannot get content image", 1);

        // Análisis de la imagen
        $resultAnnotator = $imageAnnotator->annotateImage($image, [
            Type::LABEL_DETECTION,
            Type::SAFE_SEARCH_DETECTION,
            Type::TEXT_DETECTION
        ]);

        // Obtener los resultados de la API
        $labelsResponse = $resultAnnotator->getLabelAnnotations();
        $safeSearch = $resultAnnotator->getSafeSearchAnnotation();
        $textDetection = $resultAnnotator->getTextAnnotations();

        // Obtener las etiquetas de la imagen
        $labels = [];
        foreach ($labelsResponse as $labelData) {
            $resTranslate = $translate->translate($labelData->getDescription(), ['target' => 'es']);
            $labels[] = array(
                "description" => $labelData->getDescription(),
                "translate" => $resTranslate["text"],
                "score" => $labelData->getScore()
            );
        }

        // Obtener los resultados de SafeSearch
        $safeSearch = [
            "adult" => $safeSearch->getAdult(),
            "medical" => $safeSearch->getMedical(),
            "spoof" => $safeSearch->getSpoof(),
            "violence" => $safeSearch->getViolence(),
            "racy" => $safeSearch->getRacy()
        ];

        // Obtener el texto detectado en la imagen
        $textDetections = [];
        foreach ($textDetection as $text) {
            // $resTranslate = $translate->translate($text->getDescription(), ['target' => 'es']);
            $textDetections[] = array("description" => $text->getDescription());
        }

        // Preparar la respuesta
        $response["status"] = true;
        $response["labels"] = $labels;
        $response["safeSearch"] = $safeSearch;
        $response["textDetections"] = $textDetections;
    } catch (ApiException $e) {
        error_log("Google API Error: " . $e->getMessage() . " File: " . $file);
        $response["message"] = "API Error: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("General Error: " . $e->getMessage() . " File: " . $file);
        $response["message"] = "Error: " . $e->getMessage();
    } finally {
        if (isset($imageAnnotator)) $imageAnnotator->close();
        return $response;
    }
}

function convertImage($file, $path) {
    try {
        // Validación de entradas
        if (!file_exists($file)) throw new Exception("File does not exist - " . $file, 1);

        // Configuración de la clave de Tinify
        if (empty($_ENV['TINIFY_KEY'])) throw new Exception("Tinify API key is not set.", 1);

        \Tinify\setKey($_ENV['TINIFY_KEY']);
        $source = \Tinify\fromFile($file);
        $converted = $source->convert(array("type" => "image/webp"));

        $extResult = $converted->result()->extension();
        if ($extResult !== 'webp') throw new Exception("The image was not converted correctly to webp.", 1);

        // Generar nombre de archivo para la imagen convertida
        $fileName = pathinfo($file, PATHINFO_FILENAME);
        $nameResult = $path . $fileName . '.' . $extResult;

        // Guardar el archivo convertido;
        if (!$converted->toFile($nameResult)) throw new Exception("Error saving converted image", 1);

        // Eliminar el archivo original si no es el mismo que el convertido
        if ($nameResult !== $file) {
            if (!unlink($file)) {
                error_log("Could not delete the original file: " . $file);
            }
        }

        // Preparar la respuesta
        return [
            "status" => true,
            "fileName" => $fileName . '.' . $extResult
        ];
    } catch (\Tinify\Exception $e) {
        error_log("Tinify error: " . $e->getMessage());
        return [
            "status" => false,
            "message" => "Tinify Error : " . $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        return [
            "status" => false,
            "message" => "Error: " . $e->getMessage()
        ];
    }
}

function uploadObject($bucketName, $objectName, $source, $makePublic = false) {
    $response = [
        "false" => false,
        "message" => "",
        "url" => ""
    ];
    try {
        if (empty($bucketName) || empty($objectName) || empty($source)) throw new Exception("The 'bucketName', 'objectName' and 'source' parameters are required.");

        if (!file_exists($source)) throw new Exception("File does not exist - " . $source, 1);
        if (!is_readable($source)) throw new Exception("Cannot read file - " . $source);
        $file = fopen($source, 'r');
        if (!$file) throw new Exception("Cannot open file" . $source, 1);

        $storage = new StorageClient();
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->upload($file, [
            'name' => $objectName,
            'public' => true,
            'resumable' => false,
            'validation' => false,
            'metadata' => ['Cache-control' => 'public, max-age=31536000']
        ]);

        fclose($file);
        if ($object == null)  throw new Exception("Error uploading file", 1);
        if ($makePublic) $object->update(['acl' => []], ['predefinedAcl' => 'PUBLICREAD']);

        if (!unlink($source)) error_log("Could not delete the original file: " . $source);

        $response["url"] = "https://storage.googleapis.com/$bucketName/$objectName";
        $response["status"] = true;
    } catch (ApiException $e) {
        error_log("API Error: " . $e->getMessage());
        $response["message"] = "Error: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $response["message"] = "Error: " . $e->getMessage();
    } finally {
        return $response;
    }
}

function listBuckets() {
    try {
        $storage = new StorageClient();
        $buckets = $storage->buckets();
        foreach ($buckets as $bucket) {
            echo $bucket->name() . PHP_EOL;
        }
    } catch (GoogleException $e) {
        error_log("Google Error: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
    }
}

function listObjects($bucketName) {
    try {
        $storage = new StorageClient();
        $bucket = $storage->bucket($bucketName);
        foreach ($bucket->objects() as $object) {
            printf('Object: %s' . PHP_EOL, $object->name());
        }
    } catch (GoogleException $e) {
        error_log("Google Error: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
    }
}

function deleteObject($bucketName, $objectName) {
    try {
        $storage = new StorageClient();
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->object($objectName);
        $object->delete();
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
    }
}

function downloadObject($bucketName, $objectName) {
    try {
        $storage = new StorageClient();
        $bucket = $storage->bucket($bucketName);
        $object = $bucket->object($objectName);
        $object->downloadToFile(__DIR__ . '/' . $objectName);
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
    }
}
