<?php
/**
 * Created by PhpStorm.
 * User: Giuliano Tortoreto
 * Date: 6/1/15
 * Time: 3:51 PM
 */


/**
 * This function checks the confidence level, based on the threshold for each class and the confidence received
 * @param $results, classes or concepts that have a certain confidence
 * @param $mean_class, the associative array which contains all the means of misclassified result and
 *          the means of proper classified results
 * @param $var_class, the associative array which contains all the variances of misclassified result and
 *          the means of proper classified results
 * @return mixed the result with confidence replaced with 0 or 1.
 *          0 implies to ask for clarification and 1 that is acceptance.
 * @note At the beginning there was also rejection, but tests shown that it was useless,
 *      since it implies to ask the user which class is interested in.
 */
function  checkConfidence($results, $mean_class, $var_class)
{
    //0 Rejection
    //1 Ask
    //2 Acceptance
    foreach($results as $k => $v) {
        $rejection_thres = floatval($mean_class["mis_".$k]) + floatval($var_class["mis_".$k]);
        $ask_thres = $mean_class[$k] - $var_class[$k];


        // if threshold for rejection is higher than ask
        if ($rejection_thres > $ask_thres) {
                $results[$k] = 0;
            continue;
        }
        if ($v < $ask_thres) {
            //Ask explicitly
            $results[$k] = 0;
        } else {
            //Accept
            $results[$k] = 1;
        }

    }
    return $results;
}

/**
 * This function takes in input the json containing the result of concept classification and return an array map with
 * confidence thresholds
 * @param $json_f,  associative array which contains concepts and their relative confidence
 * @return mixed, array of concept grouped for the same object and confidence level
 */
function from_json_to_conc($json_f) {
    $cache_mean_con = load_file_into_array("Threshold_files/con_mean");
    $cache_var_con = load_file_into_array("Threshold_files/con_var");

    $concepts = array();
    $confidence_concept = array();
    $conc_count = array();

    for($i=0; $i < count($json_f["concept"]); $i++) {
        if ($json_f["concept"][$i] != "O")
            if (array_key_exists(substr($json_f["concept"][$i],2), $concepts)) {
                $concepts[substr($json_f["concept"][$i],2)] .= " " . $json_f["words"][$i];
                $confidence_concept[substr($json_f["concept"][$i],2)] += floatval($json_f["conc_conf"][$i]);
                $conc_count[substr($json_f["concept"][$i],2)] += 1;
            }
            else {
                $concepts[substr($json_f["concept"][$i],2)] = $json_f["words"][$i];
                $confidence_concept[substr($json_f["concept"][$i],2)] = floatval($json_f["conc_conf"][$i]);
                $conc_count[substr($json_f["concept"][$i],2)] = 1;
            }
    }

    foreach($confidence_concept as $k => $v){
        $confidence_concept[$k] = $v/$conc_count[$k];
    }

    $concept["class_to_act"] = checkConfidence($confidence_concept,$cache_mean_con,$cache_var_con);
    $concept["class_to_word"] = $concepts;

    return $concept;
}

/**
 * This function allows to load one of the threshold files and all value to an associative array
 * @param $file_name, the file that contains all the threshold to load in the array
 * @return array, the final array which contains for each concept/class the relative threshold
 */
function load_file_into_array($file_name) {
    $cache = array();

    $handle = fopen($file_name, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            // process the line read.
            $words = explode("\t",$line);
            $cache[$words[0]] = isset($words[1]) ? floatval($words[1]) : null;
        }
        fclose($handle);
    } else {
        // error opening the file.
    }
    return $cache;
}

/**
 * This function takes in input the json containing the result of classification and return an array map with
 * confidence thresholds
 * @param $json_in,  associative array which contains class and their relative confidence
 * @return array|mixed, class with relative confidence level
 */
function from_json_to_class($json_in) {
    $cache_mean_class = load_file_into_array("Threshold_files/class_mean");
    $cache_var_class = load_file_into_array("Threshold_files/class_var");

    $class_conf = explode("\t", $json_in["class"]);
    $class_map = Array($class_conf[1] => $class_conf[0]);
    $class = $class_conf[1];

    $class_map = checkConfidence($class_map, $cache_mean_class, $cache_var_class);
    $class_map["class"] = $class;

    return $class_map;
}

/**
 * This function allows to ask to Freebase a certain class or concept
 * @param $class, class to ask to FB
 * @param $concept, concepts on which the query is based
 * @return array|int
 *      Returns an int when there are errors:
 *          0 = combination of concept and class not support
 *          1 = Freebase is down
 *      Returns an array when the query to FB has returned results
 */
function ask_Freebase($class, $concept, $limit=15, $full=false) {
    require '../Slu2Sparql.php';
    require '../SparqlConnection.php';

    // SLU to SPARQL class
    $s2s = new Slu2Sparql('en', $limit, $full);

    // Linked Open Data SPARQL Endpoint
    $endpoint = 'http://lod.openlinksw.com/sparql';

    // Initiate connection [SLOW!!!]
    $db = new SparqlConnection($endpoint);

    // IGNORE THESE!
    $db->setParameter('format', 'application/sparql-results+json');
    $db->setParameter('CXML_redir_for_subjs', '121');
    $db->setParameter('CXML_redir_for_hrefs', '');
    if($full)
        $db->setParameter('timeout', '4000');
    else
        $db->setParameter('timeout', '30000');

    $db->setParameter('debug', 'on');

    ////////print_r($class);
    //////\\//print_r($concept);
    $query = $s2s->mkSparqlQuery($class, $concept);
    //////\\//print_r("<br/>");
    //print_r($query);
    //////\\//print_r("<br/>");
    if(empty($query))
        return 0;

    //file_put_contents($file, ": " . $class , FILE_APPEND | LOCK_EX);
    $results = $db->query($query);

    if (empty($results)){
        return 1;
    }
    //////\\//print_r($results);
    $arr = json_decode($results, TRUE);
    //////\\//print_r($arr); //UNCOMMENT TO SEE the results

    $var = $arr['head']['vars'][0]; // if 1
    $temp = array();
    foreach ($arr['results']['bindings'] as $e) {
        array_push($temp,$e[$var]['value']);
        //$response .= $e[$var]['value'] . "\n";
    }
    $response = array_unique($temp);

    return $response;
}

/**
 * This function is used to store information into a certain object. After this function is called the class
 * is stored properly in order to manage it easily.
 * @param $classes, classes with relative confidence and according to it stored in the proper way
 * @param $secondary, when there is uncertainty on the class the secondary classes are stored
 * @param $doubt_certain, the associative array in which the class is stored
 */
function checkAndStoreClass($classes, $secondary, &$doubt_certain) {
    $class = array();
    $doubt_certain["sure_class"] = "?";

    switch ($classes[$classes["class"]] ) {
        case 0:
            $class["doubt"]= $secondary;
            break;
        case 1:
            $class["certain"]= $classes["class"];
            $doubt_certain["sure_class"] = $classes["class"];
            break;

    }
    $doubt_certain["class"] = $class;

    return;
}

