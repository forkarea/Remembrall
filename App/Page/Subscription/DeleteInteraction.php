<?php
declare(strict_types = 1);
namespace Remembrall\Page\Subscription;

use Klapuch\Application;
use Klapuch\Form;
use Klapuch\Output;
use Klapuch\Uri;
use Remembrall\Form\Subscription;
use Remembrall\Model\Subscribing;
use Remembrall\Page;
use Remembrall\Response;

final class DeleteInteraction extends Page\Layout {
	public function template(array $subscription): Output\Template {
		try {
			(new Form\HarnessedForm(
				new Subscription\DeleteForm(
					new Subscribing\FakeSubscription(),
					$this->url,
					$this->csrf,
					new Form\Backup($_SESSION, $subscription)
				),
				new Form\Backup($_SESSION, $subscription),
				function() use ($subscription): void {
					(new Subscribing\OwnedSubscription(
						new Subscribing\StoredSubscription(
							(int) $subscription['id'],
							$this->database
						),
						(int) $subscription['id'],
						$this->user,
						$this->database
					))->cancel();
				}
			))->validate();
			return new Application\HtmlTemplate(
				new Response\InformativeResponse(
					new Response\RedirectResponse(
						new Response\EmptyResponse(),
						new Uri\RelativeUrl($this->url, 'subscriptions')
					),
					['success' => 'Subscription has been deleted'],
					$_SESSION
				)
			);
		} catch (\UnexpectedValueException $ex) {
			return new Application\HtmlTemplate(
				new Response\InformativeResponse(
					new Response\RedirectResponse(
						new Response\EmptyResponse(),
						new Uri\RelativeUrl($this->url, 'subscriptions')
					),
					['danger' => $ex->getMessage()],
					$_SESSION
				)
			);
		}
	}
}