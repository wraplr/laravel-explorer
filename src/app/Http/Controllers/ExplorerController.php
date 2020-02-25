<?php

namespace WrapLr\LaravelExplorer\App\Http\Controllers;

use WrapLr\LaravelExplorer\App\Http\Controllers\BaseController;
use WrapLr\LaravelExplorer\App\WleDirectory;
use WrapLr\LaravelExplorer\App\WleFile;

class ExplorerController extends BaseController
{
    public function show()
    {
        $currentDirectory = $this->getCurrentWorkingDirectory();

        if (!$currentDirectory) {
            return response()->json([
                'message' => 'Wrong current working directory!',
            ], 400);
        }

        // breadcrumb
        $breadcrumbDirs = $this->getBreadcrumbDirs($currentDirectory);

        // directory list
        $directoryList = $currentDirectory->subdirectories;

        // file list
        $fileList = $currentDirectory->files;

        return response()->json([
            'content' => view('laravel-explorer::index', compact('breadcrumbDirs', 'directoryList', 'fileList'))->render(),
        ], 200);
    }

    public function refresh()
    {
        $currentDirectory = $this->getCurrentWorkingDirectory();

        if (!$currentDirectory) {
            return response()->json([
                'message' => 'Wrong current working directory!',
            ], 400);
        }

        // directory list
        $directoryList = $currentDirectory->subdirectories;

        // file list
        $fileList = $currentDirectory->files;

        return response()->json([
            'content' => view('laravel-explorer::items', compact('directoryList', 'fileList'))->render(),
        ], 200);
    }
}
