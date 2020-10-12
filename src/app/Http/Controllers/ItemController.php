<?php

namespace WrapLr\LaravelExplorer\App\Http\Controllers;

use Session;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use WrapLr\LaravelExplorer\App\Http\Controllers\BaseController;
use WrapLr\LaravelExplorer\App\WlrleDirectory;
use WrapLr\LaravelExplorer\App\WlrleFile;

class ItemController extends BaseController
{
    public function copy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        // update copy directories list
        Session::put(config('wlrle.url_prefix').'.copy.directories', (isset($request->items['directories']) ? $request->items['directories'] : []));

        // update copy files list
        Session::put(config('wlrle.url_prefix').'.copy.files', (isset($request->items['files']) ? $request->items['files'] : []));

        // reset cut directories list
        Session::put(config('wlrle.url_prefix').'.cut.directories', []);

        // reset cut files list
        Session::put(config('wlrle.url_prefix').'.cut.files', []);

        return response()->json([
            'paste' => $this->getPasteCount(),
        ], 200);
    }

    public function cut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        // reset copy directories list
        Session::put(config('wlrle.url_prefix').'.copy.directories', []);

        // reset copy files list
        Session::put(config('wlrle.url_prefix').'.copy.files', []);

        // update cut directories list
        Session::put(config('wlrle.url_prefix').'.cut.directories', (isset($request->items['directories']) ? $request->items['directories'] : []));

        // update cut files list
        Session::put(config('wlrle.url_prefix').'.cut.files', (isset($request->items['files']) ? $request->items['files'] : []));

        return response()->json([
            'paste' => $this->getPasteCount(),
        ], 200);
    }

    public function paste()
    {
        $currentDirectory = $this->getCurrentWorkingDirectory();

        if (!$currentDirectory) {
            return response()->json([
                'message' => 'Wrong current working directory!',
            ], 400);
        }

        // check paste count
        if (!$this->getPasteCount()) {
            return response()->json([
                'message' => 'Nothing to paste!',
            ], 400);
        }

        // base directory
        $base = Storage::disk('public')->path(config('wlrle.upload_directory'));

        // get copy list
        $copy = Session::get(config('wlrle.url_prefix').'.copy', []);

        // get cut list
        $cut = Session::get(config('wlrle.url_prefix').'.cut', []);

        // error handling
        $errors = [];

        // copy directories
        if (isset($copy['directories'])) {
            // copy directories
            foreach ($copy['directories'] as $directoryId) {
                // get selected directory
                $directory = WlrleDirectory::whereId($directoryId)->first();

                // get breadcrumb id's
                $breadcrumbIds = array_slice(array_column($this->getBreadcrumbDirs($currentDirectory), 'id'), 1);

                if ($directory && !in_array($directory->id, $breadcrumbIds)) {
                    // create model
                    $wlrleDirectory = new WlrleDirectory([
                        'name' => $this->getUniqueDirectoryName($currentDirectory, $directory->name),
                    ]);

                    // create new directory in database
                    $currentDirectory->subdirectories()->save($wlrleDirectory);

                    // copy files (note that we don't copy sudirectories)
                    foreach ($directory->files as $file) {
                        // phisical path, relative to base_directory/upload_directory
                        $path = Carbon::now()->format('Y/m/d');

                        // create model
                        $wlrleFile = new WlrleFile([
                            'name' => $this->getUniqueFileName($wlrleDirectory, $file->name),
                            'mime_type' => $file->mime_type,
                            'path' => $path,
                            'extension' => $file->extension,
                            'size' => $file->size,
                        ]);

                        // create new file in database
                        $wlrleDirectory->files()->save($wlrleFile);

                        // new phisical full path
                        $full = $base.'/'.$path;

                        // create it, if any
                        if (!is_dir($full)) {
                            mkdir($full, 0777, true);
                        }

                        // new storage path
                        $storagePath = $full.'/'.base_convert($wlrleFile->id, 10, 36).($file->extension == '' ? '' : '.').$file->extension;

                        // copy file phisically
                        if (!@copy($file->storagePath(), $storagePath)) {
                            // copy error
                            $wlrleFile->delete();

                            // add error
                            $errors[] = 'Could not copy file from <strong>'.$file->storagePath().'</strong> to <strong>'.$storagePath.'</strong>. System error.';
                        }
                    }
                } else {
                    // add error
                    $errors[] = 'Could not copy directory from <strong>'.implode('', array_map(function($directory) { return ($directory->directory_id == null ? '' : '/'.$directory->name); }, $this->getBreadcrumbDirs($directory))).'</strong> to <strong>'.implode('', array_map(function($directory) { return ($directory->directory_id == null ? '' : '/'.$directory->name); }, $this->getBreadcrumbDirs($currentDirectory))).'/'.$directory->name.'</strong>';
                }
            }
        }

        // copy files
        if (isset($copy['files'])) {
            // copy files
            foreach ($copy['files'] as $fileId) {
                // get selected file
                $file = WlrleFile::whereId($fileId)->first();

                if ($file) {
                    // phisical path, relative to base_directory/upload_directory
                    $path = Carbon::now()->format('Y/m/d');

                    // create model
                    $wlrleFile = new WlrleFile([
                        'name' => $this->getUniqueFileName($currentDirectory, $file->name),
                        'mime_type' => $file->mime_type,
                        'path' => $path,
                        'extension' => $file->extension,
                        'size' => $file->size,
                    ]);

                    // create new file in database
                    $currentDirectory->files()->save($wlrleFile);

                    // new phisical full path
                    $full = $base.'/'.$path;

                    // create it, if any
                    if (!is_dir($full)) {
                        mkdir($full, 0777, true);
                    }

                    // new storage path
                    $storagePath = $full.'/'.base_convert($wlrleFile->id, 10, 36).($file->extension == '' ? '' : '.').$file->extension;

                    // copy file phisically
                    if (!@copy($file->storagePath(), $storagePath)) {
                        // copy error
                        $wlrleFile->delete();

                        // add error
                        $errors[] = 'Could not copy file from <strong>'.$file->storagePath().'</strong> to <strong>'.$storagePath.'</strong>. System error.';
                    }
                }
            }
        }

        // cut directories
        if (isset($cut['directories'])) {
            // move directories
            foreach ($cut['directories'] as $directoryId) {
                // get selected directory
                $directory = WlrleDirectory::whereId($directoryId)->first();

                // get breadcrumb id's
                $breadcrumbIds = array_column($this->getBreadcrumbDirs($currentDirectory), 'id');

                if ($directory && $directory->directory_id != $currentDirectory->id && !in_array($directory->id, $breadcrumbIds)) {
                    // set parent
                    $directory->directory_id = $currentDirectory->id;
                    $directory->name = $this->getUniqueDirectoryName($currentDirectory, $directory->name);

                    // save it
                    $directory->save();
                } else {
                    // add error
                    $errors[] = 'Could not move directory from <strong>'.implode('', array_map(function($directory) { return ($directory->directory_id == null ? '' : '/'.$directory->name); }, $this->getBreadcrumbDirs($directory))).'</strong> to <strong>'.implode('', array_map(function($directory) { return ($directory->directory_id == null ? '' : '/'.$directory->name); }, $this->getBreadcrumbDirs($currentDirectory))).'/'.$directory->name.'</strong>';
                }
            }
        }

        // cut files
        if (isset($cut['files'])) {
            // move files
            foreach ($cut['files'] as $fileId) {
                // get selected file
                $file = WlrleFile::whereId($fileId)->first();

                if ($file && $file->directory_id != $currentDirectory->id) {
                    // set parent
                    $file->directory_id = $currentDirectory->id;
                    $file->name = $this->getUniqueFileName($currentDirectory, $file->name);

                    // save it
                    $file->save();
                }
            }
        }

        // reset copy directories list
        Session::put(config('wlrle.url_prefix').'.copy.directories', []);

        // reset copy files list
        Session::put(config('wlrle.url_prefix').'.copy.files', []);

        // reset cut directories list
        Session::put(config('wlrle.url_prefix').'.cut.directories', []);

        // reset cut files list
        Session::put(config('wlrle.url_prefix').'.cut.files', []);

        // any error?
        if (count($errors)) {
            return response()->json([
                'message' => $errors,
            ], 400);
        }

        return response()->json([
            'paste' => $this->getPasteCount(),
        ], 200);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        // delete directories, if any
        if (isset($request->items['directories'])) {
            foreach ($request->items['directories'] as $directoryId) {
                // get selected directory
                $directory = WlrleDirectory::whereId($directoryId)->first();

                // delete all files
                if ($directory) {
                    foreach ($this->getAllSubdirectories($directory) as $subdirectory) {
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
        }

        // delete files, if any
        if (isset($request->items['files'])) {
            foreach ($request->items['files'] as $fileId) {
                // get selected file
                $file = WlrleFile::whereId($fileId)->first();

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

        return response()->json([], 200);
    }

    // bulk rename
    public function rename(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*' => 'required|array|min:1',
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

        // error handling
        $errors = [];

        // rename directories, if any
        if (isset($request->items['directories'])) {
            foreach ($request->items['directories'] as $directoryId => $directoryName) {
                // get selected directory
                $directory = WlrleDirectory::whereId($directoryId)->first();

                if ($directory) {
                    // count directories with the same name
                    $sameCount = $currentDirectory->subdirectories()->where('id', '!=', $directory->id)->whereName($directoryName)->count();

                    if ($directoryName != "" && $sameCount == 0) {
                        // set the new name
                        $directory->name = $directoryName;

                        // save it
                        $directory->save();
                    } else {
                        // add error
                        $errors[] = 'Could not rename directory from <strong>'.$directory->name.'</strong> to <strong>'.$directoryName.'</strong>. The name already exists.';
                    }
                }
            }
        }

        // rename files, if any
        if (isset($request->items['files'])) {
            foreach ($request->items['files'] as $fileId => $fileName) {
                // get selected file
                $file = WlrleFile::whereId($fileId)->first();

                if ($file) {
                    // file extension could not be renamed
                    if (strtolower(pathinfo($file->name, PATHINFO_EXTENSION)) == strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
                        // count directories with the same name
                        $sameCount = $currentDirectory->files()->where('id', '!=', $file->id)->whereName($fileName)->count();

                        if ($fileName != "" && $sameCount == 0) {
                            // set the new name
                            $file->name = $fileName;

                            // save it
                            $file->save();
                        } else {
                            // add error
                            $errors[] = 'Could not rename file from <strong>'.$file->name.'</strong> to <strong>'.$fileName.'</strong>. The name already exists.';
                        }
                    } else {
                        // add error
                        $errors[] = 'Could not rename file from <strong>'.$file->name.'</strong> to <strong>'.$fileName.'</strong>. The file extension can\'t be changed.';
                    }
                }
            }
        }

        // any error?
        if (count($errors)) {
            return response()->json([
                'message' => $errors,
            ], 400);
        }

        return response()->json([], 200);
    }
}
