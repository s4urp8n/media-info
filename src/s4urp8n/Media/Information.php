<?php

namespace s4urp8n\Media;

class Information
{

    const ENCODING = 'UTF-8';

    private static function getBinaryMediainfo()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            return realpath(__DIR__ . '/../../../bin/win/MediaInfo.exe');
        }
        self::assertMediaInfoInstalled();
        return 'mediainfo';
    }

    private static function getBinaryExiftool()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            return realpath(__DIR__ . '/../../../bin/win/exiftool.exe');
        }

        return realpath(__DIR__ . '/../../../bin/unix/exiftool');
    }

    private static function readExif($file)
    {
        try {
            $exif = @exif_read_data($file, null, true, false);
            if ($exif) {
                return ['_EXIF_' => $exif];
            }
        } catch (\Throwable $e) {

        }
        return [];
    }

    private static function readMetadata($file)
    {
        $result = [];
        try {

            $cmd = implode(' ', [
                escapeshellarg(self::getBinaryMediainfo()),
                escapeshellarg($file),
            ]);
            $output = shell_exec($cmd);
            $output = preg_split('/[\r\n]+/i', $output);
            $section = '';
            $lineparts = '';
            foreach ($output as $line) {
                $lineparts = explode(':', $line);
                if (count($lineparts) == 1) {
                    $section = trim($lineparts[0]);
                }
                if (count($lineparts) > 1) {
                    $key = trim(array_shift($lineparts));
                    $value = trim(implode(':', $lineparts));
                    if ($section) {
                        if (!array_key_exists($section, $result)) {
                            $result[$section] = [];
                        }
                        $result[$section][$key] = $value;
                    } else {
                        $result[$key] = $value;
                    }
                }
            }
        } catch (\Throwable $e) {

        }
        return $result;
    }

    public static function getInformation($file)
    {
        if (!file_exists($file)) {
            throw new \Exception(sprintf('File %s is not exists', $file));
        }

        return array_merge(
            static::readExif($file),
            static::readExiftool($file),
            static::readMetadata($file),
        );
    }

    private static function assertMediaInfoInstalled()
    {
        $command = 'mediainfo --help';
        @exec($command, $output, $exitcode);
        if ($exitcode != 0) {
            throw new \Exception("Please install mediainfo");
        }
    }

    private static function readExiftool($file)
    {
        $cmd = implode(' ', [
            escapeshellarg(self::getBinaryExiftool()),
            escapeshellarg($file),
        ]);


        $output = [];
        @exec($cmd, $output);
        if (!$output || !is_array($output)) {
            return [];
        }

        $data = [];

        foreach ($output as $line) {
            $semicolonPosition = mb_strpos($line, ':', 0, self::ENCODING);
            if (!$semicolonPosition) {
                continue;
            }

            $key = static::trimString(mb_substr($line, 0, $semicolonPosition, self::ENCODING));
            $value = static::trimString(mb_substr($line, $semicolonPosition + 1, null, self::ENCODING));

            if (!$key || !$value) {
                continue;
            }

            $data[$key] = $value;
        }

        if ($data) {
            $data = ['_EXIFTOOL_' => $data];
        }

        return $data;
    }

    private static function trimString($string)
    {
        $string = preg_replace('#\s+#', ' ', $string);
        $string = preg_replace('#^\s+#', '', $string);
        return preg_replace('#\s+$#', '', $string);
    }
}