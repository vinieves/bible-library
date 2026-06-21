<?php

namespace App\Services\Webhooks;

use Illuminate\Database\Eloquent\Builder;

class PhoneNumberQuery
{
    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public static function whereMatchesPhone(Builder $query, string $column, mixed $phone): Builder
    {
        $variants = PhoneNumber::matchVariants($phone);

        if ($variants === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn($column, $variants);
    }
}
