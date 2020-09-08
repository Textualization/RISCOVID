<?php

global $argc,$argv;

include_once dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Textualization\RISCOVID\Compiler as Compiler;

if($argc != 3){
    echo "Usage: php make_page.php form.yaml language";
}else{
    $strings = Yaml::parse(file_get_contents(dirname(__DIR__) . "/resources/en.yaml"));
    $yaml = Yaml::parse(file_get_contents($argv[1]));
    $res = Compiler::build($yaml, $argv[2], $strings, $yaml, FALSE);
    if($res[1]){
        echo "Error: " . $res[1];
    }else{
        echo $res[0];
    }
}
