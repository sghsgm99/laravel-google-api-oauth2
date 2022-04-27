<?php

namespace App\Traits;

use App\Models\Seo;

trait SeoableTrait
{
    /**
     * @return mixed
     */
    public function seo()
    {
        return $this->morphOne(Seo::class, 'seoable')->latestOfMany();
    }

}
