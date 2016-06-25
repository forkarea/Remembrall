<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use GuzzleHttp;
use Remembrall\Exception;
use Remembrall\Model\Http;

/**
 * Page can not send codes which temporary block access to it
 */
final class AvailableWebPage implements Page {
	private $origin;
	private $response;

	public function __construct(Page $origin, Http\Response $response) {
		$this->origin = $origin;
		$this->response = $response;
	}

	public function content(): \DOMDocument {
		$header = $this->response->headers()->header('Status');
		if($this->available($header))
			return $this->origin->content();
		throw new Exception\ExistenceException(
			sprintf(
				'Web page "%s" can not be loaded because of %s',
				$this->url(),
				$header->value()
			)
		);
	}

	public function url(): string {
		return $this->origin->url();
	}

	/**
	 * Checks whether the page is normally accessible
	 * It means status code under 400
	 * Value of the header is in format: DIGIT STRING (200 OK)
	 * @param Http\Header $header
	 * @return bool
	 */
	private function available(Http\Header $header): bool {
		return filter_var($header->value(), FILTER_SANITIZE_NUMBER_INT) < 400;
	}
}