<?php
/**
 * Class for Attribute-Value & Utterance label to SPARQL Query Conversion
 * 
 * LIMITED SUPPORT! 
 * Covers most of the queries, extend if needed
 * 
 * Query Types:
 * 
 *  - Movie By X					count(X) = 1
 *  - X By Movie					ignore other concepts, but 'movie' 
 *  - Actor By Movie				ignore other concepts, but 'movie'
 *  - Movie By Actor				ignore other concepts, but 'actor'
 *  - Actor By Movie & Character	ignore other concepts, but 'movie' & 'char'
 *  - Character By Movie & Actor	ignore other concepts, but 'movie' & 'actor'
 *  
 *  - Movie By Movie name			asks to show a page
 *  - Actor By Actor name
 *  - X     By X					limit to person, director & producer
 *  
 *  - Movie Count by X
 * 
 * @author estepanov
 */
class Slu2Sparql {
	private $full = false;
	private $lang = 'en';		// language for filtering and objects
	private $limit;				// Query limit
	private $prefix = array(
			'fbase' => '<http://rdf.freebase.com/ns/>',
				
	);
		
	// schemata by concept from Freebase
	private $p = array(
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
            'budget' => array(
                'movie.budget' =>'fbase:measurement_unit.dated_money_value.amount'
            ),
            'gross_revenue' => array(
                'movie.gross_revenue' => 'fbase:measurement_unit.dated_money_value.amount'
            )
	);
	
	private $type = 'fbase:type.object.type';	// types
	private $name = 'fbase:type.object.name';	// strings (items)
	
	// Freebase types
	private $t = array(
			'film'  	=> 'fbase:film.film',
			'actor' 	=> 'fbase:film.actor',
			'director'	=> 'fbase:film.director',
			'producer'	=> 'fbase:film.producer',
			'character' => 'fbase:film.film_character',
            'person' => 'fbase:people.person'
	);
	
	function __construct($lang, $limit = NULL, $full=false) {
		$this->lang  = $lang;
		$this->limit = $limit;
        $this->full = $full;
	}
	
	/**
	 * List of Queries supported
	 * @param string  $class		output of utterance classifier
	 * @param array   $concepts 	key is attribute value is value
	 * @return string $query		SPARQL query string
	 */
	public function mkSparqlQuery($class, $concepts) {
		$query = NULL;
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
				$query = $this->qNameType($concepts['movie.name'], 'film');
			}
			// movie by actor
			elseif (isset($concepts['actor.name'])) {
				$query = $this->qMovieByActor($concepts['actor.name']);
			}
			// movie by X: only 1 X
			elseif (count($concepts) == 1 && isset($this->p['movie'][$attr])) {
                $key = key($concepts);
				if ($class == 'movie_count') {
                    $query = $this->qMovieByX($concepts[$key], $this->p['movie'][$attr], TRUE);
                }else {
					$query = $this->qMovieByX($concepts[$key], $this->p['movie'][$attr]);
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
                //$query = $this->qNameType($concepts['actor.name'], $this->t['actor']);
                $query = $this->qNameType($concepts['actor.name'], 'actor');
            }
			// actor by movie
			elseif (isset($concepts['movie.name']) && !isset($concepts['character.name'])) {
				$query = $this->qActorByMovie($concepts['movie.name']);
			}
			// actor by movie & character
			elseif (isset($concepts['movie.name']) && isset($concepts['character.name'])) {
				$query = $this->qActorByMovieChar($concepts['movie.name'], $concepts['character.name']);
			}
			else {
				return $query;
			}
		}
		// character by movie & actor
		elseif ($class == 'character' || $class == 'character_name') {
			// character by actor & movie
			if (isset($concepts['movie.name']) && isset($concepts['actor.name'])) {
				$query = $this->qCharacterByMovieActor($concepts['movie.name'], $concepts['actor.name']);
			}elseif (isset($concepts['character.name'])){
                $query = $this->qNameType($concepts['character.name'],"character");
            }
			else {
				return $query;
			}
		}
		else {
			// X by movie
			if (isset($concepts['movie.name'])) {
                if($class == 'budget' || $class == 'gross_revenue') {
                    $query = $this->qValueByMovie($concepts['movie.name'], $class);
                }else{
                    $query = $this->qXByMovie($concepts['movie.name'], $this->class2relation($class));
                }
			}
			// director by director.name
			elseif (isset($concepts['director.name'])
					&& ($class == 'director' || $class == 'director_name'))
			{
				//$query = $this->qName($concepts['director.name']);
				$query = $this->qNameType($concepts['director.name'],"director");

			}
			// producer by producer.name
			elseif (isset($concepts['producer.name'])
					&& ($class == 'producer' || $class == 'producer_name'))
			{
				//$query = $this->qName($concepts['producer.name']);
                $query = $this->qNameType($concepts['producer.name'],"producer");

            }
			// person by person.name
			elseif (isset($concepts['person.name'])
					&& ($class == 'person' || $class == 'person_name'))
			{
				//$query = $this->qName($concepts['person.name']);
                $query = $this->qNameType($concepts['person.name'], "person");
            }
			else {
				return $query;
			}
		}
		
