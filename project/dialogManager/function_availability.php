<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 6/7/15
 * Time: 12:36 PM
 */


/**
 * List of Queries supported
 * @param string  $class		output of utterance classifier
 * @param array   $concepts 	key is attribute value is value
 * @return string $query		if it supported by the system
 */
function isFeasible($class, $concepts) {
    $p = array(
        // http://www.freebase.com/film/film?schema=
        'movie' => array(
            // keys   = slu concepts/classes, when stripped of '_/.name'
            // values = Freebase RDF predicates
            'actor.name'	=> 'fbase:film.film.starring',
            'director.name'	=> 'fbase:film.film.directed_by',
            'producer.name'	=> 'fbase:film.film.produced_by',
            'company.name'	=> 'fbase:film.film.production_companies',
            'country.name'	=> 'fbase:film.film.country',
            'rating.name'	=> 'fbase:film.film.rating',
            'movie.language'=> 'fbase:film.film.language',
            'movie.subject' => 'fbase:film.film.subjects',
            'movie.genre'	=> 'fbase:film.film.genre',
            'movie.budget'	=> 'fbase:film.film.estimated_budget',
            'movie.gross_revenue' => 'fbase:film.film.gross_revenue',
            'movie.release_date'  => 'fbase:film.film.initial_release_date',
        ),
        // http://www.freebase.com/film/performance?schema=
        'actor' => array(
            'actor.name'	=> 'fbase:film.performance.actor',
            'character.name'=> 'fbase:film.performance.character',
        ),
        /* 'character' => array(
             'character.name' => 'fbase:film.performance.character',
             'actor.name'	=> 'fbase:film.performance.actor',

         )*/
    );

    $query = false;
    // no concepts: happens, no class: shouldn't happen
    if (empty($concepts) || $class == '') {
        return $query;
    }
    // no question
    elseif ($class == 'movie_other' || $class == 'other') {
        return $query;
    }
    /*
    elseif (!$this->class2relation($class)) {
        return $query;
    }
    */
    // movie by X & movie_count
    elseif ($class == 'movie' || $class == 'movie_name' || $class == 'movie_count') {
        $aa = array_keys($concepts);
        $attr = $aa[0];
        // movie by movie
        if (isset($concepts['movie.name'])) {
            $query = true;
        }
        // movie by actor
        elseif (isset($concepts['actor.name'])) {
            $query = true;
        }
        // movie by X: only 1 X
        elseif (count($concepts) == 1 && isset($p['movie'][$attr])) {
            if ($class == 'movie_count') {
                $query = true;
            }
            else {
                $query = true;
            }
        }
        else {
            return $query;
        }
    }
    // actor by movie
    elseif ($class == 'actor' || $class == 'actor_name') {
        // actor by actor name
        if (isset($concepts['actor.name'])) {
            $query = true;
        }
        // actor by movie
        elseif (isset($concepts['movie.name']) && !isset($concepts['character.name'])) {
            $query = true;
        }
        // actor by movie & character
        elseif (isset($concepts['movie.name']) && isset($concepts['character.name'])) {
            $query = true;
        }
        else {
            return $query;
        }
    }
    // character by movie & actor
    elseif ($class == 'character' || $class == 'character_name') {
        // character by actor & movie
        if (isset($concepts['movie.name']) && isset($concepts['actor.name'])) {
            $query = true;
        }
        else {
            return $query;
        }
    }
    else {
        // X by movie
        if (isset($concepts['movie.name'])) {
            $query = true;
        }
        // director by director.name
        elseif (isset($concepts['director.name'])
            && ($class == 'director' || $class == 'director_name'))
        {
            $query = true;
        }
        // producer by producer.name
        elseif (isset($concepts['producer.name'])
            && ($class == 'producer' || $class == 'producer_name'))
        {
            $query = true;
        }
        // person by person.name
        elseif (isset($concepts['person.name'])
            && ($class == 'person' || $class == 'person_name'))
        {
            $query = true;
        }
        else {
            return $query;
        }
    }

    return $query;
}

function createMap(){
    $classes = array("actor", "character", "movie",  "movie_count", "person", "producer", "director");
    $concepts = array(array("person.name" => "bla"),array("producer.name" => "bla"),array("director.name" => "bla"), array("movie.language"),
        array("movie.name" => "bla", "character.name"=>"bla"), array("actor.name"=>"bla"), array("movie.name"=>"'","actor.name"=>"bla"),
        array("movie.name" => "bla"), array("director.name" => "bla"),array("company.name" => "bla"), array("country.name" =>"bla"),
        array("rating.name" => "bla"), array("movie.subject" => "bla"), array("movie.genre" => "bla"), array("movie.budget" => "bla"),
        array("movie.gross_revenue" => "bla"), array("movie.release_date" => "bla")
    );
    $available = array();
    //print_r("Let's begin");
    $count = 0;
    foreach($classes as $class){
        foreach($concepts as $conc_key => $conc_val){
            if (isFeasible($class, $conc_val)){
                $available[$class] = isset($available[$class]) ? $available[$class] : array();
                $stringa = "";
                foreach($conc_val as $key => $value)
                    $stringa .="&".$key;
                    $available[$class][preg_replace('/&/', '', $stringa, 1)] = 0;

                $count++;
            }

        }
    }


    //result of the execution is stored, in this way is not recomputed everytime.
    $all_combi = Array ( "actor" => Array ( "movie.name&character.name" => 0,"actor.name" => 0,"movie.name&actor.name" => 0,"movie.name" => 0 ),
        "character" => Array ( "movie.name&actor.name" => 0 ),
        "movie" => Array ( "producer.name" => 0,"director.name" => 0,"movie.name&character.name" => 0,"actor.name" => 0,"movie.name&actor.name" => 0,"movie.name" => 0,"company.name" => 0,"country.name" => 0,"rating.name" => 0,"movie.subject" => 0,"movie.genre" => 0,"movie.budget" => 0,"movie.gross_revenue" => 0,"movie.release_date" => 0 ),
        "movie_count" => Array ( "producer.name" => 0,"director.name" => 0,"movie.name&character.name" => 0,"actor.name" => 0,"movie.name&actor.name" => 0,"movie.name" => 0,"company.name" => 0,"country.name" => 0,"rating.name" => 0,"movie.subject" => 0,"movie.genre" => 0,"movie.budget" => 0,"movie.gross_revenue" => 0,"movie.release_date" => 0 ),
        "person" => Array ( "person.name" => 0,"movie.name&character.name" => 0,"movie.name&actor.name" => 0,"movie.name" => 0 ),
        "producer" => Array ( "producer.name" => 0,"movie.name&character.name" => 0,"movie.name&actor.name" => 0,"movie.name" => 0 ),
        "director" => Array ( "director.name" => 0,"movie.name&character.name" => 0,"movie.name&actor.name" => 0,"movie.name" => 0 )
    );

    //print_r(", , ");
    //print_r($all_combi);
}