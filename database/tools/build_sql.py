"""Builds database/onetap_db.sql: the schema plus demo data.

Dates are written relative to NOW()/CURDATE(), so the data always looks recent
on the day you import it. Run: python3 build_sql.py
"""
import random
from pathlib import Path

random.seed(1406)
OUT = Path(__file__).resolve().parent.parent / "onetap_db.sql"
DEMO_HASH = (Path(__file__).parent / "demo_hash.txt").read_text().strip()  # password: 1tap2026


def q(v):
    if v is None:
        return "NULL"
    if isinstance(v, (int, float)):
        return str(v)
    if isinstance(v, Raw):
        return v.s
    return "'" + str(v).replace("\\", "\\\\").replace("'", "''") + "'"


class Raw:
    def __init__(self, s):
        self.s = s


def day_at(days_ago, hh, mm):
    return Raw(f"TIMESTAMP(CURDATE() - INTERVAL {days_ago} DAY, '{hh:02d}:{mm:02d}:00')")


def mins_ago(m):
    return Raw(f"(NOW() - INTERVAL {m} MINUTE)")


def plus(base, minutes):
    return Raw(f"({base.s} + INTERVAL {minutes} MINUTE)")


rows = {k: [] for k in ["roles", "users", "wallets", "reloads", "routes", "jeepneys", "trips",
                        "fare_transactions", "passenger_logs", "gps_pings", "login_sessions", "activity_logs"]}


def add(table, **kw):
    rows[table].append(kw)


# ---- roles and accounts -------------------------------------------------
for i, r in enumerate(["passenger", "driver", "admin"], 1):
    add("roles", role_id=i, role_name=r)

# The four accounts from erased_db, with their original password hashes.
existing = [
    (1, 1, "rh", "ew@gmail.com", "0920", "user", "$2y$10$EpZUJZ0HIUk5TVvFtfDS6OW4BuRgUBOCjrlCUASmyUCsHfp59WJRm", "2025-10-11 17:47:14"),
    (2, 2, "Jayram", "Garcia@gmail.com", "312", "driver", "$2y$10$A5TxqtHNnWKlL/JuwjSVEOTyoprnCiYD3jyKbJrV3LjJC6CVJu6q2", "2025-10-11 18:52:58"),
    (3, 3, "LEO CERLYN GARCIA TACSAGON", "alverrer691@gmail.com", "09087053123", "admin", "$2y$10$rF8hoLP0jok.4TnPNKw1xuosrCfRL9seGOhBbKpzmZnXDKqk0jCj2", "2025-10-12 16:11:22"),
    (4, 2, "CERLYN", "alvierrer691@gmail.com", "09087", "driver2", "$2y$10$HcfzzjJX8jIxClquj1nqa.ngMOtepdtUkMTRU.mpV4Xx5ZOhdosUm", "2025-10-12 16:15:58"),
]
for uid, rid, name, email, phone, uname, h, created in existing:
    add("users", user_id=uid, role_id=rid, full_name=name, email=email, phone=phone, username=uname,
        password_hash=h, status="active", created_at=created)

add("users", user_id=5, role_id=2, full_name="Ramon Santos", email="ramon.santos@1tap.ph", phone="09171234501",
    username="ramon", password_hash=DEMO_HASH, status="active", created_at=day_at(40, 9, 0))
add("users", user_id=6, role_id=1, full_name="Juan Dela Cruz", email="juan.dc@email.com", phone="09171234567",
    username="juandc", password_hash=DEMO_HASH, status="active", created_at=day_at(35, 10, 12))

names = ["Maria Santos", "Paolo Reyes", "Angelica Lim", "Mark Villanueva", "Tessa Gonzaga", "Carlo Mendoza",
         "Bea Ramos", "Joshua Tan", "Kristine Aquino", "Miguel Torres", "Rica Bautista", "Nathan Cruz",
         "Ella Navarro", "Jerome Dizon", "Patricia Lopez", "Ken Salazar", "Lara Fernandez", "Vince Soriano",
         "Andrea Castillo", "Rafael Gomez", "Shane Ocampo", "Monica Herrera", "Gab Panganiban", "Iris Tolentino",
         "Dominic Rivera", "Hannah Morales", "Luis Pascual", "Faith Galang"]