/**
 * This function works as checkAndStoreClass, however it has been created in order to manage easily concepts.
 * Indeed each concept is stored, using the name as key and the type as value.
 * @param $concepts, associative array which contains values with confidence threshold.
 *        the concept is stored according to the confidence threshold
 * @param $conc_class, the object in which all information are stored
 */
function checkAndStoreConc2($concepts, &$conc_class) {
    $doubt_certainty = array();
    $conc_class["known_conc"] = 0;

    $doubt = array(); $doubt["concept"] = array();
    $certain = array();$certain["concept"] = array();
    //\\//print_r("Start checking and storing concepts, ");

    foreach($concepts["class_to_act"] as $k => $v) {
        $key = $concepts["class_to_word"][$k];

        switch ($v) {
            case 0:
                //$doubt[$k] = "?";
                $doubt[$key] =
                    isset($doubt[$key]) ?
                        array_merge($doubt[$key], array($k)) :
                        array($k);

                array_push($doubt["concept"], $key);
                break;
            case 1:
                $certain[$key]=
                    isset($certain[$key]) ?
                        array_merge($doubt[$key], array($k)) :
                        array($k);
                array_push($certain["concept"], $key);
                $conc_class["known_conc"] = $key;

                break;
        }
    }

    $doubt_certainty["doubt"] = $doubt;
    $doubt_certainty["certain"] = $certain;

    $conc_class["concept"] = $doubt_certainty;
    return;
}

/**
 * This function checks if in the input object, there are concepts or classes that must be asked to the user
 * @param $conc_class, the object which contains the structure of concepts and classes
 * @return bool, true if there are doubts, false otherwise
 */
function anyDoubt($conc_class) {
    foreach($conc_class as $key => $value) {
        if (strcmp($key,"class")==0){
            foreach($value as $k => $v) {
                if (strcmp($k, "doubt")==0) {// and
                    if (strcmp(gettype($v),"string")){
                        if (strcmp($v, "?")== 0) return true;
                    }else{
                        if (count($k) > 0) return true;
                    }
                }
            }
        }else{
            foreach($value as $k => $v) {
                if (strcmp($k, "doubt")==0) {// and
                    if (strcmp(gettype($k),"string")){
                        if (strlen($k)>0) return true;
                    }else{
                        foreach($v as $element){
                            if (is_array($element))
                                if (count($element) > 0) return true;
                        }
                    }
                }
                if(strcmp($k, "certain")==0) {// and
                    if (strcmp(gettype($k),"string")){
                        if (strlen($k) == 0) return true;
                    }else{
                        foreach($v as $element){
                            if (is_array($element))
                                if (count($element) == 0) return true;                    }
                    }
                }
            }
        }
    }
    return false;
}

/**
 * This function allows to store information into the $class_conc object easily.
 * @param $class_conc, the main object that will contain all the information
 * @param $type, the type of the question if allows multi answer or just yes and no
 * @param $expected_a, the expected type of the answer, what kind of doubts it should resolve
 * @param string $what, some element to show to the user in order to give a better question
 * @param array $additional, additional elements to add to main object in order to perform a better question
 */
function setQuestion(&$class_conc, $type, $expected_a,$what="", $additional=array()){
    $class_conc["type"] = $type;
    $class_conc["expected_a"] = $expected_a;
    $class_conc["what"] = $what;

    foreach ($additional as $key => $value) {
        $class_conc[$key] = $value;
    }

}

/**
 * This function allows to set all the parameters to ask about a concept which as a low confidence
 * @param $conc_class, the object in which all information are stored
 */
function askAboutUncertainConcept(&$conc_class){
    $one_doubt_key = key($conc_class["concept"]["doubt"]["concept"]);
    $one_doubt_k = $conc_class["concept"]["doubt"]["concept"][$one_doubt_key];
    $first_value_k = key($conc_class["concept"]["doubt"][$one_doubt_k]);
    $final_value = $conc_class["concept"]["doubt"][$one_doubt_k][$first_value_k];

    if(strcmp($one_doubt_k, "?")==0) {
        $known_conc = key($conc_class["concept"]["certain"]["concept"]);
        setQuestion($conc_class, "multi", "concept_key",
            str_replace(".", " ", $conc_class["in_addition"]),
            array("to_ask"=>2, "known_conc" => $conc_class["concept"]["certain"]["concept"][$known_conc]));
        unset($conc_class["miss|wrong"]);

    }else if (strcmp($final_value, "?")==0) {
        setQuestion($conc_class, "multi", "concept_value",
            implode(getConceptsByClass($conc_class["sure_class"]), ", "),
            array("category" => $one_doubt_k));

    }elseif(strcmp($final_value, "??")==0){
        setQuestion($conc_class, "multi", "concept_confirmation",$one_doubt_k);

    }else {
        setQuestion($conc_class,"y/n","concept_value", $one_doubt_k,
            array("category"=>implode(array_map("stripName",array($final_value)))));

    }
}

/**
 * This function allows to set properly parameters about a class which has a low confidence
 * @param $conc_class, the main object which contains the class
 */
function askAboutUncertainClass(&$conc_class){
    $conc_class["known_conc"] = checkKnownConcept($conc_class);
    if (strcmp($conc_class["known_conc"],"")==0)
        $conc_class["known_conc"] = 0;

    if (strcmp(gettype($conc_class["class"]["doubt"]),"string")==0 ) {
        if(strcmp($conc_class["known_conc"], "0")!=0){
            $known_conc = key($conc_class["concept"]["certain"]["concept"]);
            $conc_class["what"] = fromListToString(str_replace("_"," ",getClassesByConcept($conc_class["known_conc"])));
            $conc_class["based_on"] = $conc_class["concept"]["certain"]["concept"][$known_conc];
        }else{
            $conc_class["what"] = fromListToString(str_replace("_"," ",getOtherClasses($conc_class["what"])));
        }
    }else {
        $known_conc = key($conc_class["concept"]["certain"]["concept"]);
        if(strcmp($conc_class["known_conc"],"0")!=0) {
            $conc_class["what"] = fromListToString(str_replace("_"," ",enrichAlternatives($conc_class)));
            $conc_class["based_on"] = $conc_class["concept"]["certain"]["concept"][$known_conc];
        }else{
            $conc_class["what"] = fromListToString(str_replace("_"," ",enrichAlternatives($conc_class,false)));
        }

    }
}

/**
 * This function aims to set the parameters in order to perform a specific question. The order of the condition
 * lead to have priority on classes and ask about concepts only after
 * @param $conc_class, main object which contains everything needed for the dialog manager
 * @return mixed, result is the main object with the information stored in order to perform a certain question
 */
