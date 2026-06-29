<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\OAuth\GoogleProvider;
use App\Libraries\OAuth\OAuthFactory;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class OAuthProviderTest extends CIUnitTestCase
{
    public function testGetAuthUrlBuildsAuthorizationEndpoint(): void
    {
        $url = (new GoogleProvider())->getAuthUrl('state-token-123');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('state-token-123', $query['state']);
        $this->assertSame('openid email profile', $query['scope']);
        $this->assertStringContainsString('/auth/social/google/callback', $query['redirect_uri']);
    }

    public function testRedirectUriIsDerivedPerProvider(): void
    {
        $naver = OAuthFactory::make('naver')->getAuthUrl('s');

        parse_str((string) parse_url($naver, PHP_URL_QUERY), $query);
        $this->assertStringContainsString('/auth/social/naver/callback', $query['redirect_uri']);
    }
}
