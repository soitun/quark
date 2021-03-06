<?php
namespace Quark\Extensions\SMS;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkArchException;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkPlainIOProcessor;

/**
 * Class SMS
 *
 * @package Quark\Extensions\SMS
 */
class SMS implements IQuarkExtension {
	/**
	 * @var SMSCenterConfig $_config
	 */
	private $_config;

	/**
	 * @var string $_message = ''
	 */
	private $_message = '';

	/**
	 * @var string[] $_phones = []
	 */
	private $_phones = array();

	/**
	 * @param string $config
	 * @param string $message
	 * @param array $phones
	 */
	public function __construct ($config, $message = '', $phones = []) {
		$this->Message($message);
		$this->Phones($phones);

		$this->_config = Quark::Config()->Extension($config);
	}

	/**
	 * @param string $message
	 *
	 * @return string
	 */
	public function Message ($message = '') {
		if (func_num_args() == 1)
			$this->_message = (string)$message;

		return $this->_message;
	}

	/**
	 * @param string $sender
	 *
	 * @return string
	 */
	public function Sender ($sender = '') {
		if (func_num_args() == 1)
			$this->_config->sender = $sender;

		return $this->_config->sender;
	}

	/**
	 * @param string $phone
	 */
	public function Phone ($phone) {
		$this->_phones[] = $phone;
	}

	/**
	 * @param array $phones
	 *
	 * @return array
	 */
	public function Phones ($phones = []) {
		if (func_num_args() == 1)
			$this->_phones = $phones;

		return $this->_phones;
	}

	/**
	 * @return bool
	 * @throws QuarkArchException
	 */
	public function Send () {
		return !isset($this->_main()->error);
	}

	/**
	 * @return float
	 * @throws QuarkArchException
	 */
	public function Cost () {
		return (float)$this->_main('&cost=1')->cost;
	}

	/**
	 * @return int
	 * @throws QuarkArchException
	 */
	public function Ping () {
		return $this->_main('&ping=1')->id;
	}

	/**
	 * @param string $append
	 *
	 * @return QuarkDTO|bool
	 * @throws QuarkArchException
	 */
	private function _main ($append = '') {
		if (strlen($this->_message) == 0)
			throw new QuarkArchException('SMS: message length should be greater than 0');

		return QuarkHTTPClient::To(
			'http://smsc.ru/sys/send.php'
			. '?login='. $this->_config->username
			. '&psw=' . $this->_config->password
			. '&phones=' . implode(',', $this->_phones)
			. '&mes=' . $this->_message
			. '&fmt=3'
			. '&charset=utf-8'
			. ($this->_config->sender != '' ? '&sender=' . $this->_config->sender : '')
			. $append,
			QuarkDTO::ForGET(new QuarkPlainIOProcessor()),
			new QuarkDTO(new QuarkJSONIOProcessor())
		);
	}
}