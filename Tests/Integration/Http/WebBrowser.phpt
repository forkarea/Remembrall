<?php
/**
 * @testCase
 * @phpVersion > 7.0.0
 */
namespace Remembrall\Integration\Http;

use GuzzleHttp;
use Remembrall\Model\Http;
use Tester\Assert;
use Tester;

require __DIR__ . '/../../bootstrap.php';

final class WebBrowser extends Tester\TestCase {
	protected function setUp() {
		parent::setUp();
		Tester\Environment::lock('http', __DIR__ . '/../../Temporary');
	}

	public function testReturnedContent() {
		$http = new GuzzleHttp\Client();
		$content = (new Http\WebBrowser($http))->send(
			new Http\ConstantRequest(
				new Http\FakeHeaders(
					['method' => 'get', 'host' => 'http://www.facedown.cz']
				)
			)
		)->content();
		$dom = Tester\DomQuery::fromHtml($content);
		Assert::equal('Facedown', current($dom->find('h1')[0]));
	}

	public function testReturnedHeadersFromHttp() {
		$http = new GuzzleHttp\Client();
		$headers = (new Http\WebBrowser($http))->send(
			new Http\ConstantRequest(
				new Http\FakeHeaders(
					['method' => 'get', 'host' => 'http://www.facedown.cz']
				)
			)
		)->headers();
		Assert::equal(
			new Http\ConstantHeader('Content-Type', 'text/html; charset=utf-8'),
			$headers->header('Content-Type')
		);
	}
}

(new WebBrowser())->run();
