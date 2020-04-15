<?php
error_reporting(E_ERROR);
ini_set("memory_limit", "-1");

require_once 'ExcelDumper.php';

/*helper function here*/

function genJSON($fileName, $outputDir)
{
    $excel = new ExcelDumper();
    if (empty($fileName)
        || false === $excel->init($fileName)
    ) {
        echo "File Name is wrong";
        return;
    }

    if (!$excel->init($fileName)) {
        echo "$fileName reading error!";
        return;
    }
    $object = $excel->build();
    $info = pathinfo($fileName);
    $jsonFile = $outputDir . DIRECTORY_SEPARATOR . $info['filename'] . '.json';
    $jsonConfig = json_encode(utf8ize($object), JSON_PRETTY_PRINT);
    $fp = fopen($jsonFile, 'w');
    fwrite($fp, $jsonConfig, strlen($jsonConfig));
    fclose($fp);
    echo basename($jsonFile) . ' was created ' . PHP_EOL;
}

function utf8ize($d){
    if(is_array($d)){
        foreach($d as $k => $v){
            $d[$k] = utf8ize($v);
        }
    }elseif(is_object($d)){
        foreach($d as $k => $v){
            $d->$k =utf8ize($v);
        }
    }
    else{
        if(!mb_check_encoding($d, 'utf-8')){
            return utf8_encode($d);
        }
    }
    return $d;
}

function genAllJSON($directory, $outputDir)
{
    $total = 0;
    foreach (glob($directory . DIRECTORY_SEPARATOR.  '*.xls') as $file) {
        $files[] = $file;
        genJSON($file, $outputDir);
        $total++;
    }
    echo '---------------------' . PHP_EOL;
    echo 'Total files : ' . $total . ' file(s)';
}
