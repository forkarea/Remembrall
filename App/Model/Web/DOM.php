<?php
declare(strict_types = 1);
namespace Remembrall\Model\Web;

/**
 * UTF-8 aware and fail-safe extension of classic DOMDocument
 */
final class DOM extends \DOMDocument {
	public function __construct(string $version = '1.0', string $encoding = 'UTF-8') {
		parent::__construct($version, $encoding);
	}

	/**
	 * Suppress errors and set output to UTF-8
	 * @param string $source
	 * @param int $options
	 * @return void
	 */
	public function loadHTML($source, $options = 0): void {
		$previous = libxml_use_internal_errors(true);
		parent::loadHTML(
			mb_convert_encoding(
				$source,
				'HTML-ENTITIES',
				'UTF-8'
			),
			$options
		);
		libxml_use_internal_errors($previous);
	}
}