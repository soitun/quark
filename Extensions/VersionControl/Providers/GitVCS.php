<?php
namespace Quark\Extensions\VersionControl\Providers;

use Quark\Quark;
use Quark\QuarkKeyValuePair;

use Quark\Extensions\VersionControl\IQuarkVersionControlProvider;

/**
 * Class GitVCS
 *
 * @package Quark\Extensions\VersionControl\Providers
 */
class GitVCS implements IQuarkVersionControlProvider {
	/**
	 * @return bool
	 */
	public function VCSInit () {
		// TODO: Implement VCSInit() method.
	}

	/**
	 * @param string $url
	 * @param QuarkKeyValuePair $user
	 *
	 * @return bool
	 */
	public function VCSRepository ($url, QuarkKeyValuePair $user) {
		// TODO: Implement VCSRepository() method.
	}

	/**
	 * @param string $message
	 *
	 * @return bool
	 */
	public function VCSCommit ($message) {
		// TODO: Implement VCSCommit() method.
	}

	/**
	 * @return bool
	 */
	public function VCSPull () {
		// TODO: Implement VCSPull() method.
	}

	/**
	 * @return bool
	 */
	public function VCSPush () {
		// TODO: Implement VCSPush() method.
	}

	/**
	 * @param int $steps
	 *
	 * @return bool
	 */
	public function VCSRollback ($steps) {
		// TODO: Implement VCSRollback() method.
	}

	/**
	 * @param int $steps
	 *
	 * @return bool
	 */
	public function VCSRestore ($steps) {
		// TODO: Implement VCSRestore() method.
	}

	/**
	 * @return string
	 */
	public function VCSLastLog () {
		// TODO: Implement VCSLastLog() method.
	}

	/**
	 * @param string $location = ''
	 *
	 * @return QuarkKeyValuePair
	 */
	public function Revision ($location = '') {
		if (func_num_args() == 0)
			$location = Quark::Host();

		$out = [];
		$code = 0;

		exec('git -C ' . $location . ' rev-parse --short HEAD', $out, $code);
		if ($code !== 0 || empty($out[0])) return null;

		$hash = trim($out[0]);

		$out1 = []; // tracked / staged
		exec('git -C ' . $location . ' diff-index --quiet HEAD --', $out1, $code1);

		$out2 = []; // untracked / modified
		exec('git -C ' . $location . ' diff-files --quiet', $out2, $code2);

		return new QuarkKeyValuePair($hash, $code1 === 0 && $code2 === 0);
	}
}