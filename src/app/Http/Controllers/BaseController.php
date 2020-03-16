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

            // add it to breadcrumg
            $breadcrumbDirs[] = $directory;

            // set parent as current dir
            $directory = $directory->parent;
        }

        return array_reverse($breadcrumbDirs);
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
}