function decideArgument($conc_class) {
    //if there are too much concepts
    if ($conc_class["too_much"]>0){
        //choose which
        $conc_class["class|conc"] = "conc";
        return $conc_class;
    }

    //if I am sure of the class
    if (count($conc_class["class"]["certain"])>0) {
            //print_r(", ");//print_r("Class certain");//print_r(", ");

        $conc_class["sure_class"] = $conc_class["class"]["certain"];
        //ask for concept
        $conc_class["class|conc"] = "conc";

        if (count($conc_class["concept"]["doubt"]["concept"]) < 1){
            setQuestion($conc_class,"multi","concept_key", fromListToString(getConceptsByClass($conc_class["sure_class"]),"\\"));
        }else{
            // I have to ask about the concept I'm uncertain
           askAboutUncertainConcept($conc_class);
        }

    }else{ // if on class there is uncertainty, resolve the class uncertainty first
        //print_r(", ");//print_r("Class uncertain"); //print_r(", ");

        $conc_class["class|conc"] = "class";
        setQuestion($conc_class,"multi","sure_class");
        askAboutUncertainClass($conc_class);
    }

    return $conc_class;
}

/**
 * This function allows to return more possible classes to the user
 * @param $conc_class, the object from which data are retrieved
 * @param bool $knowConc, this flag allows the function to know if the alternatives must be related to a certain concept or not
 * @return array, the array of alternatives
 */
function enrichAlternatives($conc_class, $knowConc= true){
    if ($knowConc)
        $related_class = getClassesByConcept($conc_class["known_conc"],2, array_keys($conc_class["class"]["doubt"]));
    else
        $related_class = getSliceOfClasses(3);

    $union = array_merge($related_class,array_keys($conc_class["class"]["doubt"]),array("movie", "actor"));

    return getSliceOfClasses(5,$union);
}

/**
 * This function aims to check if there are too many concept in the sentence received. If in the sentence too
 * much concepts are found, a flag is set to true
 * @param $doubt_certain, the object in which concepts are stored
 * @return mixed, the resulting object containing the flag set as true
 */
function checkNumberOfConcepts($doubt_certain) {
    $doubt_certain["what"] = array();
    $doubt_certain["too_much"] = 0;

    // I check that I have only one concept
    if (count($doubt_certain["concept"])>1) {
        // if I am sure about more than 2 concept I have to ask, about which the user want to know
        if (isset($doubt_certain["concept"]["certain"]["concept"])
            && count($doubt_certain["concept"]["certain"]["concept"])>2){
            $doubt_certain["too_much"] = 2;
            $doubt_certain["what"] = fromListToString($doubt_certain["concept"]["certain"]["concept"]);
            $doubt_certain["type"] = "multi";

            // if I am doubtful for more than 1, I ask about which want infos
        }else if (isset($doubt_certain["concept"]["doubt"]["concept"])
            && count($doubt_certain["concept"]["doubt"]["concept"])>2){
            $doubt_certain["too_much"] = 2;
            $doubt_certain["what"] = fromListToString($doubt_certain["concept"]["doubt"]["concept"]);
            $doubt_certain["type"] = "multi";

            // if I doubt about a concept and I am sure about another one, I will ask only about the certain one
        }else if (isset($doubt_certain["concept"]["certain"]["concept"])
            && isset($doubt_certain["concept"]["doubt"]["concept"])) {
            if ( (count($doubt_certain["concept"]["certain"]["concept"]) + count($doubt_certain["concept"]["doubt"]["concept"])) >2) {
                $doubt_certain["too_much"] = 1;
                $doubt_certain["what"] = fromListToString($doubt_certain["concept"]["certain"]["concept"]);
                $doubt_certain["type"] = "y/n";
            }
        }
    }

    return $doubt_certain;
}

/**
 * This function aims to transform an associative array into a string. Only keys are kept.
 * Equivalent to implode(array_keys($map)).
 * @param $map, the map to convert into a string
 * @param string $glue, the string that is used to  put together the elements of the array
 * @return string, the result of the concatenation of the elements
 */
function fromMapToString($map, $glue= ", ") {
    $new = array(); $words = "";
    foreach($map as $k => $v){
        if(strpos($k, "other") == false)
            $words .= $k . $glue;
    }

    return $words;
}

/**
 * This function aims to transform an associative array into a string. Only values are kept.
 * Equivalent to implode(array_values($map)).
 * @param $list
 * @param string $glue
 * @return string
 */
function fromListToString($list, $glue= ", ") {
    $words = "";
    foreach ($list as $word) {
        if(strpos($word, "other") == false)
            $words .= $word . $glue;
    }

    return $words;
}

/**
 * This function allows to transform a certain concept or class from a doubt to certain. All the changes are
 * performed into the main object
 * @param $conc_class, the main object which contains certainties and doubt
 * @param string $what, the type of the object that becomes certain, works only if values are "class" or "concept"
 * @param string $value_type, if this variable is equal to "element" it takes the new certain values
 *      from an element in the doubt array, otherwise it takes the value from the @param $correct_value
 * @param string $correct_value, if the previous variable is not equal to "element" the new certain value will be this
 * @note  it works also to convert doubt, when there are no concept at all, another function must be called
 */
function fromDoubtToCertain(&$conc_class, $what="class", $value_type="element", $correct_value="sure_class" ){

    if (strcmp($what, "class") == 0 ) {
        if (strcmp($value_type,"element")==0 )
            $conc_class[$what]["certain"] = $conc_class[$correct_value];
        else
            $conc_class[$what]["certain"] = $correct_value;

        unset($conc_class[$what]["doubt"]);

    }else {
        $one_doubt_k = key($conc_class[$what]["doubt"][$what]);
        $one_doubt_key = $conc_class[$what]["doubt"][$what][$one_doubt_k];
        $one_doubt_v = key($conc_class[$what]["doubt"][$one_doubt_key]);

        if (strcmp($value_type,"element") == 0 ){
            $one_doubt_value = $conc_class[$what]["doubt"][$one_doubt_key][$one_doubt_v];

            $conc_class[$what]["certain"][$one_doubt_key] =
                isset($conc_class[$what]["certain"][$one_doubt_key]) ?
                    array_merge($conc_class[$what]["certain"][$one_doubt_key], array($one_doubt_value)) :
                    array($one_doubt_value);

            array_push($conc_class[$what]["certain"][$what], $one_doubt_key);
        }else{
            ////////print_r("here I am, ");
            ////////print_r($one_doubt_key);
            ////////print_r("Correct value is " . $correct_value . ", ");
            $conc_class[$what]["certain"][$one_doubt_key] =
                isset($conc_class[$what]["certain"][$one_doubt_key]) ?
                    array_merge($conc_class[$what]["certain"][$one_doubt_key],  array($correct_value)) :
                    array($correct_value);

            array_push($conc_class[$what]["certain"][$what], $one_doubt_key);
        }

        unset($conc_class[$what]["doubt"][$what][$one_doubt_k]);
        unset($conc_class[$what]["doubt"][$one_doubt_key][$one_doubt_v]);
        $conc_class["known_conc"] = $one_doubt_key;

        reset($conc_class[$what]["doubt"][$what]);
    }

    return;
}

/**
 * This function add a new doubt to the main object
 * @param $concept, the new doubt key to add
 * @param $to, the main object to update
 */
function addDoubt($concept, &$to) {
    array_push($to["concept"]["doubt"]["concept"],$concept);
    $to["concept"]["doubt"][$concept] =
        isset($to["concept"]["doubt"][$concept]) ?
            array_merge($to["concept"]["doubt"][$concept], array("?")) :
            array("?");
}

