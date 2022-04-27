<?php

namespace App\Traits;

use App\Models\Image;
use Illuminate\Database\Eloquent\Collection;

/**
 * Relationships
 * @property-read Image $image
 * @property-read Image|Collection[] $images
 * @property-read Image $featureImage
 * @property-read Image $favicon
 * @property-read Image $logo
 */
trait ImageableTrait
{
    /**
     * @return mixed
     */
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable')->latestOfMany();
    }

    /**
     * @return mixed
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * @return mixed
     */
    public function featureImage()
    {
        return $this->morphOne(Image::class, 'imageable')->where('is_featured', '=', true);
    }

    /**
     * @return mixed
     */
    public function favicon()
    {
        return $this->morphOne(Image::class, 'imageable')->where('name', 'LIKE', '%favicon%');
    }

    /**
     * @return mixed
     */
    public function logo()
    {
        return $this->morphOne(Image::class, 'imageable')->where('name', 'LIKE', '%logo%');
    }
}
