<?php

namespace Tests\Unit;

use App\Modules\Core\Enums\StatusEnum;
use PHPUnit\Framework\TestCase;

class StatusEnumTest extends TestCase
{
    public function test_values_returns_expected_statuses(): void
    {
        $this->assertSame(['active', 'inactive'], StatusEnum::values());
    }

    public function test_rule_returns_validation_rule(): void
    {
        $this->assertSame('in:active,inactive', StatusEnum::rule());
    }
}
