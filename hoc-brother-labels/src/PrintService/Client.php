<?php
/**
 * HTTP client that sends structured print jobs to the external print service.
 *
 * This client never talks to the printer directly. It only performs HTTP
 * requests against a configurable local-network print microservice
 * (e.g. a brother_ql_web/label_api-style service running on the LAN).
 *
 * @package HOC\BrotherLabels
 */

namespace HOC\BrotherLabels\PrintService;

use HOC\BrotherLabels\Support\Logger;
use HOC\BrotherLabels\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Class Client
 */
class Client {

	/**
	 * Sends a print job request to the external print service.
	 *
	 * @param Request $request Print job request.
	 * @return Response
	 */
	public function send( Request $request ) {
		$base_url = trim( (string) Options::get( 'print_service_base_url', '' ) );

		if ( '' === $base_url ) {
			Logger::error( 'Print service base URL is not configured.', array( 'job_ref' => $request->get_job_ref() ) );

			return new Response( false, 0, '', __( 'Print service base URL is not configured.', 'hoc-brother-labels' ) );
		}

		$endpoint_path = (string) Options::get( 'print_jobs_endpoint_path', '/print-jobs' );
		$url           = rtrim( $base_url, '/' ) . '/' . ltrim( $endpoint_path, '/' );
		$timeout       = (int) Options::get( 'request_timeout_seconds', 10 );
		$api_token     = (string) Options::get( 'api_token', '' );

		$headers = array(
			'Content-Type' => 'application/json',
		);

		if ( '' !== $api_token ) {
			$headers['Authorization'] = 'Bearer ' . $api_token;
		}

		$args = array(
			'method'  => 'POST',
			'timeout' => max( 1, $timeout ),
			'headers' => $headers,
			'body'    => $request->get_json_body(),
		);

		Logger::info(
			'Sending print job',
			array(
				'endpoint' => $url,
				'job_ref'  => $request->get_job_ref(),
			)
		);

		$result = wp_safe_remote_post( $url, $args );

		return $this->build_response( $result, $request );
	}

	/**
	 * Builds a normalized Response object from a wp_safe_remote_post() result.
	 *
	 * @param array<string,mixed>|\WP_Error $result  Result from wp_safe_remote_post().
	 * @param Request                       $request Original request, for logging.
	 * @return Response
	 */
	private function build_response( $result, Request $request ) {
		if ( is_wp_error( $result ) ) {
			$message = $result->get_error_message();

			Logger::error(
				'Print job request failed',
				array(
					'job_ref' => $request->get_job_ref(),
					'error'   => $message,
				)
			);

			$response = new Response( false, 0, '', $message, $result );

			/** This filter is documented below. */
			return apply_filters( 'hoc_brother_labels_print_response', $response, $request );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $result );
		$body        = (string) wp_remote_retrieve_body( $result );
		$success     = ( $status_code >= 200 && $status_code < 300 );

		$message = $success
			? __( 'Print job accepted by print service.', 'hoc-brother-labels' )
			: $this->build_error_message( $status_code, $body );

		Logger::info(
			'Print job response received',
			array(
				'job_ref'     => $request->get_job_ref(),
				'status_code' => $status_code,
				'success'     => $success,
			)
		);

		$response = new Response( $success, $status_code, $body, $message, $result );

		/**
		 * Filters the normalized print service response.
		 *
		 * @param Response $response The normalized response.
		 * @param Request  $request  The original request.
		 */
		return apply_filters( 'hoc_brother_labels_print_response', $response, $request );
	}

	/**
	 * Builds a human-readable error message for a non-success response.
	 *
	 * @param int    $status_code HTTP status code.
	 * @param string $body        Raw response body.
	 * @return string
	 */
	private function build_error_message( $status_code, $body ) {
		$decoded = json_decode( $body, true );

		if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) && ! empty( $decoded['message'] ) ) {
			return sprintf(
				/* translators: 1: HTTP status code, 2: error message from print service. */
				__( 'Print service returned HTTP %1$d: %2$s', 'hoc-brother-labels' ),
				$status_code,
				sanitize_text_field( (string) $decoded['message'] )
			);
		}

		return sprintf(
			/* translators: %d: HTTP status code. */
			__( 'Print service returned HTTP %d.', 'hoc-brother-labels' ),
			$status_code
		);
	}
}
