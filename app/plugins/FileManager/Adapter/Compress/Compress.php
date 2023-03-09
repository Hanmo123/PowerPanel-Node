<?php

namespace app\plugins\FileManager\Adapter\Compress;

use League\MimeTypeDetection\FinfoMimeTypeDetector;

abstract class Compress
{
    static $adapter = [
        'application/zip' => PhpZip::class
    ];

    abstract static function Compress(array $targets, string $to);
    abstract static function Decompress(string $target, string $to);

    static public function Get(string $file)
    {
        $detector = new FinfoMimeTypeDetector();
        $mimetype = $detector->detectMimeTypeFromPath($file);
        if (isset(self::$adapter[$mimetype])) {
            return self::$adapter[$mimetype];
        } else {
            throw new \Exception('此压缩文件类型不受支持。');
        }
    }
}
