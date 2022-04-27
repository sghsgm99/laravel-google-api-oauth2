<?php

namespace App\Traits;

use App\Models\Image;
use App\Models\Services\ImageService;
use Illuminate\Http\UploadedFile;

trait ImageModelServiceTrait
{
    public function attachImage(UploadedFile $image, ?string $filename)
    {
        if (!$filename) {
            $name = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
            $extension = $image->getClientOriginalExtension();
            $filename = "{$name}.{$extension}";
        }

        return ImageService::create($this->model, $image, $filename);
    }

    public function detachImage(int $image_id)
    {
        $image = $this->model->image->whereId($image_id)->first();

        if (!$image) {
            return true;
        }

        $this->deleteImage($image);
    }

    public function deleteImage(Image $image, string $dir = null)
    {
        $this->model->FileServiceFactory($dir)->deleteFile($image->name);

        // force delete coz we also remove the physical file
        $image->Service()->forceDelete();
    }

    public function markAsFeatured(int $image_id)
    {
        foreach ($this->model->images as $image) {
            if ($image->id === $image_id) {
                $image->Service()->setAsFeatured(true);
                continue;
            }

            $image->Service()->setAsFeatured(false);
        }
    }
}
