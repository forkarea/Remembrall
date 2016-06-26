<?php
/**
 * @testCase
 * @phpVersion > 7.0.0
 */
namespace Remembrall\Unit\Http;

use Remembrall\Model\{
	Http, Subscribing
};
use Remembrall\TestCase;
use Tester;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class HtmlWebPage extends TestCase\Mockery {
	public function testValidUrl() {
		$url = 'http://www.google.com';
		Assert::same(
			$url,
			(new Subscribing\HtmlWebPage(
				new Http\ConstantRequest(
					new Http\FakeHeaders(['host' => $url])
				),
				new Http\ConstantResponse(new Http\FakeHeaders([]), '')
			))->url()
		);
	}

	public function testInvalidUrlWithoutError() {
		$url = 'fooBar';
		Assert::same(
			$url,
			(new Subscribing\HtmlWebPage(
				new Http\ConstantRequest(
					new Http\FakeHeaders(['host' => $url])
				),
				new Http\ConstantResponse(new Http\FakeHeaders(), '')
			))->url()
		);
	}

	/**
	 * @throws \Remembrall\Exception\ExistenceException Web page must be HTML
	 */
	public function testCSSContentWithError() {
		(new Subscribing\HtmlWebPage(
			new Http\ConstantRequest(new Http\FakeHeaders()),
			new Http\ConstantResponse(
				new Http\FakeHeaders(['Content-Type' => 'text/css'], false), ''
			)
		))->content();
	}

	public function testCorrectlyParsedHTMLContent() {
		Assert::same(
			'Hello Koňíčku',
			(new Subscribing\HtmlWebPage(
				new Http\ConstantRequest(new Http\FakeHeaders()),
				new Http\ConstantResponse(
					new Http\FakeHeaders(['Content-Type' => 'text/html'], true),
					'<html><p>Hello Koňíčku</p></html>'
				)
			))->content()->getElementsByTagName('p')->item(0)->nodeValue
		);
	}
}

(new HtmlWebPage())->run();
