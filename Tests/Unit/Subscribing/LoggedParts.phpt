<?php
/**
 * @testCase
 * @phpVersion > 7.1
 */
namespace Remembrall\Unit\Subscribing;

use Klapuch\{
	Uri, Log
};
use Remembrall\Model\Subscribing;
use Remembrall\TestCase;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

final class LoggedParts extends TestCase\Mockery {
	public function testLoggingOnThrowing() {
		$logs = $this->mock(Log\Logs::class);
		$logs->shouldReceive('put')->twice();
		$parts = new Subscribing\LoggedParts(
			new Subscribing\FakeParts(new \DomainException('fooMessage')), $logs
		);
		Assert::exception(function() use($parts) {
			$parts->add(new Subscribing\FakePart(), new Uri\FakeUri('url'), '//p');
		}, \DomainException::class, 'fooMessage');
		Assert::exception(function() use($parts) {
			$parts->getIterator();
		}, \DomainException::class, 'fooMessage');
	}

	public function testNoExceptionWithoutLogging() {
		$parts = new Subscribing\LoggedParts(
			new Subscribing\FakeParts(),
			$this->mock(Log\Logs::class)
		);
		Assert::noError(function() use($parts) {
			$parts->add(
				new Subscribing\FakePart(),
				new Uri\FakeUri('url'),
				'//p'
			);
		});
		Assert::noError(function() use($parts) {
			$parts->getIterator();
		});
	}
}

(new LoggedParts())->run();