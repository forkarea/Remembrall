<?php
declare(strict_types = 1);
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Unit\Subscribing;

use Klapuch\Output;
use Remembrall\Model\Subscribing;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class ConstantPart extends Tester\TestCase {
	public function testPrintingFromPassedPart() {
		Assert::same(
			'|snapshot|xxx||id|1||url|google.com|',
			(new Subscribing\ConstantPart(
				new Subscribing\FakePart(),
				'foo',
				'bar',
				['id' => 1, 'url' => 'google.com']
			))->print(new Output\FakeFormat('|snapshot|xxx|'))->serialization()
		);
	}
}

(new ConstantPart())->run();