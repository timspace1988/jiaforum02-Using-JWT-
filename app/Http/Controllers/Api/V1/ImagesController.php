<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Image;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Handlers\ImageUploadHandler;
use App\Http\Resources\ImageResource;
use App\Http\Requests\Api\V1\ImageRequest;
//use App\Http\Controllers\Api\V1\Controller;

class ImagesController extends Controller
{
    public function store(ImageRequest $request, ImageUploadHandler $uploader, Image $image){
        $user = $request->user();

        $size = $request->type == 'avatar' ? 416 : 1024;
        $result = $uploader->saveToS3($request->image, Str::plural($request->type), $user->id, $size);
        $image->path = $result['path'];
        $image->type = $request->type;
        $image->user_id = $user->id;
        $image->save();

        return new ImageResource($image);
    }
}
