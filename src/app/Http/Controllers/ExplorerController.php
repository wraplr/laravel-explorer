<?php

namespace WrapLr\LaravelExplorer\App\Http\Controllers;

use Session;
use WrapLr\LaravelExplorer\App\Http\Controllers\BaseController;
use WrapLr\LaravelExplorer\App\WlrleDirectory;
use WrapLr\LaravelExplorer\App\WlrleFile;

class ExplorerController extends BaseController
{
    public function show()
    {
        // current working directory
        $currentDirectory = $this->getCurrentWorkingDirectory();

        if (!$currentDirectory) {
            return response()->json([
                'message' => 'Wrong current working directory!',
            ], 400);
        }

        // init back list
        if (!Session::has(config('wlrle.url_prefix').'.back')) {
            Session::put(config('wlrle.url_prefix').'.back', [$currentDirectory->id]);
        }

        // init forward list
        if (!Session::has(config('wlrle.url_prefix').'.forward')) {
            Session::put(config('wlrle.url_prefix').'.forward', []);
        }

        // paste count to enable/disable paste button
        $paste = $this->getPasteCount();

        // breadcrumb dirs
        $breadcrumbDirs = $this->getBreadcrumbDirs($currentDirectory);

        // back list
        $backList = Session::get(config('wlrle.url_prefix').'.back');

        // back is the one before the last
        $back = (count($backList) > 1 ? $backList[count($backList) - 2] : 0);

        // forward list
        $forwardList = Session::get(config('wlrle.url_prefix').'.forward');

        // forward is the last one
        $forward = (count($forwardList) > 0 ? $forwardList[count($forwardList) - 1] : 0);

        // up is the one before the last
        $up = (count($breadcrumbDirs) > 1 ? $breadcrumbDirs[count($breadcrumbDirs) - 2]->id : 0);

        // directory list
        $directoryList = $currentDirectory->subdirectories;

        // file list
        $fileList = $currentDirectory->files;

        return response()->json([
            'content' => view('laravel-explorer::index', compact('paste', 'back', 'forward', 'up', 'breadcrumbDirs', 'directoryList', 'fileList'))->render(),
            'fileInfoList' => $this->toFileInfoList($fileList),
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
            'fileInfoList' => $this->toFileInfoList($fileList),
        ], 200);
    }
}
