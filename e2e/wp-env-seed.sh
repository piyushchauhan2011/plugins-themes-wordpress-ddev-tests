#!/usr/bin/env bash
## Seed Booking, Desk, Home, and one room inside wp-env (CI).
set -euo pipefail

wp() {
	npx wp-env run cli -- wp "$@"
}

wp plugin activate hotel-booking-core
wp theme activate hotel-booking
wp rewrite structure '/%postname%/' --hard
wp option update blogdescription "A quiet night, well kept"

create_page() {
	local title="$1"
	local slug="$2"
	local content="$3"
	local existing id
	existing="$(wp post list --post_type=page --name="${slug}" --field=ID --format=ids 2>/dev/null | tail -n 1 | tr -d '[:space:]')"
	if [[ -n "${existing}" && "${existing}" =~ ^[0-9]+$ ]]; then
		wp post update "${existing}" --post_title="${title}" --post_content="${content}" --post_status=publish >/dev/null
		echo "${existing}"
		return
	fi
	id="$(wp post create --post_type=page --post_title="${title}" --post_name="${slug}" --post_status=publish --post_content="${content}" --porcelain 2>/dev/null | tail -n 1 | tr -d '[:space:]')"
	echo "${id}"
}

HOME_ID="$(create_page "Home" "home" "")"
create_page "Booking" "booking" "<!-- wp:shortcode -->[hotel_inquiry_form]<!-- /wp:shortcode -->" >/dev/null
create_page "Desk" "desk" "<!-- wp:shortcode -->[hotel_inquiry_list]<!-- /wp:shortcode -->" >/dev/null
create_page "Stay" "stay" "<!-- wp:hotel-booking/rooms-grid {\"guests\":0} /-->" >/dev/null

wp option update show_on_front page
wp option update page_on_front "${HOME_ID}"

ROOM_ID="$(wp post list --post_type=hb_room --name=deluxe-king --field=ID --format=ids 2>/dev/null | tail -n 1 | tr -d '[:space:]')"
if [[ -z "${ROOM_ID}" || ! "${ROOM_ID}" =~ ^[0-9]+$ ]]; then
	ROOM_ID="$(wp post create --post_type=hb_room --post_title="Deluxe King" --post_name=deluxe-king --post_excerpt="A wide room with a king bed." --post_status=publish --porcelain 2>/dev/null | tail -n 1 | tr -d '[:space:]')"
fi
wp post meta update "${ROOM_ID}" hb_price 280 >/dev/null
wp post meta update "${ROOM_ID}" hb_guests 2 >/dev/null
wp post meta update "${ROOM_ID}" hb_beds 1 >/dev/null
wp post meta update "${ROOM_ID}" hb_size 32 >/dev/null

FAMILY_ID="$(wp post list --post_type=hb_room --name=family-room --field=ID --format=ids 2>/dev/null | tail -n 1 | tr -d '[:space:]')"
if [[ -z "${FAMILY_ID}" || ! "${FAMILY_ID}" =~ ^[0-9]+$ ]]; then
	FAMILY_ID="$(wp post create --post_type=hb_room --post_title="Family Room" --post_name=family-room --post_excerpt="Two rooms sharing a sitting area." --post_status=publish --porcelain 2>/dev/null | tail -n 1 | tr -d '[:space:]')"
fi
wp post meta update "${FAMILY_ID}" hb_price 420 >/dev/null
wp post meta update "${FAMILY_ID}" hb_guests 4 >/dev/null
wp post meta update "${FAMILY_ID}" hb_beds 3 >/dev/null
wp post meta update "${FAMILY_ID}" hb_size 56 >/dev/null

NAV_CONTENT=""
NAV_CONTENT+="<!-- wp:navigation-link {\"label\":\"Home\",\"url\":\"/\",\"kind\":\"custom\"} /-->"
NAV_CONTENT+="<!-- wp:navigation-link {\"label\":\"Rooms\",\"url\":\"/rooms/\",\"kind\":\"custom\"} /-->"
NAV_CONTENT+="<!-- wp:navigation-link {\"label\":\"Stay\",\"url\":\"/stay/\",\"kind\":\"custom\"} /-->"
NAV_CONTENT+="<!-- wp:navigation-link {\"label\":\"Book\",\"url\":\"/booking/\",\"kind\":\"custom\"} /-->"
NAV_CONTENT+="<!-- wp:navigation-link {\"label\":\"Desk\",\"url\":\"/desk/\",\"kind\":\"custom\"} /-->"

NAV_ID="$(wp post list --post_type=wp_navigation --name=primary --field=ID --format=ids 2>/dev/null | tail -n 1 | tr -d '[:space:]')"
if [[ -n "${NAV_ID}" && "${NAV_ID}" =~ ^[0-9]+$ ]]; then
	wp post update "${NAV_ID}" --post_content="${NAV_CONTENT}" --post_status=publish >/dev/null
else
	wp post create --post_type=wp_navigation --post_title="Primary" --post_name=primary --post_status=publish --post_content="${NAV_CONTENT}" >/dev/null
fi

wp rewrite flush --hard
echo "wp-env content ready."
