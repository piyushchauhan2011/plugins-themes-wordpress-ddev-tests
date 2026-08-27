# Staff login, roles, and policies

WordPress is the identity store (users, cookies, `wp_signon`). There is no Keycloak, JWT, Casbin, or Application Passwords. Core already uses `/login/` for `wp-login.php`, so staff login is **`/staff-login/`**.

**RBAC** is a custom role plus plugin capabilities. **Policies** live in [`hotel_booking_authorize()`](../wp-content/plugins/hotel-booking-core/inc/auth.php): capability first, then resource attributes (transition name, inquiry status).

```
/staff-login/  → wp_signon → /desk/     (hotel_manager)
/staff-login/  → wp-admin               (administrator)
hotel_manager → /wp-admin/              redirect /desk/
admin-post.php / admin-ajax.php         still allowed (desk Save POST)
```

## What we have today

| Role | Login | Caps | Surfaces |
| --- | --- | --- | --- |
| Administrator | `admin` / `admin` | `manage_options` plus all `hb_*` | wp-admin and `/desk/` |
| `hotel_manager` | `desk` / `desk` | `read`, `hb_access_desk`, `hb_transition_inquiries` | `/staff-login/` → `/desk/` only |
| Subscriber | `guest` / `guest` | `read` | Public site |

Hotel managers cannot open Settings, delete inquiries, reopen closed rows, or close a still-**pending** inquiry (they must Contact first). Superadmins can still close from pending; that is the workflow graph plus `manage_options`.

Editors (`edit_posts`) are **not** desk staff.

## Code

- [`inc/auth.php`](../wp-content/plugins/hotel-booking-core/inc/auth.php) — `add_role( 'hotel_manager' )` on `init`, `[hotel_staff_login]`, `admin_init` redirect
- Desk and `admin-post` handlers call `hotel_booking_authorize()`; [`hotel_booking_apply_inquiry_transition()`](../wp-content/plugins/hotel-booking-core/inc/workflow.php) checks the same policy so a crafted POST cannot skip the UI
- wp-admin **Hotel Booking** menu is `manage_options` only

## What we are not adding

- No Application Passwords or inquiry REST auth
- No Casbin / policy engine package
- No second identity provider
