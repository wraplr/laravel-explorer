<?php

namespace WrapLr\LaravelExplorer\App\Http\Controllers;

use Session;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use WrapLr\LaravelExplorer\App\WleDirectory;
use WrapLr\LaravelExplorer\App\WleFile;

class BaseController extends Controller
{
    protected function getCurrentWorkingDirectory()
    {
        $currentDirectory = WleDirectory::whereId(Session::get(config('wlrle.url_prefix').'.cwd'))->first();

        if (!$currentDirectory) {
            // get root
            $rootDirectory = WleDirectory::whereDirectoryId(null)->whereName('')->first();

            // it can't be null
            Session::put(config('wlrle.url_prefix').'.cwd', $rootDirectory->id);

            // return root directory
            return $rootDirectory;
        }

        // return current directory
        return $currentDirectory;
    }

    protected function getBreadcrumbDirs($directory)
    {
        $breadcrumbDirs = [];

        for (;;) {
            // do not add null to list accidentally
            if (!$directory) {
                break;
            }

            // add it to breadcrumb
            $breadcrumbDirs[] = $directory;

            // set parent as current dir
            $directory = $directory->parent;
        }

        return array_reverse($breadcrumbDirs);
    }

    protected function getAllSubdirectories($directory, $subdirectories = [])
    {
        if ($directory->directory_id) {
            $subdirectories[] = $directory;
        }

        foreach ($directory->subdirectories as $subdirectory) {
            $subdirectories = $this->getAllSubdirectories($subdirectory, $subdirectories);
        }

        return $subdirectories;
    }

    protected function getPasteCount()
    {
        // paste count
        $pasteCount = 0;

        // get copy list
        $copy = Session::get(config('wlrle.url_prefix').'.copy', []);

        // get cut list
        $cut = Session::get(config('wlrle.url_prefix').'.cut', []);

        // directories to copy
        if (isset($copy['directories'])) {
            $pasteCount += count($copy['directories']);
        }

        // files to copy
        if (isset($copy['files'])) {
            $pasteCount += count($copy['files']);
        }


        // directories to cut
        if (isset($cut['directories'])) {
            $pasteCount += count($cut['directories']);
        }

        // files to cut
        if (isset($cut['files'])) {
            $pasteCount += count($cut['files']);
        }

        return $pasteCount;
    }

    protected function deleteFile($filePath)
    {
        // check if it still exists
        if (!is_file($filePath)) {
            return;
        }

        // delete the file
        unlink($filePath);

        // check day's dir
        $dayPath = dirname($filePath, 1);
        if (count(File::files($dayPath))) {
            return;
        }
        rmdir($dayPath);

        // check month's dir
        $monthPath = dirname($filePath, 2);
        if (count(File::directories($monthPath))) {
            return;
        }
        rmdir($monthPath);

        // check year's dir
        $yearPath = dirname($filePath, 3);
        if (count(File::directories($yearPath))) {
            return;
        }
        rmdir($yearPath);
    }

    protected function toFileInfoList($fileList)
    {
        // file info list
        $fileInfoList = [];

        // extract file info for every file
        foreach ($fileList as $file) {
            // create file info
            $fileInfo = [
                'id' => $file->id,
                'name' => $file->name,
                'url' => $file->storageUrl(),
                'mimeType' => $file->mime_type,
            ];

            // views
            if ($file->isImage() && $file->hasViews() && count(config('wlrle.image_views')) > 0) {
                $fileInfo['views'] = [];

                foreach (config('wlrle.image_views') as $viewName => $viewTrans) {
                    $fileInfo['views'][$viewName] = $file->viewUrl($viewName);
                }
            }

            // add it to file info list
            $fileInfoList[] = $fileInfo;
        }

        // return file info list
        return $fileInfoList;
    }

    protected function getUniqueDirectoryName($currentDirectory, $directoryOriginalName)
    {
        // set it to original name by default
        $directoryName = $directoryOriginalName;

        // get all names
        $directoryNames = $currentDirectory->subdirectories->pluck('name')->all();

        // rename it, if any
        $directoryIndex = 0;
        while (in_array($directoryName, $directoryNames)) {
            $directoryName = $directoryOriginalName.' ('.(++$directoryIndex).')';
        }

        // return updated or original name
        return $directoryName;
    }

    protected function getUniqueFileName($currentDirectory, $fileOriginalName)
    {
        // set it to original name by default
        $fileName = $fileOriginalName;

        // get all names
        $fileNames = $currentDirectory->files->pluck('name')->all();

        // rename it, if any
        $fileIndex = 0;
        while (in_array($fileName, $fileNames)) {
            $fileName = pathinfo($fileOriginalName, PATHINFO_FILENAME).' ('.(++$fileIndex).')'.(pathinfo($fileOriginalName, PATHINFO_EXTENSION) == '' ? '' : '.').pathinfo($fileOriginalName, PATHINFO_EXTENSION);
        }

        // return updated or original name
        return $fileName;
    }
}
