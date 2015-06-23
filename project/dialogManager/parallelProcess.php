<?php
/**
 * Created by PhpStorm.
 * User: Giuliano Tortoreto
 * Date: 6/15/15
 * Time: 11:09 AM
 */
include_once($_SERVER['DOCUMENT_ROOT'].'ConceptCache.php');

//manage parameters received from the main process
$class= str_replace("_", " ", $argv[1]);
$conce = array();
$conce[str_replace("_", " ", $argv[2])] = str_replace("_", " ", $argv[3]);

//initialize cache class, which manages the cache file
$cache = new ConceptCache();
//pass parameters
$cache->setClass($class);
$cache->setConcepts($conce);

//run the cache class that will try to add the concept and the class to the cache file
$cache->run();

return;