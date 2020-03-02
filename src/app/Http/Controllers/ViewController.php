<?php

namespace WrapLr\LaravelExplorer\App\Http\Controllers;

use Redirect;
use WrapLr\LaravelExplorer\App\Http\Controllers\BaseController;
use WrapLr\LaravelExplorer\App\Http\Helpers\Image;
use WrapLr\LaravelExplorer\App\WleFile;

class ViewController extends BaseController
{
    public function create($viewName, $fileName)
    {
        // get file id
        $fileId = base_convert(pathinfo($fileName, PATHINFO_FILENAME), 36, 10);

        // get file
        $file = WleFile::whereId($fileId)->first();

        // no db entry?
        if (!$file) {
            abort(404);
        }

        // get image views
        $imageViews = config('wlrle.image_views');

        // do we have this view?
        if (!$imageViews || !isset($imageViews[$viewName])) {
            return Redirect::to($file->storageUrl(), 302);
        }

        // check if it is a gdimage
        if (!$file->hasViews() || !(imagetypes() & Image::imageType($file->mime_type))) {
            return Redirect::to($file->storageUrl(), 302);
        }

        // make transformations
        $image = new Image();

        // open the file
        if (!$image->open($file->storagePath())) {
            return Redirect::to($file->storageUrl(), 302);
        }

        // make transform(s)
        foreach ($imageViews[$viewName] as $transName => $transParam) {
            switch ($transName) {
            case 'autorotate': {
                $image->autoRotate();

                break;
            }
            case 'resize': {
                if (isset($transParam['width']) && isset($transParam['height'])) {
                    $image->resize($transParam['width'], $transParam['height'], (isset($transParam['keep_ar']) ? $transParam['keep_ar'] : false));
                }

                break;
            }
            case 'sharpen': {
                if (isset($transParam['level'])) {
                    $image->sharpen($transParam['level']);
                }

                break;
            }
            case 'flip': {
                if (isset($transParam['orientation'])) {
                    $image->flip($transParam['orientation']);
                }

                break;
            }
            case 'crop': {
                if (isset($transParam['left']) && isset($transParam['top']) && isset($transParam['width']) && isset($transParam['height']) && isset($transParam['background_color'])) {
                    $image->crop($transParam['left'], $transParam['top'], $transParam['width'], $transParam['height'], $transParam['background_color']);
                }

                break;
            }
            case 'cut': {
                if (isset($transParam['aspect_ratio_x']) && isset($transParam['aspect_ratio_y']) && isset($transParam['horizontal_align']) && isset($transParam['vertical_align'])) {
                    $image->cut($transParam['aspect_ratio_x'], $transParam['aspect_ratio_y'], $transParam['horizontal_align'], $transParam['vertical_align']);
                }

                break;
            }
            case 'pad': {
                if (isset($transParam['aspect_ratio_x']) && isset($transParam['aspect_ratio_y']) && isset($transParam['horizontal_align']) && isset($transParam['vertical_align']) && isset($transParam['background_color'])) {
                    $image->pad($transParam['aspect_ratio_x'], $transParam['aspect_ratio_y'], $transParam['horizontal_align'], $transParam['vertical_align'], $transParam['background_color']);
                }

                break;
            }
            }
        }

        // get quality
        $quality = (isset($imageViews[$viewName]['quality']) ? $imageViews[$viewName]['quality'] : 100);

        // save image
        $saveResult = $image->save($file->viewPath($viewName), $quality);

        // save image
        $image->close();

        // redirect to view url or original url
        return ($saveResult ? Redirect::to($file->viewUrl($viewName), 302) : Redirect::to($file->storageUrl(), 302));
    }
}
