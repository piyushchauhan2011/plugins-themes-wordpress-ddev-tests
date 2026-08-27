<?php
/**
 * Generate simple JPEG placeholders for demo rooms.
 *
 * @package Hotel_Booking
 */

function hotel_booking_make_placeholder( $path, $width, $height, $hex, $label ) {
	$image = imagecreatetruecolor( $width, $height );
	$r     = hexdec( substr( $hex, 0, 2 ) );
	$g     = hexdec( substr( $hex, 2, 2 ) );
	$b     = hexdec( substr( $hex, 4, 2 ) );
	$bg    = imagecolorallocate( $image, $r, $g, $b );
	$fg    = imagecolorallocate( $image, 247, 241, 232 );
	imagefilledrectangle( $image, 0, 0, $width, $height, $bg );
	$overlay = imagecolorallocatealpha( $image, 44, 33, 24, 70 );
	imagefilledrectangle( $image, 0, (int) ( $height * 0.62 ), $width, $height, $overlay );
	$font_width = imagefontwidth( 5 ) * strlen( $label );
	imagestring( $image, 5, (int) ( ( $width - $font_width ) / 2 ), (int) ( $height * 0.78 ), $label, $fg );
	imagejpeg( $image, $path, 88 );
	imagedestroy( $image );
}

$dir = __DIR__;
if ( ! is_dir( $dir ) ) {
	mkdir( $dir, 0755, true );
}

hotel_booking_make_placeholder( $dir . '/deluxe-king.jpg', 1600, 1000, '6b4a38', 'Deluxe King' );
hotel_booking_make_placeholder( $dir . '/garden-suite.jpg', 1600, 1000, '6b7f6a', 'Garden Suite' );
hotel_booking_make_placeholder( $dir . '/family-room.jpg', 1600, 1000, 'a65d3f', 'Family Room' );
hotel_booking_make_placeholder( $dir . '/penthouse.jpg', 1600, 1000, '2c2118', 'Penthouse' );
hotel_booking_make_placeholder( $dir . '/courtyard-twin.jpg', 1600, 1000, 'c4a574', 'Courtyard Twin' );
hotel_booking_make_placeholder( dirname( $dir ) . '/../screenshot.jpg', 1200, 900, '3d2b22', 'Hotel Booking' );

echo "placeholders written\n";
