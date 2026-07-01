<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class MaskHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('mask');
    }

    public function testMasksMiddleOfThreeCharName(): void
    {
        $this->assertSame('홍*동', mask_name('홍길동'));
    }

    public function testMasksSecondCharOfTwoCharName(): void
    {
        $this->assertSame('홍*', mask_name('홍길'));
    }

    public function testKeepsSingleCharAsIs(): void
    {
        $this->assertSame('홍', mask_name('홍'));
    }

    public function testMasksLongName(): void
    {
        $this->assertSame('남**우', mask_name('남궁민우'));
    }

    public function testHandlesEmptyAndNull(): void
    {
        $this->assertSame('', mask_name(''));
        $this->assertSame('', mask_name(null));
    }
}
