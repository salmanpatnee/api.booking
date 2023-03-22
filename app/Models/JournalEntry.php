<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];

    public function accountHead()
    {
        return $this->belongsTo(AccountHead::class);
    }

    public function forAccountHead()
    {
        return $this->belongsTo(AccountHead::class, 'for_account_head_id');
    }
}