		return $query;
	}
	
	/**
	 * Simple Name Query
	 * @param  string $name
	 * @return string
	 */
	private function qName($name) {
		$select = '?x';
		$where  = array(
				//array('?x', $this->$name, $this->mkNameStr($name))
				array('?x', $this->name, '?str'),
				$this->mkRegex($name, '?str')
		);
		return $this->mkQuery(array('SELECT' => $select, 'WHERE' => $where));
	}

    /**
     * @return boolean
     */
    public function isFull()
    {
        return $this->full;
    }

    /**
     * @param boolean $full
     */
    public function setFull($full)
    {
        $this->full = $full;
    }

    /**
	 * Simple Type Query
	 * @param  string $type
	 * @return string
	 */
	private function qType($type) {
		$select = '?x';
		$where  = array(
				array('?x', $this->type, $this->t[$type])
		);
		return $this->mkQuery(array('SELECT' => $select, 'WHERE' => $where));
	}
	
	/**
	 * Simple Name - Type Query
	 * @param  string $name
	 * @param  string $type
	 * @return string
	 */
	private function qNameType($name, $type) {
		$select = '?str';

        if($this->isFull())
            $name = str_replace("\"","",$name);

		$where  = array(
				//array('?x', $this->$name, $this->mkNameStr($name)),
				array('?x', $this->type, $this->t[$type]),
				array('?x', $this->name, '?str'),
				$this->mkRegex($name, '?str')
		);


		return $this->mkQuery(array('SELECT' => $select, 'WHERE' => $where));
	}

	/**
	 * Query movie by X
	 * @param string $x		value of X
	 * @param string $rel	relation of movie to X
	 *
	 * @return string
	 */
	private function qMovieByX($x, $rel, $cnt = NULL){
		$select = '?m';
		$where  = array(
				array('?m', $this->type, $this->t['film']),
				array('?m', $rel, '?x'),
				//array('?x', $this->$name, $this->mkNameStr($x))
				// strings from SLU won't match --> use RegEx
				array('?x', $this->name, '?str'),
				$this->mkRegex($x, '?str')
		);

        if ($this->full)
            $this->changeWhere($where, $x, 2);

        $arr = array('SELECT' => $select, 'WHERE' => $where);

		if ($cnt) {
			return $this->mkCountQuery($arr);
		}
		else {
			return $this->mkNameQuery($arr);
		}
	}

    private function qValueByMovie($m, $class){
        $select = '?b';

        $where  = array(
            array('?m', $this->name, '?str'),
            $this->mkRegex($m, '?str'),
            array('?m', $this->type, $this->t['film']),
            array('?m', $this->class2relation($class), '?x'),
            array('?x', $this->p[$class]['movie.'.$class], '?b'),
        );

        if ($this->full)
            $this->changeWhere($where, $m);

        return $this->mkQuery(array('SELECT' => $select, 'WHERE' => $where),false);

    }

    private function changeWhere(&$where, $m, $starting=0){
        $where[$starting][2] = $m."@en";
        unset($where[$starting+1]);
    }
	
	/**
	 * Query X by movie
	 * @param string $m		movie name
	 * @param string $rel	relation of movie to X
	 *
	 * @return string
	 */
	private function qXByMovie($m, $rel, $cnt = NULL){
		$select = '?x';
		$where  = array(
				//array('?m', $this->$name, $this->mkNameStr($m)),
				array('?m', $this->name, '?str'),
				$this->mkRegex($m, '?str'),
				array('?m', $this->type, $this->t['film']),
				array('?m', $rel, '?x')
		);

        if ($this->full)
            $this->changeWhere($where, $m);

		$arr = array('SELECT' => $select, 'WHERE' => $where);
		
		if ($cnt) {
			return $this->mkCountQuery($arr);
		}
		else {
			return $this->mkNameQuery($arr);
		}
	}
	
	/**
	 * Query Actor by Movie name --> goes through middle node
	 * @param  string $m
	 * @return string
	 */
	private function qActorByMovie($m) {
        //print_r("Here I am");
		$select = '?a';
		$where  = array(
				array('?m', $this->name, '?str'),
				$this->mkRegex($m, '?str'),
				array('?m', $this->type, $this->t['film']),
				array('?m', $this->p['movie']['actor.name'], '?x'),
				array('?x', $this->p['actor']['actor.name'], '?a'),
		);

        if ($this->isFull())
            $this->changeWhere($where, $m);

		return $this->mkNameQuery(array('SELECT' => $select, 'WHERE' => $where));
	}

	/**
	 * Query Movie by Actor name --> goes through middle node
	 * @param  string $a
	 * @return string
	 */
	private function qMovieByActor($a) {
		$select = '?m';
		$where  = array(
				array('?m', $this->type, $this->t['film']),
				array('?m', $this->p['movie']['actor.name'], '?x'),
				array('?x', $this->p['actor']['actor.name'], '?a'),
				array('?a', $this->name, '?str'),
				$this->mkRegex($a, '?str')
		);

        if ($this->full)
            $this->changeWhere($where, $a,3);

		return $this->mkNameQuery(array('SELECT' => $select, 'WHERE' => $where));
	}
	
	/**
	 * Query Actor by Movie & Char name --> goes through middle node
	 * @param  string $m
	 * @return string
	 */
	private function qActorByMovieChar($m, $c) {
		$select = '?a';

        if (!$this->isFull()){
            $m = strtolower(str_replace("\"","",$m));
            $c = strtolower(str_replace("\"","",$c));
        }

		$where  = array(
				array('?m', $this->name, '?str1'),
				$this->mkRegex($m, '?str1'),
				array('?m', $this->type, $this->t['film']),
				array('?m', $this->p['movie']['actor.name'], '?x'),
				array('?x', $this->p['actor']['character.name'], '?c'),
				array('?c', $this->name, '?str2'),
				$this->mkRegex($c, '?str2'),
				array('?x', $this->p['actor']['actor.name'], '?a'),
		);

        if ($this->full){
            $this->changeWhere($where, $m);
            $this->changeWhere($where, $c,5);
        }


	    return $this->mkNameQuery(array('SELECT' => $select, 'WHERE' => $where));
	}
	
	/**
	 * Query Character by Movie & Actor name --> goes through middle node
	 * @param  string $m
	 * @return string
	 */
	private function qCharacterByMovieActor($m, $a) {

        if (!$this->isFull()){
            $m = strtolower(str_replace("\"","",$m));
            $a = strtolower(str_replace("\"","",$a));
        }

        $select = '?c';
		$where  = array(
				array('?m', $this->name, '?str1'),
				$this->mkRegex($m, '?str1'),
				array('?m', $this->type, $this->t['film']),
				array('?m', $this->p['movie']['actor.name'], '?x'),
				array('?x', $this->p['actor']['actor.name'], '?str2'),
                array('?str2', $this->name, '?str3'),
				$this->mkRegex($a, '?str3'),
				array('?x', $this->p['actor']['character.name'], '?c'),
		);

        if ($this->full){
            $this->changeWhere($where, $m);
            $this->changeWhere($where, $a,5);
        }

		return $this->mkNameQuery(array('SELECT' => $select, 'WHERE' => $where));
	}
	
	/**
	 * Build Query string from array to return object
	 * @param  array  $arr
	 * @return string $str
	 */
	private function mkQuery($arr, $filter=true) {
		// add prefix
		$str  = $this->addPrefixes();
		$str .= 'SELECT ';
		$str .= $arr['SELECT'];
		$str .= ' WHERE {';
		foreach ($arr['WHERE'] as $triplet) {
			if (is_array($triplet)) {
				$str .= implode(' ', $triplet) . ' . ';
			}
			else {
				$str .= $triplet . ' . ';
			}
		}

        if($filter){
            //added to have only english names
            $str .= 'FILTER(langMatches(lang(?str), "EN")) ';
            // added language filter
        }

        $str .= '}';



        if ($this->limit) {
			$str .= ' LIMIT ' . $this->limit;
		}
		return $str;
	}
	
	/**
	 * Build Query string from array returns Name, rather than object
	 * @param  array  $arr
	 * @return string $str
	 */
	private function mkNameQuery($arr) {
		// add prefix
		$str  = $this->addPrefixes();
		$str .= 'SELECT DISTINCT ';
		$str .= $arr['SELECT'] . 'name';
		$str .= ' WHERE {';
		foreach ($arr['WHERE'] as $triplet) {
			if (is_array($triplet)) {
				$str .= implode(' ', $triplet) . ' . ';
			}
			else {
				$str .= $triplet . ' . ';
			}
		}
		// add name
		$str .= $arr['SELECT'] . ' ';
		$str .= $this->name . ' ';
		$str .= $arr['SELECT'] . 'name' . ' . ';
		// add language filter
		$str .= 'FILTER(langMatches(lang(';
		$str .= $arr['SELECT'] . 'name';
		$str .= '), "' . strtoupper($this->lang) . '"))';
		$str .= '}';
		// limit
		if ($this->limit) {
			$str .= ' LIMIT ' . $this->limit;
		}
		return $str;
	}
	
	/**
	 * Build Count Query string from array
	 * @param  array  $arr
	 * @return string $str
	 */
	private function mkCountQuery($arr) {
		// add prefix
		$str  = $this->addPrefixes();
		$str .= 'SELECT ';
		$str .= '(COUNT(';
		$str .= $arr['SELECT'];
		$str .= ') AS ?count)';
		$str .= ' WHERE {';
		foreach ($arr['WHERE'] as $triplet) {
			if (is_array($triplet)) {
				$str .= implode(' ', $triplet) . ' . ';
			}
			else {
				$str .= $triplet . ' . ';
			}
		}
		// add language filter
		$str .= '}';
	
		if ($this->limit) {
			$str .= ' LIMIT ' . $this->limit;
		}
		return $str;
	}
	
	/**
	 * RegEx for partial string matching
	 * @param string $str
	 * @param string $var
	 * @return string
	 */
	private function mkRegex($str, $var) {
        /*if ($this->$full)
            return $this->mkFullMatch($str,$var);*/
        require_once "dialogManager/function_utilities.php";
		$arr = array_map("number_to_string",explode(' ', $str));
        $count = 0;
        $newstr = "((.*[^a-zA-Z])|^)". implode("(([^a-zA-Z].*[^a-zA-Z])|[^a-zA-Z])", $arr). "(([^a-zA-Z].*)|$)";


        //$newstr = '.*' . implode('.*', $arr) . '.*';
		$regex  = 'FILTER regex(';
		$regex .= $var;
		$regex .= ', ' . '"';
		$regex .= $newstr;
		$regex .= '"' . ', ';
		$regex .= '"i"';
		$regex .= ')';
	
		return $regex;
	}
	
	/**
	 * Builds query string for item
	 * @param  string $str
	 * @return string
	 */
	private function mkNameStr($str) {
		return '"' . $str . '"' . '@' . $this->lang;
	}
	
	/**
	 * Create prefixes string for query
	 * @return string $str
	 */
	private function addPrefixes() {
		$str = '';
		foreach ($this->prefix as $k => $v) {
			$str .= 'PREFIX ' . $k . ': ' . $v . "\n";
		}
		return $str;
	}
	
	/**
	 * Set prefixes
	 * @param string $pref
	 * @param string $url
	 */
	public function setPrefix($pref, $url) {
		$this->prefix[$pref] = $url;
	}
	
	private function class2relation($class) {
		$class = trim($class);
		if (in_array($class, array('director', 'producer', 'company', 'rating', 'country'))) {
			return $this->p['movie'][$class . '.name'];
		}
		elseif (in_array($class, array('language', 'genre', 'budget', 'release_date', 'gross_revenue'))) {
			return $this->p['movie']['movie.' . $class];
		}
		elseif ($class == 'subjects') {
			return $this->p['movie']['movie.subject'];
		}
		else {
			return FALSE;
		}
	}
}