uid = 7
passenger_ids = [1, 6]
for n in names:
    uname = n.lower().replace(" ", ".")
    add("users", user_id=uid, role_id=1, full_name=n, email=f"{uname}@email.com",
        phone=f"0917{random.randint(1000000, 9999999)}", username=uname, password_hash=DEMO_HASH,
        status="active", created_at=day_at(random.randint(16, 60), random.randint(7, 20), random.randint(0, 59)))
    passenger_ids.append(uid)
    uid += 1

# ---- wallets ---------------------------------------------------------------
wallet_of = {}
for i, pid in enumerate(passenger_ids, 1):
    wallet_of[pid] = i
JUAN_W = wallet_of[6]
FROZEN_W = wallet_of[11]  # Tessa Gonzaga reported a lost phone

# ---- routes and units ---------------------------------------------------------
routes = [(1, "Libertad – SM Bacolod", "Libertad", "SM City Bacolod", 15),
          (2, "BATA – NGC", "BATA", "New Government Center", 15),
          (3, "Mandalagan – Lopue's East", "Mandalagan", "Lopue's East", 15),
          (4, "Downtown – BATA", "Downtown", "BATA", 13)]
fare_of = {}
for rid, name, o, d, fare in routes:
    add("routes", route_id=rid, route_name=name, origin=o, destination=d, base_fare=fare)
    fare_of[rid] = fare

units = [(1, "BCN 105", "FAB 1051", "active"), (2, "BCN 106", "FAB 1062", "active"),
         (3, "BCN 107", "FAB 1073", "breakdown"), (4, "BCN 108", "FAB 1084", "offline"),
         (5, "BCN 109", "FAB 1095", "active")]
for unit_id, code, plate, status in units:
    add("jeepneys", unit_id=unit_id, operator_id=3, unit_code=code, plate_no=plate, capacity=25, status=status)

# ---- trips, fares, counts -------------------------------------------------------
spent = {w: 0 for w in wallet_of.values()}
trip_id = 1
txn_id = 1
count_id = 1
log_rows = []  # (when, user_id, action, details)

# (driver, unit, route, afternoon unit/route or None)
schedule = [(5, 1, 1, (3, 3)), (4, 2, 2, None), (2, 5, 4, (4, 4))]
departures = [(7, 0), (9, 30), (12, 15), (15, 0)]
juan_trips_left = 14


def add_trip(driver, unit, route, start, end, peak_target, blocked, juan=False, active=False, final=None):
    global trip_id, txn_id, count_id
    t = trip_id
    trip_id += 1
    add("trips", trip_id=t, unit_id=unit, route_id=route, driver_id=driver, started_at=start, ended_at=end)
    fare = fare_of[route]
    pool = [w for w in wallet_of.values() if w not in (FROZEN_W, JUAN_W)]
    paid_n = max(1, peak_target - random.randint(0, 3))
    payers = random.sample(pool, min(paid_n, len(pool)))
    if juan:
        payers[0] = JUAN_W
    for k, w in enumerate(payers):
        when = plus(start, 2 + k * 2)
        add("fare_transactions", txn_id=txn_id, wallet_id=w, trip_id=t, amount=fare, status="paid", scanned_at=when)
        spent[w] += fare
        txn_id += 1
    others = [w for w in pool if w not in payers]
    for k in range(blocked):
        when = plus(start, 2 + len(payers) * 2 + k)
        add("fare_transactions", txn_id=txn_id, wallet_id=random.choice(others), trip_id=t, amount=fare,
            status="blocked_full", scanned_at=when)
        txn_id += 1
    cash = peak_target - len(payers)
    boarded = len(payers) + max(0, cash)
    add("passenger_logs", count_id=count_id, trip_id=t, boarded=0, alighted=0, onboard=0, counted_at=start); count_id += 1
    if final is None:
        add("passenger_logs", count_id=count_id, trip_id=t, boarded=boarded, alighted=0, onboard=boarded,
            counted_at=plus(start, 2 + len(payers) * 2)); count_id += 1
        add("passenger_logs", count_id=count_id, trip_id=t, boarded=boarded, alighted=boarded, onboard=0,
            counted_at=end); count_id += 1
    else:
        b, a, o, peak_when, last_when = final
        add("passenger_logs", count_id=count_id, trip_id=t, boarded=25, alighted=0, onboard=25, counted_at=peak_when); count_id += 1
        add("passenger_logs", count_id=count_id, trip_id=t, boarded=b, alighted=a, onboard=o, counted_at=last_when); count_id += 1
    return t, len(payers), fare * len(payers)


