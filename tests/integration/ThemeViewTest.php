<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Libraries\ThemeView;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * 테마 뷰 해석 우선순위 통합 테스트 (임시 뷰 파일 사용).
 *
 * @internal
 */
final class ThemeViewTest extends CIUnitTestCase
{
    private string $viewPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewPath = sys_get_temp_dir() . '/themeview_' . uniqid() . '/';
        $this->write('themes/default/sample.php', 'DEFAULT-SAMPLE');
        $this->write('themes/default/only_default.php', 'ONLY-DEFAULT');
        $this->write('themes/spring/sample.php', 'SPRING-SAMPLE');
        $this->write('plain.php', 'PLAIN');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        cache()->delete('site_settings');
        $this->rrmdir($this->viewPath);
    }

    private function write(string $relative, string $content): void
    {
        $full = $this->viewPath . $relative;
        @mkdir(dirname($full), 0777, true);
        file_put_contents($full, $content);
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

    private function themeView(string $activeTheme): ThemeView
    {
        // SettingModel::getAllAsMap 캐시를 직접 채워 DB 없이 active_theme 주입
        cache()->save('site_settings', ['active_theme' => $activeTheme], 60);

        // debug=false 로 뷰 디버그 주석 제거
        return new ThemeView(config('View'), $this->viewPath, null, false);
    }

    public function testRendersActiveThemeViewFirst(): void
    {
        $output = $this->themeView('spring')->render('sample');

        $this->assertSame('SPRING-SAMPLE', $output);
    }

    public function testFallsBackToDefaultThemeWhenMissingInActive(): void
    {
        // spring 테마에는 only_default 뷰가 없음 → default 테마로 폴백
        $output = $this->themeView('spring')->render('only_default');

        $this->assertSame('ONLY-DEFAULT', $output);
    }

    public function testUsesDefaultThemeWhenActiveIsDefault(): void
    {
        $output = $this->themeView('default')->render('sample');

        $this->assertSame('DEFAULT-SAMPLE', $output);
    }

    public function testFallsBackToPlainViewWhenNotInAnyTheme(): void
    {
        $output = $this->themeView('spring')->render('plain');

        $this->assertSame('PLAIN', $output);
    }

    public function testThemePrefixedViewPassesThrough(): void
    {
        $output = $this->themeView('spring')->render('themes/default/sample');

        $this->assertSame('DEFAULT-SAMPLE', $output);
    }
}
