<?php
namespace Quark\ViewResources\ChartJS;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkViewResource;

use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class ChartJS
 *
 * @package Quark\ViewResources\ChartJS
 */
class ChartJS implements IQuarkViewResource, IQuarkForeignViewResource {
	private $_version = '';

	/**
	 * @param string $version = '1.0.2'
	 */
	public function __construct ($version = '1.0.2') {
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResourceType;
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/' . $this->_version . '/Chart.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}
}