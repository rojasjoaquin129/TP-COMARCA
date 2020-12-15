<?php
namespace Components;
class JsonHandler
{
    /* Used to save a json into a file */
    public static function saveJson($objeto, $path)
    {
        if ($objeto != null) {
            $array = (array) JsonHandler::readJson($path);
            array_push($array, $objeto);
            $archivo = fopen($path, 'w');
            fwrite($archivo, json_encode($array, JSON_PRETTY_PRINT));
            fclose($archivo);
            return true;
        }

        return false;
    }

    public static function saveAllJson($all, $path)
    {
        if ($all != null) {
            $archivo = fopen($path, 'w');
            fwrite($archivo, json_encode($all, JSON_PRETTY_PRINT));
            fclose($archivo);
            return true;
        }

        return false;
    }

    public static function saveUnique($objeto, $path)
    {
        if ($objeto != null) {
            $array = array();
            array_push($array, $objeto);
            $archivo = fopen($path, 'w');
            fwrite($archivo, json_encode($array, JSON_PRETTY_PRINT));
            fclose($archivo);
            return true;
        }

        return false;
    }

    /* Used to read a file and decode the contained json */
    public static function readJson(string $path)
    {
        if (!empty($path)) {

            if (file_exists($path)) {
                $archivo = fopen($path, 'r');
                $fileSize = filesize($path);

                if ($fileSize > 0) {
                    $datos = fread($archivo, $fileSize);
                    $json = json_decode($datos);
                } else {
                    $readFile = '{}';
                    $json = json_decode($readFile);
                }

                fclose($archivo);
            } else {
                $readFile = '{}';
                $json = json_decode($readFile);
            }
        }

        return $json;
    }
}