/**
 * This function allows to manage when the user answer to a question about the class the user desire to receive
 * @param $doubt_certain, the main object that contains class and concepts
 * @param $answer, the answer received from the user
 */
function manageNewClass(&$doubt_certain, $answer){
    //answer must match a certain pattern
    preg_match_all('/[a-zA-Z]+/', $answer, $fieldsm);
    $class = checkWord($fieldsm);
    if(!isItANo($answer)){
        if (!empty($class)){
            $doubt_certain["sure_class"] = $class;
            fromDoubtToCertain($doubt_certain, "class", "string", $class);
        }
    }else{
        fromUncertainToDoubt($doubt_certain,"class");
    }
}

/**
 * This function allows to check the result of the question performed to the user. The type of the is open, so the
 * user can tell also no, in case none of the concept type suggested satisfy the user needs.
 * In addition this function manages also the case in which to the user is asked about a concept name.
 * @param $doubt_certain, the main object in which answer is stored if proper
 * @param $answer, the answer of the user
 */
function manageConceptByOpenQuestion(&$doubt_certain, $answer){
    $one_doubt_key = key($doubt_certain["concept"]["doubt"]["concept"]);
    $one_doubt_k = $doubt_certain["concept"]["doubt"]["concept"][$one_doubt_key];

    if (strcmp($doubt_certain["expected_a"], "concept_key")==0){
        //add doubt
        //preg_match_all('/([a-zA-Z]+)/', $answer, $fieldsm);
        //$word = checkWord($fieldsm, $doubt_certain["sure_class"], true);
        if (isItANo($answer)){
            fromCertainToUncertain($doubt_certain);
        }else
            addDoubt($answer, $doubt_certain);
    }else{
        if (isItANo($answer)){
            unset($doubt_certain["concept"]["doubt"]["concept"][$one_doubt_key]);
            unset($doubt_certain["concept"]["doubt"][$one_doubt_k]);
        }else{
            ////////print_r("Something broken here");
            preg_match_all('/([a-zA-Z]+)/', $answer, $fieldsm);
            $word = checkWord($fieldsm, $doubt_certain["sure_class"], true);
            fromDoubtToCertain($doubt_certain,"concept", "stringa", $word);
        }

    }
}

/**
 * This function allows to check the answer of the user. It is used in order to understand answer that
 * should be yes or no for concept.
 * @param $doubt_certain, the object that contains all the data about the state
 * @param $answer, the answer sent by the user
 */
function manageConceptByClosedQuestion(&$doubt_certain, $answer){
    $one_doubt_key = key($doubt_certain["concept"]["doubt"]["concept"]);
    $one_doubt_k = $doubt_certain["concept"]["doubt"]["concept"][$one_doubt_key];

    if(strcmp($doubt_certain["expected_a"], "concept_value")==0){
        ////////print_r($answer);
        if (isItAYes($answer))
            fromDoubtToCertain($doubt_certain, "concept", "element");
        else
            fromUncertainToDoubt($doubt_certain,"concept");
    }else{
        if (isItAYes($answer)){
            ////////print_r("Answer is eventually yes");
            $doubt_certain["concept"]["doubt"][$one_doubt_k] = "?";
        }else{
            ////////print_r("Answer is still no");//////print_r(", ");
            unset($doubt_certain["concept"]["doubt"]["concept"][$one_doubt_key]);
            unset($doubt_certain["concept"]["doubt"][$one_doubt_k]);

        }
    }
}

/**
 * This function aims to check if the user accepted to add the missing concept and
 * if so add it to the main object
 * @param $doubt_certain, the main object to which must be added the new missing concept
 * @param $answer, the answer transcription of the user
 * @return bool, return true if the user accepted, false otherwise
 */
function addMissingConcept(&$doubt_certain, $answer){
    if(isItAYes($answer)){
        addDoubt("?", $doubt_certain);
    }else{
        return false;
    }

    return true;
}

/**
 * This function allows the user that accepted to add the missing concept to lead the main object to a
 * correct state where each concept key is a name. This means that it checks if there are question marks in
 * concept doubts, if so it removes them and add the new name with the relative concept type
 * @param $conc_class, the main object that will contain all the concept needed to perform question and
 *          ask to freebase answers
 * @param $new_value, the new value that replace the question mark
 * @param $new_type, the type of the new value that is replacing the question mark
 */
function resolveMissingConcept(&$conc_class, $new_value, $new_type){
    ////////print_r(", Trying to resolve the missing concept, ");
    unset($conc_class["concept"]["doubt"]);

    $conc_class["concept"]["certain"][$new_value] =
        isset($conc_class["concept"]["certain"][$new_value]) ?
            array_merge($conc_class["concept"]["certain"][$new_value],  array($new_type)) :
            array($new_type);

    array_push($conc_class["concept"]["certain"]["concept"], $new_value);

}

/**
 * This function check the answer of the user and on top of it performs the changes to the data structure
 * according to the answer of the user.
 * @param $doubt_certain, the main object that contains the data structure with all the sensible infos
 * @param $answer, the string sent by the user in answer to the question. Can be open and closed answer
 * @return boolean, true if the user didn't say restart
 */
function checkAnswer(&$doubt_certain, $answer){
    ////print_r("I'm checking the answer.");

    if(strpos($answer,"restart")!==false){
        return false;
    }

    if ($doubt_certain["to_ask"]==2){
        if (strcmp($doubt_certain["miss|wrong"],"miss")==0){
           return addMissingConcept($doubt_certain, $answer);
        }else if(strcmp($doubt_certain["miss|wrong"],"wrong")==0){
            return false;
        }else if (strcmp($doubt_certain["class|conc"],"conc")==0){
            ////////print_r("Here I'm passing");
            if (strcmp($doubt_certain["type"],"multi")==0)
                resolveMissingConcept($doubt_certain, $answer, $doubt_certain["in_addition"]);

            return true;
        }
    }else if ($doubt_certain["to_ask"]==3){
        chooseName($doubt_certain, $answer);

    }else if (strcmp($doubt_certain["class|conc"],"class")==0) {
        manageNewClass($doubt_certain,$answer);
    }else{
        if(strcmp($doubt_certain["type"], "multi")==0) {
            manageConceptByOpenQuestion($doubt_certain, $answer);
        }else{
            manageConceptByClosedQuestion($doubt_certain, $answer);
        }
    }


    return true;

}

/**
 * This function allows to change the data structure of the main object in case the user says that
 * the required class is not the one that the system understood.
 * @param $doubt_certain, the data structure that will contain the new doubt
 * @param bool $class, the flag that makes changes on the class
 */
function fromCertainToUncertain(&$doubt_certain, $class=true){
    if($class){
        $doubt_certain["class"]["doubt"] = "?";
        unset($doubt_certain["class"]["certain"]);
    }


}

