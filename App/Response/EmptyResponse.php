<?php
declare(strict_types = 1);
namespace Remembrall\Response;

use Klapuch\Application;
use Klapuch\Output;

final class EmptyResponse implements Application\Response {
	public function body(): Output\Format {
		return new Output\FakeFormat();
	}

	public function headers(): array {
		return [];
	}
}