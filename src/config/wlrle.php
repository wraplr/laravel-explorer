<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Url Prefix
    |--------------------------------------------------------------------------
    |
    | The url to this package. Change it if necessary.
    |
    */

    'url_prefix' => 'laravel-explorer',


    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middlewares which should be applied to all package routes, except the
    | image view creator.
    |
    */

    'middleware' => ['web'],


    /*
    |--------------------------------------------------------------------------
    | Upload Directory
    |--------------------------------------------------------------------------
    |
    | The folder use to store files, relative to storage/app/public.
    | Don't forget to run 'php artisan storage:link'.
    |
    */

    'upload_directory' => 'files',


    /*
    |--------------------------------------------------------------------------
    | Transform Path To Name
    |--------------------------------------------------------------------------
    |
    | If it set to true, the system will transform the date path to a filename,
    | like: storage/files/2019/12/17/image.jpg => storage/files/20191217image.jpg
    | Don't forget to add the required rewrite rule to your webserver.
    |
    */

    'transform_path_to_name' => true,


    /*
    |--------------------------------------------------------------------------
    | Valid File Mime Types
    |--------------------------------------------------------------------------
    |
    | The files with the following mime types will be uploaded.
    | See mime_content_type for more information.
    |
    */

    'valid_file_mime_types' => [
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/x-ms-bmp',
        'application/pdf',
        'text/plain',
    ],


    /*
    |--------------------------------------------------------------------------
    | Default Image View
    |--------------------------------------------------------------------------
    |
    | The default image view in the main UI.
    |
    */

    'default_image_view' => 'thumbnail',


    /*
    |--------------------------------------------------------------------------
    | Valid View Image Types
    |--------------------------------------------------------------------------
    |
    | The image views will be generated only for these image types. Check your
    | php gd image library for supported image types.
    |
    */

    'valid_view_image_types' => [
        IMG_GIF,
        IMG_JPG,
        IMG_PNG,
        IMG_WEBP,
        IMG_BMP,
    ],


    /*
    |--------------------------------------------------------------------------
    | Image Views
    |--------------------------------------------------------------------------
    |
    | Create different views for uploaded images. It requires php gd extension.
    | Larvel explorer will autonegerate the view files if you add a redirect rule
    | to /{url_prefix}/view/{view_name}/{file}.
    |
    | The following elementary image transformations can be applied:
    | @method autorotate - rotates/flips the image using jpegs's exif::Orientation. No paremters available.
    | @method resize - resizes the image.
    |   @param width (integer): the width in pixels, if 0, will be calcuated by heigth and aspect ratio.
    |   @param height (integer): the height in pixels, if 0, will be calcuated by width and aspect ratio.
    |   @param keep_ar (boolean, optional): if it's true, then the width or height will be recalculated by aspect ratio. Note that, if width or height is 0, then this parameter will be ignored.
    | @method sharpen - sharpens the image.
    |   @param level (integer): 0 - no sharpen, 1 - soft, 2 - bright.
    | @method flip - flips the image.
    |   @param orientation (integer): 0x00 - no flip, 0x01 - flip horizontal, 0x02 - flip vertical. Multiple params can be used at the same time, i.e.: 0x01 | 0x02
    | @method crop - crops the image.
    |   @param left (integer): left offset, in pixels.
    |   @param top (integer): top offset, in pixels.
    |   @param width (integer): the width in pixels.
    |   @param height (integer): the height in pixels.
    |   @param background_color (integer): the background color if the algorithm has to fill the image. The format is: 0x000000.
    | @method cut - cuts the image.
    |   @param aspect_ratio_x (integer): aspect ratio x.
    |   @param aspect_ratio_y (integer): aspect ratio y.
    |   @param horizontal_align (integer): where to align the image horizontally, if needed. Values: 1 - left, 2 - center, 3 - right.
    |   @param vertical_align (integer): where to align the image vertically, if needed. Values: 1 - top, 2 - middle, 3 - botton.
    | @method pad - pads the image.
    |   @param aspect_ratio_x (integer): aspect ratio x.
    |   @param aspect_ratio_y (integer): aspect ratio y.
    |   @param horizontal_align (integer): where to align the image horizontally, if needed. Values: 1 - left, 2 - center, 3 - right.
    |   @param vertical_align (integer): where to align the image vertically, if needed. Values: 1 - top, 2 - middle, 3 - botton.
    |   @param background_color (integer): the background color, if original aspect ratio differs from the new aspect ratio. The format is: 0x000000.
    | @param quality - the new image quality, in per cent. It applies only for jpeg images.
    |
    */

    'image_views' => [
        'thumbnail' => [
            'autorotate' => [],
            'resize' => [
                'width' => 512,
                'height' => 512,
                'keep_ar' => true,
            ],
            'quality' => 80,
        ],
    ],
];
