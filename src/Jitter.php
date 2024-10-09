<?php

/**
 * @copyright Copyright (c) Kyle Andrews <codingwithkyle@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace codewithkyle\JitterCore;

use Imagick;

class Jitter
{
    public static function TransformImage(string $tempImage, array $transform, string $resizeOn = null): void
    {
        self::doImageTransform($tempImage, $transform, $resizeOn);
        self::convertImageFormat($tempImage, $transform);
    }

    public static function BuildTransform(array $params): array
    {
        $clientAcceptsWebp = strpos($_SERVER["HTTP_ACCEPT"], "image/webp") !== false;
        $width = null;
        $height = null;
        $aspectRatioValues = [];

        if (isset($params["ar"]))
        {
            $values = explode(":", $params["ar"]);
            if (count($values) == 2)
            {
                $aspectRatioValues = [intval($values[0]), intval($values[1])];
            }
        }

        if (isset($params["w"]) && isset($params["h"]))
        {
            $width = intval($params["w"]) ?? null;
            $height = intval($params["h"]) ?? null;
        }
        elseif (isset($params["w"]))
        {
            $width = intval($params["w"]) ?? null;
        }
        elseif (isset($params["h"]))
        {
            $height = intval($params["h"]) ?? null;
        }

        $quality = 80;
        if (isset($params["q"]))
        {
            $quality = intval($params["q"]) ?? 80;
        }

        $mode = "clip";
        if (isset($params["m"]))
        {
            $mode = $params["m"];
        }

        $bg = "ffffff";
        if (isset($params["bg"]))
        {
            $bg = ltrim($params["bg"], "#");
        }

        $focusPoints = [];
        if (isset($params["fp-x"]) && isset($params["fp-y"]))
        {
            $focusPoints[0] = floatval($params["fp-x"]);
            if ($focusPoints[0] < 0)
            {
                $focusPoints[0] = 0;
            }
            if ($focusPoints[0] > 1)
            {
                $focusPoints[0] = 1;
            }

            $focusPoints[1] = floatval($params["fp-y"]);
            if ($focusPoints[1] < 0)
            {
                $focusPoints[1] = 0;
            }
            if ($focusPoints[1] > 1)
            {
                $focusPoints[1] = 1;
            }
        }
        else
        {
            $focusPoints = [0.5, 0.5];
        }

        $format = "auto";
        if (isset($params["fm"]))
        {
            switch ($params["fm"])
            {
                case "gif":
                    $format = "gif";
                    break;
                case "jpeg":
                    $format = "jpeg";
                    break;
                case "webp":
                    if (!$clientAcceptsWebp){
                        $format = "png";
                    } else {
                        $format = "webp";
                    }
                    break;
                case "png":
                    $format = "png";
                    break;
                default:
                    $format = "auto";
                    break;
            }
        }
        if ($format === "auto")
        {
            if ($clientAcceptsWebp)
            {
                $format = "webp";
            }
            else
            {
                $format = "png";
            }
        }

        $transform = [
            "width" => $width,
            "height" => $height,
            "format" => $format,
            "mode" => $mode,
            "quality" => $quality,
            "background" => $bg,
            "focusPoint" => $focusPoints,
            "aspectRatio" => $aspectRatioValues,
        ];
        return $transform;
    }

    private static function doImageTransform(string $tempImage, array $transform, string $resizeOn = null): void
    {
        $img = new Imagick($tempImage);
        $img->setImageCompression(Imagick::COMPRESSION_NO);
        $img->setImageCompressionQuality(100);
        $img->setOption("png:compression-level", "9");

        // Handle null width & height values
        $width = $transform["width"];
        $height = $transform["height"];
        $baseImageWidth = $img->getImageWidth();
        $baseImageHeight = $img->getImageHeight();
        if (is_null($width))
        {
            $width = $baseImageWidth;
        }
        if (is_null($height))
        {
            $height = $baseImageHeight;
        }

        if (count($transform["aspectRatio"]) != 2)
        {
            if (isset($transform["width"]) && isset($transform["height"]))
            {
                $transform["aspectRatio"] = [$transform["width"], $transform["height"]];
            }
            else
            {
                $transform["aspectRatio"] = [$baseImageWidth, $baseImageHeight];
            }
        }

        if (!is_null($transform["width"]) && is_null($transform["height"]))
        {
            $height = round(($transform["aspectRatio"][1] / $transform["aspectRatio"][0]) * $width);
        }
        else if (is_null($transform["width"]) && !is_null($transform["height"]))
        {
            $width = round(($transform["aspectRatio"][0] / $transform["aspectRatio"][1]) * $height);
        }

        switch ($transform["mode"]) {
            case "fit":
                $img->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 0.75);
                $img->writeImage($tempImage);
                break;
            case "letterbox":
                $img->setImageBackgroundColor("#" . $transform["background"]);
                $img->thumbnailImage($width, $height, true, true);
                $img->writeImage($tempImage);
                break;
            case "croponly":
                $leftPos = floor($baseImageWidth * $transform["focusPoint"][0]) - floor($width / 2);
                $leftPos = max(0, min($width, $leftPos));
                $topPos = floor($baseImageHeight * $transform["focusPoint"][1]) - floor($height / 2);
                $topPos = max(0, min($height, $topPos));
                $img->cropImage($width, $height, $leftPos, $topPos);
                $img->writeImage($tempImage);
                break;
            case "crop":
                $aspectRatio = $transform["aspectRatio"][0] / $transform["aspectRatio"][1];

                if ($resizeOn === "height" || $resizeOn === "h")
                {
                    $width = (int)($height * $aspectRatio);
                    $img->resizeImage($width, null, Imagick::FILTER_LANCZOS, 0.75);
                }
                else if ($resizeOn === "width" || $resizeOn === "w")
                {
                    $height = (int)($width / $aspectRatio);
                    $img->resizeImage(null, $height, Imagick::FILTER_LANCZOS, 0.75);
                }
                else
                {
                    if ($width / $height > $aspectRatio)
                    {
                        $height = (int)($width / $aspectRatio);
                        $img->resizeImage($width, null, Imagick::FILTER_LANCZOS, 0.75);
                    }
                    else
                    {
                        $width = (int)($height * $aspectRatio);
                        $img->resizeImage(null, $height, Imagick::FILTER_LANCZOS, 0.75);
                    }
                }

                $leftPos = floor($img->getImageWidth() * $transform["focusPoint"][0]) - floor($width / 2);
                $leftPos = max(0, min($width, $leftPos));
                $topPos = floor($img->getImageHeight() * $transform["focusPoint"][1]) - floor($height / 2);
                $topPos = max(0, min($height, $topPos));
                $img->cropImage($width, $height, $leftPos, $topPos);
                $img->writeImage($tempImage);
                break;
            default:
                if (is_null($resizeOn))
                {
                    if ($width < $height)
                    {
                        $img->resizeImage($width, null, Imagick::FILTER_LANCZOS, 0.75);
                    }
                    elseif ($height < $width)
                    {
                        $img->resizeImage(null, $height, Imagick::FILTER_LANCZOS, 0.75);
                    }
                    else
                    {
                        if ($baseImageWidth < $baseImageHeight)
                        {
                            $img->resizeImage($width, null, Imagick::FILTER_LANCZOS, 0.75);
                        } 
                        elseif ($baseImageHeight < $baseImageWidth)
                        {
                            $img->resizeImage(null, $height, Imagick::FILTER_LANCZOS, 0.75);
                        }
                        else
                        {
                            $img->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 0.75);
                        }
                    }
                }
                else
                {
                    if ($resizeOn === "height" || $resizeOn === "h")
                    {
                        $img->resizeImage(null, $height, Imagick::FILTER_LANCZOS, 0.75);
                    }
                    else
                    {
                        $img->resizeImage($width, null, Imagick::FILTER_LANCZOS, 0.75);
                    }
                }
                $img->writeImage($tempImage);
                break;
        }
    }

    private static function convertImageFormat(string $tempImage, array $transform): void
    {
        $img = new Imagick($tempImage);
        switch ($transform["format"])
        {
            case "jpeg":
                $img->setImageFormat("jpeg");
                $img->setImageCompressionQuality($transform["quality"]);
                $img->writeImage($tempImage);
                break;
            case "jpg":
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
            case "webp":
                if (\count(\Imagick::queryFormats("WEBP")) > 0 || file_exists("/usr/bin/cwebp"))
                {
                    if (\count(\Imagick::queryFormats("WEBP")) > 0)
                    {
                        $img->setImageFormat("webp");
                        $img->setImageCompressionQuality($transform["quality"]);
                        $img->writeImage($tempImage);
                    }
                    else
                    {
                        $command = escapeshellcmd("/usr/bin/cwebp -q " . $transform["quality"] . " " . $tempImage . " -o " . $tempImage);
                        shell_exec($command);
                    }
                }
                else
                {
                    // Force PNG since this machine cannot generate WEBP images
                    $transform["format"] = "png";
                    self::convertImageFormat($tempImage, $transform);
                }
                break;
            default:
                break;
        }
    }
}
