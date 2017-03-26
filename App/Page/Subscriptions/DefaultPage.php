<?php
declare(strict_types = 1);
namespace Remembrall\Page\Subscriptions;

use Gajus\Dindent;
use Klapuch\Dataset;
use Klapuch\Form;
use Klapuch\Output;
use Remembrall\Control\Subscription;
use Remembrall\Model\Misc;
use Remembrall\Model\Subscribing;
use Remembrall\Page;
use Texy;

final class DefaultPage extends Page\BasePage {
	private const FIELDS = ['last_update', 'interval', 'expression', 'url'];

	public function render(array $parameters): Output\Format {
		$subscriptions = iterator_to_array(
			(new Subscribing\FormattedSubscriptions(
				new Subscribing\OwnedSubscriptions(
					$this->user,
					$this->database
				),
				new Texy\Texy(),
				new Dindent\Indenter()
			))->iterate(
				new Dataset\CombinedSelection(
					new Dataset\SqlRestSort($_GET['sort'] ?? '', self::FIELDS)
				)
			)
		);
		$dom = new \DOMDocument();
		$dom->loadXML(
			sprintf(
				'<forms>%s</forms>',
				(new Subscription\DeleteForms(
					$subscriptions,
					$this->url,
					$this->csrf,
					new Form\EmptyStorage()
				))->render()
			)
		);
		return new Output\CombinedFormat(
			new Output\DomFormat($dom, 'xml'),
			new Output\ValidXml(
				new Misc\XmlPrintedObjects(
					'subscriptions',
					['subscription' => $subscriptions]
				),
				__DIR__ . '/templates/constraint.xsd'
			)
		);
	}
}