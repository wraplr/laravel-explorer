<?php

namespace WrapLr\LaravelExplorer\App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use WrapLr\LaravelExplorer\App\Http\Controllers\BaseController;
use WrapLr\LaravelExplorer\App\WleDirectory;

class DirectoryController extends BaseController
{
    public function change($id)
    {
        $currentDirectory = WleDirectory::whereId($id)->first();

        if (!$currentDirectory) {
            return response()->json([
                'message' => 'Wrong directory id ('.$id.')!',
            ], 400);
        }

        // change directory in the session
        Session::put('laravel-explorer.cwd', $currentDirectory->id);

        // breadcrumb
        $breadcrumbDirs = $this->getBreadcrumbDirs($currentDirectory);

        // directory list
        $directoryList = $currentDirectory->subdirectories;

        // file list
        $fileList = $currentDirectory->files;

        return response()->json([
            'parent' => ($currentDirectory->parent ? $currentDirectory->parent->id : 0),
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
        $currentDirectory->subdirectories()->save(new WleDirectory([
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
        $directory = WleDirectory::whereId($id)->first();

        if (!$directory) {
            return response()->json([
                'message' => 'Wrong directory selected!',
            ], 400);
        }

        // get all names
        $subdirectories = $currentDirectory->subdirectories->pluck('name')->all();

        // name already exists (even if it's own name)
        if (in_array($request->name, $subdirectories)) {
            return response()->json([
                'name' => $directory->name,
            ], 200);
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

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        // delete directories
        foreach ($request->items as $directoryId) {
            // get selected directory
            $directory = WleDirectory::whereId($directoryId)->first();

            // delete all files
            if ($directory) {
                foreach ($this->getSubdirectories($directory) as $subdirectory) {
                    foreach ($subdirectory->files as $file) {
                        if ($file) {
                            // delete file from storage
                            $this->deleteFile($file->storagePath());

                            // delete file's all views
                            foreach (config('wlrle.image_views') as $viewName => $viewTrans) {
                                $this->deleteFile($file->viewPath($viewName));
                            }

                            // remove file from database
                            $file->delete();
                        }
                    }
                }
            }

            // delete directory and all its subdirectories
            $directory->delete();
        }

        return response()->json([], 200);
    }

    private function getSubdirectories($directory, $subdirectories = [])
    {
        if ($directory->directory_id) {
            $subdirectories[] = $directory;
        }

        foreach ($directory->subdirectories as $subdirectory) {
            $subdirectories = $this->getSubdirectories($subdirectory, $subdirectories);
        }

        return $subdirectories;
    }
}
