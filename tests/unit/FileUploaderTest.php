<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FileUploader;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class FileUploaderTest extends CIUnitTestCase
{
    /**
     * 업로드 파일 목 생성.
     */
    private function file(string $name, string $ext, int $size, int $error = UPLOAD_ERR_OK, bool $valid = true): UploadedFile
    {
        $mock = $this->createMock(UploadedFile::class);
        $mock->method('getError')->willReturn($error);
        $mock->method('isValid')->willReturn($valid);
        $mock->method('getClientExtension')->willReturn($ext);
        $mock->method('getSize')->willReturn($size);
        $mock->method('getName')->willReturn($name);

        return $mock;
    }

    public function testAcceptsAllowedFileWithinSizeLimit(): void
    {
        $errors = (new FileUploader())->validateFiles([
            $this->file('doc.pdf', 'pdf', 1024),
            $this->file('img.jpg', 'jpg', 2_000_000),
        ]);

        $this->assertSame([], $errors);
    }

    public function testRejectsDisallowedExtension(): void
    {
        $errors = (new FileUploader())->validateFiles([
            $this->file('virus.exe', 'exe', 1024),
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('허용되지 않는', $errors[0]);
    }

    public function testRejectsOversizedFile(): void
    {
        $errors = (new FileUploader())->validateFiles([
            $this->file('big.jpg', 'jpg', 11 * 1024 * 1024),
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('크기 초과', $errors[0]);
    }

    public function testSkipsEmptyFileInput(): void
    {
        $errors = (new FileUploader())->validateFiles([
            $this->file('', '', 0, UPLOAD_ERR_NO_FILE),
        ]);

        $this->assertSame([], $errors);
    }

    public function testExtensionCheckIsCaseInsensitive(): void
    {
        $errors = (new FileUploader())->validateFiles([
            $this->file('PHOTO.JPG', 'JPG', 1024),
        ]);

        $this->assertSame([], $errors);
    }
}
