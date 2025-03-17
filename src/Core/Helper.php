<?php

namespace ClassicO\NovaMediaLibrary\Core;

use ClassicO\NovaMediaLibrary\API;
use Illuminate\Support\Facades\Storage;

class Helper
{
    public static function storage()
    {
        return Storage::disk(config('nova-media-library.disk', 'public'));
    }

    public static function directories()
    {
        $len = strlen(substr((string) self::folder(), 1));
        $array = [];

        foreach (self::storage()->allDirectories(config('nova-media-library.folder')) as $item) {
            if ($item == 'nml_temp') {
                continue;
            }
            $path = str_replace('/', '.', substr((string) $item, $len));

            if ($path) {
                data_set($array, $path, 0);
            }
        }

        return $array;
    }

    public static function replace($str)
    {
        return preg_replace('/(\/)\\1+/', '$1', str_replace('\\', '/', $str));
    }

    public static function folder($path = '')
    {
        return self::replace('/' . config('nova-media-library.folder', '') . '/' . $path);
    }

    public static function size($bytes)
    {
        if ($bytes / 1073741824 >= 1) {
            return round($bytes / 1073741824, 2) . ' ' . __('gb');
        }

        if ($bytes / 1048576 >= 1) {
            return round($bytes / 1048576, 2) . ' ' . __('mb');
        }

        if ($bytes / 1024 >= 1) {
            return round($bytes / 1024, 2) . ' ' . __('kb');
        }

        return $bytes . ' ' . __('b');
    }

    public static function isPrivate($folder)
    {
        $disk = config('nova-media-library.disk');
        $private = false;

        if ($disk == 's3') {
            $private = config('nova-media-library.private', false);
        } elseif ($disk == 'local') {
            $private = ! str_starts_with((string) self::folder($folder), '/public/');
        }

        return $private;
    }

    public static function visibility($bool)
    {
        return $bool ? 'private' : 'public';
    }

    public static function preview($item, $size)
    {
        if (! in_array($size, data_get($item, 'options.img_sizes', []))) {
            return null;
        }

        $url = data_get($item, 'url');

        return data_get($item, 'private') ? $url . '&img_size=' . $size : API::getImageSize($url, $size);
    }

    public static function localPublic($folder, $private)
    {
        return
            config('nova-media-library.disk') == 'local' and
            ! $private and
            str_starts_with((string) self::folder($folder), '/public/');
    }
}
