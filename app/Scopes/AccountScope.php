<?php

namespace App\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class AccountScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (! Auth::check()) {
            return;
        }

        /** @var User $authUser */
        $authUser = Auth::user();
        $fullColumnName = $model->getTable().'.account_id';
        $builder->where($fullColumnName, '=', $authUser->account_id);
    }
}
