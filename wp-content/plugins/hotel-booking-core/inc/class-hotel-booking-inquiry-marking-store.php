<?php
/**
 * Symfony MarkingStore that reads and writes inquiry.status.
 *
 * @package Hotel_Booking_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps inquiry.status onto a Symfony Marking.
 */
class Hotel_Booking_Inquiry_Marking_Store implements \Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface {

	/**
	 * @param object $subject Inquiry row.
	 * @return \Symfony\Component\Workflow\Marking
	 */
	public function getMarking( object $subject ): \Symfony\Component\Workflow\Marking {
		$place = 'pending';
		if ( isset( $subject->status ) && is_string( $subject->status ) && '' !== $subject->status ) {
			$place = $subject->status;
		}

		return new \Symfony\Component\Workflow\Marking( array( $place => 1 ) );
	}

	/**
	 * @param object                              $subject Inquiry row.
	 * @param \Symfony\Component\Workflow\Marking $marking New marking.
	 * @param array<string, mixed>                $context Unused.
	 * @return void
	 */
	public function setMarking( object $subject, \Symfony\Component\Workflow\Marking $marking, array $context = array() ): void {
		unset( $context );
		$places          = array_keys( $marking->getPlaces() );
		$subject->status = isset( $places[0] ) ? (string) $places[0] : 'pending';
	}
}
