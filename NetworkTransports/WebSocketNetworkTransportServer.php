<?php
namespace Quark\NetworkTransports;

use Quark\IQuarkNetworkTransport;

use Quark\QuarkClient;
use Quark\QuarkDTO;
use Quark\QuarkHTMLIOProcessor;

/**
 * Class WebSocketNetworkTransportServer
 *
 * @package Quark\NetworkTransports
 */
class WebSocketNetworkTransportServer implements IQuarkNetworkTransport {
	const GuID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

	const OP_CONTINUATION = 0x0;
	const OP_TEXT = 0x1;
	const OP_BINARY = 0x2;
	const OP_CLOSE = 0x8;
	const OP_PING = 0x9;
	const OP_PONG = 0xA;

	/**
	 * @var string $_buffer
	 */
	private $_buffer = '';

	/**
	 * @var string $_msgBuffer
	 */
	private $_msgBuffer = '';

	/**
	 * @var int|null $_fragmentedOpcode
	 */
	private $_fragmentedOpcode = null;

	/**
	 * @var bool $_connected
	 */
	private $_connected = false;

	/**
	 * @var string $_subProtocol
	 */
	private $_subProtocol = '';

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function EventConnect (QuarkClient &$client) {
		// TODO: Implement EventConnect() method.
	}

	/**
	 * @param QuarkClient $client
	 * @param string $data
	 *
	 * @return mixed
	 */
	public function EventData (QuarkClient &$client, $data) {
		$this->_buffer .= $data;

		if ($this->_connected) {
			while (true) {
				$payload = $this->FrameIn($this->_buffer, $client);

				if ($payload === null) break;
				if ($payload === false) continue;

				$client->TriggerData($payload);
			}
		}
		else {
			if (!preg_match(QuarkDTO::HTTP_PROTOCOL_REQUEST, $this->_buffer)) return;

			$request = new QuarkDTO();
			$request->UnserializeRequest($this->_buffer. "\r\n");

			$this->_buffer = '';

			$response = new QuarkDTO(new QuarkHTMLIOProcessor());
			$response->Protocol(QuarkDTO::HTTP_VERSION_1_1);
			$response->Status(QuarkDTO::STATUS_101_SWITCHING_PROTOCOLS);
			$response->Headers(array(
				QuarkDTO::HEADER_CONNECTION => QuarkDTO::CONNECTION_UPGRADE,
				QuarkDTO::HEADER_UPGRADE => QuarkDTO::UPGRADE_WEBSOCKET,
				QuarkDTO::HEADER_SEC_WEBSOCKET_ACCEPT => base64_encode(sha1($request->Header(QuarkDTO::HEADER_SEC_WEBSOCKET_KEY) . self::GuID, true)),
			));

			if (strlen($this->_subProtocol) != 0)
				$response->Header(QuarkDTO::HEADER_SEC_WEBSOCKET_PROTOCOL, $this->_subProtocol);

			$client->Send($response->SerializeResponse());

			$this->_connected = true;

			$client->TriggerConnect();
		}
	}

	/**
	 * @param QuarkClient $client
	 *
	 * @return mixed
	 */
	public function EventClose (QuarkClient &$client) {
		$client->TriggerClose();
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function Send ($data) {
		return $this->_connected ? self::FrameOut($data) : $data;
	}

	/**
	 * @param string $data
	 * @param QuarkClient $client = null
	 *
	 * @return string|false|null
	 */
	private function FrameIn (&$data, QuarkClient $client = null) {
		$bufLen = strlen($data);
		if ($bufLen < 2) return null;

		$first = ord($data[0]);
		$second = ord($data[1]);

		$fin = ($first & 0x80) === 0x80;
		$opcode = $first & 0x0f;

		$masked = ($second & 0x80) === 0x80;
		$payloadLen = $second & 0x7f;

		$offset = 2;

		// extended lengths
		if ($payloadLen === 126) {
			if ($bufLen < $offset + 2) return null;

			$ext = substr($data, $offset, 2);
			$un = unpack('n', $ext);
			$payloadLen = $un[1];
			$offset += 2;
		}
		elseif ($payloadLen === 127) {
			if ($bufLen < $offset + 8) return null;

			$ext = substr($data, $offset, 8);
			$parts = array_values(unpack('N2', $ext));
			$payloadLen = ($parts[0] << 32) + $parts[1];
			$offset += 8;
		}

		$maskKey = '';
		if ($masked) {
			if ($bufLen < $offset + 4) return null;

			$maskKey = substr($data, $offset, 4);
			$offset += 4;
		}

		if ($bufLen < $offset + $payloadLen) return null;

		$payload = ($payloadLen > 0) ? substr($data, $offset, $payloadLen) : '';

		$consumed = $offset + $payloadLen;
		$data = ($consumed < $bufLen) ? substr($data, $consumed) : '';

		if ($masked && $payloadLen > 0) {
			$out = '';
			$i = 0;

			while ($i < $payloadLen) {
				$out .= $payload[$i] ^ $maskKey[$i % 4];

				$i++;
			}

			$payload = $out;
		}

		if ($opcode === self::OP_PING) {
			// TODO: handle ping event
			//if ($client !== null)
			//$client->Send(self::FrameOut($payload, self::OP_PONG));

			return false;
		}

		if ($opcode === self::OP_PONG) {
			// TODO: handle pong event
			return false;
		}

		if ($opcode === self::OP_CLOSE) {
			// TODO: handle close event
			//if ($client !== null)
			//$client->TriggerClose();

			return false;
		}

		if ($opcode === self::OP_CONTINUATION) {
			$this->_msgBuffer .= $payload;

			if (!$fin)
				return false;

			$complete = $this->_msgBuffer;

			$this->_msgBuffer = '';
			$this->_fragmentedOpcode = null;

			return $complete;
		}

		if (!$fin) {
			$this->_msgBuffer = $payload;
			$this->_fragmentedOpcode = $opcode;

			return false;
		}

		return $payload;
	}

	/**
	 * @param string $data
	 * @param int $op
	 *
	 * @return string
	 */
	public static function FrameOut ($data, $op = self::OP_TEXT) {
		$length = strlen($data);
		$firstByte = 0x80 | ($op & 0x0f); // FIN + opcode
		$header = chr($firstByte);

		if ($length <= 125) {
			$header .= chr($length);
		}
		elseif ($length <= 0xffff) {
			$header .= chr(126) . pack('n', $length); // 2 bytes for length
		}
		else {
			// 64-bit length (network order)
			// pack('J') not portable, using two 32-bit numbers (big-endian)
			$hi = ($length & 0xffffffff00000000) >> 32;
			$lo = $length & 0xffffffff;
			$header .= chr(127) . pack('NN', $hi, $lo);
		}

		return $header . $data;
	}
}