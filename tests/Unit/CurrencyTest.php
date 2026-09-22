<?php

namespace Tests\Unit;

use App\Support\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function test_amounts_are_formatted_like_the_browser_does(): void
    {
        $this->assertSame("4.650,00\u{00A0}€", Currency::format(4650, 'EUR', 'hr_HR'));
        $this->assertSame("4.650,00\u{00A0}USD", Currency::format(4650, 'USD', 'hr_HR'));
        $this->assertSame("0,50\u{00A0}CHF", Currency::format(0.5, 'CHF', 'hr_HR'));
    }

    public function test_the_format_follows_the_language(): void
    {
        $this->assertSame('€4,650.00', Currency::format(4650, 'EUR', 'en_GB'));
        $this->assertSame("4.650,00\u{00A0}€", Currency::format(4650, 'EUR', 'de_DE'));
        $this->assertSame("4.650,00\u{00A0}€", Currency::format(4650, 'EUR', 'sl_SI'));
    }
}
