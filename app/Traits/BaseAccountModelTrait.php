<?php

namespace App\Traits;

use App\Models\Account;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Account $account
 * @method static Builder|Account whereAccountId($value) // scopeWhereAccountId
 */
trait BaseAccountModelTrait
{
    public function getClassNameAttribute(): string
    {
        return self::class;
    }

    /**
     * Relationship to the Account Model.
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function scopeWhereAccountId(Builder $query, $id)
    {
        return $query->where('account_id', $id);
    }
}
