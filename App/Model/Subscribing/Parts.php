<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Klapuch\Uri;
use Klapuch\Output;
use Klapuch\Dataset;

interface Parts {
	/**
	 * Add a new part to the parts
	 * @param Part $part
	 * @param Uri\Uri $uri
	 * @param string $expression
	 * @return void
	 */
	public function add(Part $part, Uri\Uri $uri, string $expression): void;

	/**
	 * Go through all the parts
	 * @param \Klapuch\Dataset\Selection $selection
	 * @return \Traversable
	 */
	public function iterate(Dataset\Selection $selection): \Traversable;
}