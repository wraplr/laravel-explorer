<?php

Route::group(['prefix' => config('wlrle.url_prefix'), 'middleware' => config('wlrle.middleware'), 'namespace' => '\WrapLr\LaravelExplorer\App\Http\Controllers'], function () {
    Route::get('/', 'ExplorerController@show');
    Route::get('/refresh', 'ExplorerController@refresh');

    Route::group(['prefix' => 'directory'], function () {
        Route::get('/{id}/change/{request}', 'DirectoryController@change');
        Route::post('/create', 'DirectoryController@create');
        Route::patch('/{id}/rename', 'DirectoryController@rename');
    });

    Route::group(['prefix' => 'file'], function () {
        Route::post('/upload', 'FileController@upload');
        Route::patch('/{id}/rename', 'FileController@rename');
    });

    Route::group(['prefix' => 'item'], function () {
        Route::post('/copy', 'ItemController@copy');
        Route::post('/cut', 'ItemController@cut');
        Route::post('/paste', 'ItemController@paste');
        Route::delete('/delete', 'ItemController@delete');
    });

    Route::group(['prefix' => 'view'], function () {
        Route::get('/{view}/{file}', 'ViewController@create');
    });
});
