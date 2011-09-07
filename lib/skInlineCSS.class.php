<?php

/**
 * This class can be used to convert HTML with CSS into HTML with inline styles
 *
 * The code is based on CssToInlineStyles by Tijs Verkoyen
 * https://github.com/tijsverkoyen/CssToInlineStyles
 *
 * Original CssToInlineStyles License
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * 
 * Copyright©, Tijs Verkoyen. All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 * - Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * - Neither the name of Tijs Verkoyen, nor the names of its contributors may be
 *   used to endorse or promote products derived from this software without
 *   specific prior written permission.
 * 
 * This software is provided by the copyright holders and contributors as is and
 * any express or implied warranties, including, but not limited to, the implied
 * warranties of merchantability and fitness for a particular purpose are
 * disclaimed. In no event shall the copyright owner or contributors be liable
 * for any direct, indirect, incidental, special, exemplary, or consequential
 * damages (including, but not limited to, procurement of substitute goods or
 * services; loss of use, data, or profits; or business interruption) however
 * caused and on any theory of liability, whether in contract, strict liability,
 * or tort (including negligence or otherwise) arising in any way out of the use
 * of this software, even if advised of the possibility of such damage.
 *
 * @package sknInlineCSSPlugin
 * @author Tijs Verkoyen <php-css-to-inline-styles@verkoyen.eu>
 * @author Jaik Dean <jaik@studioskylab.com>
 **/
class skInlineCSS
{

	/**
	 * Options
	 *
	 * @var array
	 **/
	protected $options;

	/**
	 * skCSSSelectorXPathConverter instance
	 *
	 * @var object
	 **/
	protected $converter = null;

	/**
	 * DOMDocument instance
	 *
	 * @var object
	 **/
	protected $doc = null;


	/**
	 * Constructor
	 *
	 * @param array $options
	 * @author Jaik Dean
	 **/
	public function __construct($options = array())
	{
		$this->options = array_merge(array(
			'apply_style_blocks'             => true,
			'apply_linked_stylesheets'       => false,
			'remove_matched_stylesheet_tags' => true,
		), $options);
	}


	/**
	 * Set an option
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return object Self
	 * @author Jaik Dean
	 **/
	public function setOption($name, $value)
	{
		$this->options[$name] = $value;
		return $this;
	}


