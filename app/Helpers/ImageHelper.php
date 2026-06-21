<?php

namespace App\Helpers;

class ImageHelper
{
    /**
     * Resizes an image if its dimensions exceed the max limit and returns its base64 encoded string.
     *
     * @param string $filePath
     * @param string $mimeType
     * @param int $maxDimension
     * @return array ['mime_type' => string, 'data' => string]
     */
    public static function resizeAndEncode(string $filePath, string $mimeType, int $maxDimension = 4000): array
    {
        // Safely fallback to original if GD is missing or file doesn't exist
        if (!function_exists('imagecreatefromjpeg') || !file_exists($filePath)) {
            return [
                'mime_type' => $mimeType,
                'data' => base64_encode(file_get_contents($filePath))
            ];
        }

        $info = @getimagesize($filePath);
        if (!$info) {
            return [
                'mime_type' => $mimeType,
                'data' => base64_encode(file_get_contents($filePath))
            ];
        }

        list($width, $height) = $info;

        // If dimensions are within the limit, return original file encoded
        if ($width <= $maxDimension && $height <= $maxDimension) {
            return [
                'mime_type' => $mimeType,
                'data' => base64_encode(file_get_contents($filePath))
            ];
        }

        // Calculate new dimensions
        $ratio = min($maxDimension / $width, $maxDimension / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = @imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($filePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImage = @imagecreatefromwebp($filePath);
                }
                break;
        }

        if (!$sourceImage) {
            return [
                'mime_type' => $mimeType,
                'data' => base64_encode(file_get_contents($filePath))
            ];
        }

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Handle transparency for PNG and WEBP
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Encode to string
        ob_start();
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($newImage, null, 85);
                break;
            case 'image/png':
                imagepng($newImage, null, 6);
                break;
            case 'image/webp':
                imagewebp($newImage, null, 85);
                break;
            default:
                imagejpeg($newImage, null, 85);
                $mimeType = 'image/jpeg';
                break;
        }
        $imageData = ob_get_clean();

        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        return [
            'mime_type' => $mimeType,
            'data' => base64_encode($imageData)
        ];
    }
}
