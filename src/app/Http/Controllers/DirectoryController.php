<?php

namespace WrapLr\LaravelExplorer\App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use WrapLr\LaravelExplorer\App\Http\Controllers\BaseController;
use WrapLr\LaravelExplorer\App\WlrleDirectory;

class DirectoryController extends BaseController
{
    public function change($id, $request)
    {
        $currentDirectory = WlrleDirectory::whereId($id)->first();

        if (!$currentDirectory) {
            return response()->json([
                'message' => 'Wrong directory id ('.$id.')!',
            ], 400);
        }

        // get current back list
        $backList = Session::get(config('wlrle.url_prefix').'.back');

        // get current forward list
        $forwardList = Session::get(config('wlrle.url_prefix').'.forward');

        // update forward and back list
        if ($request == 'back') {
            $forwardList[] = array_pop($backList);
        } else {
            if ($request == 'forward') {
                array_pop($forwardList);
            } else {
                $forwardList = [];
            }

            // prevent to add the same dir again
            if (count($backList) == 0 || $backList[count($backList) - 1] != $currentDirectory->id) {
                $backList[] = $currentDirectory->id;
            }
        }

        // maximize back history count in 10 items
        while (count($backList) > 10) {
            array_shift($backList);
        }

        // update back list
        Session::put(config('wlrle.url_prefix').'.back', $backList);

        // update forward list
        Session::put(config('wlrle.url_prefix').'.forward', $forwardList);

        // save current working directory
        Session::put(config('wlrle.url_prefix').'.cwd', $currentDirectory->id);

        // breadcrumb
        $breadcrumbDirs = $this->getBreadcrumbDirs($currentDirectory);

        // directory list
        $directoryList = $currentDirectory->subdirectories;

        // file list
        $fileList = $currentDirectory->files;

        return response()->json([
            'back' => (count($backList) > 1 ? $backList[count($backList) - 2] : 0),
            'forward' => (count($forwardList) > 0 ? $forwardList[count($forwardList) - 1] : 0),
            'up' => ($currentDirectory->parent ? $currentDirectory->parent->id : 0),
            'content' => view('laravel-explorer::items', compact('directoryList', 'fileList'))->render(),
            'breadcrumb' => view('laravel-explorer::bread', compact('breadcrumbDirs'))->render(),
            'fileInfoList' => $this->toFileInfoList($fileList),
        ], 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        $currentDirectory = $this->getCurrentWorkingDirectory();

        if (!$currentDirectory) {
            return response()->json([
                'message' => 'Wrong current working directory!',
            ], 400);
        }

        // check if directory exists on the same level => dirname => dirname (x)
        $directoryName = $request->name;

        // get all names
        $subdirectories = $currentDirectory->subdirectories->pluck('name')->all();

        // rename it, if any
        $directoryIndex = 0;
        while (in_array($directoryName, $subdirectories)) {
            $directoryName = $request->name.' ('.(++$directoryIndex).')';
        }

        // create new directory
        $currentDirectory->subdirectories()->save(new WlrleDirectory([
            'name' => $directoryName,
        ]));

        // reload subdirectories ('cause it was lazy loaded before)
        $currentDirectory->load('subdirectories');

        // directory list
        $directoryList = $currentDirectory->subdirectories;

        // file list
        $fileList = $currentDirectory->files;

        return response()->json([
            'content' => view('laravel-explorer::items', compact('directoryList', 'fileList'))->render(),
        ], 200);
    }

    public function rename(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        $currentDirectory = $this->getCurrentWorkingDirectory();

        if (!$currentDirectory) {
            return response()->json([
                'message' => 'Wrong current working directory!',
            ], 400);
        }

        // get directory to change
        $directory = WlrleDirectory::whereId($id)->first();

        if (!$directory) {
            return response()->json([
                'message' => 'Wrong directory selected!',
            ], 400);
        }

        // get all names
        $subdirectories = $currentDirectory->subdirectories->where('id', '!=', $directory->id)->pluck('name')->all();

        // name already exists (even if it's own name)
        if (in_array($request->name, $subdirectories)) {
            return response()->json([
                'name' => $directory->name,
                'message' => 'Could not rename directory from '.$directory->name.' to '.$request->name,
            ], 400);
        }

        // set name
        $directory->name = $request->name;

        // save it
        $directory->save();

        // success
        return response()->json([
            'name' => $directory->name,
        ], 200);
    }
}
