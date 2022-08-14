<?php
require "vendor/autoload.php";

$file = 'C:\OpenServer\domains\media-info\samples\IMG_0045.HEIC';

$info = s4urp8n\Media\Information::getInformation($file);

dd($info);