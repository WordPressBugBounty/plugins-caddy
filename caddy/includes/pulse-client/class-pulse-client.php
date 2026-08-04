<?php
/**
 * Pulse client - shared helpers.
 *
 * Portable, license-agnostic. Forked from the Refinery survey SDK, renamed so a
 * Tribe plugin and a Refinery plugin can coexist on one site without a
 * class_exists collision. Points at the Pulse Hub, NOT Refinery.
 *
 * @package Pulse_Client
 */

defined( 'ABSPATH' ) || exit;

/*
 * Bundle version. Every Tribe plugin should ship the SAME Pulse bundle. Because
 * the class is guarded by class_exists() below, if an OLDER copy loaded first it
 * wins and this plugin silently runs its stale survey/telemetry code. Surface the
 * mismatch when debugging so it can be fixed by updating all Tribe plugins.
 */
if ( ! defined( 'PULSE_CLIENT_BUNDLE_VERSION' ) ) {
	define( 'PULSE_CLIENT_BUNDLE_VERSION', '0.1.0' );
} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG && '0.1.0' !== PULSE_CLIENT_BUNDLE_VERSION ) {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-only notice, gated on WP_DEBUG.
	error_log( 'Pulse client: bundle 0.1.0 requested but ' . PULSE_CLIENT_BUNDLE_VERSION . ' already loaded first; update all Tribe plugins to the same Pulse bundle.' );
}

if ( ! class_exists( 'Pulse_Client' ) ) {

	/**
	 * Static helpers shared by the survey + telemetry clients.
	 */
	class Pulse_Client {

		const VERSION   = '0.1.0';
		const REST_PATH = '/wp-json/pulse/v1';

		/**
		 * Anonymous install ID, generated once and reused for both survey and
		 * telemetry payloads so the hub can join them. Site-wide (shared across
		 * any Tribe plugin on this site).
		 *
		 * @return string
		 */
		public static function install_id() {
			$id = get_option( 'pulse_install_id' );
			if ( ! $id ) {
				$id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : md5( home_url() . wp_rand() );
				update_option( 'pulse_install_id', $id, false );
			}
			return $id;
		}

		/**
		 * First time this site was seen, used to derive tenure.
		 *
		 * @return int Unix timestamp.
		 */
		public static function first_seen_ts() {
			$ts = (int) get_option( 'pulse_first_seen', 0 );
			if ( ! $ts ) {
				$ts = time();
				update_option( 'pulse_first_seen', $ts, false );
			}
			return $ts;
		}

		/**
		 * Days since first seen.
		 *
		 * @return int
		 */
		public static function tenure_days() {
			return (int) floor( ( time() - self::first_seen_ts() ) / DAY_IN_SECONDS );
		}

		/**
		 * Build a hub endpoint URL. Hub base is filterable per plugin slug.
		 *
		 * @param string $hub_url Base hub URL passed by the plugin.
		 * @param string $route   'survey' or 'telemetry'.
		 * @param string $slug    Plugin slug (for the filter context).
		 * @return string
		 */
		public static function endpoint( $hub_url, $route, $slug = '' ) {
			$hub_url = apply_filters( 'pulse_hub_url', $hub_url, $slug );
			return untrailingslashit( $hub_url ) . self::REST_PATH . '/' . ltrim( $route, '/' );
		}

		/**
		 * Option key holding the telemetry opt-in state for a plugin.
		 *
		 * @param string $slug Plugin slug.
		 * @return string
		 */
		public static function telemetry_optin_key( $slug ) {
			return 'pulse_telemetry_optin_' . sanitize_key( $slug );
		}

		/**
		 * Whether telemetry is opted in for a plugin (default OFF).
		 *
		 * @param string $slug Plugin slug.
		 * @return bool
		 */
		public static function telemetry_enabled( $slug ) {
			return 'yes' === get_option( self::telemetry_optin_key( $slug ), 'no' );
		}

		/**
		 * POST a JSON payload to the hub. Short timeout.
		 *
		 * @param string $url      Endpoint URL.
		 * @param array  $payload  Body.
		 * @param bool   $blocking Whether to wait for the response. Telemetry passes
		 *                         false so a slow hub never stalls an admin page load;
		 *                         the survey keeps it true since it redirects after.
		 * @return array|WP_Error
		 */
		public static function post( $url, $payload, $blocking = true ) {
			$host  = wp_parse_url( $url, PHP_URL_HOST );
			$local = $host && preg_match( '/\.(local|test)$/', $host );

			return wp_remote_post(
				$url,
				array(
					'timeout'   => 8,
					'blocking'  => (bool) $blocking,
					// Local dev sites use self-signed certs; keep verification on for real hosts.
					'sslverify' => apply_filters( 'pulse_sslverify', ! $local, $url ),
					'headers'   => array(
						'Accept'       => 'application/json',
						'Content-Type' => 'application/json',
					),
					'body'      => wp_json_encode( $payload ),
				)
			);
		}
	}
}
