<?php

/**
 * @copyright Copyright (c) Kyle Andrews <codingwithkyle@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace codewithkyle\Jitter;

use Imagick;

class Jitter
{
    public static function Transform(string $tempImage, array $transform, string $resizeOn = null): void
    {
        self::transformImage($tempImage, $transform, $resizeOn);
        self::convertImage($tempImage, $transform);
    }

    private static function transformImage(string $tempImage, array $transform, string $resizeOn = null): void
    {
        $img = new Imagick($tempImage);
        $img->setImageCompression(Imagick::COMPRESSION_NO);
        $img->setImageCompressionQuality(100);
        $img->setOption("png:compression-level", 9);

        switch ($transform["mode"]) {
            case "fit":
                $img->resizeImage($transform["width"], $transform["height"], Imagick::FILTER_LANCZOS, 0.75);
                $img->writeImage($tempImage);
                break;
            case "letterbox":
                $img->setImageBackgroundColor("#" . $transform["background"]);
                $img->thumbnailImage($transform["width"], $transform["height"], true, true);
                $img->writeImage($tempImage);
                break;
            case "crop":
                $leftPos = floor($img->getImageWidth() * $transform["focusPoint"][0]) - floor($transform["width"] / 2);
                $topPos = floor($img->getImageHeight() * $transform["focusPoint"][1]) - floor($transform["height"] / 2);
                $img->cropImage($transform["width"], $transform["height"], $leftPos, $topPos);
                $img->writeImage($tempImage);
                break;
            default:
                if (is_null($resizeOn)) {
                    if ($transform["width"] < $transform["height"]) {
                        $img->resizeImage($transform["width"], null, Imagick::FILTER_LANCZOS, 0.75);
                    } elseif ($transform["height"] < $transform["width"]) {
                        $img->resizeImage(null, $transform["height"], Imagick::FILTER_LANCZOS, 0.75);
                    } else {
                        $rawWidth = $img->getImageWidth();
                        $rawHeight = $img->getImageHeight();
                        if ($rawWidth < $rawHeight) {
                            $img->resizeImage($transform["width"], null, Imagick::FILTER_LANCZOS, 0.75);
                        } elseif ($rawHeight < $rawWidth) {
                            $img->resizeImage(null, $transform["height"], Imagick::FILTER_LANCZOS, 0.75);
                        } else {
                            $img->resizeImage($transform["width"], $transform["height"], Imagick::FILTER_LANCZOS, 0.75);
                        }
                    }
                } else {
                    if ($resizeOn === "height" || $resizeOn === "h") {
                        $img->resizeImage(null, $transform["height"], Imagick::FILTER_LANCZOS, 0.75);
                    } else {
                        $img->resizeImage($transform["width"], null, Imagick::FILTER_LANCZOS, 0.75);
                    }
                }

                $leftPos = floor($img->getImageWidth() * $transform["focusPoint"][0]) - floor($transform["width"] / 2);
                $topPos = floor($img->getImageHeight() * $transform["focusPoint"][1]) - floor($transform["height"] / 2);
                $img->cropImage($transform["width"], $transform["height"], $leftPos, $topPos);
                $img->writeImage($tempImage);
                break;
        }
    }

    private static function convertImageFormat(string $tempImage, array $transform): void
    {
        $img = new Imagick($tempImage);
        switch ($transform["format"]) {
            case "jpeg":
                $img->setImageFormat("jpeg");
                $img->setImageCompressionQuality($transform["quality"]);
                $img->writeImage($tempImage);
                break;
            case "gif":
                $img->setImageFormat("gif");
                $img->setImageCompressionQuality($transform["quality"]);
                $img->writeImage($tempImage);
                break;
            case "png":
                $img->setImageFormat("png");
                $img->setImageCompressionQuality($transform["quality"]);
                $img->writeImage($tempImage);
                break;
            default:
                if (\count(\Imagick::queryFormats("WEBP")) > 0 || file_exists("/usr/bin/cwebp")) {
                    if (\count(\Imagick::queryFormats("WEBP")) > 0) {
                        $img->setImageFormat("webp");
                        $img->setImageCompressionQuality($transform["quality"]);
                        $img->writeImage($tempImage);
                    } elseif (file_exists("/usr/bin/cwebp")) {
                        $command = escapeshellcmd("/usr/bin/cwebp -q " . $transform["quality"] . " " . $tempImage . " -o " . $tempImage);
                        shell_exec($command);
                    }
                }
                break;
        }
    }
}