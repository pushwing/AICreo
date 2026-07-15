<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\SettingModel;
use Tests\Support\AdminTestCase;

/**
 * 소셜 로그인 제공자별 관리자 On/Off 스위치 (#234).
 *
 * @internal
 */
final class OAuthSettingTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        cache()->delete('site_settings');
    }

    public function testOauthTabRendersToggleSwitches(): void
    {
        $body = $this->withSession($this->adminSession)->get('admin/settings/oauth')->getBody();

        $this->assertStringContainsString('name="oauth_naver_enabled"', $body);
        $this->assertStringContainsString('name="oauth_kakao_enabled"', $body);
        $this->assertStringContainsString('name="oauth_google_enabled"', $body);
        $this->assertStringContainsString('type="checkbox" class="form-check-input" role="switch"', $body);
    }

    public function testSaveOauthSettingsPersists(): void
    {
        // 네이버만 체크 → 카카오·구글은 hidden value=0 만 전송되는 상황 재현
        $result = $this->withSession($this->adminSession)->post('admin/settings/oauth', [
            'oauth_naver_enabled'  => '1',
            'oauth_kakao_enabled'  => '0',
            'oauth_google_enabled' => '0',
        ]);

        $result->assertRedirectTo('/admin/settings/oauth');

        $map = (new SettingModel())->getAllAsMap();
        $this->assertSame('1', $map['oauth_naver_enabled']);
        $this->assertSame('0', $map['oauth_kakao_enabled']);
        $this->assertSame('0', $map['oauth_google_enabled']);
    }
}
