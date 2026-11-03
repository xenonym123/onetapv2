# 1TAP — QR fare payment and passenger monitoring

The 1TAP website, rebuilt in MVC. Plain PHP and MySQL, no framework, runs on XAMPP.

## Set it up on XAMPP

1. Copy the whole `1tap-mvc` folder into `C:\xampp\htdocs\`.
2. In the XAMPP Control Panel, start **Apache** and **MySQL**.
3. Open <http://localhost/phpmyadmin>, go to the **Import** tab, pick `1tap-mvc/database/onetap_db.sql`, and click **Import**.
   This creates a new database called `onetap_db`. Your old `erased_db` is left alone.
4. Open <http://localhost/1tap-mvc/>.

If your MySQL root user has a password, put it in `app/config/config.php`.

**Re-import the SQL file the day before you present.** The demo trips, fares and GPS points are dated relative to when you import, so "today" on the dashboards stays today.

## Accounts

| Role | Username | Password |
| --- | --- | --- |
| Admin (operator side) | `admin` | your existing admin password |
| Driver | `ramon` | `1tap2026` |
| Passenger | `juandc` | `1tap2026` |

Your old accounts (`user`, `driver`, `driver2`, `admin`) are kept with their original passwords. The 28 other passengers are sample riders, all with password `1tap2026`.

The login page has only **Passenger** and **Driver** tabs. Admin accounts sign in from either tab and still land on the admin dashboard, because the role comes from the account, not the tab.

## Demo the QR payment

Use two browser windows, or a phone and a laptop:

1. **Passenger:** sign in as `juandc` and open **My QR**. The code changes every 30 seconds.
2. **Driver:** sign in as `driver` (or `ramon`, who already has a trip running). On **Home**, start a trip, then open **QR Scanner**.
3. Click **Start camera** and hold the passenger's QR up to the webcam. Or copy the code text from the passenger's page and paste it into the box under the camera.
4. The fare is taken from the wallet and the passenger count goes up by one.

To show the full-capacity block, open **Passenger Count** and press **Someone got on** until it reads 25/25. The next scan is refused and logged as "Blocked · full". Press **Someone got off** and the scanner takes fares again.

Browsers only allow the camera on `localhost` or `https`. Run the **scanner** on the laptop at `http://localhost/...`, and use the phone only to **show** the QR.

## How the code is organised (MVC)

```
public/              the only folder the browser sees
  index.php          front controller: every request starts here
  .htaccess          sends /passenger/reload etc. to index.php
  assets/            css, js, images, and the bundled QR, scanner and map libraries
app/
  routes.php         every URL and the controller method that handles it
  config/            database settings, icons
  core/              Router, Controller, Model, View, Database, Auth, helpers
  controllers/       Home, Auth, Passenger, Driver, Admin
  models/            one class per table; the only place SQL is written
  views/             HTML templates; layouts/ and partials/ are shared
database/
  onetap_db.sql      the 12 tables plus demo data
  tools/build_sql.py rebuilds onetap_db.sql (optional, needs Python)
```

How one request flows, using a wallet reload as the example:

`POST /passenger/reload` → `routes.php` → `PassengerController::reload()` → `Reload::create()` and `Wallet::credit()` (models) → back to `views/passenger/reload.php`.

## Old pages and where they went

| Before | Now |
| --- | --- |
| `index.php`, `about.php` | `/`, `/about` |
| `1tap.php` (1TAP Card) | `/pay-with-qr` |
| `auth.php` + login modal | `/login`, `/register` (passengers only) |
| `userhome.php`, `userride.php`, `userreload.php` | `/passenger`, `/passenger/rides`, `/passenger/reload` |
| `userprofile.php`, `update_profile.php` | `/passenger/profile` |
| new | `/passenger/qr`, `/passenger/track` |
| `driverhome.php`, `drivercount.php`, `driverearning.php`, `driverroute.php` | `/driver`, `/driver/count`, `/driver/earnings`, `/driver/route` |
| new | `/scan` (the jeepney's QR scanner) |
| `adminhome.php`, `adminmanagement.php`, `admintracking.php`, `adminreport.php`, `adminlogs.php` | `/admin`, `/admin/users`, `/admin/tracking`, `/admin/reports`, `/admin/logs` |

## How the ride QR works

Each wallet has a secret `qr_token`. The QR contains `1TAP-<wallet>-<time window>-<signature>`, where the signature is an HMAC of the wallet and the current 30-second window. The scanner accepts the current window and the one before, so a code still works if it flips while being scanned. A screenshot stops working within a minute. **Freeze my QR** blocks the wallet, and unfreezing issues a new token.

A scan is refused, and saved with a reason, when the unit is full, the QR is frozen, or the balance is too low. Each passenger pays once per trip.

## Settings (`app/config/config.php`)

- `pretty_urls`: leave `true`. If links give "Not Found", set it to `false` and the site uses `index.php?url=...` instead.
- `debug`: set to `false` before presenting. This hides error details and the demo-accounts box on the login page.
- `qr_window`: seconds before a QR changes (30).
- `reload.min` / `reload.max`: reload limits (₱20 to ₱5,000).

## Notes

- Reloads are approved right away for the demo. A live version would hand off to GCash or Maya.
- The camera count and GPS run on simulators: the **Someone got on / got off** buttons, and the driver's phone location while a trip runs. The sample units' positions come from the demo data.
- The maps use OpenStreetMap, so they need internet. Everything else works offline.
- Needs PHP 8.0 or newer (XAMPP 8.0.x is fine) with `pdo_mysql` and `mbstring`, which XAMPP turns on by default.
