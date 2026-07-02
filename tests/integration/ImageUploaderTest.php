<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Libraries\ImageUploader;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * ImageUploader 통합 테스트.
 *
 * move() 는 move_uploaded_file 기반이라 테스트에서 직접 호출 불가 →
 * stub 으로 실제 파일을 대상 경로에 배치해 디렉터리 생성·경로 규칙을 검증한다.
 *
 * @internal
 */
final class ImageUploaderTest extends CIUnitTestCase
{
    private const FOLDER = 'test_uploads';

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->rrmdir(FCPATH . 'uploads/' . self::FOLDER);
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function file(string $ext, string $mime, int $size, bool $stubMove = true): UploadedFile
    {
        $mock = $this->createMock(UploadedFile::class);
        $mock->method('getClientExtension')->willReturn($ext);
        $mock->method('getMimeType')->willReturn($mime);
        $mock->method('getSize')->willReturn($size);

        if ($stubMove) {
            $mock->method('move')->willReturnCallback(
                static function (string $path, string $name): bool {
                    file_put_contents($path . '/' . $name, 'binary');

                    return true;
                },
            );
        }

        return $mock;
    }

    public function testUploadsValidImageToDisk(): void
    {
        $result = (new ImageUploader(self::FOLDER))->upload($this->file('png', 'image/png', 1024));

        $this->assertTrue($result['success']);
        $this->assertMatchesRegularExpression(
            '#^uploads/' . self::FOLDER . '/\d{4}/\d{2}/[0-9a-f]{32}\.png$#',
            $result['path'],
        );
        $this->assertFileExists(FCPATH . $result['path']);
    }

    public function testRejectsNonImageMime(): void
    {
        $result = (new ImageUploader(self::FOLDER))->upload($this->file('pdf', 'application/pdf', 1024, false));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('업로드 가능', $result['error']);
    }

    public function testRejectsDisallowedExtensionEvenWithImageMime(): void
    {
        // MIME 은 이미지지만 확장자가 화이트리스트에 없음
        $result = (new ImageUploader(self::FOLDER))->upload($this->file('bmp', 'image/jpeg', 1024, false));

        $this->assertFalse($result['success']);
    }

    public function testRejectsOversizedImage(): void
    {
        $result = (new ImageUploader(self::FOLDER))->upload($this->file('jpg', 'image/jpeg', 3 * 1024 * 1024, false));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('2MB', $result['error']);
    }
}
