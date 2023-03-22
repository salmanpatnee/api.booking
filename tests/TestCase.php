<?php

namespace Tests;

use App\Models\Expense;
use App\Models\ExpenseType;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    protected function createExpenseType($attributes = [])
    {
        return ExpenseType::factory()->create($attributes);
    }

    protected function createExpense($attributes = [])
    {
        return Expense::factory()->create($attributes);
    }
}
