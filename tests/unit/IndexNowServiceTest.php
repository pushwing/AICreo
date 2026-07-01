<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\Seo\IndexNowService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class IndexNowServiceTest extends CIUnitTestCase
{
    public function testDisabledWithoutKey(): void
    {
        $svc = new IndexNowService('', 'https://example.com/');
        $this->assertFalse($svc->isEnabled());
    }

    public function testDisabledOnLocalHost(): void
    {
        $svc = new IndexNowService('abc123', 'http://localhost:8080/');
        $this->assertFalse($svc->isEnabled());
    }

    public function testDisabledOnTestDomain(): void
    {
        $svc = new IndexNowService('abc123', 'https://myshop.test/');
        $this->assertFalse($svc->isEnabled());
    }

    public function testEnabledWithKeyAndRealDomain(): void
    {
        $svc = new IndexNowService('abc123', 'https://example.com/');
        $this->assertTrue($svc->isEnabled());
    }

    public function testSubmitNoOpWhenDisabled(): void
    {
        $svc = new IndexNowService('', 'https://example.com/');
        // 비활성이면 네트워크 호출 없이 즉시 false
        $this->assertFalse($svc->submit(['https://example.com/foo']));
    }

    public function testSubmitNoOpWhenNoUrls(): void
    {
        $svc = new IndexNowService('abc123', 'https://example.com/');
        $this->assertFalse($svc->submit([]));
    }

    public function testGetKeyReturnsConfiguredKey(): void
    {
        $svc = new IndexNowService('mykey123', 'https://example.com/');
        $this->assertSame('mykey123', $svc->getKey());
    }
}
