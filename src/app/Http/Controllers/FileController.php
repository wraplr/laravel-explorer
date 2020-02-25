<?php

namespace WrapLr\LaravelExplorer\App\Http\Controllers;

use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use WrapLr\LaravelExplorer\App\Http\Controllers\BaseController;
use WrapLr\LaravelExplorer\App\WleFile;

class FileController extends BaseController
{
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required',
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

        // upload path, relative to base_directory/upload_directory
        $path = Carbon::now()->format('Y/m/d');

        // base directory
        $base = Storage::disk('public')->path(config('wle.upload_directory'));

        // full path
        $full = $base.'/'.$path;

        // create it, if any
        if (!is_dir($full)) {
            mkdir($full, 0777, true);
        }

        // get uploaded file
        $files = $request->file('file');

        // save uploaded files
        foreach ($files as $file) {
            // check for unique name
            $fileName = $file->getClientOriginalName();

            // get all names
            $fileNames = $currentDirectory->files->pluck('name')->all();

            // rename it, if any
            $fileIndex = 0;
            while (in_array($fileName, $fileNames)) {
                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).' ('.(++$fileIndex).')'.(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION) == '' ? '' : '.').pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            }

            // create model
            $wleFile = new WleFile([
                'name' => $fileName,
                'mime_type' => '', // will get it after move
                'path' => $path,
                'extension' => strtolower($file->getClientOriginalExtension()),
                'size' => $file->getSize(),
            ]);

            // create new file in database
            $currentDirectory->files()->save($wleFile);

            // move file to path
            $file->move($full, base_convert($wleFile->id, 10, 36).($file->getClientOriginalExtension() == '' ? '' : '.').$file->getClientOriginalExtension());

            // set mime type
            $wleFile->mime_type = mime_content_type($wleFile->storagePath());

            // update it
            $wleFile->save();
        }

        // return success
        return response()->json([], 200);
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

        // get change directory
        $file = WleFile::whereId($id)->first();

        if (!$file) {
            return response()->json([
                'message' => 'Wrong file selected!',
            ], 400);
        }

        // get all names
        $files = $currentDirectory->files->pluck('name')->all();

        // name already exists (even if it's own name)
        if (in_array($request->name, $files)) {
            return response()->json([
                'name' => $file->name,
            ], 200);
        }

        // set name
        $file->name = $request->name;

        // save it
        $file->save();

        // success
        return response()->json([
            'name' => $file->name,
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

        // delete files
        foreach ($request->items as $fileId) {
            // get selected file
            $file = WleFile::whereId($fileId)->first();

            if ($file) {
                // delete file from storage
                $this->deleteFile($file->storagePath());

                // delete file's all views
                foreach (config('wle.image_views') as $viewName => $viewTrans) {
                    $this->deleteFile($file->viewPath($viewName));
                }

                // remove file from database
                $file->delete();
            }
        }

        // success
        return response()->json([], 200);
    }
}
