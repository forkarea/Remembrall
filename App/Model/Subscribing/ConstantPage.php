<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

final class ConstantPage implements Page {
	private $url;
	private $content;

	public function __construct(string $url, string $content) {
		$this->url = $url;
		$this->content = $content;
	}

	public function content(): \DOMDocument {
		$dom = new \DOMDocument();
		@$dom->loadHTML($this->content);
		return $dom;
	}

	public function url(): string {
		return $this->url;
	}
}