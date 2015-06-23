<?php

// SLU output example
// $out = array("EAT" => "list", "object" => "movie",  "language" => "Chinese");

$input = array("EAT" => "list", "object" => "movie",  "language" => "Chinese", "items" => array(array('name' => 'pluto'), array('name' => 'topolino')));

$input_obj = json_decode('{ "head": { "link": [], "vars": ["actorn"] },
  "results": { "distinct": false, "ordered": true, "bindings": [
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Italia Coppola" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Al Lettieri" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "James Caan" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Al Martino" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Ron Gilbert" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Marlon Brando" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Diane Keaton" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Nick Vallelonga" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Julie Gregg" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Robert Duvall" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Richard Conte" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Al Pacino" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Louis Guss" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "John Marley" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Lenny Montana" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Tony Lip" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Richard Bright" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Raymond Martino" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Tom Rosqui" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Simonetta Stefanelli" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "John Martino" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Gian-Carlo Coppola" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Abe Vigoda" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Roman Coppola" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Joe Spinell" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Sterling Hayden" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Sonny Grosso" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Morgana King" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Victor Rendina" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Peter Lemongello" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Merrill Joels" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Jeannie Linero" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "John Cazale" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Gianni Russo" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Vito Scotti" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Sal Richards" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Frank Sivero" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Carmine Coppola" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Sofia Coppola" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Corrado Gaipa" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Talia Shire" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Rudy Bond" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Saro Urz\u00EC" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Richard S. Castellano" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Filomena Spagnuolo" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Carol Morley" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Don Costello" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Gabriele Torrei" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Lou Martini Jr." }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Franco Citti" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Alex Rocco" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Gray Frederickson" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Angelo Infanti" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Tybee Brascia" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Robert Dahdah" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Joseph Medeglia" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Frank Macetta" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Tony Giorgio" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Randy Jurgensen" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Rick Petrucelli" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Matthew Vlahakis" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Anthony Gounaris" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Ardell Sheridan" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Burt Richards" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Joe Lo Grippo" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Salvatore Corsitto" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Nino Ruggeri" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Ed Vantura" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Tere Livrano" }},
    { "actorn": { "type": "literal", "xml:lang": "en", "value": "Max Brandt" }} ] } }');

include_once('functions.php');
include_once('DialogManager.class.php');

DialogManager::restoreState('mystate');
$dm = DialogManager::getInstance();
$dm->setInput($input_obj);
$dm->setConditions('conditions/conditions.xml');
$myresult = $dm->runConditions();
$dm->setCustomValue('resultState', 'FirstStep');
$dm->saveSate('mystate');

?>