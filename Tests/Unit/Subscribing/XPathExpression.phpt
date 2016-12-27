<?php
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Unit\Subscribing;

use Remembrall\Model\Subscribing;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class XPathExpression extends Tester\TestCase {
	public function testValidExpression() {
		$expression = '//p';
		Assert::same(
			(string)new Subscribing\XPathExpression(
				new Subscribing\FakePage,
				$expression
			),
			$expression
		);
	}

	public function testAllowingInvalidExpression() {
		$expression = '123';
		Assert::same(
			(string)new Subscribing\XPathExpression(
				new Subscribing\FakePage,
				$expression
			),
			$expression
		);
	}

	public function testMatch() {
		$dom = new \DOMDocument();
		$dom->loadHTML('<p>Hi there</p>');
		$page = new Subscribing\FakePage($dom);
		$expression = new Subscribing\XPathExpression($page, '//p');
		$match = $expression->matches();
		Assert::same(1, $match->length);
		Assert::same($match->item(0)->nodeValue, 'Hi there');
		Assert::same($match->item(0)->nodeName, 'p');
	}

	public function testMultipleMatches() {
		$dom = new \DOMDocument();
		$dom->loadHTML('<p>Hi</p><p>there</p>');
		$page = new Subscribing\FakePage($dom);
		$expression = new Subscribing\XPathExpression($page, '//p');
		$match = $expression->matches();
		Assert::same(2, $match->length);
		Assert::same($match->item(0)->nodeValue, 'Hi');
		Assert::same($match->item(0)->nodeName, 'p');
		Assert::same($match->item(1)->nodeValue, 'there');
		Assert::same($match->item(1)->nodeName, 'p');

	}

	public function testNoMatch() {
		$dom = new \DOMDocument();
		$dom->loadHTML('<p>Hi there</p>');
		$page = new Subscribing\FakePage($dom);
		$expression = (new Subscribing\XPathExpression(
			$page, '//foo'
		))->matches();
		Assert::same(0, $expression->length);
	}
}

(new XPathExpression())->run();