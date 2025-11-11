<?php

namespace TsTeacherLogin\Service;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Attendance implements MessageComponentInterface {

	/**
	 * @var \SplObjectStorage
	 */
	protected $clients;

	public function __construct() {
		$this->clients = new \SplObjectStorage;
	}

	/**
	 * A new websocket connection
	 *
	 * @param ConnectionInterface $conn
	 */
	public function onOpen(ConnectionInterface $conn) {

		// Store the new connection to send messages to later
		$this->clients->attach($conn);
		$conn->send('..:: Hello from the Notification Center ::..');

		echo "New connection \n";

	}

	/**
	 * Handle message sending
	 *
	 * @param ConnectionInterface $from
	 * @param string $msg
	 */
	public function onMessage(ConnectionInterface $from, $msg) {

		#__out(get_class($from));

		$numRecv = count($this->clients) - 1;
		echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
			, $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

		foreach ($this->clients as $client) {
			if ($from !== $client) {
				// The sender is not the receiver, send to each client connected
				$client->send($msg);
			}
		}

	}

	/**
	 * A connection is closed
	 * @param ConnectionInterface $conn
	 */
	public function onClose(ConnectionInterface $conn)
	{
		// The connection is closed, remove it, as we can no longer send it messages
		$this->clients->detach($conn);

		echo "Connection {$conn->resourceId} has disconnected\n";

	}

	/**
	 * Error handling
	 *
	 * @param ConnectionInterface $conn
	 * @param \Exception $e
	 */
	public function onError(ConnectionInterface $conn, \Exception $e)
	{
		$conn->send("Error : " . $e->getMessage());
		$conn->close();
	}

}