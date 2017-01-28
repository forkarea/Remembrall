<?php
declare(strict_types = 1);
namespace Remembrall\Page\Parts;

use Klapuch\Output;
use Remembrall\Model\Subscribing;
use Remembrall\Page;

final class UnreliablePage extends Page\BasePage {
	public function render(array $parameters): Output\Format {
		return new Output\ValidXml(
			new Output\WrappedXml(
				'parts',
				...(new Subscribing\UnreliableParts(
					new Subscribing\CollectiveParts($this->database),
					$this->database
				))->print(new Output\Xml([], 'part'))
			),
			__DIR__ . '/templates/constraint.xsd'
		);
	}
}