for days_ago in range(14, 0, -1):
    for driver, unit, route, pm in schedule:
        if driver == 2 and days_ago % 6 == 0:
            continue  # a day off
        for k, (hh, mm) in enumerate(departures):
            u, r = (pm if (pm and k >= 2) else (unit, route))
            if u == 3 and days_ago < 2:
                u, r = unit, route  # BCN 107 broke down yesterday
            if u == 4 and days_ago < 3:
                u, r = unit, route  # BCN 108 has been offline for a few days
            mm2 = mm + random.randint(0, 14)
            start = day_at(days_ago, hh, mm2)
            dur = random.randint(55, 80)
            end = plus(start, dur)
            peak = random.choice([14, 16, 18, 19, 20, 21, 22, 23, 24, 25, 25])
            blocked = random.randint(1, 3) if peak == 25 else 0
            juan = juan_trips_left > 0 and days_ago <= 8 and driver == 5 and k in (0, 2)
            if juan:
                juan_trips_left -= 1
            t, n, cash = add_trip(driver, u, r, start, end, peak, blocked, juan=juan)
            if days_ago <= 2:
                log_rows.append((start, driver, "trip_start", f"Started trip #{t}"))
                log_rows.append((end, driver, "trip_end", f"Ended trip #{t} · {n} paid fares · ₱{cash}"))

# Today: earlier trips that already ended, then the two units out on the road now.
for driver, unit, route, back in [(5, 1, 1, 240), (5, 1, 1, 150), (4, 2, 2, 200), (2, 5, 4, 230), (2, 5, 4, 140)]:
    start = mins_ago(back)
    end = mins_ago(back - 70)
    t, n, cash = add_trip(driver, unit, route, start, end, random.choice([18, 21, 23, 25]), 0, juan=(back == 240))
    log_rows.append((start, driver, "trip_start", f"Started trip #{t}"))
    log_rows.append((end, driver, "trip_end", f"Ended trip #{t} · {n} paid fares · ₱{cash}"))

LIVE_105, _, _ = add_trip(5, 1, 1, mins_ago(48), None, 22, 3, active=True,
                          final=(26, 8, 18, mins_ago(22), mins_ago(2)))
LIVE_106, _, _ = add_trip(4, 2, 2, mins_ago(35), None, 21, 0, active=True,
                          final=(24, 2, 22, mins_ago(20), mins_ago(3)))
log_rows.append((mins_ago(48), 5, "trip_start", f"Started trip #{LIVE_105} on BCN 105"))
log_rows.append((mins_ago(35), 4, "trip_start", f"Started trip #{LIVE_106} on BCN 106"))
log_rows.append((mins_ago(23), 5, "fare_blocked", "BCN 105 full: 3 scans blocked"))
log_rows.append((mins_ago(130), 3, "unit_status", "BCN 107 set to breakdown"))

