<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 6/7/15
 * Time: 5:56 PM
 */


require "./dialogManager/function_utilities.php";

print_r("<b>Test</b> - Check classificator<br>");
$input_phrase = "star_of_thor";
$out = shell_exec("python3.4 /var/www/html/project/classifier/giveMeTheProb.py /var/www/html/project/classifier/TestD/NLSPARQL.test.txt '/var/www/html/project/classifier/NB/results_prior.res' '/var/www/html/project/classifier/TestD/NLSPARQL.test.utt.labels.txt' " . $input_phrase . " /var/www/html/project/classifier/");
$out = json_decode($out, true);
print_r($out);
print_r("<br>---------<br>");


print_r("<b>Test</b> - class by concept<br>");
print_r(getClassesByConcept("movie.name"));
print_r("<br>---------<br>");

print_r("<b>Test</b> - sliceOfClasses<br>");
print_r(getSliceOfClasses());
print_r("<br>---------<br>");

print_r("<b>Test</b> - ConceptsByClass<br>");
print_r(getConceptsByClass("actor"));
print_r("<br>---------<br>");

print_r("<b>Test</b> - getOtherClasses<br>");
print_r(getOtherClasses("actor<br>movie<br>director<br>"));
print_r("<br>---------<br>");


print_r("<b>Test</b> - sluToFB<br>");
print_r(sluToFb("movie.name","beauty and the beast",false));
print_r("<br>---------<br>");