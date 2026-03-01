<?php
/* Copyright (C) 2026	Opendsi		<support@opendsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/webportal/lib/functions.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 */

/**
 * Return the main currency ('EUR', 'USD', ...)
 *
 * @return 	string							Value returned
 */
if (!function_exists('getDolCurrency')) {
	function getDolCurrency()
	{
		global $conf;
		return (string) $conf->currency;
	}
}

/**
 * Returns a list of HTML boolean attributes.
 *
 * Boolean attributes are attributes whose presence on an HTML element
 * represents a true value, and absence represents false. They do not
 * require a value like name="value"; simply including the attribute
 * enables its behavior.
 *
 * Examples of usage:
 * <input type="checkbox" checked>
 * <input type="text" readonly>
 *
 * @return string[] An array of HTML boolean attribute names.
 */
if (!function_exists('getListOfHtmlBooleanAttributes')) {
	function getListOfHtmlBooleanAttributes(): array
	{
		return [
			// Input / Form
			'checked',
			'disabled',
			'readonly',
			'required',
			'autofocus',
			'multiple',

			// Option
			'selected',

			// Form / General
			'novalidate',
			'formnovalidate',

			// Media
			'autoplay',
			'controls',
			'loop',
			'muted',
			'playsinline',

			// Other elements
			'hidden',
			'open',
			'ismap',
			'reversed',
			'allowfullscreen',
			'itemscope',
			'nomodule',
			'defer',
			'async',
			'default',
			'inert',
		];
	}
}

/**
 * Builds an array of safe and properly escaped HTML attributes from a key-value pair list.
 *
 * This function ensures that HTML attributes are correctly encoded for safe output,
 * while allowing certain attributes to remain unescaped if explicitly specified.
 * Special handling is applied for attributes such as `href`, which are processed
 * using `dolPrintHTMLForAttributeUrl()`. All other attributes are escaped using
 * `dolPrintHTMLForAttribute()`.
 *
 * Note: Disabling escaping (via `$unescapedAttr`) is **not recommended** unless you
 * fully trust the input data, as it may lead to XSS vulnerabilities.
 *
 * Example:
 * ```php
 * $attr = [
 *     'href' => 'https://example.com?a=1&b=2',
 *     'class' => 'btn btn-primary',
 *     'title' => 'View details'
 * ];
 * $result = commonHtmlAttributeBuilder($attr);
 *
 * // Output:
 * // [
 * //   'href' => 'href="https://example.com?a=1&amp;b=2"',
 * //   'class' => 'class="btn btn-primary"',
 * //   'title' => 'title="View details"'
 * // ]
 * ```
 *
 * @param array<string, string|int|float|null|bool> $attr          Associative array of attribute names and their values.
 * @param string[]                            $unescapedAttr  Optional list of attribute names that should **not** be escaped.
 *
 * @return array<string, string> An array where each key corresponds to the attribute name
 *                               and each value is a full `key="escaped_value"` string ready for HTML output.
 */
if (!function_exists('commonHtmlAttributeBuilder')) {
	function commonHtmlAttributeBuilder($attr, array $unescapedAttr = [])
	{
		$TCompiledAttr = array();
		if (empty($attr)) {
			return [];
		}

		foreach ($attr as $key => $value) {
			// special boolean attributes case
			if (in_array($key, getListOfHtmlBooleanAttributes())) {
				if ($value) {
					$TCompiledAttr[$key] = $key;
				}
				continue;
			}

			if (!empty($unescapedAttr) && in_array($key, $unescapedAttr)) {
				// Not recommended
				$value = dol_htmlentities((string) $value, ENT_QUOTES | ENT_SUBSTITUTE);
			} elseif ($key == 'href') {
				$value = dolPrintHTMLForAttributeUrl((string) $value);
			} else {
				$value = dolPrintHTMLForAttribute((string) $value);
			}

			$TCompiledAttr[$key] = $key.'="'.$value.'"';    // $value has been escaped by the dolPrintHTMLForAttribute... just before
		}

		return $TCompiledAttr;
	}
}

/**
 * Recursively merges two arrays while preserving keys and replacing existing values.
 *
 * Unlike PHP's native array_merge_recursive(), this function does not combine values
 * into an array when duplicate keys are found. Instead, values from the second array
 * will override values from the first array, unless both values are arrays, in which
 * case the function will merge them recursively.
 *
 * Note : function name is not in camelCase because of name of native php function named array_merge_recursive
 * this approach will help developers to find this function
 *
 * Example:
 *  $a = ['color' => 'blue', 'style' => ['font' => 'Arial', 'size' => 10]];
 *  $b = ['color' => 'red', 'style' => ['size' => 12]];
 *  Result:
 *  [
 *      'color' => 'red',
 *      'style' => [
 *          'font' => 'Arial',
 *          'size' => 12
 *      ]
 *  ]
 *
 * @template T of mixed
 * @param array<string, T> $array1  The base array (default parameters).
 * @param array<string, T> $array2  The array with values to override or extend the base array.
 * @return array<string, T>			The merged array with recursive replacement.
 */
if (!function_exists('array_merge_recursive_distinct')) {
	function array_merge_recursive_distinct(array $array1, array $array2): array
	{
		$merged = $array1;

		foreach ($array2 as $key => $value) {
			if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
				$merged[$key] = array_merge_recursive_distinct($merged[$key], $value);
			} else {
				$merged[$key] = $value;
			}
		}

		return $merged;
	}
}
