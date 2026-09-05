<?php

declare(strict_types=1);

namespace tests\unit\components;

use app\components\BookCoverStorage;
use Imagick;
use ImagickPixel;
use InvalidArgumentException;
use Yii;
use yii\web\UploadedFile;

class BookCoverStorageTest extends \Codeception\Test\Unit
{
    private string $directory;

    protected function _before(): void
    {
        $this->directory = Yii::getAlias('@runtime/books-test-' . bin2hex(random_bytes(4)));
    }

    protected function _after(): void
    {
        if (is_dir($this->directory)) {
            foreach (glob($this->directory . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->directory);
        }
    }

    public function testVariantAndUrl(): void
    {
        verify(BookCoverStorage::variant('abc.jpeg'))->equals('abc.jpeg');
        verify(BookCoverStorage::variant('abc.jpeg', 60))->equals('abc_60.jpeg');
        verify(BookCoverStorage::url('abc.jpeg', 600))->stringContainsString('books/abc_600.jpeg');
    }

    public function testDeleteIgnoresEmptyName(): void
    {
        $storage = new BookCoverStorage();
        $storage->directory = $this->directory;
        $storage->delete(null);
        $storage->delete('');
        verify(true)->true();
    }

    public function testStoreWritesJpegAndThumbnails(): void
    {
        $storage = new BookCoverStorage();
        $storage->directory = $this->directory;
        $filename = $storage->store($this->uploadedJpeg(80, 80));
        $files = glob($this->directory . '/*.jpeg') ?: [];
        verify($files)->arrayCount(4);
        $uuid = pathinfo($filename, PATHINFO_FILENAME);

        verify(is_file($this->directory . '/' . $filename))->true();
        verify(is_file($this->directory . '/' . $uuid . '_60.jpeg'))->true();
        verify(is_file($this->directory . '/' . $uuid . '_600.jpeg'))->true();

        $storage->delete($filename);
        verify(is_file($this->directory . '/' . $filename))->false();
    }

    public function testRejectsFailedUploadTooLargeAndTooBig(): void
    {
        $storage = new BookCoverStorage();
        $storage->directory = $this->directory;
        $failed = new UploadedFile(['name' => 'a.jpg', 'tempName' => '', 'size' => 1, 'error' => UPLOAD_ERR_NO_FILE]);
        try {
            $storage->store($failed);
            $this->fail('upload');
        } catch (InvalidArgumentException $exception) {
            verify($exception->getMessage())->equals('Image upload failed.');
        }

        $huge = $this->uploadedJpeg(10, 10);
        $huge->size = BookCoverStorage::MAX_BYTES + 1;
        try {
            $storage->store($huge);
            $this->fail('size');
        } catch (InvalidArgumentException $exception) {
            verify($exception->getMessage())->equals('Image must be 10 MB or smaller.');
        }

        $this->expectException(InvalidArgumentException::class);
        $storage->store($this->uploadedJpeg(BookCoverStorage::MAX_WIDTH + 1, 10));
    }

    public function testRejectsUnsupportedFormat(): void
    {
        $path = $this->directory . '-bad.txt';
        file_put_contents($path, 'not an image');
        $file = new UploadedFile([
            'name' => 'x.txt',
            'tempName' => $path,
            'size' => filesize($path),
            'error' => UPLOAD_ERR_OK,
        ]);
        $storage = new BookCoverStorage();
        $storage->directory = $this->directory;
        $this->expectException(InvalidArgumentException::class);
        try {
            $storage->store($file);
        } finally {
            unlink($path);
        }
    }

    private function uploadedJpeg(int $width, int $height): UploadedFile
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
        $path = $this->directory . '/src-' . $width . 'x' . $height . '.jpeg';
        $image = new Imagick();
        $image->newImage($width, $height, new ImagickPixel('red'));
        $image->setImageFormat('jpeg');
        $image->writeImage($path);
        $image->clear();

        return new UploadedFile([
            'name' => 'cover.jpg',
            'tempName' => $path,
            'type' => 'image/jpeg',
            'size' => filesize($path),
            'error' => UPLOAD_ERR_OK,
        ]);
    }
}
