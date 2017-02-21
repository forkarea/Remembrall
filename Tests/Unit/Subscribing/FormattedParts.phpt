<?php
declare(strict_types = 1);
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Unit\Subscribing;

use Klapuch\Dataset;
use Remembrall\Model\Subscribing;
use Tester;
use Tester\Assert;
use Texy;

require __DIR__ . '/../../bootstrap.php';

final class FormattedParts extends Tester\TestCase {
	public function testApplyingToAllDuringIterating() {
		Assert::equal(
			[
				new Subscribing\FormattedPart(
					new Subscribing\FakePart('foo'),
					new Texy\Texy()
				),
				new Subscribing\FormattedPart(
					new Subscribing\FakePart('bar'),
					new Texy\Texy()
				),
			],
			iterator_to_array(
				(new Subscribing\FormattedParts(
					new Subscribing\FakeParts(
						null,
						new Subscribing\FakePart('foo'),
						new Subscribing\FakePart('bar')
					),
					new Texy\Texy()
				))->iterate(new Dataset\FakeSelection())
			)
		);
	}
}

(new FormattedParts())->run();