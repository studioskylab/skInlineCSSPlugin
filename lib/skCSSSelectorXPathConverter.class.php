<?php

/**
 * Converts CSS selectors to XPaths
 *
 * This class is essentially a port of the CSS selector to xpath conversion
 * code from the old Selector class from Prototype.js.
 *
 * Usage:
 * <code>
 * $css = 'div#home > p.primary';
 * $c = new CSSSelectorXPathConverter();
 * echo $c->convertSelector($css);
 * // Outputs: .//*[local-name()='div' or local-name()='DIV'][@id='home']/*[local-name()='p' or local-name()='P'][contains(concat(' ', @class, ' '), ' primary ')]
 * </code>
 *
 * @package skInlineCSSPlugin
 * @author Jaik Dean
 **/
class skCSSSelectorXPathConverter
{

	/**
	 * Map of CSS selector matching regexs to their XPath equivalents
	 *
	 * @var array
	 **/
	protected $patterns = array();

	/**
	 * Map of CSS pseudo-selector matching regexs to their XPath equivalents
	 *
	 * @var array
	 **/
	protected $pseudos  = array();


	/**
	 * Constructor
	 *
	 * @author Jaik Dean
	 **/
	public function __construct()
	{
		// setup selector patterns
		$this->patterns = array(
			// combinators
			'/^\s*~\s*/'  => '/following-sibling::*',    // later sibling
			'/^\s*>\s*/'  => '/*',                       // child
			'/^\s*\+\s*/' => '/following-sibling::*[1]', // adjacent
			'/^\s/'       => '//*',                      // descendant

			// selectors
			'/^\s*(\*|[\w\-]+)(\b|$)?/'   => array($this, 'convertTag'),   // tag
			'/^#([\w\-\*]+)(\b|$)/'       => array($this, 'convertId'),    // id
			'/^\.([\w\-\*]+)(\b|$)/'      => array($this, 'convertClass'), // class
			'/^:((first|last|nth|nth-last|only)(-child|-of-type)|empty|checked|(en|b|$|\'(?=\s|[:+~>])))/' => array($this, 'convertPseudo'), // pseudo
			'/^\[((?:[\w-]+:)?[\w-]+)\]/' => array($this, 'convertAttributePresence'), // attribute presence
			'/\[((?:[\w-]*:)?[\w-]+)\s*(?:([!^$*~|]?=)\s*(([\'"])([^\4]*?)\4|([^\'"][^\]]*?)))?\]/' => array($this, 'convertAttribute'), // attribute
		);

		// setup pseudo-selector patterns
		$this->pseudos = array(
			'first-child'      => '[not(preceding-sibling::*)]',
			'last-child'       => '[not(following-sibling::*)]',
			'only-child'       => '[not(preceding-sibling::* or following-sibling::*)]',
			/* the following selectors don't work when converted to inline styles
			'empty'            => '[count(*) = 0 and (count(text()) = 0)]',
			'checked'          => '[@checked]',
			'disabled'         => "[(@disabled) and (@type!='hidden')]",
			'enabled'          => "[not(@disabled) and (@type!='hidden')]",*/
			'not'              => array($this, 'convertPseudoNot'),
			'nth-child'        => array($this, 'convertPseudoNthChild'),
			'nth-last-child'   => array($this, 'convertPseudoNthLastChild'),
			'nth-of-type'      => array($this, 'convertPseudoNthOfType'),
			'nth-last-of-type' => array($this, 'convertPseudoNthLastOfType'),
			'first-of-type'    => array($this, 'convertPseudoFirstOfType'),
			'last-of-type'     => array($this, 'convertPseudoLastOfType'),
			'only-of-type'     => array($this, 'convertPseudoOnlyOfType'),
      		'nth'              => array($this, 'convertPseudoNth'),
		);
	}


