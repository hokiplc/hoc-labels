<?php
/**
 * Represents an outgoing print job request.
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\PrintService;

defined( 'ABSPATH' ) || exit;

/**
 * Class Request
 */
class Request {

	/**
	 * The print job payload.
	 *
	 * @var array<string,mixed>
	 */
	private $payload;

	/**
	 * Job reference, used for logging.
	 *
	 * @var string
	 */
	private $job_ref;

	/**
	 * Constructor.
	 *
	 * @param array<string,mixed> $payload Print job payload.
	 */
	public function __construct( array $payload ) {
		$this->payload = $payload;
		$this->job_ref = isset( $payload['job_ref'] ) ? (string) $payload['job_ref'] : '';
	}

	/**
	 * Returns the payload array.
	 *
	 * @return array<string,mixed>
	 */
	public function get_payload() {
		return $this->payload;
	}

	/**
	 * Returns the JSON-encoded payload body.
	 *
	 * @return string
	 */
	public function get_json_body() {
		return (string) wp_json_encode( $this->payload );
	}

	/**
	 * Returns the job reference.
	 *
	 * @return string
	 */
	public function get_job_ref() {
		return $this->job_ref;
	}
}
