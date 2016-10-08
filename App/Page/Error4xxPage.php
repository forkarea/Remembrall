<?php
declare(strict_types = 1);
namespace Remembrall\Page;

use Nette\Application;
use Nette\Application\UI;

final class Error4xxPage extends UI\Presenter {
	public function startup() {
		parent::startup();
		if(!$this->getRequest()->isMethod(Application\Request::FORWARD)) {
			$this->error();
		}
	}

	public function renderDefault(Application\BadRequestException $exception) {
		// load template 403.latte or 404.latte or ... 4xx.latte
		$file = __DIR__ . "/templates/Error/{$exception->getCode()}.latte";
		$this->template->setFile(
			is_file($file) ? $file : __DIR__ . '/templates/Error/4xx.latte'
		);
	}
}