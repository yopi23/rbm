<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Exception;

class ThermalPrinterService
{
    /**
     * Generate ESC/POS thermal printer ready binary file from an image.
     *
     * @param string $sourcePath Path relative to storage/app/public (e.g. logos/abc.png)
     * @return string|null Path to the generated thermal file relative to storage/app/public
     */
    public static function generateThermalLogo($sourcePath)
    {
        try {
            $fullPath = Storage::disk('public')->path($sourcePath);
            
            if (!file_exists($fullPath)) {
                return null;
            }

            // Load Image based on extension
            $info = getimagesize($fullPath);
            $mime = $info['mime'];
            
            $img = null;
            switch ($mime) {
                case 'image/jpeg': 
                case 'image/jpg':
                    $img = imagecreatefromjpeg($fullPath);
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($fullPath);
                    break;
                case 'image/gif':
                    $img = imagecreatefromgif($fullPath);
                    break;
                default:
                    return null;
            }

            if (!$img) return null;

            // Resize Logic
            $maxWidth = 250;
            $maxHeight = 150;
            $width = imagesx($img);
            $height = imagesy($img);

            $scale = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int)($width * $scale);
            $newHeight = (int)($height * $scale);

            // Create new canvas with white background
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $white);

            // Handle Transparency for PNG
            if ($mime == 'image/png') {
                imagealphablending($resized, true);
                imagesavealpha($resized, true);
            }

            // Copy and Resize
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Convert to ESC/POS Raster Bit Image Data
            $escPosData = self::convertToEscPosRaster($resized, $newWidth, $newHeight);

            // Save to file
            $pathInfo = pathinfo($sourcePath);
            $newFilename = $pathInfo['filename'] . '_thermal.bin'; // Use .bin for binary data
            $newPath = $pathInfo['dirname'] . '/' . $newFilename;

            // Fix if dirname is just '.'
            if ($pathInfo['dirname'] === '.') {
                $newPath = $newFilename;
            }

            Storage::disk('public')->put($newPath, $escPosData);

            // Free memory
            imagedestroy($img);
            imagedestroy($resized);

            return $newPath;

        } catch (Exception $e) {
            // Log error
            error_log("Thermal Printer Service Error: " . $e->getMessage());
            return null;
        }
    }

    private static function convertToEscPosRaster($gdImage, $width, $height)
    {
        // Calculate bytes per row (width / 8, rounded up)
        $bytesWidth = (int)(($width + 7) / 8);

        // Header: GS v 0 (ASCII) -> 1D 76 30 00
        // Parameters: xL xH yL yH
        // x = bytesWidth (not pixels)
        // y = height (pixels)
        
        $xL = $bytesWidth & 0xFF;
        $xH = ($bytesWidth >> 8) & 0xFF;
        $yL = $height & 0xFF;
        $yH = ($height >> 8) & 0xFF;

        $header = chr(0x1D) . chr(0x76) . chr(0x30) . chr(0x00) . 
                  chr($xL) . chr($xH) . 
                  chr($yL) . chr($yH);

        $body = '';

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $bytesWidth; $x++) {
                $byte = 0;
                for ($b = 0; $b < 8; $b++) {
                    $pixelX = $x * 8 + $b;
                    
                    if ($pixelX < $width) {
                        $rgbIndex = imagecolorat($gdImage, $pixelX, $y);
                        $colors = imagecolorsforindex($gdImage, $rgbIndex);
                        
                        // Logic: Print Black if Dark.
                        // White paper is the background (0). Black dot is 1.
                        
                        // Check Alpha first (Transparent = White)
                        if ($colors['alpha'] > 100) {
                            $isBlack = false;
                        } else {
                            // Luminance
                            $gray = ($colors['red'] * 0.299) + ($colors['green'] * 0.587) + ($colors['blue'] * 0.114);
                            $isBlack = ($gray < 128); // Threshold 128
                        }

                        if ($isBlack) {
                            $byte |= (1 << (7 - $b));
                        }
                    }
                }
                $body .= chr($byte);
            }
        }

        return $header . $body;
    }
}