# ---- reloads so every balance is real ---------------------------------------------
reload_id = 1
methods = ["gcash", "gcash", "maya", "store", "kiosk"]
for pid, w in wallet_of.items():
    target = spent[w] + (250 if w == JUAN_W else random.choice([60, 120, 185, 240, 310, 455]))
    amounts = []
    while sum(amounts) < target:
        amounts.append(random.choice([100, 200, 200, 300, 500]))
    if w == JUAN_W:
        amounts[-1] -= sum(amounts) - target  # land Juan on exactly ₱250
        amounts = [a for a in amounts if a > 0]
    for k, a in enumerate(amounts):
        days = max(0, 15 - int(15 * k / max(1, len(amounts))))
        when = day_at(days, random.randint(6, 20), random.randint(0, 59)) if days else mins_ago(300 + k)
        m = random.choice(methods)
        add("reloads", reload_id=reload_id, wallet_id=w, amount=a, method=m, reloaded_at=when)
        reload_id += 1
        if days <= 3:
            log_rows.append((when, pid, "reload", f"Reloaded ₱{a} via {m}"))
    balance = sum(amounts) - spent[w]
    add("wallets", wallet_id=w, user_id=pid, qr_token="%032x" % random.getrandbits(128), balance=balance,
        status="frozen" if w == FROZEN_W else "active", updated_at=mins_ago(random.randint(5, 600)))

# ---- GPS -----------------------------------------------------------------------
ping = 1
paths = {
    1: [(10.6525, 122.9590), (10.6551, 122.9571), (10.6578, 122.9549), (10.6604, 122.9526), (10.6627, 122.9504)],
    2: [(10.7030, 122.9719), (10.6981, 122.9711), (10.6932, 122.9703), (10.6884, 122.9694), (10.6851, 122.9689)],
}
for unit, pts in paths.items():
    for k, (la, lo) in enumerate(pts):
        add("gps_pings", ping_id=ping, unit_id=unit, latitude=la, longitude=lo, recorded_at=mins_ago(20 - k * 4)); ping += 1
for unit, (la, lo), back in [(3, (10.6923, 122.9662), 135), (4, (10.6765, 122.9510), 1700), (5, (10.6703, 122.9497), 75)]:
    add("gps_pings", ping_id=ping, unit_id=unit, latitude=la, longitude=lo, recorded_at=mins_ago(back)); ping += 1

# ---- logins and activity ---------------------------------------------------------
sess = 1
for uid_, back in [(3, 90), (5, 52), (4, 40), (2, 245), (6, 15), (6, 1500), (3, 1600), (7, 400), (8, 640), (9, 820)]:
    add("login_sessions", session_id=sess, user_id=uid_, login_at=mins_ago(back),
        logout_at=None if back < 100 else mins_ago(back - 45)); sess += 1
    log_rows.append((mins_ago(back), uid_, "login", "Signed in"))
log_rows.append((mins_ago(610), 11, "qr_frozen", "Froze ride QR (lost phone)"))
log_rows.append((mins_ago(1440 * 3), 3, "user_added", "Added driver account ramon"))

# Order by the SQL expression is not possible here, so insert logs and let ORDER BY logged_at sort them.
log_id = 1
for when, u, action, details in log_rows:
    add("activity_logs", log_id=log_id, user_id=u, action=action, details=details, logged_at=when); log_id += 1

