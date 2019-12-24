<?php

namespace App\Handlers;

use Illuminate\Support\Str;
use Image;

class ImageUploadHandler{
    //only allow to upload images with following extensions
    protected $allowed_ext = ['png', 'jpg', 'gif', 'jpeg'];

    public function save($file, $folder, $file_prefix, $max_width = false){
        //build folder structure
        $folder_name = "upload/images/$folder/" . date("Ym/d", time());

        //public_path() will get public folder's physical address
        $upload_path = public_path() . '/' . $folder_name;

        //sometime, the file's name from clipping board has no extension name, we need to add png to it
        $extension = strtolower($file->getClientOriginalExtension()) ?:'png';

        //add a prefix to filename, the prefix could be related model's id
        $filename = $file_prefix . '_' . time() . '_' . Str::random(10) . '.' . $extension;

        //if the uploaded file is not a image, will terminate the operation
        if(! in_array($extension, $this->allowed_ext)){
            return false;
        }

        //move the image to the target storage directory
        $file->move($upload_path, $filename);

        //if we have max_width limit, we need to resize the image
        if($max_width && $extension !='gif'){
            $this->reduceSize($upload_path . '/' . $filename, $max_width);
        }

        //config('app.url') is public folder address on server, public_path() is the public folder physical address on local computer, e.g. /home/vagrant/Code/jiaforum/public
        return ['path' => config('app.url') . "/$folder_name/$filename"];
    }

    public function reduceSize($file_path, $max_width){
        //Get the instance
        $image = Image::make($file_path);

        //adjust the image size resize($width, $height, Closure $callback = null)
        $image->resize($max_width, null, function($constraint){
            //set $max_width as width and resize height in same sratio
            $constraint->aspectRatio();

            //Avoid image being resized bigger if the image width is smaller than max_width
            $constraint->upsize();
        });

        //Save the resized image
        $image->save();
    }
}
