<?php

namespace Wraplr\LaravelExplorer\App\Http\Controllers;

use Session;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Wraplr\LaravelExplorer\App\Http\Controllers\BaseController;
use Wraplr\LaravelExplorer\App\WlrleDirectory;
use Wraplr\LaravelExplorer\App\WlrleFile;

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
        Session::put(config('wlrle.url_prefix').'.copy.directories', (isset($request->items['directory']) ? $request->items['directory'] : []));

        // update copy files list
        Session::put(config('wlrle.url_prefix').'.copy.files', (isset($request->items['file']) ? $request->items['file'] : []));

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
        Session::put(config('wlrle.url_prefix').'.cut.directories', (isset($request->items['directory']) ? $request->items['directory'] : []));

        // update cut files list
        Session::put(config('wlrle.url_prefix').'.cut.files', (isset($request->items['file']) ? $request->items['file'] : []));

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
        $base = Storage::disk('public')->path(config('wlrle.storage_directory'));

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
                    // copy directories/files recursively
                    $copyRecursively = function($currentDirectory, $directory) use (&$copyRecursively, &$errors, $base) {
                        // create model
                        $wlrleDirectory = new WlrleDirectory([
                            'name' => $this->getUniqueDirectoryName($currentDirectory, $directory->name),
                        ]);

                        // create new directory in database
                        $currentDirectory->subdirectories()->save($wlrleDirectory);

                        // copy subdirectories
                        foreach ($directory->subdirectories as $subdirectory) {
                            // call it for the subdirectory
                            $copyRecursively($wlrleDirectory, $subdirectory);
                        }

                        // copy files
                        foreach ($directory->files as $file) {
                            // phisical path, relative to base_directory/storage_directory
                            $path = Carbon::now()->format('Y/m/d');

                            // create model
                            $wlrleFile = new WlrleFile([
                                'name' => $this->getUniqueFileName($wlrleDirectory, $file->name),
                                'path' => $path,
                                'file' => '',
                                'extension' => $file->extension,
                                'mime_type' => $file->mime_type,
                                'size' => $file->size,
                            ]);

                            // create new file in database
                            $wlrleDirectory->files()->save($wlrleFile);

                            // create unique hashid for file
                            $wlrleFile->file = config('wlrle.file_hashid')($wlrleFile->id);

                            // new phisical full path
                            $full = $base.'/'.$path;

                            // create it, if any
                            if (!is_dir($full)) {
                                mkdir($full, 0777, true);
                            }

                            // new storage path
                            $storagePath = $full.'/'.$wlrleFile->file.($file->extension == '' ? '' : '.').$file->extension;

                            // copy file phisically
                            if (@copy($file->storagePath(), $storagePath)) {
                                // save file's hashid
                                $wlrleFile->save();
                            } else {
                                // copy error
                                $wlrleFile->delete();

                                // add error
                                $errors[] = 'Could not copy file from <strong>'.$file->storagePath().'</strong> to <strong>'.$storagePath.'</strong>. System error.';
                            }
                        }
                    };

                    // call it for the main directory
                    $copyRecursively($currentDirectory, $directory);
                } else {
                    // add error
                    $errors[] = 'Could not copy directory from <strong>'.$this->getDirectoryPath($directory).'</strong> to <strong>'.$this->getDirectoryPath($currentDirectory).'/'.$directory->name.'</strong>. Same directory.';
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
                    // phisical path, relative to base_directory/storage_directory
                    $path = Carbon::now()->format('Y/m/d');

                    // create model
                    $wlrleFile = new WlrleFile([
                        'name' => $this->getUniqueFileName($currentDirectory, $file->name),
                        'path' => $path,
                        'file' => '',
                        'extension' => $file->extension,
                        'mime_type' => $file->mime_type,
                        'size' => $file->size,
                    ]);

                    // create new file in database
                    $currentDirectory->files()->save($wlrleFile);

                    // create unique hashid for file
                    $wlrleFile->file = config('wlrle.file_hashid')($wlrleFile->id);

                    // new phisical full path
                    $full = $base.'/'.$path;

                    // create it, if any
                    if (!is_dir($full)) {
                        mkdir($full, 0777, true);
                    }

                    // new storage path
                    $storagePath = $full.'/'.$wlrleFile->file.($file->extension == '' ? '' : '.').$file->extension;

                    // copy file phisically
                    if (@copy($file->storagePath(), $storagePath)) {
                        // save file's hashid
                        $wlrleFile->save();
                    } else {
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
                    $errors[] = 'Could not move directory from <strong>'.$this->getDirectoryPath($directory).'</strong> to <strong>'.$this->getDirectoryPath($currentDirectory).'/'.$directory->name.'</strong>. Same directory.';
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
        if (isset($request->items['directory'])) {
            foreach ($request->items['directory'] as $directoryId) {
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
        if (isset($request->items['file'])) {
            foreach ($request->items['file'] as $fileId) {
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
        if (isset($request->items['directory'])) {
            foreach ($request->items['directory'] as $directoryId => $directoryName) {
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
        if (isset($request->items['file'])) {
            foreach ($request->items['file'] as $fileId => $fileName) {
                // get selected file
                $file = WlrleFile::whereId($fileId)->first();

                if ($file) {
                    // file extension could not be renamed
                    if ($file->extension == strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) {
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
                        $errors[] = 'Could not rename file from <strong>'.$file->name.'</strong> to <strong>'.$fileName.'</strong>. The file extension can not be changed.';
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
