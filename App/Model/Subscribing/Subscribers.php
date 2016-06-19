<?php
declare(strict_types = 1);
namespace Remembrall\Model\Subscribing;

use Remembrall\Exception;

interface Subscribers {
	/**
	 * Register a new subscriber to the system
	 * @param string $email
	 * @param string $password
	 * @throws Exception\DuplicateException
	 * @return Subscriber
	 */
	public function register(string $email, string $password): Subscriber;
}