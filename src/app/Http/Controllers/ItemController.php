<?php

namespace WrapLr\LaravelExplorer\App\Http\Controllers;

use Session;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use WrapLr\LaravelExplorer\App\Http\Controllers\BaseController;
use WrapLr\LaravelExplorer\App\WleDirectory;
use WrapLr\LaravelExplorer\App\WleFile;

class ItemController extends BaseController
{
    public function copy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
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
                $directory = WleDirectory::whereId($directoryId)->first();

                // get breadcrumb id's
                $breadcrumbIds = array_slice(array_column($this->getBreadcrumbDirs($currentDirectory), 'id'), 1);

                if ($directory && !in_array($directory->id, $breadcrumbIds)) {
                    // create model
                    $wleDirectory = new WleDirectory([
                        'name' => $this->getUniqueDirectoryName($currentDirectory, $directory->name),
                    ]);

                    // create new directory in database
                    $currentDirectory->subdirectories()->save($wleDirectory);

                    // copy files (note that we don't copy sudirectories)
                    foreach ($directory->files as $file) {
                        // phisical path, relative to base_directory/upload_directory
                        $path = Carbon::now()->format('Y/m/d');

                        // create model
                        $wleFile = new WleFile([
                            'name' => $this->getUniqueFileName($wleDirectory, $file->name),
                            'mime_type' => $file->mime_type,
                            'path' => $path,
                            'extension' => $file->extension,
                            'size' => $file->size,
                        ]);

                        // create new file in database
                        $wleDirectory->files()->save($wleFile);

                        // new phisical full path
                        $full = $base.'/'.$path;

                        // create it, if any
                        if (!is_dir($full)) {
                            mkdir($full, 0777, true);
                        }

                        // new storage path
                        $storagePath = $full.'/'.base_convert($wleFile->id, 10, 36).($file->extension == '' ? '' : '.').$file->extension;

                        // copy file phisically
                        if (!@copy($file->storagePath(), $storagePath)) {
                            // copy error
                            $wleFile->delete();

                            // add error
                            $errors[] = 'Could not copy file from '.$file->storagePath().' to '.$storagePath;
                        }
                    }
                } else {
                    // add error
                    $errors[] = 'Could not copy directory '.implode('', array_map(function($directory) { return ($directory->directory_id == null ? '' : '/'.$directory->name); }, $this->getBreadcrumbDirs($directory))).' to '.implode('', array_map(function($directory) { return ($directory->directory_id == null ? '' : '/'.$directory->name); }, $this->getBreadcrumbDirs($currentDirectory)));
                }
            }
        }

        // copy files
        if (isset($copy['files'])) {
            // copy files
            foreach ($copy['files'] as $fileId) {
                // get selected file
                $file = WleFile::whereId($fileId)->first();

                if ($file) {
                    // phisical path, relative to base_directory/upload_directory
                    $path = Carbon::now()->format('Y/m/d');

                    // create model
                    $wleFile = new WleFile([
                        'name' => $this->getUniqueFileName($currentDirectory, $file->name),
                        'mime_type' => $file->mime_type,
                        'path' => $path,
                        'extension' => $file->extension,
                        'size' => $file->size,
                    ]);

                    // create new file in database
                    $currentDirectory->files()->save($wleFile);

                    // new phisical full path
                    $full = $base.'/'.$path;

                    // create it, if any
                    if (!is_dir($full)) {
                        mkdir($full, 0777, true);
                    }

                    // new storage path
                    $storagePath = $full.'/'.base_convert($wleFile->id, 10, 36).($file->extension == '' ? '' : '.').$file->extension;

                    // copy file phisically
                    if (!@copy($file->storagePath(), $storagePath)) {
                        // copy error
                        $wleFile->delete();

                        // add error
                        $errors[] = 'Could not copy file from '.$file->storagePath().' to '.$storagePath;
                    }
                }
            }
        }

        // cut directories
        if (isset($cut['directories'])) {
            // move directories
            foreach ($cut['directories'] as $directoryId) {
                // get selected directory
                $directory = WleDirectory::whereId($directoryId)->first();

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
                    $errors[] = 'Could not move directory '.implode('', array_map(function($directory) { return ($directory->directory_id == null ? '' : '/'.$directory->name); }, $this->getBreadcrumbDirs($directory))).' to '.implode('', array_map(function($directory) { return ($directory->directory_id == null ? '' : '/'.$directory->name); }, $this->getBreadcrumbDirs($currentDirectory)));
                }
            }
        }

        // cut files
        if (isset($cut['files'])) {
            // move files
            foreach ($cut['files'] as $fileId) {
                // get selected file
                $file = WleFile::whereId($fileId)->first();

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
                $directory = WleDirectory::whereId($directoryId)->first();

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
                $file = WleFile::whereId($fileId)->first();

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
}
