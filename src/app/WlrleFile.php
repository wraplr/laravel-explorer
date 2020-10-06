<?php

namespace WrapLr\LaravelExplorer\App;

use Storage;
use Illuminate\Database\Eloquent\Model;
use WrapLr\LaravelExplorer\App\Http\Helpers\Image;

class WlrleFile extends Model
{
    protected $fillable = [
        'name',
        'mime_type',
        'path',
        'extension',
        'size',
    ];

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
        return Storage::disk('public')->path(config('wlrle.upload_directory')).'/'.$viewName.'/'.$this->path.'/'.base_convert($this->id, 10, 36).($this->extension == '' ? '' : '.').$this->extension;
    }

    public function viewUrl($viewName)
    {
        if (!$viewName || !isset(config('wlrle.image_views')[$viewName])) {
            return $this->storageUrl();
        }

        if (config('wlrle.transform_path_to_name')) {
            return Storage::url(config('wlrle.upload_directory')).'/'.$viewName.'/'.str_replace('/', '', $this->path).base_convert($this->id, 10, 36).($this->extension == '' ? '' : '.').$this->extension;
        } else {
            return Storage::url(config('wlrle.upload_directory')).'/'.$viewName.'/'.$this->path.'/'.base_convert($this->id, 10, 36).($this->extension == '' ? '' : '.').$this->extension;
        }
    }

    public function storagePath()
    {
        return Storage::disk('public')->path(config('wlrle.upload_directory')).'/'.$this->path.'/'.base_convert($this->id, 10, 36).($this->extension == '' ? '' : '.').$this->extension;
    }

    public function storageUrl()
    {
        if (config('wlrle.transform_path_to_name')) {
            return Storage::url(config('wlrle.upload_directory')).'/'.str_replace('/', '', $this->path).base_convert($this->id, 10, 36).($this->extension == '' ? '' : '.').$this->extension;
        } else {
            return Storage::url(config('wlrle.upload_directory')).'/'.$this->path.'/'.base_convert($this->id, 10, 36).($this->extension == '' ? '' : '.').$this->extension;
        }
    }
}
