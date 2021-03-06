<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Gajus\Dindent;
use Klapuch\Dataset;
use Klapuch\Iterator;
use Klapuch\Time;
use Klapuch\Uri;
use Texy;

/**
 * Formatted subscriptions
 */
final class FormattedSubscriptions implements Subscriptions {
	private $origin;
	private $texy;
	private $indenter;

	public function __construct(
		Subscriptions $origin,
		Texy\Texy $texy,
		Dindent\Indenter $indenter
	) {
		$this->origin = $origin;
		$this->texy = $texy;
		$this->indenter = $indenter;
	}

	public function subscribe(
		Uri\Uri $url,
		string $expression,
		string $language,
		Time\Interval $interval
	): void {
		$this->origin->subscribe($url, $expression, $language, $interval);
	}

	public function all(Dataset\Selection $selection): \Traversable {
		return new Iterator\MappedIterator(
			$this->origin->all($selection),
			function(Subscription $subscription): Subscription {
				return new FormattedSubscription(
					$subscription,
					$this->texy,
					$this->indenter
				);
			}
		);
	}
}