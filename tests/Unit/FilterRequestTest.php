<?php

namespace Tests\Unit;

use App\Modules\Core\Requests\FilterRequest;
use PHPUnit\Framework\TestCase;

class FilterRequestTest extends TestCase
{
    public function test_rules_contains_standard_filter_fields(): void
    {
        $request = new FilterRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('search', $rules);
        $this->assertArrayHasKey('status', $rules);
        $this->assertArrayHasKey('from_date', $rules);
        $this->assertArrayHasKey('to_date', $rules);
        $this->assertArrayHasKey('sort_by', $rules);
        $this->assertArrayHasKey('sort_order', $rules);
        $this->assertArrayHasKey('limit', $rules);
        $this->assertSame('nullable|in:asc,desc', $rules['sort_order']);
    }

    public function test_query_parameters_and_body_parameters_for_scribe(): void
    {
        $request = new FilterRequest;
        $queryParameters = $request->queryParameters();

        $this->assertArrayHasKey('search', $queryParameters);
        $this->assertArrayHasKey('status', $queryParameters);
        $this->assertArrayHasKey('from_date', $queryParameters);
        $this->assertArrayHasKey('to_date', $queryParameters);
        $this->assertArrayHasKey('sort_by', $queryParameters);
        $this->assertArrayHasKey('sort_order', $queryParameters);
        $this->assertArrayHasKey('limit', $queryParameters);
        $this->assertSame([], $request->bodyParameters());
    }
}