# ---- write the file ------------------------------------------------------------------
SCHEMA = """-- 1TAP database: QR fare payment and passenger monitoring for Bacolod e-jeepneys
-- Import this in phpMyAdmin (Import tab) or run: mysql -u root < onetap_db.sql
-- Demo accounts added for the presentation all use the password: 1tap2026
--   admin: your existing "admin" account   driver: ramon   passenger: juandc

SET NAMES utf8mb4;
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS onetap_db;
CREATE DATABASE onetap_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE onetap_db;

CREATE TABLE roles (
  role_id    TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_name  VARCHAR(20) NOT NULL,
  PRIMARY KEY (role_id),
  UNIQUE KEY uq_role_name (role_name)
) ENGINE=InnoDB;

CREATE TABLE users (
  user_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id        TINYINT UNSIGNED NOT NULL,
  full_name      VARCHAR(100) NOT NULL,
  email          VARCHAR(100) NOT NULL,
  phone          VARCHAR(20) NOT NULL,
  username       VARCHAR(50) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at     DATETIME NOT NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_username (username),
  KEY fk_users_role (role_id),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (role_id)
) ENGINE=InnoDB;

CREATE TABLE wallets (
  wallet_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  qr_token    CHAR(32) NOT NULL,
  balance     DECIMAL(10,2) NOT NULL DEFAULT 0,
  status      ENUM('active','frozen') NOT NULL DEFAULT 'active',
  updated_at  DATETIME NOT NULL,
  PRIMARY KEY (wallet_id),
  UNIQUE KEY uq_wallet_user (user_id),
  CONSTRAINT fk_wallets_user FOREIGN KEY (user_id) REFERENCES users (user_id)
) ENGINE=InnoDB;

CREATE TABLE reloads (
  reload_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  wallet_id    INT UNSIGNED NOT NULL,
  amount       DECIMAL(10,2) NOT NULL,
  method       ENUM('gcash','maya','store','kiosk') NOT NULL,
  reloaded_at  DATETIME NOT NULL,
  PRIMARY KEY (reload_id),
  KEY idx_reloads_wallet (wallet_id, reloaded_at),
  CONSTRAINT fk_reloads_wallet FOREIGN KEY (wallet_id) REFERENCES wallets (wallet_id)
) ENGINE=InnoDB;

CREATE TABLE routes (
  route_id     SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  route_name   VARCHAR(100) NOT NULL,
  origin       VARCHAR(60) NOT NULL,
  destination  VARCHAR(60) NOT NULL,
  base_fare    DECIMAL(6,2) NOT NULL,
  PRIMARY KEY (route_id)
) ENGINE=InnoDB;

CREATE TABLE jeepneys (
  unit_id      SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  operator_id  INT UNSIGNED NOT NULL,
  unit_code    VARCHAR(20) NOT NULL,
  plate_no     VARCHAR(20) NOT NULL,
  capacity     TINYINT UNSIGNED NOT NULL DEFAULT 25,
  status       ENUM('active','breakdown','offline') NOT NULL DEFAULT 'active',
  PRIMARY KEY (unit_id),
  UNIQUE KEY uq_unit_code (unit_code),
  CONSTRAINT fk_jeepneys_operator FOREIGN KEY (operator_id) REFERENCES users (user_id)
) ENGINE=InnoDB;

CREATE TABLE trips (
  trip_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  unit_id     SMALLINT UNSIGNED NOT NULL,
  route_id    SMALLINT UNSIGNED NOT NULL,
  driver_id   INT UNSIGNED NOT NULL,
  started_at  DATETIME NOT NULL,
  ended_at    DATETIME NULL,
  PRIMARY KEY (trip_id),
  KEY idx_trips_driver (driver_id, started_at),
  KEY idx_trips_unit (unit_id, ended_at),
  CONSTRAINT fk_trips_unit FOREIGN KEY (unit_id) REFERENCES jeepneys (unit_id),
  CONSTRAINT fk_trips_route FOREIGN KEY (route_id) REFERENCES routes (route_id),
  CONSTRAINT fk_trips_driver FOREIGN KEY (driver_id) REFERENCES users (user_id)
) ENGINE=InnoDB;

-- One row per QR scan. A scan refused at full capacity is kept with its status.
CREATE TABLE fare_transactions (
  txn_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  wallet_id   INT UNSIGNED NOT NULL,
  trip_id     INT UNSIGNED NOT NULL,
  amount      DECIMAL(6,2) NOT NULL,
  status      ENUM('paid','blocked_full','blocked_balance','blocked_frozen') NOT NULL,
  scanned_at  DATETIME NOT NULL,
  PRIMARY KEY (txn_id),
  KEY idx_fares_wallet (wallet_id, scanned_at),
  KEY idx_fares_trip (trip_id),
  KEY idx_fares_time (scanned_at),
  CONSTRAINT fk_fares_wallet FOREIGN KEY (wallet_id) REFERENCES wallets (wallet_id),
  CONSTRAINT fk_fares_trip FOREIGN KEY (trip_id) REFERENCES trips (trip_id)
) ENGINE=InnoDB;

-- Camera counts: running totals for the trip.
CREATE TABLE passenger_logs (
  count_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  trip_id     INT UNSIGNED NOT NULL,
  boarded     SMALLINT UNSIGNED NOT NULL,
  alighted    SMALLINT UNSIGNED NOT NULL,
  onboard     SMALLINT UNSIGNED NOT NULL,
  counted_at  DATETIME NOT NULL,
  PRIMARY KEY (count_id),
  KEY idx_logs_trip (trip_id, count_id),
  CONSTRAINT fk_plogs_trip FOREIGN KEY (trip_id) REFERENCES trips (trip_id)
) ENGINE=InnoDB;

CREATE TABLE gps_pings (
  ping_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  unit_id      SMALLINT UNSIGNED NOT NULL,
  latitude     DECIMAL(9,6) NOT NULL,
  longitude    DECIMAL(9,6) NOT NULL,
  recorded_at  DATETIME NOT NULL,
  PRIMARY KEY (ping_id),
  KEY idx_gps_unit (unit_id, ping_id),
  CONSTRAINT fk_gps_unit FOREIGN KEY (unit_id) REFERENCES jeepneys (unit_id)
) ENGINE=InnoDB;

CREATE TABLE login_sessions (
  session_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  login_at    DATETIME NOT NULL,
  logout_at   DATETIME NULL,
  PRIMARY KEY (session_id),
  KEY idx_sessions_user (user_id, login_at),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users (user_id)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  log_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(40) NOT NULL,
  details    VARCHAR(255) NOT NULL DEFAULT '',
  logged_at  DATETIME NOT NULL,
  PRIMARY KEY (log_id),
  KEY idx_activity_action (action, log_id),
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
"""