/**
 * This function allows to perform a transformation on the main data structure. If a certain concept or class
 * is considered more probable, it is asked to the user. If the user answered that the concept/class is not the right
 * one this function allows to change the value associated to it.
 * @param $doubt_certain, the main object that will contain the doubt on the concept/class
 * @param string $what, if it is equal to "class" than it will create a doubt in the class,
 *        otherwise on the concept, but the value must be equal to "concept"
 */
function fromUncertainToDoubt(&$doubt_certain, $what="class") {
    if(strcmp($what,"class")==0){
        unset($doubt_certain[$what]["doubt"]);
        $doubt_certain[$what]["doubt"] = "?";
    }else{
        $one_doubt_key = key($doubt_certain[$what]["doubt"][$what]);
        $one_doubt_k = $doubt_certain[$what]["doubt"][$what][$one_doubt_key];
        $one_doubt_value = key($doubt_certain[$what]["doubt"][$one_doubt_k]);
        unset($doubt_certain[$what]["doubt"][$one_doubt_k][$one_doubt_value]);

        $doubt_certain[$what]["doubt"][$one_doubt_k] =
            isset($doubt_certain[$what]["doubt"][$one_doubt_k]) ?
                array_merge($doubt_certain[$what]["doubt"][$one_doubt_k], array("?")) :
                array("?");
    }

}

/**
 * This function allows to check if the answer of the user contains an affirmative word
 * @param $words, the string transcript of the user answer
 * @return bool, true if it contains a synonim of yes, false otherwise
 */
function isItAYes($words) {
    //from thesaurus.com
    $yes_syn = array("yes","affirmative","amen","fine","good","okay","true","yea","all right","aye","beyond a doubt","by all means","certainly","definitely","even so","exactly","gladly","good enough","granted","indubitably","just so","most assuredly","naturally","of course","positively","precisely","sure thing","surely","undoubtedly","unquestionably","very well","willingly","without fail","yep");

    foreach($yes_syn as $syn)
        if(strpos($words, $syn)!==false)
            return true;

    return false;
}


/**
 * This function allows to check if the answer of the user contains an affirmative word
 * @param $words, the string transcript of the user answer
 * @return bool true if it contains a synonim of no, false otherwise
 */
function isItANo($words) {
    //from thesaurus.com
    $no_syn = array("no","negative","nix","absolutely not","by no means","never","no way","not at all", "none", "no one","nobody","nothing","nil","zero","zilch","no one at all","no part","not a bit","not a soul","not a thing","not any","not anyone","not anything","not one");

    foreach($no_syn as $syn)
        if(strpos($words, $syn)!==false)
            return true;

    return false;
}

/**
 * This function allows to check the answer sent by the user. The system checks if one of the words said
 * by the user contains one of the classes/concepts provided, otherwise return empty string
 * @param $words, this is the array of the answer sent by the user. In the array each element is a word
 * @param bool $conc, this is the flag that allows to use the function with both class and concept
 * @return string, the corresponding value of the words that matches one of the possible classes/concepts
 */
function checkWord(&$words, $by_element="all", $conc=false) {
    if (!$conc)
        if (strcmp(gettype($by_element),"string")==0 && strcmp($by_element,"all")==0)
            $possible = array_keys(getAllClassToConcept());
        else
            $possible = getClassesByConcept($by_element);
    else if($conc) {
        $possible = getConceptsByClass($by_element);
    }

    foreach($possible as $wrd){
        foreach($words as $array){
            foreach ($array as $k => $v){
                $word = strtolower($v);
                ////////print_r("Comparing: " . $word ." vs ". $wrd);
                if(strpos($word, $wrd) !== false){
                    if ($conc){
                        if (strcmp($word,"name")==0)
                            return $by_element . "." . $word;
                        else
                            return $wrd . ".name";
                    }else
                        return $wrd;
                }else if (strpos($wrd, $word) !== false){
                    return $wrd;
                }
            }
        }
    }

    return "";
}

/**
 * This function allows to remove ".name" from a string
 * @param $w, the input word
 * @return mixed, the string without ".name"
 */
function stripName($w){
    return str_replace(".name","",$w);
}

/**
 * This function allows to retrieve the possible concepts relative to a certain class. The retrieved concept
 * are only the one that are supported from the SLU, so it will suggest only concept that are compatible
 * with the determined class.
 * @param $class, the input class to which must be related the concept that will returned
 * @param int $limit, the number of alternatives that will be returned from the system
 * @return array, the array containing some concepts related to a certain class
 */
function getConceptsByClass($class, $limit = 15){
    $all_combi = getAllClassToConcept();

    $temp = array_keys($all_combi[$class]);
    $result = array();
    foreach($temp as $k => $v){
        $words = explode("&",$v);
        $temp = "";
        foreach($words as $word){
            $splitted = explode(".",$word);
            if (strcmp($class, "movie")==0){
                if (strcmp($splitted[0], $class)==0){
                    array_push($result, $class);
                }else{
                    array_push($result, $splitted[0]);
                }
            }else{
                array_push($result, $splitted[0]);
            }
        }
    }

    return getSliceOfClasses($limit, array_unique($result));
}

/**
 * This function has been created in order to retrieve only a limited number of classes.
 * If the number of available classes is bigger than the limit, the element to put in the array of classes
 * will be chosen randomly.
 * @param int $limit, the number of element to add to the array that will be returned
 * @param string $input, the array of classes from which take a slice or the string "all" that it means
 *          that all classes are accepted
 * @return array, the associative array containing the number of element required
 */
function getSliceOfClasses($limit=5, $input="all"){
    if (strcmp(gettype($input),"string")==0 && strcmp($input,"all")==0)
        $all_classes = array_keys(getAllClassToConcept());
    else
        $all_classes = $input;

    $result = array();
    if ($limit < count($all_classes)){
        while($limit > count($result)){
            $intero = rand(0,count($all_classes)-1);
            if(in_array($intero, array_keys($all_classes))==true)
                array_push($result, $all_classes[$intero]);
            $result = array_unique($result);
        }
    }else{
        $result = $all_classes;
    }

    return array_unique($result);
}

/**
 * This function contains the mapping between accepted class and relative concept combination. This function
 * is the base for many function that on top of it return relative classes or concepts.
 * @return array, the associative array of all supported combination of class and concept
 */
function getAllClassToConcept(){
    $all_combi = Array ( "actor" => Array ( "movie.name&character.name" => 0,"actor.name" => 0,"movie.name" => 0 ),
        "character" => Array ( "movie.name&actor.name" => 0 ),
        "movie" => Array ( "producer.name" => 0,"director.name" => 0,"actor.name" => 0,"movie.name" => 0,"company.name" => 0,"country.name" => 0,"rating.name" => 0,"movie.subject" => 0,"movie.genre" => 0,"movie.budget" => 0,"movie.gross_revenue" => 0,"movie.release_date" => 0 ),
        "movie_count" => Array ( "producer.name" => 0,"director.name" => 0,"actor.name" => 0,"movie.name&actor.name" => 0,"movie.name" => 0,"company.name" => 0,"country.name" => 0,"rating.name" => 0,"movie.subject" => 0,"movie.genre" => 0,"movie.budget" => 0,"movie.gross_revenue" => 0,"movie.release_date" => 0 ),
        "person" => Array ( "person.name" => 0,"movie.name" => 0 ),
        "producer" => Array ( "producer.name" => 0,"movie.name" => 0 ),
        "director" => Array ( "director.name" => 0,"movie.name" => 0 ),
        'company'=> Array ( "movie.name" => 0 ), 'rating'=> Array ( "movie.name" => 0 ), 'country'=> Array ( "movie.name" => 0 ),
        'language'=> Array ( "movie.name" => 0 ), 'genre'=> Array ( "movie.name" => 0 ), 'budget'=> Array ( "movie.name" => 0 ),
        'release_date' => Array ( "movie.name" => 0 ), 'gross_revenue' => Array ( "movie.name" => 0 )
    );

    return $all_combi;
}

