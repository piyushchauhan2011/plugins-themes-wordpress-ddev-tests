<?php
/**
 * Constants PHPStan cannot see: hotel-booking-core.php exits unless ABSPATH is set.
 *
 * @package Hotel_Booking
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

if ( ! defined( 'HOTEL_BOOKING_CORE_PATH' ) ) {
	define( 'HOTEL_BOOKING_CORE_PATH', 'wp-content/plugins/hotel-booking-core/' );
}

if ( ! defined( 'HOTEL_BOOKING_CORE_URL' ) ) {
	define( 'HOTEL_BOOKING_CORE_URL', 'wp-content/plugins/hotel-booking-core/' );
}

if ( ! function_exists( 'pll_current_language' ) ) {
	/**
	 * @param string $field Language field.
	 * @return string|false
	 */
	function pll_current_language( $field = 'slug' ) {
		unset( $field );
		return false;
	}
}

if ( ! function_exists( 'pll_languages_list' ) ) {
	/**
	 * @param array<string, mixed> $args List args.
	 * @return mixed
	 */
	function pll_languages_list( $args = array() ) {
		unset( $args );
		return array();
	}
}

if ( ! function_exists( 'pll_the_languages' ) ) {
	/**
	 * @param array<string, mixed> $args Output args.
	 * @return array<string, mixed>|string
	 */
	function pll_the_languages( $args = array() ) {
		unset( $args );
		return array();
	}
}

if ( ! function_exists( 'pll_get_post_translations' ) ) {
	/**
	 * @param int $post_id Post ID.
	 * @return array<string, int>
	 */
	function pll_get_post_translations( $post_id ) {
		unset( $post_id );
		return array();
	}
}

if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * Minimal WP-CLI surface for static analysis.
	 */
	class WP_CLI {
		/**
		 * @param string $name     Command name.
		 * @param mixed  $callable Handler.
		 * @return void
		 */
		public static function add_command( $name, $callable ) {
			unset( $name, $callable );
		}

		/**
		 * @param string $message Message.
		 * @return void
		 */
		public static function success( $message ) {
			unset( $message );
		}

		/**
		 * @param string $message Message.
		 * @return void
		 */
		public static function warning( $message ) {
			unset( $message );
		}

		/**
		 * @param string $message Message.
		 * @return never
		 */
		public static function error( $message ) {
			unset( $message );
			throw new RuntimeException( 'WP_CLI::error' );
		}

		/**
		 * @param string $message Message.
		 * @return void
		 */
		public static function log( $message ) {
			unset( $message );
		}
	}
}

if ( ! function_exists( 'pll_get_post_language' ) ) {
	/**
	 * @param int    $post_id Post ID.
	 * @param string $field   Language field.
	 * @return string|false
	 */
	function pll_get_post_language( $post_id, $field = 'slug' ) {
		unset( $post_id, $field );
		return false;
	}
}

if ( ! function_exists( 'pll_set_post_language' ) ) {
	/**
	 * @param int    $post_id Post ID.
	 * @param string $lang    Language slug.
	 * @return void
	 */
	function pll_set_post_language( $post_id, $lang ) {
		unset( $post_id, $lang );
	}
}

if ( ! function_exists( 'pll_save_post_translations' ) ) {
	/**
	 * @param array<string, int> $arr Language slug to post ID.
	 * @return void
	 */
	function pll_save_post_translations( $arr ) {
		unset( $arr );
	}
}

if ( ! class_exists( 'Hotel_Booking_PHPStan_PLL_Model', false ) ) {
	/**
	 * Minimal Polylang model surface for static analysis.
	 */
	class Hotel_Booking_PHPStan_PLL_Model {
		/**
		 * @var mixed
		 */
		public $options = array();

		/**
		 * @param array<string, mixed> $args Language args.
		 * @return bool
		 */
		public function add_language( $args ) {
			unset( $args );
			return true;
		}

		/**
		 * @param array<string, mixed> $args List args.
		 * @return mixed
		 */
		public function get_languages_list( $args = array() ) {
			unset( $args );
			return array();
		}

		/**
		 * @return void
		 */
		public function clean_languages_cache() {}
	}

	/**
	 * Minimal Polylang bootstrap object for static analysis.
	 */
	class Hotel_Booking_PHPStan_PLL {
		/**
		 * @var Hotel_Booking_PHPStan_PLL_Model
		 */
		public $model;
	}
}

if ( ! function_exists( 'PLL' ) ) {
	/**
	 * @return Hotel_Booking_PHPStan_PLL
	 */
	function PLL() {
		$pll        = new Hotel_Booking_PHPStan_PLL();
		$pll->model = new Hotel_Booking_PHPStan_PLL_Model();
		return $pll;
	}
}
