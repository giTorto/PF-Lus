<?php
/**
 * SPARQL Functions
 * 
 * based on https://github.com/cgutteridge/PHP-SPARQL-Lib
 * 
 * @author estepanov
 *
 */
class SparqlConnection {
	
	private $endpoint;
	private $params = array();
	private $error;
	
	function __construct($endpoint) {
		$this->endpoint = $endpoint;
	}
	
	/**
	 * Set query parameters [reqires knowing]
	 *
	 * @param string $param
	 * @param string $value
	 */
	public function setParameter($param, $value) {
		$this->params[$param] = $value;
	}
	
	/**
	 * Query Endpoint
	 * @param string $query
	 */
	public function query($query, $timeout = NULL) {
		$q  = $this->buildUrl($query);
		$result = file_get_contents($q);
		
		return $result;
	}
	
	/**
	 * Build a query URL from settings and query
	 * @param  string $query
	 * @param  string $timeout
	 * @return string
	 */
	private function buildUrl($query) {
		$str  = $this->endpoint;
		$str .= '?';
		$str .= 'query=' . urlencode($query);
		foreach ($this->params as $param => $value) {
			$str .= '&';
			$str .= $param;
			$str .= '=';
			$str .= urlencode($value);
		}		
		return $str;
	}
	
}
