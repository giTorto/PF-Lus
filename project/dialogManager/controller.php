<?php

    require 'function_utilities.php';
    $file = 'controller.log';

    // receive json
    $input_string = $_GET['SLUObject'];
    $input_json = json_decode($input_string, true);
    file_put_contents($file, "\ntry : " . $input_json["transcript"], FILE_APPEND | LOCK_EX);

    // take and clean phrase
    $input_phrase = "\"".$input_json["transcript"]."\"";
    $input_phrase =  str_ireplace("_", " ", strtolower(trim($input_phrase)));
    ////////print_r($input_phrase);

    // set up some variables
    $wrap_up = array(); $concepts = array(); $classes= array(); $secondary_class = array(); $message_to_return="";

    // check if transcript confidence is enough high
    if ($input_json["confidence"] > 0.70) {
        // perform classification
        $out = shell_exec("python3.4 /var/www/html/project/classifier/giveMeTheProb.py  /var/www/html/project/classifier/TestD/NLSPARQL.test.txt '/var/www/html/project/classifier/NB/results_prior.res' '/var/www/html/project/classifier/TestD/NLSPARQL.test.utt.labels.txt' " . $input_phrase . " /var/www/html/project/classifier/");

        $out = json_decode($out, true);
        //print_r("<br/>".$out["class"]."<br/>");
        //print_r($out["concept"]); //////print_r($out["conc_conf"]); //////print_r("<br/>");
        $secondary_class = $out["secondary_class"];
        $concepts = from_json_to_conc($out);
        $classes = from_json_to_class($out);
        $wrap_up["classes"] = $classes;
        $wrap_up["concepts"] = $concepts;
    }

    //the control pass to the example.php which manages the dialog management phase
    include("./example.php");

    ////////print_r(", ".$message_to_return.", ");
    $callback = $_GET['callback'];
    $json = json_encode($tts);

    $tts = array('results' => $message_to_return);
    $json = json_encode($tts);
    $callback = $_GET['callback'];
    file_put_contents($file, ": " . $json , FILE_APPEND | LOCK_EX);

    echo $callback.'('. $json . ')';

?>