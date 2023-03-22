<?php

namespace Tests\Feature;

use App\Models\ExpenseType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExpenseTypeTest extends TestCase
{
    use RefreshDatabase;

    private $expenseType;
    private $tableName = 'expense_types';

    public function setUp(): void
    {
        parent::setUp();

        $this->expenseType = $this->createExpenseType();
    }

    /**
     * Get the list of expense types.
     *
     * @return void
     */
    public function test_get_all_expense_types()
    {
        $response = $this->getJson(route('expense-types.index'))
            ->assertOk()
            ->json();

        $this->assertEquals(1, count($response));

        $this->assertEquals($this->expenseType->name, $response['data'][0]['name']);
    }

    /**
     * Get the single expense type.
     *
     * @return void
     */
    public function test_get_single_expense_type()
    {
        $response = $this->getJson(route('expense-types.show', $this->expenseType->id))
            ->assertOk()
            ->json();

        $this->assertEquals($response['data']['name'], $this->expenseType->name);
    }

    /**
     * Add new expense type.
     *
     * @return void
     */
    public function test_add_new_expense_type()
    {
        $expenseType = ExpenseType::factory()->make();

        $attributes = ['name' => $expenseType->name];

        $response = $this->postJson(route('expense-types.store'), $attributes)
            ->assertCreated()
            ->json();

        $this->assertEquals($expenseType->name, $response['data']['name']);

        $this->assertDatabaseHas($this->tableName, $attributes);
    }

    /**
     * Name field is required while adding new expense type.
     *
     * @return void
     */
    public function test_name_field_is_required()
    {
        $this->withExceptionHandling();

        $response = $this->postJson(route('expense-types.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    /**
     * Name field should be unique while adding new expense type.
     *
     * @return void
     */
    public function test_name_field_should_be_unique()
    {
        $this->withExceptionHandling();

        $expenseType = $this->createExpenseType();

        $this->postJson(route('expense-types.store'), ['name' => $expenseType['name']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    /**
     * Update an expense type.
     *
     * @return void
     */
    public function test_update_expense_type()
    {
        $this->patchJson(route('expense-types.update', $this->expenseType->id), [
            'name' => 'Updated Name'
        ])->assertOk();

        $this->assertDatabaseHas($this->tableName, [
            'id' => $this->expenseType->id,
            'name' => 'Updated Name'
        ]);
    }

    /**
     * Delete an expense type.
     *
     * @return void
     */
    public function test_delete_expense_type()
    {
        $this->deleteJson(route('expense-types.destroy', $this->expenseType->id))
            ->assertOk();

        $this->assertDatabaseMissing($this->tableName, ['name' => $this->expenseType->name]);
    }
}
