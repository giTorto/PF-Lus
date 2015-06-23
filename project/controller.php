<?php

    $asr_output = $_GET['SLUObject'];

    //What was David Leans first film?
    if (stripos($asr_output,'David Leans') !== false) {
        $tts_output='David Leans first film was Oliver Twist';
    }
    //which was the first film to have a sequel?
    else if (stripos($asr_output,'sequel') !== false) {
        $tts_output='The first film to have a sequel was King Kong. The sequel was called Son of Kong';
    }
    //The Golden Bear is awarded at which film festival?
    else if (stripos($asr_output,'Golden Bear') !== false) {
        $tts_output="The Golden Bear is awarded at the Berlin Film festival";
    }
    //What actor studied as a priest then an architect before becoming an actor?
    else if (stripos($asr_output,'architect') !== false) {
        $tts_output="Anthony Quinn";
    }
    //What was Clint Eastwoods first film
    else if (stripos($asr_output,'Clint') !== false) {
        $tts_output="Clint Eastwood's first movie was Francis in the Navy in 1955";
    }
    //Quentin Tarantino had his directorial debut with what film?
    else if (stripos($asr_output,'Tarantino') !== false) {
        $tts_output="Quentin Tarantino's first movie was the Reservoir Dogs";
    }
    //What was Bruce Lees first Hollywood produced film?
    else if (stripos($asr_output,'Bruce') !== false) {
        $tts_output="Bruce Lees first Hollywood film was Enter the Dragon";
    }
    else{
        $tts_output ='I am sorry I do not know the answer to this question';
        //$tts_output=$asr_output;
    }

    $tts = array('results' => $tts_output);
    $json = json_encode($tts);
    $callback = $_GET['callback'];
    echo $callback.'('. $json . ')';
?>