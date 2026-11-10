<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;

class ImageProcessor
{
    protected const DETAIL_MAX_DIMENSION = 1600;

    protected const THUMB_SIZE = 500;

    /**
     * Store an uploaded product/category image as two variants on the given disk:
     * a detail-size copy (capped, aspect preserved) and a fixed-size square thumbnail
     * (cropped to cover) for card/listing grids. Previously the full-resolution upload
     * was served identically for both a 400x400 CSS thumbnail and the full detail view.
     *
     * @return string the stored path of the detail image (thumbnail path is derived via thumbPath())
     */
    public static function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->read($file->getRealPath());

        // Derive the extension from the real (sniffed) content type, never the client's filename.
        $extension = match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $filename = $directory.'/'.uniqid().'.'.$extension;

        $detail = clone $image;
        $detail->scaleDown(self::DETAIL_MAX_DIMENSION, self::DETAIL_MAX_DIMENSION);
        Storage::disk($disk)->put($filename, (string) $detail->encode(new AutoEncoder(quality: 85)));

        $thumb = clone $image;
        $thumb->cover(self::THUMB_SIZE, self::THUMB_SIZE);
        Storage::disk($disk)->put(self::thumbPath($filename), (string) $thumb->encode(new AutoEncoder(quality: 80)));

        return $filename;
    }

    public static function thumbPath(string $path): string
    {
        $dot = strrpos($path, '.');

        return $dot === false
            ? $path.'-thumb'
            : substr($path, 0, $dot).'-thumb'.substr($path, $dot);
    }

    public static function deleteWithThumb(string $path, string $disk = 'public'): void
    {
        Storage::disk($disk)->delete([$path, self::thumbPath($path)]);
    }
}
