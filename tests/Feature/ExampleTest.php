<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Reker7\MoonShineBlocks\Tests\TestCase;

final class ExampleTest extends TestCase
{
    #[Test]
    public function it_boots_without_errors(): void
    {
        $this->assertTrue(true);
    }
}
