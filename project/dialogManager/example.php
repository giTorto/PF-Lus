<?php

function getAnswer(){
    global $answer;
    return $answer;
}

// include main functions and objects
include_once('functions.php');
include_once('DialogManager.class.php');

// setting up some variables
$perform_query = false; $dialogue_object = array(); $what_wait = "";
$answer = $input_phrase; $message_to_return = "";

// if asr confidence is too low, this variable remains empty in order to avoid useless call
if (empty($wrap_up)) {
    $message_to_return = "Sorry, I don't understand what you said. Can you repeat please?";
    return;
}

// comes from super file
$input_class = $wrap_up['classes'];
$input_concept = $wrap_up['concepts'];

// get previous state of the DialogManager
$has_state = DialogManager::restoreState('previous_state');

// get the instance of the DialogManager
$dm = DialogManager::getInstance();

// if the previous state cannot be loaded, I set the concepts from SLU
if (!$has_state) {
    $dm->setCustomValue("waitFor", "q");
}else {
    //take the old input
    $dialogue_object = $dm->getInput();
}

// set the value according to which is the custom value in dialog manager (waiting for answer or for question)
$what_wait = $dm->getCustomValue("waitFor") != null ? $dm->getCustomValue("waitFor") : "q";

// if the dialog manager is waiting for question
if (strcmp($what_wait, "q") == 0) {
    //check and store classes and concepts
    checkAndStoreClass($input_class, $secondary_class, $dialogue_object);
    checkAndStoreConc2($input_concept, $dialogue_object);

    // check if there are right number of concepts, less than 3
    $dialogue_object = checkNumberOfConcepts($dialogue_object);
    $dialogue_object["expected_a"] = "?";

}else{
    // if waiting for answer I get the previous input
    $dialogue_object = $dm->getInput();
    // check the answer of the user against the previous values
    // from previous value easy to direct
    $check = checkAnswer($dialogue_object,$answer);
    //check is equal to false if and only if restart is said by the user
    if (!$check){
        unlink("states/previous_state.ser");
        $message_to_return = "Ok. Let's restart from the beginning. Tell me a question";
        return;
    }

}

////print_r($doubt_certain);
// no matter if the system is certain about a certain concept, Greedy approach
// check if already cached, otherwise try to cache it
cacheConcepts($dialogue_object["concept"]);

// checks if the confidence of the concept and class was high enough
if (anyDoubt($dialogue_object) || $dialogue_object["too_much"]) {
    ////print_r("There are doubts");////////print_r(", ");
    //set some values
    $dm->setCustomValue("waitFor", "a"); $dialogue_object["to_ask"] = 1;
    // set parameters in order to perform a certain question
    $dialogue_object = decideArgument($dialogue_object);
}else{
    ////print_r("NO doubts");//////print_r(", ");
    // if I need one more concept or if the concept is not usable for a certain class,
    if (!moreInfoNeeded($dialogue_object)){
        $dm->setCustomValue("waitFor", "q");

        //////print_r("before re-arranging, ");
        // clean a bit the main object used as input
        re_arrange_concept($dialogue_object);

        // check if there are many mappings or not
        //////print_r("before checking mappings, ");

        if(checkFBNames($dialogue_object)==0){
            //////print_r("many doubts,");
            $dm->setCustomValue("waitFor", "a");
        }else{
            // no mappings or exactly one
            $dialogue_object["response"] =
                ask_Freebase($dialogue_object["class"]["certain"],
                    $dialogue_object["concept"]["certain"], 10,
                    $dialogue_object["full"]);

            unset($dialogue_object["fb_names"]);
        }

    }else{     // cannot ask to freebase
        //////////print_r("Something missing");////////print_r(", ");
        $dialogue_object["to_ask"] = 2;
    }

}



//////////print_r(", Data stored:, ");
//////////print_r($doubt_certain);
//////////print_r(", ------------, ");

//set the new input
$dm->setInput($dialogue_object);

//-------------------------- Natural Language Generation ------------------------//

// set the filename of the conditions to verify
$dm->setConditionsFilename('conditions/conditions.xml');

// run the DialogManager and get the result
$myresult = $dm->run();

// save the state for future use
$dm->saveState('previous_state');

//0 is the default answer when there are no questions
if(strcmp($myresult,"0")==0) {
    if ($dialogue_object["response"] == 0) { //empty query
        $answer = "Sorry, I don't know how to answer to this question";
    } elseif ($dialogue_object["response"] == 1) {//freebase is down
        $answer = "I'm so sorry, but I have a very bad headache in this moment. So I think that is better that you make your questions later, please.";
    } elseif (empty($dialogue_object["response"])) {
        $answer = prompt($dialogue_object, 'no_response.txt');
    } else {
        if (count($dialogue_object["response"]) == 1) {
            $answer = setResponseInfo($dialogue_object, true);
        } else {
            $first_part = "";
            $answer = setResponseInfo($dialogue_object, false);
            //if there are many result, and I found many with that name
            if (checkFBNames($dialogue_object) == 0){
                addDetail($first_part, $dialogue_object);
                $answer .= $first_part;
            }
            unset($dialogue_object["fb_names"]);

        }
    }
    $message_to_return = $answer;
}else{ // not waiting for Freebase
    $message_to_return = $myresult;
}

?>