/**
 * This function allows to retrieve supported classes given a fixed number of concepts. If the number of concept
 * is bigger than 2, it will consider only the first 2 elements.
 * @param $in_concept, the associative array containing the concepts
 * @param int $limit, the maximum number of classes returned
 * @param array $array_diff, the classes to remove, in case some are already available
 * @return array, the associative array containing a fixed number of possible classes
 */
function getClassesByConcept($in_concept, $limit=20, $array_diff=array()){
    $concepts = array_slice(explode(" ", $in_concept),0,2);
    $all_combi = getAllClassToConcept();
    $i = 0; $result = array(); $result[0] = array(); $result[1]=array();

    foreach($concepts as $k => $v){
        foreach($all_combi as $class => $array){
            foreach($array as $conc =>$zero){
                if (strpos($conc,$v)!== false){
                    array_push($result[$i],$class);
                }
            }
        }
        $i++;
    }
    if (count($concepts)>1 && strcmp($concepts[1], "")!=0)
        $result = array_intersect($result[0],$result[1]);
    else
        $result = $result[0];

    $result = array_unique($result);
    //////print_r($result);
    //////print_r($array_diff);
    $result = array_diff($result, $array_diff);

    return getSliceOfClasses($limit, $result);
}

/**
 * This function allows to retrieve different classes, without returning the already "known" once
 * @param $class_proposed, the array of already "known" classes
 * @return array, the array of classes that are not yet "known"
 */
function getOtherClasses($class_proposed) {
    $classes = explode(", ",$class_proposed);
    $possible_classes = array_keys(getAllClassToConcept());
    $diff = array_diff($possible_classes, $classes);
    $result = copyJustValues($diff);


    return getSliceOfClasses(7,$result);

}

/**
 * This function is like array_values.
 * @param $in_array, the input array with key values object
 * @return array, the array with values and new indexes
 */
function copyJustValues($in_array){
    $result = array();
    foreach($in_array as $k => $v)
        array_push($result,$v);

    return $result;
}

/**
 * This function allows to return the concatenation of already certain concept in the given object that
 * must have a certain data structure
 * @param $doubt_certain, the main object in which certain concept are available
 * @return string, the concatenation of all concept type
 */
function checkKnownConcept($doubt_certain){
    $result = "";
    foreach (array_unique($doubt_certain["concept"]["certain"]["concept"]) as $k => $v) {
        foreach($doubt_certain["concept"]["certain"][$v] as $key => $value){
            $result .= $value . " ";
        }

    }

    return $result;
}

/**
 * This function allows to change how concepts are written in order to deal with the ask_freebase function
 * which expects a certain format for concepts and class.
 * @param $doubt_certain, the main object in which elements are rearranged
 */
function invert_concept(&$doubt_certain) {
    $temp = array();
    foreach($doubt_certain["concept"]["certain"] as $k => $v) {
        foreach($v as $key => $value){
            if(strcmp($k,"concept")!=0)
                $temp[$value] = str_replace('"','',$k);
        }

    }

    $doubt_certain["concept"]["certain"] = $temp;
}

/**
 * This function allows to bring the dialogue object to the previous structure. This method is
 * called when all concept are certain, but the freebase mapping is not unique and so a question
 * is performed.
 * @param $doubt_certain, the main dialogue object which contains concepts and class to ask to
 *          freebase.
 */
function revert_concept(&$doubt_certain) {
    $temp = array();
    foreach($doubt_certain["concept"]["certain"] as $k => $v){
        array_push($temp["concept"], $v);
        $temp[$v] = isset($temp[$v]) ? array_merge($temp[$v], array($k))
            : array($k);
        unset($doubt_certain["concept"]["certain"][$k]);
    }

    $doubt_certain["concept"]["certain"] = $temp;
}

/**
 * This function allows to remove useless fields from the main object. Indeed this function is called only
 * when all concepts and the class are confirmed by the user.
 * @param $doubt_certain, the main object in which all sensible information are stored
 */
function re_arrange_concept(&$doubt_certain) {
    invert_concept($doubt_certain);
    unset($doubt_certain["concept"]["doubt"]);

    $doubt_certain["to_ask"] = 0;
    unset($doubt_certain["expected_a"]);
    unset($doubt_certain["class|conc"]);
    unset($doubt_certain["type"]);
    unset($doubt_certain["too_much"]);
    unset($doubt_certain["concept"]["certain"]["concept"]);

    $doubt_certain["#concept"] = count($doubt_certain["concept"]["certain"]);

}

/**
 * This function checks if the given class and the given combination of concepts are supported.
 *  If the given combination of concepts miss a concept the function will set the main object in
 *  order to allow the user to say the missing one.
 *      Otherwise, if some concept are not supported
 *      with a certain class the function will set all the properties into the main object in order
 *      to advise the user that has to restart.
 * @param $doubt_certain, the main object that will contain all the information needed to answer to the user
 * @return bool, return true if there are missing or wrong concept, return false if concepts and class are
 *          properly matched
 */
function moreInfoNeeded(&$doubt_certain){
    $copy = $doubt_certain["concept"]["certain"];
    unset($copy["concept"]);
    $conceptsForClass = array_keys(getAllClassToConcept()[$doubt_certain["sure_class"]]);
    $store_other = array();
    $remember = "";
    $final_proposal = "";
    $doubt_certain["miss|wrong"] = "wrong";



    foreach($conceptsForClass as $i => $element){
        $needed = explode("&", $element);
        $count = 0;
        $needed_count = count($needed);
        foreach($copy as $k => $v){
            if(strcmp($k,"concept")!=0){

                foreach($v as $ind =>$interesting) {
                    ////////print_r("-->What I have: " . var_dump($copy).", ");

                    foreach($needed as $index => $need) {
                        ////////print_r("-->-->Compare words :" . $interesting . " vs " . $need .", ");

                        if (strcmp($interesting, $need)==0)
                            $count++;
                        else
                            $remember = $need;
                    }
                }

            }
        }

        ////////print_r("-->Compare numbers :" . $needed_count . " vs " . $count .", ");
        if ($needed_count == $count){
            return false;
        }else{
            if ($needed_count>1 && $count>0){
                $final_proposal = $remember;
                $doubt_certain["miss|wrong"] = "miss";
            }
            else{
                array_push($store_other, $remember);
            }
            $remember ="";


        }
    }

    $key = key($doubt_certain["concept"]["certain"]["concept"]);

    $doubt_certain["category"] = $doubt_certain["concept"]["certain"]["concept"][$key];


    if (strcmp($doubt_certain["miss|wrong"],"wrong")==0){
        $doubt_certain["what"] = fromListToString($store_other);
        $doubt_certain["in_addition"] = $store_other;
    }else{
        $doubt_certain["what"] = str_replace("."," ",$final_proposal);
        $doubt_certain["in_addition"] = $final_proposal;

    }

    $doubt_certain["one_more_question"] = 1;
    unlink("states/previous_state.ser");
    return true;
}

