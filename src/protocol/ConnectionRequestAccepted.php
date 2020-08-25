<?php

/*
 * RakLib network library
 *
 *
 * This project is not affiliated with Jenkins Software LLC nor RakNet.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace raklib\protocol;

#include <rules/RakLibPacket.h>

use raklib\RakLib;
use raklib\utils\InternetAddress;
use function strlen;

class ConnectionRequestAccepted extends Packet{
	public static $ID = MessageIdentifiers::ID_CONNECTION_REQUEST_ACCEPTED;

	/** @var InternetAddress */
	public $address;
	/** @var int */
	public $systemIndex = -1;
	/** @var InternetAddress[] */
	public $systemAddresses = [];

	/** @var int */
	public $sendPingTime;
	/** @var int */
	public $sendPongTime;

	/**
	 * @param InternetAddress[] $systemAddresses
	 */
	public static function create(InternetAddress $clientAddress, array $systemAddresses, int $sendPingTime, int $sendPongTime) : self{
		$result = new self;
		$result->address = $clientAddress;
		$result->systemAddresses = $systemAddresses;
		$result->sendPingTime = $sendPingTime;
		$result->sendPongTime = $sendPongTime;
		return $result;
	}

	public function __construct(string $buffer = "", int $offset = 0){
		parent::__construct($buffer, $offset);
		$this->systemAddresses[] = new InternetAddress("127.0.0.1", 0, 4);
	}

	protected function encodePayload() : void{
		$this->putAddress($this->address);
		$this->putLShort($this->systemIndex);

		$dummy = new InternetAddress("0.0.0.0", 0, 4);
		for($i = 0; $i < RakLib::$SYSTEM_ADDRESS_COUNT; ++$i){
			$this->putAddress($this->systemAddresses[$i] ?? $dummy);
		}

		$this->putLong($this->sendPingTime);
		$this->putLong($this->sendPongTime);
	}

	protected function decodePayload() : void{
		$this->address = $this->getAddress();
		$this->systemIndex = $this->getLShort();

		$len = $this->getBufferSize();
		$dummy = new InternetAddress("0.0.0.0", 0, 4);

		for($i = 0; $i < RakLib::$SYSTEM_ADDRESS_COUNT; ++$i){
			$this->systemAddresses[$i] = $this->getOffset() + 16 < $len ? $this->getAddress() : $dummy; //HACK: avoids trying to read too many addresses on bad data
		}

		$this->sendPingTime = $this->getLong();
		$this->sendPongTime = $this->getLong();
	}
}
