<?php

namespace WrapLr\LaravelExplorer\App;

use Storage;
use Illuminate\Database\Eloquent\Model;
use WrapLr\LaravelExplorer\App\Http\Helpers\Image;

class WleFile extends Model
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

    public function canView()
    {
        return in_array(Image::imageType($this->mime_type), config('wlrle.valid_image_view_types'));
    }

    public function viewPath($view_name)
    {
        return Storage::disk('public')->path(config('wlrle.upload_directory')).'/'.$view_name.'/'.$this->path.'/'.base_convert($this->id, 10, 36).($this->extension == '' ? '' : '.').$this->extension;
    }

    public function viewUrl($view_name)
    {
        if (!$view_name || !isset(config('wlrle.image_views')[$view_name])) {
            return $this->storageUrl();
        }

        if (config('wlrle.transform_path_to_name')) {
            return Storage::url(config('wlrle.upload_directory')).'/'.$view_name.'/'.str_replace('/', '', $this->path).base_convert($this->id, 10, 36).($this->extension == '' ? '' : '.').$this->extension;
        } else {
            return Storage::url(config('wlrle.upload_directory')).'/'.$view_name.'/'.$this->path.'/'.base_convert($this->id, 10, 36).($this->extension == '' ? '' : '.').$this->extension;
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
