<?php
/**
 * Created by PhpStorm.
 * User: Giuliano Tortoreto
 * Date: 6/13/15
 * Time: 11:14 AM
 */

class ConceptCache{
    private $class = "";
    private $concepts =array();
    private $text = array();
    private $cache_location = "from_SLU_to_Names/cache.txt";

    /**
     * @return string
     */
    public function getCacheLocation()
    {
        return $this->cache_location;
    }

    /**
     * @param string $cache_location
     */
    public function setCacheLocation($cache_location)
    {
        $this->cache_location = $cache_location;
    }

    /**
     * @return array
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param array $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }



    /**
     * This function checks if for a certain name already exist a direct mapping to Freebase.
     * If not add a new concept type and a new concept name with relative mappings.
     */
    public function run() {
        require_once "function_utilities.php";
        $response=0;

        $file = $this->getCacheLocation();

        $concept = $this->getConcepts();

        $key = strtolower(key($concept)); $value = strtolower($concept[$key]);

        //check before ask to Freebase
        if(!sluToFb($key,$value)){
            usleep(1000000);
            $response = ask_Freebase($this->getClass(), $concept, 20);
            //print_r("I asked to Fb\n");
        }


        //print_r($response);
        //print_r("\nKey = " . $key . " " . " value = ". $value);
        //print_r(" class = ". $this->getClass());
        $response_ok = $this->checkResponse($response, $value);
        if(is_array($response) && !empty($response) && !sluToFb($key,$value) && $response_ok){
            $row = $key . "\t" . $value . "\t" . implode("\t",$response) . "\n";
            file_put_contents($file, $row, FILE_APPEND | LOCK_EX);
        }elseif($response==0 || $response==1)
            return;

    }

    /**
     * @return array
     */
    public function getConcepts()
    {
        return $this->concepts;
    }

    /**
     * @param array $concepts
     */
    public function setConcepts($concepts)
    {
        $this->concepts = $concepts;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    private function checkResponse($response, $value)
    {
        require_once "function_utilities.php";
        $value = array_map("number_to_string", explode(" ", $value));
        $regex = "/((.*[^a-zA-Z])|^)" .
            implode("(([^a-zA-Z].*[^a-zA-Z])|[^a-zA-Z])",$value) . "(([^a-zA-Z].*)|$)/i";


        $result = preg_grep($regex, $response);

        //print_r($response);
        //print_r($regex . "\n");
        //print_r($result);

        if (empty($result)) {
            return false;
        }

        return true;
    }
}