/**
 * This function should allow to store movie.name, actor.name...., in order to devise an authomatic mapping
 * from name coming from SLU and the full names of Freebase.
 * This function creates a background process for each concept
 * @param $concepts
 */
function cacheConcepts($concepts){
    $class = "";
    $conce = array();
    foreach($concepts as $cert_doubt => $cd_array){
        foreach($cd_array as $key => $specific_conc){
            if (strcmp($key,"concept")==0)//key = rocky
                continue;
            else{
                foreach($specific_conc as $ind => $interesting){ // interesting = movie.name
                    $conce[$interesting] = $key;
                    $prefix_suffix = explode(".",$interesting);
                    if (strcmp($prefix_suffix[1], "name")==0)
                        $class = $prefix_suffix[0];
                    else
                        $class = $prefix_suffix[1];


                    $output_file = "out_parallel.txt";
                    $class = str_replace(" ","_",$class);

                    $cmd = "php -f ./parallelProcess.php ". $class .
                        " " . str_replace(" ", "_", $interesting).
                        " " . str_replace(" ", "_", $conce[$interesting]);

                    exec(sprintf("%s > %s 2>&1 &", $cmd, $output_file));
                }
            }
        }
    }

}

/**
 * This function allows to retrieve the data contained in the cache.
 * @return array, an associative array with concept type e concept name as key and the list of
 *      mappings to freebase
 */
function loadMap() {
    $resp = file_get_contents("http://localhost/project/dialogManager/from_SLU_to_Names/cache.txt");

    $contents = explode("\n",$resp);
    $array_map = array();

    foreach($contents as $line) {
        $words = explode("\t",$line);
        $key = $words[0] . "&" . $words[1];
        unset($words[0]); unset($words[1]);
        $array_map[$key] = array_values($words);
    }

    return $array_map;
}

/**
 * This function just check if the combination already exist in the cache file. According to the passed
 * parameters return a boolean or the array of matches.
 * @param $key, the concept key like "movie.name"
 * @param $value, the concept value like "rocky
 * @param bool $simple, this parameter determine the type of the result. if true return just a boolean
 *          otherwise the array of matches
 * @return bool|array,
 *      bool(@oaram $simple=true) -> true if the name is already cached, false otherwise
 *      array(@oaram $simple=false) -> array with matches
 */
function sluToFb($key,$value, $simple=true){
    $already_cached = loadMap();
    $result = array();

    foreach ($already_cached as $k => $FBname) {
        $temp = explode("&",$k);
        $type = $temp[0]; $simple_name = strtolower($temp[1]);

        if(strcmp($key,$type)==0){
            $regex = "/((.*[^a-zA-Z])|^)".
                str_replace(" ","(([^a-zA-Z].*[^a-zA-Z])|[^a-zA-Z])",$simple_name)
                . "(([^a-zA-Z].*)|$)/";

            preg_match($regex, $value, $result);

            if(!empty($result[0])){

                if($simple){
                    return true;
                }else{
                    $regex = "/((.*[^a-zA-Z])|^)".
                        str_replace(" ","(([^a-zA-Z].*[^a-zA-Z])|[^a-zA-Z])", $value)
                        . "(([^a-zA-Z].*)|$)/i";

                    $result = preg_grep($regex,$FBname);

                    return $result;

                }
            }

        }

    }

    if($simple)
        return false;
    else
        return $result;

}

/**
 * This function return the Full Names of FB given the main object containing key and values
 * @param $conc_class, the main object which contains $key and $value to check
 * @return array, a list of keys with the corresponding Fb mappings to the original value
 */
function getSluToFbNames($conc_class){
    $result = array();

    foreach($conc_class["concept"]["certain"] as $key => $value){
        if(strcmp($key,"concept")!=0){
            $result[$key] = sluToFb($key,$value,false);
        }
    }


    return $result;
}

/**
 * This function allows to transform a concept written in concept tagger way in a human readable way.
 * @param $key, the concept to simplify
 * @return string, the resulting simplified concept
 */
function simplifyConcept($key){
    $splitted = explode(".",$key);
    $result = "";

    if (strcmp($splitted[0], "movie")==0) {
        if (strcmp($splitted[1], "name")==0){
            $result = $splitted[0];
        }else{
            $result = $splitted[1];
        }
    }else{
        $result = $splitted[0];
    }

    return $result;
}

/**
 * This function allows to check if one element in the response is a number, if so it returns in a more
 * readable way
 * @param $word, a word that can be also a number
 * @return string, the word transformed if and only if it was a number
 */
function if_number_format_it($word) {
    return is_numeric($word) ? number_format($word) . " dollar" : $word;
}

/**
 * This function allows to generate an answer to the user given the dialogue object and a flag
 * that distinguish between response with only one element and more.
 * @param $conc_class, the dialogue object containing all the information of the dialogue,
 *         Freebase answer included
 * @param $single, the flag that tell to the function if there is only one element or more
 * @return mixed|string, return the answer for the user.
 */
function setResponseInfo(&$conc_class, $single){
    $conc_class["response"] = implode(", ", array_map("if_number_format_it",$conc_class["response"]));
    $answer = ""; $count = 1; $conc_class["be_verb"] = "is";

    if(!$single){
        $conc_class["sure_class"] = $conc_class["sure_class"]. "s";
        $conc_class["be_verb"] = "are";
    }

    foreach($conc_class["concept"]["certain"] as $key => $value){
        if (strcmp($key,"concept")!=0){
            if (strpos($conc_class["sure_class"], "movie")!==false){
            //get movie, one concept
                $conc_class["z"] = $value;
                $answer = prompt($conc_class, 'movie_response.txt');
                break;
            }else if ($conc_class["#concept"]>1){
                //two concepts
                if (strcmp($conc_class["sure_class"],"character")==0){
                    if (strpos($key,"movie")!==false){
                        $conc_class["z"] = $value;
                    }else{
                        $conc_class["x"] = $value;
                    }
                    if ($count==$conc_class["#concept"])
                        $answer = prompt($conc_class, 'character_response.txt');

                }else{
                    if (strpos($key,"movie")!==false){
                        $conc_class["z"] = $value;
                    }else{
                        $conc_class["x"] = $value;
                    }

                    if ($count==$conc_class["#concept"])
                            $answer = prompt($conc_class, 'actor_by_char_response.txt');
                }

                $count++;
            }else{
                //get other, one concept
                $conc_class["z"] = $value;
                $answer = prompt($conc_class, 'by_movie_response.txt');
                break;

            }

        }
    }
    return $answer;

}

