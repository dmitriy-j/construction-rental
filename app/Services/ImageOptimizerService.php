<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageOptimizerService
{
    const QUALITY_JPG = 85;
    const QUALITY_WEBP = 80;

    /**
     * Оптимизировать изображение: создать версии thumbnail (150x150), medium (600x400), large (1200x800).
     * Исходное изображение остаётся как fallback.
     */
    public function optimize(EquipmentImage $image, UploadedFile $file): void
    {
        try {
            $equipmentId = $image->equipment_id;
            $basePath = "equipment/{$equipmentId}";
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $timestamp = time();

            // Создаём директории если нет
            $paths = ['thumb', 'medium', 'large'];
            foreach ($paths as $p) {
                $dir = "public/{$basePath}/{$p}";
                if (!Storage::exists($dir)) {
                    Storage::makeDirectory($dir);
                }
            }

            // Получаем информацию об изображении через getimagesize
            $sourcePath = $file->getRealPath();
            $imageInfo = @getimagesize($sourcePath);
            if (!$imageInfo) {
                throw new \Exception('Невозможно определить тип изображения');
            }

            $mimeType = $imageInfo['mime'];
            $origW = $imageInfo[0];
            $origH = $imageInfo[1];

            // Создаём GD-ресурс
            $srcImage = $this->createImageFromFile($sourcePath, $mimeType);
            if (!$srcImage) {
                throw new \Exception('Не удалось создать GD-ресурс');
            }

            // Размеры для ресайза
            $sizes = [
                'thumb' => ['w' => 150, 'h' => 150, 'crop' => true],
                'medium' => ['w' => 600, 'h' => 400, 'crop' => false],
                'large' => ['w' => 1200, 'h' => 800, 'crop' => false],
            ];

            $generatedPaths = [];
            foreach ($sizes as $sizeName => $size) {
                $resized = $this->resizeImage($srcImage, $origW, $origH, $size['w'], $size['h'], $size['crop']);

                // Сохраняем WebP
                $webpFilename = "{$filename}_{$timestamp}_{$sizeName}.webp";
                $webpPath = "{$basePath}/{$sizeName}/{$webpFilename}";

                ob_start();
                imagewebp($resized, null, self::QUALITY_WEBP);
                $webpData = ob_get_clean();
                Storage::put("public/{$webpPath}", $webpData);

                $generatedPaths[$sizeName] = $webpPath;

                // Сохраняем JPEG как fallback для старых браузеров
                $jpgFilename = "{$filename}_{$timestamp}_{$sizeName}.jpg";
                $jpgPath = "{$basePath}/{$sizeName}/{$jpgFilename}";

                ob_start();
                imagejpeg($resized, null, self::QUALITY_JPG);
                $jpgData = ob_get_clean();
                Storage::put("public/{$jpgPath}", $jpgData);

                $generatedPaths[$sizeName . '_jpg'] = $jpgPath;

                imagedestroy($resized);
            }

            imagedestroy($srcImage);

            // Обновляем запись с путями к разным версиям
            $image->update([
                'thumbnail_path' => $generatedPaths['thumb'],
                'medium_path' => $generatedPaths['medium'],
                'large_path' => $generatedPaths['large'],
                'path' => $basePath . '/' . $file->getClientOriginalName(),
            ]);

            Log::info("Image optimized for equipment #{$equipmentId}", [
                'image_id' => $image->id,
                'original' => $file->getClientOriginalName(),
                'webp_thumb' => $generatedPaths['thumb'],
                'webp_medium' => $generatedPaths['medium'],
                'webp_large' => $generatedPaths['large'],
            ]);

        } catch (\Throwable $e) {
            Log::error('Image optimization failed: ' . $e->getMessage(), [
                'image_id' => $image->id,
                'file' => $file->getClientOriginalName(),
            ]);
            // В случае ошибки просто используем оригинальное изображение
            $file->storeAs("public/equipment/{$image->equipment_id}", $file->getClientOriginalName());
        }
    }

    /**
     * Создать GD-ресурс из файла.
     */
    private function createImageFromFile(string $path, string $mimeType): ?\GdImage
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };
    }

    /**
     * Ресайз с сохранением пропорций.
     */
    private function resizeImage(\GdImage $src, int $srcW, int $srcH, int $dstW, int $dstH, bool $crop = false): \GdImage
    {
        if ($crop) {
            // Центрированная обрезка
            $minRatio = max($dstW / $srcW, $dstH / $srcH);
            $tmpW = (int)round($srcW * $minRatio);
            $tmpH = (int)round($srcH * $minRatio);

            $tmp = imagecreatetruecolor($tmpW, $tmpH);
            imagecopyresampled($tmp, $src, 0, 0, 0, 0, $tmpW, $tmpH, $srcW, $srcH);

            $dst = imagecreatetruecolor($dstW, $dstH);
            $offsetX = (int)(($tmpW - $dstW) / 2);
            $offsetY = (int)(($tmpH - $dstH) / 2);
            imagecopy($dst, $tmp, 0, 0, $offsetX, $offsetY, $dstW, $dstH);
            imagedestroy($tmp);

            return $dst;
        }

        // Ресайз с сохранением пропорций (вписываем в контейнер)
        $ratio = min($dstW / $srcW, $dstH / $srcH);
        $newW = (int)round($srcW * $ratio);
        $newH = (int)round($srcH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        // Если нужно, центрируем на холсте заданного размера
        if ($newW !== $dstW || $newH !== $dstH) {
            $canvas = imagecreatetruecolor($dstW, $dstH);
            $fill = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $fill);
            imagecopy($canvas, $dst, (int)(($dstW - $newW) / 2), (int)(($dstH - $newH) / 2), 0, 0, $newW, $newH);
            imagedestroy($dst);
            return $canvas;
        }

        return $dst;
    }
}
