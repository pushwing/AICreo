<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\OAuth\GoogleProvider;
use App\Libraries\OAuth\KakaoProvider;
use App\Libraries\OAuth\NaverProvider;
use App\Libraries\OAuth\OAuthFactory;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @internal
 */
final class OAuthFactoryTest extends CIUnitTestCase
{
    public function testMakeResolvesEachProvider(): void
    {
        $this->assertInstanceOf(NaverProvider::class, OAuthFactory::make('naver'));
        $this->assertInstanceOf(KakaoProvider::class, OAuthFactory::make('kakao'));
        $this->assertInstanceOf(GoogleProvider::class, OAuthFactory::make('google'));
    }

    public function testMakeThrowsOnUnsupportedProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OAuthFactory::make('facebook');
    }

    public function testSupportedListsAllProviders(): void
    {
        $this->assertSame(['naver', 'kakao', 'google'], OAuthFactory::supported());
    }
}
