<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['expense_type_id', 'payment_method_id', 'date', 'description', 'amount', 'created_by'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'payment_method_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $with = ['expenseType'];

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function scopeIndexQuery($query)
    {
        $term       = request('search', '');
        $sortOrder  = request('sortOrder', 'desc');
        $orderBy    = request('orderBy', 'date');

        $query->search($term);

        if (!empty(request('start_date')))
            $query->where('date', '>=', request('start_date'));
        if (!empty(request('end_date')))
            $query->where('date', '<=', request('end_date'));

        $query->orderBy($orderBy, $sortOrder);
    }

    public function scopeSearch($query, $term)
    {
        $term = "%$term%";

        $query->where(function ($query) use ($term) {
            $query->where('description', 'like', $term)
                ->orWhere('amount', 'like', $term)
                ->orWhereHas('expenseType', function ($query) use ($term) {
                    $query->where('name', 'like', $term);
                });
        });
    }
}
