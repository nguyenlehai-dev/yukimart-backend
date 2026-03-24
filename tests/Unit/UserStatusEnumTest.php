<?php

namespace Tests\Unit;

use App\Modules\Core\Enums\UserStatusEnum;
use PHPUnit\Framework\TestCase;

class UserStatusEnumTest extends TestCase
{
    public function test_values_returns_expected_statuses(): void
    {
        $this->assertSame(['active', 'inactive', 'banned'], UserStatusEnum::values());
    }

    public function test_rule_returns_validation_rule(): void
    {
        $this->assertSame('in:active,inactive,banned', UserStatusEnum::rule());
    }
}
