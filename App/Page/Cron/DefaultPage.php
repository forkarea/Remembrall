<?php
declare(strict_types = 1);
namespace Remembrall\Page\Cron;

use Klapuch\Application;
use Klapuch\Dataset;
use Nette\Mail;
use Remembrall\Model\Misc;
use Remembrall\Model\Subscribing;
use Remembrall\Model\Web;
use Remembrall\Page;

final class DefaultPage extends Page\Layout {
	public function response(array $parameters): Application\Response {
		try {
			$parts = new Web\HarnessedParts(
				new Web\UnreliableParts(
					new Web\CollectiveParts($this->database),
					$this->database
				),
				new Misc\LoggingCallback($this->logs)
			);
			/** @var \Remembrall\Model\Web\Part $part */
			foreach ($parts->all(new Dataset\FakeSelection('')) as $part) {
				try {
					$part->refresh();
				} catch (\Throwable $ex) {
					$this->log($ex);
				}
			}
			$subscriptions = new Subscribing\HarnessedSubscriptions(
				new Subscribing\ChangedSubscriptions(
					new Subscribing\FakeSubscriptions(),
					new Mail\SendmailMailer(),
					$this->database
				),
				new Misc\LoggingCallback($this->logs)
			);
			/** @var \Remembrall\Model\Subscribing\Subscription $subscription */
			foreach ($subscriptions->all(new Dataset\FakeSelection('', [])) as $subscription) {
				try {
					$subscription->notify();
				} catch (\Throwable $ex) {
					$this->log($ex);
				}
			}
			exit('OK');
		} catch (\Throwable $ex) {
			throw $ex;
		}
	}
}