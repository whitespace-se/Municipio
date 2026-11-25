<?php

declare(strict_types=1);

namespace Modularity\Module\Hero;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

function wp_strip_all_tags(string $value): string
{
    return strip_tags($value);
}

function __(string $value, string $domain): string
{
    return $value;
}

class HeroTest extends TestCase
{
    #[TestDox('accepts exactly 500 visible characters')]
    public function testAcceptsFiveHundredVisibleCharacters(): void
    {
        $value = '<p><strong>' . str_repeat('a', times: 499) . '</strong>&amp;</p>';

        static::assertTrue($this->createHero()->validateBodyLength(true, $value, [], ''));
    }

    #[TestDox('rejects 501 visible characters')]
    public function testRejectsFiveHundredAndOneVisibleCharacters(): void
    {
        $value = '<p><strong>' . str_repeat('a', times: 500) . '</strong>&amp;</p>';

        static::assertSame(
            'The body may contain no more than 500 visible characters.',
            $this->createHero()->validateBodyLength(true, $value, [], ''),
        );
    }

    #[TestDox('preserves an earlier validation error')]
    public function testPreservesEarlierValidationError(): void
    {
        static::assertSame(
            'Existing error',
            $this->createHero()->validateBodyLength('Existing error', str_repeat('a', times: 501), [], ''),
        );
    }

    private function createHero(): Hero
    {
        return (new \ReflectionClass(Hero::class))->newInstanceWithoutConstructor();
    }
}
