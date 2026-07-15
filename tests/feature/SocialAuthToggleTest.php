<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SettingModel;
use Tests\Support\FeatureTestCase;

/**
 * 비활성화된 소셜 로그인 제공자 접근 차단 (#234).
 *
 * @internal
 */
final class SocialAuthToggleTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cache()->delete('site_settings');
    }

    private function disableProvider(string $provider): void
    {
        (new SettingModel())->saveSettings(["oauth_{$provider}_enabled" => '0']);
    }

    public function testDisabledProviderRedirectIsBlocked(): void
    {
        $this->disableProvider('naver');

        $result = $this->get('auth/social/naver');

        $result->assertRedirectTo('/auth/login');
        $this->assertSame('지원하지 않는 로그인 방식입니다.', session('error'));
    }

    public function testDisabledProviderCallbackIsBlocked(): void
    {
        $this->disableProvider('kakao');

        // state 불일치로 우연히 리다이렉트되는 것과 구분하기 위해 메시지까지 검증
        $result = $this->get('auth/social/kakao/callback?code=dummy&state=dummy');

        $result->assertRedirectTo('/auth/login');
        $this->assertSame('지원하지 않는 로그인 방식입니다.', session('error'));
    }

    public function testEnabledProviderRedirectsToProviderAuthUrl(): void
    {
        $result = $this->get('auth/social/google');

        $result->assertStatus(302);
        $location = $result->response()->getHeaderLine('Location');
        $this->assertStringContainsString('accounts.google.com', $location);
    }

    public function testLoginPageHidesDisabledProviderButton(): void
    {
        $this->disableProvider('naver');
        cache()->delete('site_settings');

        $body = $this->get('auth/login')->getBody();

        $this->assertStringNotContainsString('/auth/social/naver"', $body);
        $this->assertStringContainsString('/auth/social/kakao"', $body);
        $this->assertStringContainsString('/auth/social/google"', $body);
    }

    public function testLoginPageShowsAllProvidersByDefault(): void
    {
        $body = $this->get('auth/login')->getBody();

        $this->assertStringContainsString('/auth/social/naver"', $body);
        $this->assertStringContainsString('/auth/social/kakao"', $body);
        $this->assertStringContainsString('/auth/social/google"', $body);
    }
}
