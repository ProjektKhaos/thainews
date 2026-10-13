# Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
import http.cookiejar
import json
import os
import pathlib
import re
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timedelta
from zoneinfo import ZoneInfo

PROJECT = pathlib.Path(__file__).resolve().parents[1]
APPROVALS = ("house", "capacity", "stay", "prices", "discounts", "fees", "checkin", "rules", "contact", "privacy", "mail", "backup", "release")


with tempfile.TemporaryDirectory(prefix="cosmos-public-test-") as directory:
    db = pathlib.Path(directory) / "booking.sqlite"
    env = {**os.environ, "COSMOS_DB_PATH": str(db), "COSMOS_DOCUMENT_ROOT": str(PROJECT), "COSMOS_BOOKING_PUBLIC_ENABLED": "1", "COSMOS_MAIL_MODE": "smtp", "COSMOS_MAIL_APPROVED": "1", "COSMOS_SMTP_HOST": "mail.example.invalid", "COSMOS_SMTP_USER": "fixture", "COSMOS_SMTP_PASSWORD": "fixture", "COSMOS_ADMIN_EMAIL": "admin@example.invalid", "COSMOS_MAIL_FROM": "host@example.invalid"}
    subprocess.run(["php", str(PROJECT / "scripts/migrate.php")], env=env, check=True, capture_output=True)
    hash_value = subprocess.check_output(["php", "-r", "echo password_hash('temporary-test-password', PASSWORD_DEFAULT);"], text=True)
    with sqlite3.connect(db) as connection:
        connection.executemany("INSERT OR REPLACE INTO settings(key,value) VALUES(?,?)", [("base_rate_satang", "280000"), ("max_guests", "4"), ("booking_public_enabled", "1"), ("policy_version", "fixture-v1")] + [("approved_" + name, "1") for name in APPROVALS] + [("privacy_text_" + lang, "Fixture policy") for lang in ("en", "sv", "th")])
        connection.execute("INSERT INTO admin_users(username,password_hash) VALUES(?,?)", ("testadmin", hash_value))
    with socket.socket() as sock:
        sock.bind(("127.0.0.1", 0))
        port = sock.getsockname()[1]
    base = f"http://127.0.0.1:{port}/"
    server = subprocess.Popen(["php", "-S", f"127.0.0.1:{port}", "-t", str(PROJECT)], env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        for _ in range(50):
            try:
                html = opener.open(base + "login.php", timeout=1).read().decode()
                break
            except urllib.error.URLError:
                time.sleep(0.05)
        else:
            raise AssertionError("server unavailable")
        try:
            opener.open(base + "booking_api.php?action=status", timeout=3)
            raise AssertionError("API bypassed preview")
        except urllib.error.HTTPError as error:
            assert error.code == 401
        csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)
        opener.open(urllib.request.Request(base + "login.php", data=urllib.parse.urlencode({"word": "huset", "csrf": csrf, "next": "/sv/book/"}).encode()), timeout=3).read()
        html = opener.open(base + "sv/book/", timeout=3).read().decode()
        assert 'id="cosmosBookingForm"' in html
        config = json.loads(re.search(r"window\.COSMOS_BOOKING=(\{.*?\})</script>", html).group(1))
        start = (datetime.now(ZoneInfo("Asia/Bangkok")) + timedelta(days=30)).date().isoformat()
        end = (datetime.now(ZoneInfo("Asia/Bangkok")) + timedelta(days=32)).date().isoformat()
        headers = {"Content-Type": "application/json", "X-CSRF-Token": config["csrf"], "Origin": base.rstrip("/")}
        for altered, expected in (({"X-CSRF-Token": "wrong"}, 403), ({"Origin": "https://evil.example"}, 403), ({"Content-Type": "text/plain"}, 415)):
            request = urllib.request.Request(base + "booking_api.php?action=quote", data=json.dumps({"checkIn": start, "checkOut": end, "guests": 2}).encode(), headers={**headers, **altered}, method="POST")
            try:
                opener.open(request, timeout=3)
                raise AssertionError("invalid request accepted")
            except urllib.error.HTTPError as error:
                assert error.code == expected, (error.code, expected)

        def api(action, data):
            request = urllib.request.Request(base + "booking_api.php?action=" + action, data=json.dumps(data).encode(), headers=headers, method="POST")
            with opener.open(request, timeout=3) as response:
                return response.status, json.load(response)

        code, quote = api("quote", {"checkIn": start, "checkOut": end, "guests": 2})
        assert code == 201 and quote["price"]["totalSatang"] == 560000
        try:
            api("quote", {"checkIn": start, "checkOut": end, "guests": 0})
            raise AssertionError("invalid guest count accepted")
        except urllib.error.HTTPError as error:
            assert error.code == 422
        payload = {"quoteToken": quote["quoteToken"], "idempotencyKey": "a" * 32, "name": "Test Guest", "email": "test@example.invalid", "language": "sv", "privacy": True}
        code, created = api("inquire", payload)
        assert code == 201 and created["status"] == "inquiry"
        _, retry = api("inquire", payload)
        assert retry["reference"] == created["reference"]
        with sqlite3.connect(db) as connection:
            assert connection.execute("SELECT count(*) FROM bookings").fetchone()[0] == 1
            assert connection.execute("SELECT count(*) FROM notification_outbox").fetchone()[0] == 2
        availability = json.load(opener.open(base + "booking_api.php?action=availability&from=" + start + "&to=" + end, timeout=3))
        assert "Test Guest" not in json.dumps(availability) and availability["blocked"] == []
        admin_html = opener.open(base + "admin.php", timeout=3).read().decode()
        admin_csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', admin_html).group(1)
        admin_html = opener.open(urllib.request.Request(base + "admin.php", data=urllib.parse.urlencode({"username": "testadmin", "password": "temporary-test-password", "csrf": admin_csrf}).encode()), timeout=3).read().decode()
        admin_csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', admin_html).group(1)
        admin_html = opener.open(urllib.request.Request(base + "admin.php", data=urllib.parse.urlencode({"csrf": admin_csrf, "action": "setting", "key": "base_rate_satang", "value": "300000"}).encode()), timeout=3).read().decode()
        assert "Changes saved" in admin_html
        _, updated_quote = api("quote", {"checkIn": start, "checkOut": end, "guests": 2})
        assert updated_quote["price"]["totalSatang"] == 600000
        result = subprocess.run(["node", str(PROJECT / "tests/browser_open.cjs")], env={**env, "COSMOS_TEST_BASE": base}, capture_output=True, text=True)
        assert result.returncode == 0, result.stderr
        print("PASS public flow in isolated fixture: quote, inquiry, idempotency, outbox")
        print(result.stdout.strip())
    finally:
        server.terminate()
        server.wait(timeout=5)
