<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Nette\Caching;
use Remembrall\Model\{
	Access, Storage
};

/**
 * Cache any given part
 */
final class CachedPart extends Storage\Cache implements Part {
	public function __construct(Part $origin, Caching\IStorage $cache) {
		parent::__construct($origin, $cache);
	}

	public function source(): Page {
		return $this->read(__FUNCTION__);
	}

	public function content(): string {
		return $this->read(__FUNCTION__);
	}

	public function equals(Part $part): bool {
		return $this->read(__FUNCTION__, $part);
	}

	public function expression(): Expression {
		return $this->read(__FUNCTION__);
	}

	public function visitedAt(): Interval {
		return $this->read(__FUNCTION__);
	}
}