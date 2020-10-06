<?php

namespace WrapLr\LaravelExplorer\App;

use Illuminate\Database\Eloquent\Model;

class WlrleDirectory extends Model
{
    protected $fillable = [
        'name',
    ];

    public function subdirectories()
    {
        return $this->hasMany(static::class, 'directory_id')->orderBy('name', 'asc');
    }

    public function files()
    {
        return $this->hasMany('WrapLr\LaravelExplorer\App\WlrleFile', 'directory_id')->orderBy('name', 'asc');
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'directory_id');
    }
}
