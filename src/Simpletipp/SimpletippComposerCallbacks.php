<?php

namespace Simpletipp;

class SimpletippComposerCallbacks
{
    public static function postInstallUpdate(\Composer\EventDispatcher\Event $event)
    {
        $composer = $event->getComposer();
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        echo $vendorDir;
        // do stuff
    }

    private static function deleteFolder($path)
    {
        if (is_dir($path) === true) {
            $files = array_diff(scandir($path), array('.', '..'));
            foreach ($files as $file) {
                Delete(realpath($path) . '/' . $file);
            }
            return rmdir($path);
        } else if (is_file($path) === true) {
            return unlink($path);
        }
        return false;
    }

    private static function copyFolder($src, $dst)
    {
        $dir = opendir($src);
        mkdir($dst);
        while (false !== ($file = readdir($dir)))
        {
            if (($file != '.') && ($file != '..'))
            {
                if (is_dir($src . '/' . $file))
                {
                    self::recurse_copy($src . '/' . $file, $dst . '/' . $file);
                }
                else
                {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
