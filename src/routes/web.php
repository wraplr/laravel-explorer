<?php

Route::group(['prefix' => config('wlrle.url_prefix'), 'middleware' => config('wlrle.middleware'), 'namespace' => '\WrapLr\LaravelExplorer\App\Http\Controllers'], function () {
    Route::get('/', 'ExplorerController@show')->name('wraplr.laravel-explorer.show');
    Route::get('/refresh', 'ExplorerController@refresh')->name('wraplr.laravel-explorer.refresh');

    Route::group(['prefix' => 'directory'], function () {
        Route::get('/{id}/change', 'DirectoryController@change')->name('wraplr.laravel-explorer.directory.change');
        Route::post('/create', 'DirectoryController@create')->name('wraplr.laravel-explorer.directory.create');
        Route::patch('/{id}/rename', 'DirectoryController@rename')->name('wraplr.laravel-explorer.directory.rename');
        Route::delete('/delete', 'DirectoryController@delete')->name('wraplr.laravel-explorer.directory.delete');
    });

    Route::group(['prefix' => 'file'], function () {
        Route::post('/upload', 'FileController@upload')->name('wraplr.laravel-explorer.file.upload');
        Route::patch('/{id}/rename', 'FileController@rename')->name('wraplr.laravel-explorer.file.rename');
        Route::delete('/delete', 'FileController@delete')->name('wraplr.laravel-explorer.file.delete');
    });

    Route::group(['prefix' => 'view'], function () {
        Route::get('/{view}/{file}', 'ViewController@create')->name('wraplr.laravel-explorer.view.create');
    });
});