	/**
	 * Convert the given HTML to have inline style attributes
	 *
	 * @param string $html
	 * @param string $css Optional additional CSS
	 * @return string HTML with inline styles
	 * @author Jaik Dean
	 **/
	public function convert($html, $css = '')
	{
		// load the html dom
		$this->doc = new DOMDocument();
		$this->doc->loadHTML($html);

		// extract the css from the page
		$css = $this->extractCSSFromDocument($this->doc) . "\n$css";

		// parse the CSS
		$rules = $this->parseCSS($css);

		// if there are no rules, return the html we have
		if (empty($rules)) {
			return $html;
		}

		// recurse through the rules
		foreach ($rules as $rule) {
			// find the elements to apply to
			$elements = $this->selectElements($rule['selector']);

			// recurse through the elements
			foreach ($elements as $element) {

				// if there are no styles stored, store them in a data attribute
				if ($element->attributes->getNamedItem('data-skInlineCSS') == null) {
					$original_styles = '';

					if ($element->attributes->getNamedItem('style') !== null) {
						$original_styles = $element->attributes->getNamedItem('style')->value;
					}

					// store original styles
					$element->setAttribute('data-skInlineCSS', $original_styles);

					// clear the element's styles
					$element->setAttribute('style', '');
				}

				$properties = array();

				// get current styles
				$styles = $element->attributes->getNamedItem('style');

				// any styles defined before?
				if ($styles !== null) {
					// get value for the styles attribute
					$defined_styles = (string) $styles->value;

					// split into properties
					$defined_properties = explode(';', $defined_styles);

					// loop properties
					foreach ($defined_properties as $property) {
						// validate the property
						if ($property == '') {
							continue;
						}

						// split into chunks
						$chunks = explode(':', trim($property), 2);

						// validate
						if (!isset($chunks[1])) {
							continue;
						}

						// loop chunks
						$properties[$chunks[0]] = trim($chunks[1]);
					}
				}

				// add new properties into the list
				foreach ($rule['properties'] as $key => $value) {
					$properties[$key] = $value;
				}

				// build string
				$property_chunks = array();

				// build chunks
				foreach ($properties as $key => $values) {
					foreach ((array) $values as $value) {
						$property_chunks[] = $key . ':' . $value . ';';
					}
				}

				// build properties string
				$properties_string = implode('', $property_chunks);

				// set attribute
				if ($properties_string != '') {
					$element->setAttribute('style', $properties_string);
				}
			}
		}

		// search elements
		$elements = $this->selectElements('[data-skInlineCSS]');

		// loop found elements
		foreach ($elements as $element) {
			// get the original styles
			$original_style = $element->attributes->getNamedItem('data-skInlineCSS')->value;

			if ($original_style != '') {
				$original_properties = array();
				$original_styles     = explode(';', $original_style);

				foreach ($original_styles as $property) {
					// validate property
					if ($property == '') {
						continue;
					}

					// split into chunks
					$chunks = explode(':', trim($property), 2);

					// validate
					if (!isset($chunks[1])) {
						continue;
					}

					// loop chunks
					$original_properties[$chunks[0]] = trim($chunks[1]);
				}

				// get current styles
				$styles     = $element->attributes->getNamedItem('style');
				$properties = array();

				// any styles defined before?
				if ($styles !== null) {
					// get value for the styles attribute
					$defined_styles = (string) $styles->value;

					// split into properties
					$defined_properties = explode(';', $defined_styles);

					// loop properties
					foreach ($defined_properties as $property) {
						// validate property
						if ($property == '') {
							continue;
						}

						// split into chunks
						$chunks = explode(':', trim($property), 2);

						// validate
						if (!isset($chunks[1])) {
							continue;
						}

						// loop chunks
						$properties[$chunks[0]] = trim($chunks[1]);
					}
				}

				// add new properties into the list
				foreach ($original_properties as $key => $value) {
					$properties[$key] = $value;
				}

				// build string
				$property_chunks = array();

				// build chunks
				foreach ($properties as $key => $values) {
					foreach ((array) $values as $value) {
						$property_chunks[] = $key . ': ' . $value . ';';
					}
				}

				// build properties string
				$properties_string = implode(' ', $property_chunks);

				// set attribute
				if ($properties_string != '') {
					$element->setAttribute('style', $properties_string);
				}
			}

			// remove placeholder
			$element->removeAttribute('data-skInlineCSS');
		}

		return $this->doc->saveHTML();
	}


	/**
	 * Get the elements which match the given selector
	 *
	 * @param string $selector CSS selector
	 * @return DOMNodeList
	 * @author Jaik Dean
	 **/
	protected function selectElements($selector)
	{
		if ($this->converter === null) {
			$this->converter = new skCSSSelectorXPathConverter();
		}

		$xpath = new DOMXPath($this->doc);
		$query = $this->converter->convertSelector($selector);

		return $xpath->query($query);
	}


