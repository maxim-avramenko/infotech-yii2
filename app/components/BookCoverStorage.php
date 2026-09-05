<?php

declare(strict_types=1);

namespace app\components;

use Imagick;
use ImagickException;
use InvalidArgumentException;
use Yii;
use yii\web\UploadedFile;

class BookCoverStorage
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    public const MAX_WIDTH = 2000;
    public const MAX_HEIGHT = 2000;

    public string $directory = '@app/web/books';

    public function store(UploadedFile $file): string
    {
        $this->assertValid($file);

        $directory = $this->ensureDirectory();
        $uuid = $this->uuidV4();
        $filename = $uuid . '.jpeg';

        try {
            $image = new Imagick($file->tempName);
            if ($image->getNumberImages() > 1) {
                $image = $image->coalesceImages();
                $image->setIteratorIndex(0);
            }
            $frame = $image->getImage();
            $frame->setImageFormat('jpeg');
            $frame->setImageCompressionQuality(90);
            $frame->stripImage();
            $frame->writeImage($directory . '/' . $filename);

            $thumb60 = clone $frame;
            $thumb60->cropThumbnailImage(60, 60);
            $thumb60->writeImage($directory . '/' . $uuid . '_60.jpeg');
            $thumb60->clear();

            $thumb600 = clone $frame;
            $thumb600->cropThumbnailImage(600, 600);
            $thumb600->writeImage($directory . '/' . $uuid . '_600.jpeg');
            $thumb600->clear();

            $frame->clear();
            $image->clear();
        } catch (ImagickException $exception) {
            $this->delete($filename);
            throw new InvalidArgumentException('Image could not be converted to JPEG.');
        }

        return $filename;
    }

    public function delete(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $uuid = pathinfo($filename, PATHINFO_FILENAME);
        foreach ([$filename, $uuid . '_60.jpeg', $uuid . '_600.jpeg'] as $name) {
            $path = $this->ensureDirectory() . '/' . $name;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public static function variant(string $filename, ?int $size = null): string
    {
        if ($size === null) {
            return $filename;
        }

        return pathinfo($filename, PATHINFO_FILENAME) . '_' . $size . '.jpeg';
    }

    public static function url(string $filename, ?int $size = null): string
    {
        return \yii\helpers\Url::to('@web/books/' . self::variant($filename, $size));
    }

    private function assertValid(UploadedFile $file): void
    {
        if ($file->error !== UPLOAD_ERR_OK || $file->tempName === '') {
            throw new InvalidArgumentException('Image upload failed.');
        }
        if ($file->size > self::MAX_BYTES) {
            throw new InvalidArgumentException('Image must be 10 MB or smaller.');
        }

        try {
            $probe = new Imagick();
            $probe->pingImage($file->tempName);
            $width = $probe->getImageWidth();
            $height = $probe->getImageHeight();
            $probe->clear();
        } catch (ImagickException) {
            throw new InvalidArgumentException('Image format is not supported.');
        }

        if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
            throw new InvalidArgumentException('Image must be 2000x2000 pixels or smaller.');
        }
    }

    private function ensureDirectory(): string
    {
        $directory = Yii::getAlias($this->directory);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create books directory.');
        }

        return $directory;
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
