<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the tenant scope to a given Eloquent query builder.
     *
     * Works in both web (auth user) and API (tenant on request) contexts.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $builder->where($model->getTable() . '.tenant_id', auth()->user()->tenant_id);
        } elseif (request() && request()->attributes->get('tenant')) {
            $builder->where($model->getTable() . '.tenant_id', request()->attributes->get('tenant')->id);
        }
    }
}
