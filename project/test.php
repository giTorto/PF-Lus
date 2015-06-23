<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 6/7/15
 * Time: 5:56 PM
 */


require "./dialogManager/function_utilities.php";

print_r("<b>Test</b> - Check classificator ");
$input_phrase = "star_of_thor";
$out = shell_exec("python3.4 /var/www/html/project/classifier/giveMeTheProb.py /var/www/html/project/classifier/TestD/NLSPARQL.test.txt '/var/www/html/project/classifier/NB/results_prior.res' '/var/www/html/project/classifier/TestD/NLSPARQL.test.utt.labels.txt' " . $input_phrase . " /var/www/html/project/classifier/");
$out = json_decode($out, true);
if(!empty($out))
    print_r("--> OK <br>");
else
    print_r("--> Failed <br>");
print_r($out);

print_r("<br>---------<br>");


print_r("<b>Test</b> - class by concept ");
$val = getClassesByConcept("movie.name");
if(!empty($val))
    print_r("--> OK <br>");
else
    print_r("--> Failed <br>");
print_r($val);
print_r("<br>---------<br>");

print_r("<b>Test</b> - sliceOfClasses ");
$val = getSliceOfClasses();
if(!empty($val))
    print_r("--> OK <br>");
else
    print_r("--> Failed <br>");
print_r($val);
print_r("<br>---------<br>");

print_r("<b>Test</b> - ConceptsByClass ");
$val = getConceptsByClass("actor");
if(!empty($val))
    print_r("--> OK <br>");
else
    print_r("--> Failed <br>");
print_r($val);
print_r("<br>---------<br>");

print_r("<b>Test</b> - getOtherClasses ");
$val = getOtherClasses("actor<br>movie<br>director<br>");
if(!empty($val))
    print_r("--> OK <br>");
else
    print_r("--> Failed <br>");
print_r($val);
print_r("<br>---------<br>");


print_r("<b>Test</b> - cache ");
$val = sluToFb("movie.name","beauty and the beast",false);
if(!empty($val))
    print_r("--> OK <br>");
else
    print_r("--> Failed <br>");
print_r($val);
print_r("<br>---------<br>");