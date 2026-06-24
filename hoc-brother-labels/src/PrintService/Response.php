<?php
/**
 * Normalized response from the external print service.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\PrintService;

defined( 'ABSPATH' ) || exit;

/**
 * Class Response
 */
class Response {

	/**
	 * Whether the print job was accepted successfully.
	 *
	 * @var bool
	 */
	private $success;

	/**
	 * HTTP status code, or 0 if the request failed before a response was received.
	 *
	 * @var int
	 */
	private $status_code;

	/**
	 * Raw response body.
	 *
	 * @var string
	 */
	private $body;

	/**
	 * Human-readable message describing the result.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Raw response data, as returned by wp_remote_post()/WP_Error.
	 *
	 * @var mixed
	 */
	private $raw;

	/**
	 * Constructor.
	 *
	 * @param bool   $success     Whether the job succeeded.
	 * @param int    $status_code HTTP status code.
	 * @param string $body        Raw response body.
	 * @param string $message     Human-readable message.
	 * @param mixed  $raw         Raw response/WP_Error.
	 */
	public function __construct( $success, $status_code, $body, $message, $raw = null ) {
		$this->success     = (bool) $success;
		$this->status_code = (int) $status_code;
		$this->body         = (string) $body;
		$this->message      = (string) $message;
		$this->raw           = $raw;
	}

	/**
	 * Whether the print job succeeded.
	 *
	 * @return bool
	 */
	public function is_success() {
		return $this->success;
	}

	/**
	 * HTTP status code.
	 *
	 * @return int
	 */
	public function get_status_code() {
		return $this->status_code;
	}

	/**
	 * Raw response body.
	 *
	 * @return string
	 */
	public function get_body() {
		return $this->body;
	}

	/**
	 * Human-readable message.
	 *
	 * @return string
	 */
	public function get_message() {
		return $this->message;
	}

	/**
	 * Raw response/WP_Error data.
	 *
	 * @return mixed
	 */
	public function get_raw() {
		return $this->raw;
	}
}
