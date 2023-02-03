<?php

namespace Bsik\FsTools;

/******************************  Requires       *****************************/
require_once BSIK_AUTOLOAD;

use \Bsik\Std;
use \Exception;
use \SplFileInfo;
use \ZipArchive;

class BsikFileSystem {

    final public static function list_folder(string|\SplFileInfo $path) : ?\RecursiveIteratorIterator {
        $folder = is_string($path) ? new \SplFileInfo($path) : $path;
        if (!$folder->isDir())
            return null;
        /** @var \RecursiveIteratorIterator SplFileInfo[] $files */
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder->getRealPath()), \RecursiveIteratorIterator::LEAVES_ONLY);
    }

    final public static function delete_files(...$files) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    final public static function clear_folder(string $path_dir, bool $self = false) : bool {
        if (!file_exists($path_dir)) return false;
        $di = new \RecursiveDirectoryIterator($path_dir, \FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ( $ri as $file ) {
            $file->isDir() ?  rmdir($file) : self::delete_files($file);
        }
        if ($self) {
            rmdir($path_dir);
        }
        return true;
    }

    final public static function hash_directory(string $directory) {
        if (!is_dir($directory)) { 
            return false; 
        }
        $files = [];
        $dir = dir($directory);
        while (false !== ($file = $dir->read())) {
            if ($file != '.' and $file != '..') {
                if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) { 
                    $files[] = self::hash_directory($directory . DIRECTORY_SEPARATOR . $file); 
                } else { 
                    $files[] = md5_file($directory . DIRECTORY_SEPARATOR . $file); 
                }
            }
        }
        $dir->close();
        return md5(implode('', $files));
    }

    final public static function xcopy(string $source, string $dest, int $permissions = 0755) : bool {
        $sourceHash = self::hash_directory($source);
        // Check for symlinks
        if (is_link($source))
            return symlink(readlink($source), $dest);
        // Simple copy for a file
        if (is_file($source)) {
            $file = explode(DIRECTORY_SEPARATOR, $source);
            return copy(
                    $source, is_dir($dest) 
                    ? rtrim($dest, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.end($file) 
                    : $dest
            );
        }
        // Make destination directory
        if (!is_dir($dest)) 
            mkdir($dest, $permissions, true);
        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..')
                continue;
            // Deep copy directories
            if ($sourceHash != self::hash_directory($source.DIRECTORY_SEPARATOR.$entry)) {
                if (!self::xcopy($source.DIRECTORY_SEPARATOR.$entry, $dest.DIRECTORY_SEPARATOR.$entry, $permissions)) {
                    return false;
                }
            }
        }
        // Clean up
        $dir->close();
        return true;
    }

    
}

class BsikZip {


    final public static function zip_status_message($status) : string {
        switch( (int) $status )
        {
            case ZipArchive::ER_OK           : return 'N No error';
            case ZipArchive::ER_MULTIDISK    : return 'N Multi-disk zip archives not supported';
            case ZipArchive::ER_RENAME       : return 'S Renaming temporary file failed';
            case ZipArchive::ER_CLOSE        : return 'S Closing zip archive failed';
            case ZipArchive::ER_SEEK         : return 'S Seek error';
            case ZipArchive::ER_READ         : return 'S Read error';
            case ZipArchive::ER_WRITE        : return 'S Write error';
            case ZipArchive::ER_CRC          : return 'N CRC error';
            case ZipArchive::ER_ZIPCLOSED    : return 'N Containing zip archive was closed';
            case ZipArchive::ER_NOENT        : return 'N No such file';
            case ZipArchive::ER_EXISTS       : return 'N File already exists';
            case ZipArchive::ER_OPEN         : return 'S Can\'t open file';
            case ZipArchive::ER_TMPOPEN      : return 'S Failure to create temporary file';
            case ZipArchive::ER_ZLIB         : return 'Z Zlib error';
            case ZipArchive::ER_MEMORY       : return 'N Malloc failure';
            case ZipArchive::ER_CHANGED      : return 'N Entry has been changed';
            case ZipArchive::ER_COMPNOTSUPP  : return 'N Compression method not supported';
            case ZipArchive::ER_EOF          : return 'N Premature EOF';
            case ZipArchive::ER_INVAL        : return 'N Invalid argument';
            case ZipArchive::ER_NOZIP        : return 'N Not a zip archive';
            case ZipArchive::ER_INTERNAL     : return 'N Internal error';
            case ZipArchive::ER_INCONS       : return 'N Zip archive inconsistent';
            case ZipArchive::ER_REMOVE       : return 'S Can\'t remove file';
            case ZipArchive::ER_DELETED      : return 'N Entry has been deleted';
            default: return sprintf('Unknown status %s', $status );
        }
    }   

    /** 
     * list_files - list all files with name and index - keys are unique full path.
     * @param ZipArchive $zip
     * @return array => empty on zip not loaded
     */
    final public static function list_files(ZipArchive $zip) : array {
        $list = [];
        if ($zip->filename && $zip->status === ZipArchive::ER_OK) { //small workaround hack to check its intialized 
            //List the files in zip:
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $list[$stat['name']] = [
                    "name" => basename($stat['name']),
                    "index" => $i
                ];
            }
        }
        return $list;
    }

    /** 
     * zip_folder - loads the archive into memory
     * 
     * @param string $path  => the path to the folder to zip
     * @param string $out   => the zip full name (path + name)
     * @throws Exception    => E_PLAT_ERROR on zip cant be opened from 'open_zip'.
     * @return bool   => true on saved
     */
    final public static function zip_folder(string $path, string $out) : bool {
        $zip = self::open_zip($out, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        /** @var \RecursiveIteratorIterator $files */
        $origin_path = new SplFileInfo($path);
        $files = BsikFileSystem::list_folder($origin_path) ?? [];
        foreach ($files as $name => $file) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($origin_path->getRealPath()) + 1);
            if (!$file->isDir()) {
                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            } elseif ($relativePath !== false) {
                //Create empty folder:
                $zip->addEmptyDir($relativePath);
            }
        }
        // Zip archive will be created only after closing object
        return $zip->close();
    }

    /** 
     * open_zip - loads the archive into memory
     * 
     * @param string $path => the zip full name (path + name)
     * @param ?int $flags => ZipArchive FLAGS
     * @throws Exception => E_PLAT_ERROR on zip cant be opened.
     * @return ZipArchive => the zip object
     */
    final public static function open_zip(string $path, int $flags = 0) : ZipArchive {
        $zip = new ZipArchive();
        $result = $zip->open($path, $flags);
        if ($result !== true) {
            $error = self::zip_status_message($result);
            throw new Exception("Module zip file cant be opened [{$error}]", E_PLAT_ERROR);
        }
        return $zip;
    }

    /** 
     * extract - extract the loaded zip archive to a folder
     * 
     * @param ZipArchive|string $zip
     * @param string $to
     * @throws Exception => E_PLAT_ERROR on zip cant be opened.
     * @return bool      => the extracted path
     */
    final public static function extract_zip(ZipArchive|string $zip, string $to, int $flags = 0) : bool {
        $close = false;
        if (is_string($zip)) {
            $zip = self::open_zip($zip, $flags);
            $close = true;
        }
        $success = $zip->extractTo($to);
        if ($close) $zip->close();
        return $success;
    }
}
