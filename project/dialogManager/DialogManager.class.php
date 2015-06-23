<?php

class DialogManager {

    static private $instance;
	protected $conditions_filename;
	protected $input;
	protected $custom;
	
	/*
	* The constructor
	*/
	public function __construct() {
		$this->conditions_filename = 'conditions/conditions.xml';
		$this->custom = array();
	}
	
	/*
	* The function to be called statically to get the instance of the class
	*/
	public static function getInstance() {
        if (self::$instance == NULL) {
            self::$instance = new DialogManager();
        }
        return self::$instance;
    }
	
	/*
	* The function to be called statically to create a text from a template
	* input:
	* $query_results: is the object obtained by the execute SLU query
	* $template: is the filename of the template to be used
	*
	* returns the text
	*/
	public static function NLG($query_results, $template) {
		$input = $query_results;
		$prompt = file_get_contents('./nlg-templates/'.$template);

		preg_match_all('/<foreach \[%(.*?)%\] limit=(.*?)[[:space:]]*>(.*?)<\/foreach>/is', $prompt, $matches);
		for ($k = 0; $k < count($matches[1]); $k++) {
			$forfields = preg_split('/->/', $matches[1][$k]);
			$i = 0;
			foreach ($forfields as $if) {
				if ($i == 0) {
					$bindings = $input->{$if};
					$i++;
				} else {
					$bindings = $bindings->{$if};
				}
			}
			$index = 0;
			$prompt_tmp = '';
			foreach ($bindings as $item) {
				if ($index >= $matches[2][$k]) {
					break;
				}
				$tmp = $matches[3][$k];
				preg_match_all('/\[%(.*?)%\]/', $matches[3][$k], $fieldsm);
				foreach ($fieldsm[1] as $fields) {
					$inputfields = preg_split('/->/', $fields);
					$i = 0;
					foreach ($inputfields as $if) {
						if ($i == 0) {
							$value = $item->{$if};
							$i++;
						} else {
							$value = $value->{$if};
						}
					}
					$tmp = str_replace($fieldsm[0][0], $value, $tmp);
				}
				$prompt_tmp .= $tmp;
				$index++;
			}
			$prompt = str_replace($matches[0][$k], $prompt_tmp, $prompt);
		}
		
		preg_match_all('/\[%(.*?)%\]/', $prompt, $fieldsm);
		foreach ($fieldsm[1] as $fields) {
			$inputfields = preg_split('/->/', $fields);
			$i = 0;
			foreach ($inputfields as $if) {
				if ($i == 0) {
					$value = $input->{$if};
					$i++;
				} else {
					$value = $value->{$if};
				}
			}
			$prompt = str_replace($fieldsm[0][0], $value, $prompt);
		}
		
		return $prompt;
	}
	
	/*
	* Function to get previously set custom value
	* input:
	* $name: the name of the value you want to get
	*/
	public function getCustomValue($name) {
		if (isset($this->custom[$name])) {
			return $this->custom[$name];
		} else {
			return NULL;
		}		
	}
	
	/*
	* Function to set custom value
	* input:
	* $name: the name of the value you want to set
	* $value: the value to set
	*/
	public function setCustomValue($name, $value) {
		if (!is_array($this->custom)) {
			$this->custom = array();
		}
		$this->custom[$name] = $value;
	}
	
	/*
	* Function to set the condition filename of the XML to use
	* input:
	* $filename: the filename of the xml with the complete path (relative or absolute)
	*/
	public function setConditionsFilename($filename) {
		$this->conditions_filename = $filename;
	}
	
	/*
	* Function to get previously set input
	*/
	public function getInput() {
		return $this->input;
	}
	
	/*
	* Function to set the input (SLU concepts array)
	* input:
	* $input_object: the array with the SLU concepts
	*/
	public function setInput($input_object) {
		$this->input = $input_object;
	}
	
	/*
	* Function to save the state of the class
	* input:
	* $state_name: the hash you want to use to store the state
	*/
	public function saveState($state_name) {
		file_put_contents('states/'.$state_name.'.ser', serialize($this));
	}
	
	/*
	* Function to restore previously set state
	* input:
	* $state_name: the hash the state was saved with
	*/
	public static function restoreState($state_name) {
		if (file_exists('states/'.$state_name.'.ser')) {
			self::$instance = unserialize(file_get_contents('states/'.$state_name.'.ser'));
			return true;
		} else {
			return false;
		}
	}
	
