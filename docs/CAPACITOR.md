# Capacitor (iOS / Android shell)

This DDEV site is **WordPress in a browser**. There is no Capacitor app, no `ios/` or `android/` tree, and no `@capacitor/*` package in the pnpm workspace.

**Possible:** yes — [Capacitor](https://capacitorjs.com/) is a native **shell** around a web surface. It does not replace WordPress, DDEV, or the [theme/plugin zip](DEPLOYMENT.md). Desk and wp-admin stay a browser.

```
Guest phone
  ├─ prototype: WebView server.url → WordPress (same origin as the site)
  └─ later: bundled www/ → GET /wp-json/hotel-booking/v1/rooms  (CORS not in the plugin today)
WordPress still owns rooms, booking POST, inquiries, jobs
```

## What we have today

| Piece | Where |
| --- | --- |
| Public room catalog | `GET /wp-json/hotel-booking/v1/rooms` (also `/suggest`, `/{id}`) in [`inc/rest-api.php`](../wp-content/plugins/hotel-booking-core/inc/rest-api.php). `permission_callback` is `__return_true`. nginx FastCGI **bypasses** `/wp-json/` |
| Language on REST | `lang` query arg (`en`, `es`); see [I18N.md](I18N.md) |
| Booking | PHP form + nonce to `admin-post.php`, [`inc/inquiry-form.php`](../wp-content/plugins/hotel-booking-core/inc/inquiry-form.php). **Not** REST |
| Desk | Cookie + `hb_access_desk` on `/desk/` after `/staff-login/`. No Application Passwords, no inquiry REST |
| CORS | None in the plugin |

## Ladder

1. **Prototype: live site in a WebView.** Set Capacitor `server.url` to the production URL (or DDEV, with the TLS caveats below). The WebView origin is WordPress, so booking nonces, cookies, and Polylang `/` vs `/es/` keep working. Do **not** load `/desk/` or `/wp-admin/` in a guest app.
2. **Local device friction.** DDEV HTTPS uses mkcert; phones do not trust that CA. Simulators are easier than a physical device. Same-LAN hostname, `ddev share`, or a hosted URL are the usual workarounds. iOS App Transport Security and Android cleartext rules block untrusted HTTP/HTTPS unless you opt in. A public HTTPS host is the least painful target.
3. **Guest app that talks REST.** Bundle static `www/` (or Ionic) and `fetch` `GET /wp-json/hotel-booking/v1/rooms?lang=`. The WebView origin is then `capacitor://localhost` or `https://localhost`, so **CORS would be a later plugin change** (not in this repo). Inquiry POST is still not REST; a write route would be a separate feature.
4. **Native plugins** (status bar, push) only after a web surface exists. Push is out of scope here.

## Scaffold outside this repo

Do not add Capacitor to the pnpm workspace or CI. If you try it, use another directory (or a gitignored folder in a clone):

```bash
npm init @capacitor/app
npx cap add ios
npx cap add android
```

Point `server.url` at WordPress for the WebView prototype. Do not commit `ios/`, `android/`, or Capacitor `node_modules/` here.

## What we are not adding in this repo

- No `@capacitor/core` (or other Capacitor packages) in the pnpm workspace
- No `ios/` / `android/` / Capacitor `www/` in git
- No Capacitor in GitHub Actions, wp-env, or PHPUnit
- No CORS headers and no inquiry REST write route
- No Application Passwords for desk
- No push notifications
- The WordPress zip still ships **only** the theme and the plugin; see [DEPLOYMENT.md](DEPLOYMENT.md)
