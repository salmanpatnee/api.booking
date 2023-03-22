<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private $expense;
    private $tableName = 'expenses';

    public function setUp(): void
    {
        parent::setUp();

        $this->expense = $this->createExpense();
    }

    /**
     * Get the list of expense types.
     *
     * @return void
     */
    public function test_get_all_expenses()
    {
        $response = $this->getJson(route('expenses.index'))
            ->assertOk()
            ->json();

        // $this->assertEquals(1, count($response));

        // $this->assertEquals($this->expenseType->name, $response['data'][0]['name']);
    }
}