/**
 * This function allows to easily check if in the cache there are mappings between the concept given
 * and Fb names.
 * @param $conc_class, the main object that contains concept and after the function is performed also
 *          the mapping if existent
 * @return int, 0 when there are more than one mapping from the name given and Fb names,
 *              1 otherwise -> 1 or 0 mappings
 */
function checkFBNames(&$conc_class){
    $conc_class["full"] = false;
    $continue = 1;

    if (!isset($conc_class["fb_names"])){
        $conc_class["fb_names"] = getSluToFbNames($conc_class);
    }

    $continue = setQuestionOrObject($conc_class);

    return $continue;
}

/**
 * This function allows to check the answer raised in order to find the exact name in Fb.
 * @param $conc_class, the main object that will store the information
 * @param $answer, the answer sent by the user
 */
function chooseName(&$conc_class, $answer){
    $regex = phrase_to_regex(str_replace('"','',$answer));
    ////////print_r(", The regex is " .$regex);
    // to check
    $temp = array();
    ////////print_r(", the starting array is: ". var_dump($conc_class["fb_names"]));

    if (isItANo($answer)){
        return;
    }


    $temp[$conc_class["key"].".name"] =
        preg_grep($regex,$conc_class["fb_names"][$conc_class["key"].".name"]);

    ////////print_r(",  the key to update is " . $conc_class["key"]);

    // if the name of the movie is equal to the root, I look for the ones with minimum edit distance
    if (count($conc_class["fb_names"][$conc_class["key"].".name"]) == count($temp[$conc_class["key"].".name"])){
        reduce_elements($temp, $conc_class["key"].".name", $answer);
    }

    if(!empty($temp[$conc_class["key"].".name"])){
        unset($conc_class["fb_names"][$conc_class["key"].".name"]);
        if (empty($conc_class["fb_names"])){
            $conc_class["fb_names"] = $temp;
        }else{
            $conc_class["fb_names"][$conc_class["key"].".name"] = $temp[$conc_class["key"].".name"];
        }
    }

}

/**
 * This function reduces the number of element into an array of string to the number of elements
 * which has the lowest levenshtein distance.
 * @param $names, the array of array of words to reduce
 * @param $key, the key to check the lowest levenshtein distance
 * @param $answer, the answer on which reduce the number of elements
 */
function reduce_elements(&$names, $key, $answer){
    $nearest_once = array();
    $distance_words = array();
    foreach($names[$key] as $ind => $word){
        $distance_words[$ind] = levenshtein($word, $answer);
    }

    $smallest_value = min($distance_words);

    $keys = array_keys($distance_words,$smallest_value);

    foreach($keys as $k){
        array_push($nearest_once,$names[$key][$k]);
    }

    $names[$key] = $nearest_once;
}

/**
 * This method allows to set all the fields into the main object in order to make a question
 * for the user, if needed. Wherease, if there is a unique mapping of the concept on Fb,
 * the concept name is replaced with the Fb name. Another case is that the name is not in the
 * cache, so the system will go on.
 * @param $conc_class, the main object that will store all the changes.
 * @return int, 0 if there are many concept mapping which implies a question
 *              1 if there is a unique mapping or none
 */
function setQuestionOrObject(&$conc_class){
    $to_reach = $conc_class["#concept"]; $count=0;
    $unique_mapping= false;
    ////////print_r(", SETTING QUESTION, ");
    ////////print_r($conc_class["fb_names"]);
    foreach($conc_class["fb_names"] as $key => $value){
        ////////print_r("the " . $key . " has " . count($value). ", ");
        if (count($value) == 1){
            // unique mapping
            $unique_mapping = true;
            $conc_class["concept"]["certain"][$key] = '"'.$value[key($value)].'"';
            $count++;
            $conc_class["many_called_the_same"]=0;

        }elseif (count($value) > 1){
            //prepare question
            $conc_class["to_ask"] = 3;
            ////print_r($value);
            $conc_class["what"] = implode(", ",getSliceOfClasses(4, copyJustValues($value)));
            ////print_r(", passed, ");

            $conc_class["type"] = "concept_value";
            $conc_class["key"] = str_replace(".name", "",$key);
            $unique_mapping = false;
            $conc_class["many_called_the_same"]=1;
            revert_concept($conc_class);
            return 0;
        }else {
            $unique_mapping = false;
            $conc_class["many_called_the_same"]=0;

            //ask to freebase, I still don't know that name
        }
    }

   // //////print_r("compare " . $count . " vs " . $to_reach);
    if ($unique_mapping && $count==$to_reach){
        $conc_class["full"] = true;
    }

    return 1;
}

/**
 * This method allows to create a regex from a phrase received by the user. This function is
 * used to understand which mapping to Fb the user chosen.
 * @param $answer, the answer said by the user.
 * @return string, the regex created starting from the answer of the user
 */
function phrase_to_regex($answer){
    $words = explode(" ", $answer);
    $result = "/((.*[^a-zA-Z])|^)";
    $array = array();
    foreach($words as $word){
       array_push($array,number_to_string($word));
    }
    $result .= implode("(([^a-zA-Z].*[^a-zA-Z])|[^a-zA-Z])", $array) . "(([^a-zA-Z].*)|$)/i";
    return $result;
}

/**
 * This function allows to map a transcript number into a arabic number or into a roman one.
 * @param $word, the word that can be a string number or everything else
 * @return string, the number replaced with some alternatives or the original word
 */
function number_to_string($word){
    $mapping = array("one" => "(one|1|i)", "two" => "(two|2|ii)", "three" => "(three|3|iii)",
        "four" => "(four|4|iv)", "five" => "(five|5|v)", "six" => "(six|6|vi)", "seven" => "(seven|7|vii)",
        "eight" => "(eight|8|viii)", "nine" => "(nine|9|ix)","1" => "(one|1|i)", "2" => "(two|2|ii)", "3" => "(three|3|iii)",
        "4" => "(four|4|iv)", "5" => "(five|5|v)", "6" => "(six|6|vi)", "7" => "(seven|7|vii)",
        "8" => "(eight|8|viii)", "9" => "(nine|9|ix)");

    $word = empty($mapping[$word]) ? $word : $mapping[$word];
    return $word;
}

/**
 * This function allows to advise the user that the answer is not precise. Indeed in the meanwhile
 * the answer of the query has been computed, the system find out that there are many concept
 * with that name.
 * @param $answer, the string that contains the message to give to the user
 * @param $doubt_certain, the main object that contains all the dialogue information
 */
function addDetail(&$answer, &$doubt_certain){
    $chiavi = array();
    foreach ($doubt_certain["fb_names"] as $key => $value){
        if(count($value)>1){
            array_push($chiavi, simplifyConcept($key));
        }
    }

    $answer .= "Despite there are many " .implode("s and ", $chiavi) ."s with this name."
        . " If you want a more specific answer, ask it again.";
}
?>


