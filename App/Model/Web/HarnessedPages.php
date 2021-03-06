<?php
declare(strict_types = 1);
namespace Remembrall\Model\Web;

use Klapuch\Uri;
use Remembrall\Model\Misc;

/**
 * Pages harnessed by callback
 */
final class HarnessedPages implements Pages {
	private $origin;
	private $callback;

	public function __construct(Pages $origin, Misc\Callback $callback) {
		$this->origin = $origin;
		$this->callback = $callback;
	}

	public function add(Uri\Uri $uri, Page $page): Page {
		return $this->callback->invoke(
			[$this->origin, __FUNCTION__],
			func_get_args()
		);
	}
}