	/**
	 * Convert the given CSS selector string to its XPath equivalent
	 *
	 * @param string $selector CSS selector
	 * @return string XPath
	 * @author Jaik Dean
	 **/
	public function convertSelector($selector)
	{
		$prematch = false;
		$xpath    = array('.//*');

		// while we are still matching selectors, keep going
		while ($selector && $prematch != $selector && preg_match('/\S/', $selector)) {
			$prematch = $selector;

			// attempt to match each of the patterns
			foreach ($this->patterns as $search => $replace) {
				if (preg_match($search, $selector, $matches)) {
					$xpath[]  = (is_string($replace) ? $replace : call_user_func($replace, $matches));
					$selector = substr_replace($selector, '', strpos($selector, $matches[0]), strlen($matches[0]));
					break;
				}
			}
		}

		return implode('', $xpath);
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertTag($matches)
	{
		if (array_key_exists(1, $matches) && $matches[1] == '*') {
			return '';
		}

		return "[local-name()='" . mb_strtolower($matches[1]) . "' or local-name()='" . mb_strtoupper($matches[1]) . "']";
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertId($matches)
	{
		return "[@id='{$matches[1]}']";
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertClass($matches)
	{
		return "[contains(concat(' ', @class, ' '), ' {$matches[1]} ')]";
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudo($matches)
	{
		if (!array_key_exists($matches[1], $this->pseudos)) {
			return '';
		}

		$pseudo = $this->pseudos[$matches[1]];

		return (is_string($pseudo) ? $pseudo : call_user_func($pseudo, $matches));
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudoNot($matches)
	{
		$selector = $matches[6];
		$prematch = false;
		$xpath    = array();

		// while we are still matching selectors, keep going
		while ($selector && $prematch != $selector && preg_match('/\S/', $selector)) {
			$prematch = $sel;

			// attempt to match each of the patterns
			foreach ($this->patterns as $search => $replace) {
				if (preg_match($search, $selector, $matches)) {
					$string   = (is_string($replace) ? $replace : call_user_func($replace, $matches));
					$xpath[]  = '(' . mb_substr($string, 1, mb_strlen($string) - 1) . ')';
					$selector = substr_replace($selector, '', strpos($selector, $matches[0]), strlen($matches[0]));
					break;
				}
			}
		}

		return '[not(' + implode(' and ', $xpath) + ')]';
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudoNthChild($matches)
	{
		return $this->convertPseudoNth('(count(./preceding-sibling::*) + 1) ', $matches);
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudoNthLastChild($matches)
	{
		return $this->convertPseudoNth('(count(./following-sibling::*) + 1) ', $matches);
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudoNthOfType($matches)
	{
		return $this->convertPseudoNth('position() ', $matches);
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudoNthLastOfType($matches)
	{
		return $this->convertPseudoNth('(last() + 1 - position()) ', $matches);
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudoFirstOfType($matches)
	{
		$matches[6] = '1';
		return $this->convertPseudoNthOfType($matches);
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudoLastOfType($matches)
	{
		$matches[6] = '1';
		return $this->convertPseudoNthLastOfType($matches);
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudoOnlyOfType($matches)
	{
		return $this->convertPseudoFirstOfType($matches) . $this->convertPseudoLastOfType($matches);
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertPseudoNth($fragment, $matches)
	{
		$formula = $matches[6];

		switch ($formula) {
			case 'even': $formula = '2n+0'; break;
			case 'odd':  $formula = '2n+1'; break;
		}

		// digits only
		if (preg_match('/^(\d+)$/', $formula, $mm)) {
			return "[$fragment= {$mm[1]}]";
		}

		// an+b
		if (preg_match('/^(-?\d*)?n(([+-])(\d+))?/', $formula, $mm)) {
			if ($mm[1] == '-') {
				$mm[1] = -1;
			}

			$a = $mm[1] ? $mm[1] : 1;
			$b = $mm[2] ? $mm[2] : 0;

			return "[(({$fragment} - {$b}) mod {$a} = 0) and (({$fragment} - {$b}) div {$a} >= 0)]";
		}
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertAttributePresence($matches)
	{
		return '[@' . $matches[1] . ']';
	}


	/**
	 * undocumented function
	 *
	 * @param array $matches
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertAttribute($matches)
	{
		$matches[3] = ($matches[5] ? $matches[5] : $matches[6]);

		$operators = array(
			'='  => "[@#{1}='#{3}']",
			'!=' => "[@#{1}!='#{3}']",
			'^=' => "[starts-with(@#{1}, '#{3}')]",
			'$=' => "[substring(@#{1}, (string-length(@#{1}) - string-length('#{3}') + 1))='#{3}']",
			'*=' => "[contains(@#{1}, '#{3}')]",
			'~=' => "[contains(concat(' ', @#{1}, ' '), ' #{3} ')]",
			'|=' => "[contains(concat('-', @#{1}, '-'), '-#{3}-')]"
		);

		$string = $operators[$matches[2]];

		$replace = array(
			'#{1}' => $matches[1],
			'#{2}' => $matches[2],
			'#{3}' => $matches[3],
		);

		return strtr($string, $replace);
	}
}