<?php

namespace Wraplr\LaravelExplorer\App;

use Storage;
use Illuminate\Database\Eloquent\Model;
use Wraplr\LaravelExplorer\App\Helpers\Image;

class WlrleFile extends Model
{
    protected $fillable = [
        'name',
        'path',
        'file',
        'extension',
        'mime_type',
        'size',
    ];

    // store file extensions in lowercase
    public function setExtensionAttribute($value)
    {
        $this->attributes['extension'] = strtolower($value);
    }

    // other methods
    public function isImage()
    {
        return (Image::imageType($this->mime_type) > 0);
    }

    public function hasViews()
    {
        return in_array(Image::imageType($this->mime_type), config('wlrle.valid_view_image_types'));
    }

    public function viewPath($viewName)
    {
        return Storage::disk('public')->path(config('wlrle.storage_directory')).'/'.$viewName.'/'.$this->path.'/'.$this->file.($this->extension == '' ? '' : '.').$this->extension;
    }

    public function viewUrl($viewName)
    {
        if (!$viewName || !isset(config('wlrle.image_views')[$viewName])) {
            return $this->storageUrl();
        }

        if (config('wlrle.transform_path_to_name')) {
            return Storage::url(config('wlrle.storage_directory')).'/'.$viewName.'/'.str_replace('/', '', $this->path).$this->file.($this->extension == '' ? '' : '.').$this->extension;
        } else {
            return Storage::url(config('wlrle.storage_directory')).'/'.$viewName.'/'.$this->path.'/'.$this->file.($this->extension == '' ? '' : '.').$this->extension;
        }
    }

    public function storagePath()
    {
        return Storage::disk('public')->path(config('wlrle.storage_directory')).'/'.$this->path.'/'.$this->file.($this->extension == '' ? '' : '.').$this->extension;
    }

    public function storageUrl()
    {
        if (config('wlrle.transform_path_to_name')) {
            return Storage::url(config('wlrle.storage_directory')).'/'.str_replace('/', '', $this->path).$this->file.($this->extension == '' ? '' : '.').$this->extension;
        } else {
            return Storage::url(config('wlrle.storage_directory')).'/'.$this->path.'/'.$this->file.($this->extension == '' ? '' : '.').$this->extension;
        }
    }
}
    