order = ["roles", "users", "wallets", "reloads", "routes", "jeepneys", "trips", "fare_transactions",
         "passenger_logs", "gps_pings", "login_sessions", "activity_logs"]
parts = [SCHEMA, "\n-- ---------------------------------------------------------------- demo data\n"]
for t in order:
    data = rows[t]
    if t == "activity_logs":
        # Re-number so log_id follows time order: the logs page sorts newest first by id.
        parts.append(f"\n-- {t}\n")
        parts.append("CREATE TEMPORARY TABLE tmp_logs LIKE activity_logs;\n")
        cols = list(data[0].keys())
        for i in range(0, len(data), 200):
            chunk = data[i:i + 200]
            parts.append(f"INSERT INTO tmp_logs ({', '.join(cols)}) VALUES\n" +
                         ",\n".join("(" + ", ".join(q(r[c]) for c in cols) + ")" for r in chunk) + ";\n")
        parts.append("INSERT INTO activity_logs (user_id, action, details, logged_at)\n"
                     "  SELECT user_id, action, details, logged_at FROM tmp_logs ORDER BY logged_at, log_id;\n"
                     "DROP TEMPORARY TABLE tmp_logs;\n")
        continue
    cols = list(data[0].keys())
    parts.append(f"\n-- {t} ({len(data)} rows)\n")
    for i in range(0, len(data), 400):
        chunk = data[i:i + 400]
        parts.append(f"INSERT INTO {t} ({', '.join(cols)}) VALUES\n" +
                     ",\n".join("(" + ", ".join(q(r[c]) for c in cols) + ")" for r in chunk) + ";\n")

parts.append("\nSET FOREIGN_KEY_CHECKS = 1;\n")
OUT.write_text("".join(parts), encoding="utf-8")
print(OUT, {t: len(v) for t, v in rows.items()}, "juan spent", spent[JUAN_W])
