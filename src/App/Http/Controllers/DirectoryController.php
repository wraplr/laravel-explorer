<?php

namespace Wraplr\LaravelExplorer\App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Wraplr\LaravelExplorer\App\Http\Controllers\BaseController;
use Wraplr\LaravelExplorer\App\WlrleDirectory;

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
            'content' => view('wlrle::items', compact('directoryList', 'fileList'))->render(),
            'breadcrumb' => view('wlrle::bread', compact('breadcrumbDirs'))->render(),
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

        // check for unique directory name
        $directoryName = $this->getUniqueDirectoryName($currentDirectory, $request->name);

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
            'content' => view('wlrle::items', compact('directoryList', 'fileList'))->render(),
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

        // count directories with the same name
        $sameCount = $currentDirectory->subdirectories()->where('id', '!=', $directory->id)->whereName($request->name)->count();

        // name already exists (even if it's own name)
        if ($sameCount > 0) {
            return response()->json([
                'message' => 'Could not rename directory from <strong>'.$directory->name.'</strong> to <strong>'.$request->name.'</strong>. The name already exists.',
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

    public function path($id)
    {
        // get current working directory
        $currentDirectory = $this->getCurrentWorkingDirectory();

        // check if they are the same
        if ($currentDirectory->id == $id) {
            return response()->json([], 200);
        }

        // get directory by id
        $directory = WlrleDirectory::whereId($id)->first();

        // error
        if (!$directory) {
            return response()->json([
                'message' => 'Could not get directory path from <strong>id = '.$id.'</strong>.',
            ], 400);
        }

        // success
        return response()->json([
            'message' => 'Would you like to go to <strong>'.$this->getDirectoryPath($directory).'</strong>?<button type="button" class="btn btn-primary btn-sm ml-3" data-id="'.$id.'" data-request="goto">Yes</button>',
        ], 200);
    }
}
