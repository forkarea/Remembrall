<?php
declare(strict_types = 1);
namespace Remembrall\Page;

use Klapuch\Output;
use Remembrall\Exception\NotFoundException;
use Remembrall\Model\Subscribing;

final class PartsPage extends BasePage {
	public function renderDefault() {
		$xml = new \DOMDocument();
		$xml->load(self::TEMPLATES . '/Parts/default.xml');
		echo (new Output\XsltTemplate(
			self::TEMPLATES . '/Parts/default.xsl',
			new Output\MergedXml(
				$xml,
				new \SimpleXMLElement(
					(string)new Output\WrappedXml(
						'subscriptions',
						...(new Subscribing\OwnedSubscriptions(
							$this->subscriber,
							$this->database
						))->print(new Output\Xml([], 'subscription'))
					)
				),
				...$this->layout()
			)
		))->render();
	}

	public function renderDelete(array $parameters) {
		try {
			['id' => $id] = $parameters;
			(new Subscribing\OwnedSubscription(
				new Subscribing\PostgresSubscription((int)$id, $this->database),
				(int)$id,
				$this->subscriber,
				$this->database
			))->cancel();
			header('Location: ' . $this->url->reference() . 'parts');
			exit;
		} catch(NotFoundException $ex) {
			echo $ex->getMessage();
		}
	}
}