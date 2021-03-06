<?php
declare(strict_types = 1);
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Unit\Web;

use Remembrall\Model\Web;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class DOM extends Tester\TestCase {
	public function testUtf8Encoding() {
		$dom = new Web\DOM();
		$dom->loadHTML('<p>Příliš žluťoučký kůň úpěl ďábelské ódy.</p>');
		Assert::same(
			'Příliš žluťoučký kůň úpěl ďábelské ódy.',
			$dom->getElementsByTagName('p')->item(0)->nodeValue
		);
	}

	public function testSuppressingWarningOnInvalidHtml() {
		Assert::noError(
			function() {
				(new Web\DOM())->loadHTML(
					'<a href="script.php?foo=bar&hello=world">link</a>'
				);
			}
		);
	}
}

(new DOM())->run();