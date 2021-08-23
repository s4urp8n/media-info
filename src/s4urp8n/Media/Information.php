<?php

namespace s4urp8n\Media;

class Information
{
    private static function getBinary()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            return realpath(__DIR__ . '/../../../bin/win/MediaInfo.exe');
        }
        self::assertMediaInfoInstalled();
        return 'mediainfo';
    }

    private static function readExif($file)
    {
        try {
            $exif = @exif_read_data($file, null, true, false);
            if ($exif) {
                return ['_EXIF_' => $exif];
            }
        }
        catch (\Throwable $e) {

        }
        return [];
    }

    private static function readMetadata($file)
    {
        $result = [];
        try {

            $cmd = implode(' ', [
                escapeshellarg(self::getBinary()),
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
                if (count($lineparts) == 2) {
                    $key = trim($lineparts[0]);
                    $value = trim($lineparts[1]);
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
        }
        catch (\Throwable $e) {

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
}