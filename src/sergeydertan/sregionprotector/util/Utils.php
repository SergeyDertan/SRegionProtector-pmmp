<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util;

use Exception;
use pocketmine\utils\Config;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;

abstract class Utils
{
    private function __construct()
    {
    }

    public static function objArrayUnique(array $arr): array
    {
        $result = [];
        foreach ($arr as $id => $value) {
            if (!self::objInArray($value, $result)) $result[$id] = $value;
        }
        return $result;
    }

    public static function objInArray(object $val, array $arr): bool
    {
        foreach ($arr as $item) {
            if ($item === $val) return true;
        }
        return true;
    }

    public static function copyResource(string $file, bool $fixMissing = true, bool $removeAbsent = true): void
    {
        $target = SRegionProtectorMain::getInstance()->getMainFolder() . $file;
        if (!file_exists($target)) {
            stream_copy_to_stream(self::getResource($file), $t = fopen($target, "wb"));
            fclose($t);
            return;
        }
        if (!$fixMissing) return;

        $src = yaml_parse(stream_get_contents(self::getResource($file)));

        $trg = new Config($target, Config::YAML);

        $tt = $trg->getAll();

        if (self::copyArrayOfArrays($src, $tt, $removeAbsent)) {
            $trg->setAll($tt);
            $trg->save();
        }
    }

    public static function getResource(string $file)
    {
        return SRegionProtectorMain::getInstance()->getResource($file);
    }

    /**
     * recursive copy array of arrays
     * @param array $source
     * @param array $target
     * @param bool $removeAbsent
     * @return bool
     * @return true if target was changed
     */
    public static function copyArrayOfArrays(array $source, array &$target, bool $removeAbsent = true): bool
    {
        $changed = false;
        foreach ($source as $key => $value) {
            if (!isset($target[$key])) {
                $target[$key] = $value;
                $changed = true;
            } else {
                if (gettype($value) !== gettype($target[$key])) {
                    $changed = true;
                    $target[$key] = $value;
                    continue;
                }
                if (is_array($value)) {
                    $c = self::copyArrayOfArrays((array)$value, $target[$key], $removeAbsent);
                    if ($c) $changed = true;
                }
            }
        }
        if ($removeAbsent) {
            $c = false;
            foreach (array_keys($target) as $key) {
                if (!isset($source[$key])) {
                    $c = true;
                    unset($target[$key]);
                }
            }
            if ($c) $changed = true;
        }
        return $changed;
    }

    public static function removeDir(string $dir): void
    {
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    public static function httpRequest(string $url): ?string
    {
        try {
            $req = curl_init();

            curl_setopt($req, CURLOPT_URL, $url);
            curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($req, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($req, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0");

            $data = curl_exec($req);
            curl_close($req);
            return $data;
        } catch (Exception $ignore) {
            return null;
        }
    }

    public static function currentTimeMillis(): int
    {
        $mt = explode(' ', microtime());
        return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
    }

    public static function createDir(string $path): bool
    {
        if (file_exists($path)) return true;
        return mkdir($path, 0777, true);
    }

    public static function resourceExists(string $file): bool
    {
        return SRegionProtectorMain::getInstance()->getResource($file) !== null;
    }

    /**
     * @param string $first
     * @param string $second
     * @return string greater version string of empty string "" if they`re equal
     */
    public static function compareVersions(string $first, string $second): string
    {
        if (strcasecmp($first, $second) === 0) return "";

        $f = explode(".", $first);
        $s = explode(".", $second);

        $bigger = count($f) >= count($s) ? $f : $s;
        $smaller = count($f) < count($s) ? $f : $s;

        for ($i = 0; $i < count($smaller); ++$i) {
            if ((int)$smaller[$i] > (int)$bigger[$i]) {
                return implode(".", $smaller);
            } else if ((int)$smaller[$i] < (int)$bigger[$i]) {
                return implode(".", $bigger);
            }
            if (count($smaller) === $i + 1 && count($smaller) < count($bigger)) return implode(".", $bigger);
        }
        throw new RuntimeException("Unreachable code reached");
    }

    public static function startsWith(string $haystack, string $needle, bool $caseInsensitive = true): bool
    {
        $length = strlen($needle);
        return $caseInsensitive ? strcasecmp(substr($haystack, 0, $length), $needle) === 0 : substr($haystack, 0, $length) === $needle;
    }

    public static function endsWith(string $haystack, string $needle, bool $caseInsensitive = true): bool
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return $caseInsensitive ? strcasecmp(substr($haystack, -$length), $needle) === 0 : substr($haystack, -$length) === $needle;
    }
}