	/*
	* Function to run the Dialog Manager
	*/
	public function run() {
		$dom = new DOMDocument();
		$dom->load($this->conditions_filename);

		$condition_verified = false;
		$ifs = $dom->getElementsByTagName('if');
		$switch_to_default = true;
		if ($ifs->length > 0) {
			foreach ($ifs as $if) {
				$conditions = $if->getElementsByTagName('condition');
				$conditions_verified = array();
				foreach ($conditions as $key => $condition) {
					$condition_verified = false;
					$field = trim($condition->getElementsByTagName('field')->item(0)->nodeValue);
					$operator = trim($condition->getElementsByTagName('operator')->item(0)->nodeValue);
					if ($condition->getElementsByTagName('value')->length)
						$value = trim($condition->getElementsByTagName('value')->item(0)->nodeValue);
					if ($condition->getElementsByTagName('minValue')->length)
						$minvalue = trim($condition->getElementsByTagName('minValue')->item(0)->nodeValue);
					if ($condition->getElementsByTagName('maxValue')->length)
						$maxvalue = trim($condition->getElementsByTagName('maxValue')->item(0)->nodeValue);	
					
					switch ($operator) {
						case '==': {
							if ($this->input[$field] == $value) {
								$condition_verified = true;
							}
							break;
						}
						case '>': {
							if ($this->input[$field] > $value) {
								$condition_verified = true;
							}
							break;
						}
						case '<': {
							if ($this->input[$field] < $value) {
								$condition_verified = true;
							}
							break;
						}
						case '<=': {
							if ($this->input[$field] <= $value) {
								$condition_verified = true;
							}
							break;
						}
						case '>=': {
							if ($this->input[$field] >= $value) {
								$condition_verified = true;
							}
							break;
						}
						case '!=':  {
							if ($this->input[$field] != $value) {
								$condition_verified = true;
							}
							break;
						}
						case '<<': {
							if ($this->input[$field] > $minvalue && $this->input[$field] < $maxvalue) {
								$condition_verified = true;
							}
							break;
						}
						case '<=<': {
							if ($this->input[$field] >= $minvalue && $this->input[$field] < $maxvalue) {
								$condition_verified = true;
							}
							break;
						}
						case '<<=': {
							if ($this->input[$field] > $minvalue && $this->input[$field] <= $maxvalue) {
								$condition_verified = true;
							}
							break;
						}
						case '<=<=': {
							if ($this->input[$field] >= $minvalue && $this->input[$field] <= $maxvalue) {
								$condition_verified = true;
							}
							break;
						}
						case '!<<': {
							if ($this->input[$field] < $minvalue || $this->input[$field] > $maxvalue) {
								$condition_verified = true;
							}
							break;
						}
						case '~*': {
							if (stristr($this->input[$field], $value)) {
								$condition_verified = true;
							}
							break;
						}
						case '!~*': {
							if (!stristr($this->input[$field], $value)) {
								$condition_verified = true;
							}
							break;
						}
						default: {
							break;
						}
					}
					$conditions_verified[$key] = $condition_verified;
				}
				
				$exe_action = true;
				foreach ($conditions_verified as $cv) {
					if (!$cv) {
						$exe_action = false;
						break;
					}
				}
				if ($exe_action) {
					$switch_to_default = false;
					$exe_function = trim($if->getElementsByTagName('action')->item(0)->nodeValue);
					if (stristr($exe_function, '(')) {
						preg_match('/\((.*)\)/', $exe_function, $matches);
						return call_user_func_array(substr($exe_function, 0, strpos($exe_function, '(')), array_merge(array($this->input), str_getcsv($matches[1])));
					} else {
						return $exe_function($this->input);
					}
					break;
				}
			}
		}

		if ($switch_to_default) {
			// execute default
			$conditions = $dom->getElementsByTagName('default');
			if ($conditions->length > 0) {
				$exe_function = trim($conditions->item(0)->nodeValue);
				if (stristr($exe_function, '(')) {
					preg_match('/\((.*)\)/', $exe_function, $matches);
					return call_user_func_array(substr($exe_function, 0, strpos($exe_function, '(')), array_merge(array($this->input), str_getcsv($matches[1])));
				} else {
					return $exe_function($this->input);
				}
			}
		}
	}
	
}


?>