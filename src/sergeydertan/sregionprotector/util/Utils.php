<?php
declare(strict_types=1);

namespace sergeydertan\sregionprotector\util;

use http\Exception\RuntimeException;
use Phar;
use pocketmine\utils\Config;
use sergeydertan\sregionprotector\main\SRegionProtectorMain;

abstract class Utils
{
    private function __construct()
    {
    }

    public static function copyResource(string $file, bool $fixMissing = true, bool $removeAbsent = true): void
    {
        $target = SRegionProtectorMain::getInstance()->getMainFolder() . $file;
        if (!file_exists($target)) {
            copy(self::getResource($file), $target);
            return;
        }
        if (!$fixMissing) return;

        $src = (new Config(self::getResource($file), Config::YAML))->getAll();
        $trg = new Config($target, Config::YAML);

        $tt = $trg->getAll();

        if (self::copyArrayOfArrays($src, $tt, $removeAbsent)) {
            $trg->setAll($tt);
            $trg->save();
        }
    }

    public static function getResource(string $file): string
    {
        @mkdir($dir = sys_get_temp_dir() . "/srp/", 0777, true);

        $fileDir = explode("/", $file);
        array_pop($fileDir);
        @mkdir($dir . implode("/", $fileDir));

        @unlink($dir . $file);
        if (SRegionProtectorMain::getInstance()->isPhar()) {
            (new Phar(SRegionProtectorMain::getInstance()->getFile()))->extractTo($dir, ["resources/$file"]);
        } else {
            copy(SRegionProtectorMain::getInstance()->getFile() . "resources/$file", $dir . $file);
        }
        return $dir . $file;
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
        if (SRegionProtectorMain::getInstance()->isPhar()) {
            //TODO
        } else {
            return file_exists(SRegionProtectorMain::getInstance()->getFile() . "/$file");
        }
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

    public static function startsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }
}
