<?php

namespace StackFormation;

class Preprocessor {

    public function process($filepath) {
        if (!is_file($filepath)) {
            throw new \Exception('File not found');
        }
        $json = file_get_contents($filepath);

        $json = $this->injectFilecontent($json, dirname($filepath));
        $json = $this->replaceRef($json);
        $json = $this->replaceMarkers($json);
        return $json;
    }

    public function replaceMarkers($json) {
        $markers = array(
            '###TIMESTAMP###' => date(\DateTime::ISO8601),
        );
        return str_replace(array_keys($markers), array_values($markers), $json);
    }

    public function injectFilecontent($jsonString, $basePath) {
        return preg_replace_callback('/(\s*)(.*){\s*"Fn::FileContent"\s*:\s*"(.+?)"\s*}/', function(array $matches) use ($basePath) {
            $file = $basePath . '/' . $matches[3];
            if (!is_file($file)) {
                throw new \Exception("File $file not found");
            }

            $result = ' {"Fn::Join": ["", ' . json_encode(file($file), JSON_PRETTY_PRINT) . ']}';

            $whitespace = trim($matches[1], "\n");
            $result = str_replace("\n", "\n".$whitespace, $result);

            return $matches[1] . $matches[2] . $result;
        }, $jsonString);
    }

    public function replaceRef($jsonString) {
        return preg_replace('/\{\s*Ref\s*:\s*([a-zA-Z0-9:]+?)\s*\}/', '", {"Ref": "$1"}, "', $jsonString);
    }

}