	/**
	 * undocumented function
	 *
	 * @param DOMDocument $doc
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function extractCSSFromDocument(DOMDocument $doc)
	{
		$query = array();

		if ($this->options['apply_style_blocks']) {
			$query[] = 'style';
		}

		if ($this->options['apply_linked_stylesheets']) {
			$query[] = 'link[rel="stylesheet"]';
		}

		if (!count($query)) {
			return '';
		}

		$styles = $this->selectElements(implode(', ', $query));
		$css    = '';

		foreach ($styles as $style) {
			switch ($style->nodeName) {
				case 'style':
					$css .= "\n" . $style->textContent;
				break;

				case 'link':
					$css .= "\n" . file_get_contents($style->getAttribute('href'));
				break;
			}

			// remove the original <style> or <link> elements if necessary
			if ($this->options['remove_matched_stylesheet_tags']) {
				$style->parentNode->removeChild($style);
			}
		}

		return $css;
	}


	/**
	 * undocumented function
	 *
	 * @return array
	 * @author Jaik Dean
	 **/
	protected function parseCSS($css)
	{
		$return = array();

		// remove newlines
		$css = str_replace(array("\r", "\n"), '', $css);

		// replace double quotes with single quotes
		$css = str_replace('"', '\'', $css);

		// remove comments
		$css = preg_replace('|/\*.*?\*/|', '', $css);

		// remove multiple spaces
		$css = preg_replace('/\s\s+/', ' ', $css);

		// rules are split by }
		$rules = explode('}', $css);

		// loop rules
		foreach ($rules as $i => $rule) {
			// split into chunks
			$chunks = explode('{', $rule);

			// invalid rule?
			if (!isset($chunks[1])) {
				continue;
			}

			// set the selectors
			$selectors = explode(',', $chunks[0]);

			// get properties
			$properties = trim($chunks[1]);

			// loop selectors
			foreach ($selectors as $selector) {
				// build an array for each selector
				$ruleset = array();

				// store selector
				$selector = trim($selector);
				$ruleset['selector'] = $selector;

				// process the properties
				$ruleset['properties'] = $this->parseCSSProperties($properties);

				// calculate specificity
				$ruleset['specificity'] = $this->calculateSelectorSpecificity($selector) + $i;

				// add into global rules
				$return[] = $ruleset;
			}
		}

		// sort based on specificity
		if (!empty($return)) {
			usort($return, array($this, 'sortBySpecificity'));
		}

		return $return;
	}


	/**
	 * Process the CSS-properties
	 *
	 * @return	array
	 * @param	string $propertyString	The CSS-properties.
	 */
	protected function parseCSSProperties($properties)
	{
		$properties = explode(';', $properties);
		$pairs      = array();

		// recurse through properties
		foreach ($properties as $property) {
			// split into chunks
			$chunks = explode(':', $property, 2);

			// validate
			if (!isset($chunks[1])) {
				continue;
			}

			// cleanup
			$chunks[0] = trim($chunks[0]);
			$chunks[1] = trim($chunks[1]);

			// add to pairs array
			if (!isset($pairs[$chunks[0]]) || !in_array($chunks[1], $pairs[$chunks[0]])) {
				$pairs[$chunks[0]][] = $chunks[1];
			}
		}

		// sort the pairs
		ksort($pairs);

		// return
		return $pairs;
	}


	/**
	 * Calculate the specificity of the given selector
	 *
	 * @param string $selector
	 * @return int
	 * @author Jaik Dean
	 **/
	protected function calculateSelectorSpecificity($selector)
	{
		// cleanup selector
		$selector = str_replace(array('>', '+'), array(' > ', ' + '), $selector);

		// split the selector into chunks based on spaces
		$chunks = explode(' ', $selector);

		$specificity = array(0, 0, 0);

		// loop chunks
		foreach ($chunks as $chunk) {
			if (strstr($chunk, '#') !== false) {
				// IDs
				$specificity[0]++;
			} elseif (strstr($chunk, '.')) {
				// classes
				$specificity[1]++;
			} else {
				// anything else (tags)
				$specificity[2]++;
			}
		}

		// we can safely assume no selector will contain more than 99 parts,
		// so let's use 2 digits for each
		return (int) $specificity[0]
			. str_pad($specificity[1], 2, '0', STR_PAD_LEFT)
			. str_pad($specificity[2], 2, '0', STR_PAD_LEFT)
			. '0000';
	}


	/**
	 * undocumented function
	 *
	 * @return int
	 * @author Jaik Dean
	 **/
	protected function sortBySpecificity($a, $b)
	{
		if ($a['specificity'] < $b['specificity']) {
			return -1;
		}

		if ($a['specificity'] > $b['specificity']) {
			return 1;
		}

		return 0;
	}


	/**
	 * Convert the given CSS selector to an XPath
	 *
	 * @param string $selector
	 * @return string
	 * @author Jaik Dean
	 **/
	protected function convertSelectorToXPath($selector)
	{
		return $this->selector_converter->convertSelector($selector);
	}

}