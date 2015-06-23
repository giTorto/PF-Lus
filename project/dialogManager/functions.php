<?php


// function to prompt a simple template getting data from the concepts array
function prompt($input, $template) {
	$prompt = file_get_contents('./nlg-templates/'.$template);
	preg_match_all('/\[%(.*?)%\]/', $prompt, $fieldsm);

    foreach ($fieldsm[1] as $key => $fields) {

        $inputfields = preg_split('/->/', $fields);
        if (count($inputfields) > 1) {
            $i = 0;
            foreach ($inputfields as $if) {
                //print_r(", ");//print_r($if);//print_r(", ");
                if ($i == 0) {
                    $value = $input->{$if};
                    $i++;
                } else {
                    $value = $value->{$if};
                }
            }
        } else {
            $value = $input[reset($inputfields)];
        }
		$prompt = str_replace($fieldsm[0][$key], $value, $prompt);
	}
	return $prompt;
}

// example of a simple function that return a plain message
function returnMessage($input, $string) {
	return $string;
}

function storeGoodInfos($input, $string) {
    $dm = DialogManager::getInstance();
    $dm->setCustomValue($string, $input);

}
?>