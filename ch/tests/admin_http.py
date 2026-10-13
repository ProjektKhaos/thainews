"""Isolated staff administration acceptance tests. Never uses production storage."""
import hashlib
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


def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def request(opener, url, values=None):
    data = urllib.parse.urlencode(values).encode() if values is not None else None
    try:
        with opener.open(urllib.request.Request(url, data=data), timeout=15) as response:
            return response.status, response.read().decode(errors='replace')
    except urllib.error.HTTPError as error:
        return error.code, error.read().decode(errors='replace')


def csrf(html):
    return re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)


with tempfile.TemporaryDirectory(prefix='cosmos-management-') as directory:
    root = pathlib.Path(directory)
    db = root / 'booking.sqlite'
    (root / 'media').mkdir()
    (root / 'backups').mkdir()
    env = {**os.environ, 'COSMOS_DB_PATH': str(db), 'COSMOS_DOCUMENT_ROOT': str(PROJECT), 'COSMOS_BACKUP_DIR': str(root / 'backups'), 'COSMOS_BOOKING_PUBLIC_ENABLED': '0'}
    for _ in range(2):
        subprocess.run(['php', str(PROJECT / 'scripts/migrate.php')], env=env, check=True, capture_output=True)
    setup_token = 'a' * 64
    with sqlite3.connect(db) as connection:
        assert connection.execute('SELECT COUNT(*) FROM schema_migrations').fetchone()[0] == 2
        connection.execute("INSERT INTO admin_setup(token_hash,expires_at) VALUES(?,datetime('now','+1 hour'))", (hashlib.sha256(setup_token.encode()).hexdigest(),))
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    base = f'http://127.0.0.1:{port}/'
    server = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', '-t', str(PROJECT)], env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        owner = client()
        for _ in range(50):
            try:
                status, html = request(owner, base + 'admin.php')
                break
            except urllib.error.URLError:
                time.sleep(0.05)
        assert status == 200 and 'Staff sign in' in html
        status, html = request(owner, base + 'admin.php?setup=' + setup_token)
        assert 'Create your administrator account' in html
        status, html = request(owner, base + 'admin.php', {'action': 'setup', 'csrf': csrf(html), 'username': 'testadmin', 'password': 'temporary-test-password', 'password_confirm': 'temporary-test-password'})
        assert status == 200 and 'Overview' in html and 'Your administrator account is ready' in html
        token = csrf(html)
        status, html = request(client(), base + 'admin.php?setup=' + setup_token)
        assert 'already been used' in html and 'Create your administrator account' not in html

        def action(values, expected=200):
            status, result = request(owner, base + 'admin.php', {'csrf': token, **values})
            assert status == expected, (values.get('action'), status, result[-1500:])
            return result

        assert 'session token is invalid' in action({'csrf': 'wrong', 'action': 'setting', 'key': 'base_rate_satang', 'value': '1'}, 403)
        settings = {'action': 'settings', 'max_guests': '4', 'included_guests': '2', 'min_nights': '1', 'max_nights': '90', 'contact_name': 'Test Owner', 'contact_email': 'owner@example.invalid', 'policy_version': 'test-1', 'privacy_text_en': 'Fixture policy', 'backup_retention_days': '30'}
        assert 'Changes saved' in action(settings)
        action({'action': 'monthly_rate', 'amount_thb': '44000', 'reference_days': '30'})
        rate_html = request(owner, base + 'admin.php?view=rates')[1]
        assert '44,000.00 THB / 30 nights' in rate_html and '1,466.67 THB' in rate_html
        status, invalid_rate = request(owner, base + 'admin.php?view=rates', {'csrf': token, 'action': 'monthly_rate', 'amount_thb': 'not-an-amount', 'reference_days': '30'})
        assert status == 422
        rate_forms = re.findall(r'<form\b[^>]*>.*?</form>', invalid_rate, re.S)
        monthly_form = next(form for form in rate_forms if 'name="action" value="monthly_rate"' in form)
        nightly_form = next(form for form in rate_forms if 'name="action" value="base_rate"' in form)
        assert 'value="not-an-amount"' in monthly_form and 'not-an-amount' not in nightly_form
        action({'action': 'base_rate', 'amount_thb': '3000.50'})
        action({'action': 'discount', 'label': 'Seven nights', 'family': 'length', 'min_nights': '7', 'kind': 'percent', 'display_value': '10', 'priority': '10'})
        action({'action': 'fee', 'label': 'Cleaning', 'kind': 'fixed', 'amount_thb': '500'})
        with sqlite3.connect(db) as connection:
            assert connection.execute("SELECT value FROM settings WHERE key='base_rate_satang'").fetchone()[0] == '300050'
        start = (datetime.now(ZoneInfo('Asia/Bangkok')) + timedelta(days=30)).date()
        end = start + timedelta(days=2)
        booking = {'action': 'manual_booking', 'name': '=FORMULA Guest', 'email': 'guest@example.invalid', 'phone': '123', 'check_in': str(start), 'check_out': str(end), 'guests': '2', 'status': 'confirmed', 'amount_thb': '7000.25', 'price_reason': 'Agreed by telephone'}
        invalid_booking = {**booking, 'email': 'not-valid', 'name': '<script>Draft guest</script>', 'csrf': token}
        status, draft_booking = request(owner, base + 'admin.php?view=bookings&new=1', invalid_booking)
        assert status == 422 and 'Register a manual booking' in draft_booking
        assert 'value="&lt;script&gt;Draft guest&lt;/script&gt;"' in draft_booking and 'value="7000.25"' in draft_booking
        assert '<script>Draft guest</script>' not in draft_booking
        assert 'Booking CH-' in action(booking)
        with sqlite3.connect(db) as connection:
            row = connection.execute('SELECT id,reference,status,total_satang FROM bookings').fetchone()
            assert row[2:] == ('confirmed', 700025)
        assert 'unavailable' in action(booking, 409)
        action({'action': 'block', 'check_in': str(end), 'check_out': str(end + timedelta(days=2)), 'reason': 'Owner stay'})
        action({'action': 'block', 'check_in': str(start), 'check_out': str(end), 'reason': 'Conflict'}, 409)
        action({'action': 'note', 'id': str(row[0]), 'note': '<script>alert(1)</script>'})
        detail = request(owner, base + 'admin.php?view=bookings&id=' + str(row[0]))[1]
        assert '&lt;script&gt;' in detail and '<script>alert(1)</script>' not in detail
        status, invalid_contact = request(owner, base + 'admin.php?view=bookings&id=' + str(row[0]), {'csrf': token, 'action': 'guest_details', 'id': str(row[0]), 'return_id': str(row[0]), 'name': 'Unsaved guest change', 'email': 'invalid', 'phone': '555'})
        assert status == 422 and row[1] in invalid_contact
        assert 'value="Unsaved guest change"' in invalid_contact and 'value="555"' in invalid_contact
        with sqlite3.connect(db) as connection:
            assert connection.execute('SELECT name FROM bookings WHERE id=?', (row[0],)).fetchone()[0] == '=FORMULA Guest'
        action({'action': 'guest_details', 'id': str(row[0]), 'name': '=FORMULA Guest', 'email': 'corrected@example.invalid', 'phone': '456'})
        action({'action': 'reschedule', 'id': str(row[0]), 'check_in': str(start), 'check_out': str(end), 'guests': '2', 'amount_thb': '7000.25', 'price_reason': 'Retain agreed amount'})
        action({'action': 'reschedule', 'id': str(row[0]), 'check_in': str(end), 'check_out': str(end + timedelta(days=1)), 'guests': '2', 'amount_thb': '7000.25', 'price_reason': 'Conflicting owner dates'}, 409)
        action({'action': 'payment', 'id': str(row[0]), 'status': 'paid_manual'})
        action({'action': 'transition', 'id': str(row[0]), 'status': 'cancelled'})
        action({'action': 'manual_booking', 'name': 'Calculated Guest', 'email': '', 'check_in': str(start + timedelta(days=10)), 'check_out': str(start + timedelta(days=17)), 'guests': '2', 'status': 'inquiry'})
        with sqlite3.connect(db) as connection:
            assert connection.execute('SELECT total_satang FROM bookings ORDER BY id DESC LIMIT 1').fetchone()[0] == 1940315
        csv = request(owner, base + 'admin.php?view=bookings&export=1')[1]
        assert "'=FORMULA Guest" in csv
        assert '7,000.25' in request(owner, base + 'admin.php?view=bookings&q=' + row[1])[1]

        status, invalid_content = request(owner, base + 'admin.php?view=content&scope=page-house', {'csrf': token, 'action': 'content', 'scope': 'page-house', 'content[copy-02]': '<script>Unsaved page text</script>', 'content[unknown]': 'reject'})
        assert status == 422
        assert '&lt;script&gt;Unsaved page text&lt;/script&gt;' in invalid_content and 'name="scope" value="page-house"' in invalid_content
        assert '<script>Unsaved page text</script>' not in invalid_content
        assert request(owner, base + 'admin.php?view=guide')[0] == 200
        assert 'Getting started' in request(owner, base + 'admin.php')[1] and 'Daily system checks' in request(owner, base + 'admin.php')[1]
        assert 'Getting started' not in request(client(), base + 'admin.php?view=guide')[1]
        before = request(client(), base + 'public.php')[1]
        action({'action': 'content', 'scope': 'landing', 'content[intro]': 'Owner draft <script>test</script> $1', 'publish': '0'})
        assert request(client(), base + 'public.php')[1] == before
        draft = request(owner, base + 'public.php?preview=1')[1]
        assert 'Owner draft &lt;script&gt;test&lt;/script&gt; $1' in draft
        assert request(client(), base + 'public.php?preview=1')[0] == 403
        action({'action': 'content', 'scope': 'landing', 'content[intro]': 'Published welcome', 'content[seo_title]': 'Welcome $1', 'content[caption_0]': 'Terrace updated', 'content[image_0]': 'photo-10', 'content[hero_slot]': '0', 'content[hero_radius]': '24', 'publish': '1'})
        published = request(client(), base + 'public.php')[1]
        assert 'Published welcome' in published and '<title>Welcome $1</title>' in published
        assert 'border-radius: 24px' in published and 'assets/photos/photo-10.webp' in published
        action({'action': 'content', 'scope': 'page-house', 'content[copy-02]': 'Updated house heading', 'publish': '1'})
        assert 'Updated house heading' in request(owner, base + 'en/house/')[1]

        # Upload via real HTTP multipart; server decodes and re-encodes the raster.
        boundary = 'cosmos-fixture-boundary'
        payload = (f'--{boundary}\r\nContent-Disposition: form-data; name="csrf"\r\n\r\n{token}\r\n--{boundary}\r\nContent-Disposition: form-data; name="action"\r\n\r\nupload\r\n--{boundary}\r\nContent-Disposition: form-data; name="return_view"\r\n\r\nmedia\r\n--{boundary}\r\nContent-Disposition: form-data; name="label"\r\n\r\nFixture photo\r\n--{boundary}\r\nContent-Disposition: form-data; name="photo"; filename="photo.webp"\r\nContent-Type: image/webp\r\n\r\n').encode() + (PROJECT / 'photos/photo-01.webp').read_bytes() + f'\r\n--{boundary}--\r\n'.encode()
        with owner.open(urllib.request.Request(base + 'admin.php', data=payload, headers={'Content-Type': f'multipart/form-data; boundary={boundary}'}), timeout=15) as response:
            assert b'Photo uploaded' in response.read()
        assert request(client(), base + 'media.php?id=1')[0] == 404
        assert request(owner, base + 'media.php?id=1')[0] == 200
        action({'action': 'content', 'scope': 'landing', 'content[image_0]': 'upload-1', 'publish': '1'})
        assert 'media.php?id=1' in request(client(), base + 'public.php')[1]
        assert request(client(), base + 'media.php?id=1')[0] == 200
        action({'action': 'gallery_photo', 'photo_key': 'upload-1', 'label': 'Uploaded terrace', 'category': 'terrace', 'position': '1', 'visible': '1'})
        gallery = request(owner, base + 'en/gallery/')[1]
        assert 'Uploaded terrace' in gallery and 'media.php?id=1' in gallery
        action({'action': 'gallery_photo', 'photo_key': 'photo-01', 'label': 'Private terrace', 'category': 'terrace', 'position': '2'})
        gallery = request(owner, base + 'en/gallery/')[1]
        assert 'Private terrace' not in gallery
        action({'action': 'backup'})
        archives = list((root / 'backups').glob('*.tar'))
        assert len(archives) == 1
        check = subprocess.run(['php', str(PROJECT / 'scripts/restore_backend.php'), str(archives[0]), '--check-only'], env=env, capture_output=True, text=True)
        assert check.returncode == 0, check.stderr
        assert 'PASS' in check.stdout

        action({'action': 'user_save', 'username': 'stafftest', 'password_new': 'temporary-staff-password', 'role': 'staff', 'active': '1'})
        staff = client()
        login = request(staff, base + 'admin.php')[1]
        status, login = request(staff, base + 'admin.php', {'action': 'login', 'username': 'stafftest', 'password': 'temporary-staff-password', 'csrf': csrf(login)})
        assert status == 200
        stafftoken = csrf(login)
        status, html = request(staff, base + 'admin.php', {'action': 'user_save', 'username': 'hack', 'password_new': 'temporary-hacker-password', 'role': 'admin', 'active': '1', 'csrf': stafftoken})
        assert status == 403
        assert request(staff, base + 'admin.php?view=backup')[0] == 403
        action({'action': 'user_save', 'id': '2', 'username': 'stafftest', 'role': 'staff'})
        assert 'Staff sign in' in request(staff, base + 'admin.php')[1]
        action({'action': 'user_save', 'id': '1', 'username': 'testadmin', 'role': 'staff', 'active': '1'}, 422)
        action({'action': 'booking_gate', 'enabled': '1'}, 409)
        assert request(owner, base + 'booking_api.php?action=inquire', {})[0] == 503
        assert 'Public bookings closed' in request(owner, base + 'admin.php')[1]

        # A fresh cookie cannot bypass the IP-based login throttle.
        with sqlite3.connect(db) as connection:
            connection.execute('DELETE FROM rate_limits')
        for _ in range(11):
            stranger = client()
            login = request(stranger, base + 'admin.php')[1]
            status, html = request(stranger, base + 'admin.php', {'action': 'login', 'username': 'unknown', 'password': 'incorrect', 'csrf': csrf(login)})
        assert status == 429 and 'Too many attempts' in html
        with sqlite3.connect(db) as connection:
            connection.execute('DELETE FROM rate_limits')
        browser = subprocess.run(['node', str(PROJECT / 'tests/browser_admin.cjs')], env={**env, 'COSMOS_TEST_BASE': base}, capture_output=True, text=True)
        assert browser.returncode == 0, browser.stdout + browser.stderr
        print(browser.stdout.strip())
        action({'action': 'password', 'current_password': 'temporary-test-password', 'new_password': 'replacement-test-password'})
        maintenance = root / '.maintenance'
        maintenance.write_text('Existing maintenance must be preserved')
        blocked = subprocess.run(['php', str(PROJECT / 'scripts/restore_backend.php'), str(archives[0]), '--restore'], env={**env, 'COSMOS_ADMIN_MAINTENANCE': '1'}, capture_output=True, text=True)
        assert blocked.returncode == 1 and maintenance.read_text() == 'Existing maintenance must be preserved'
        maintenance.unlink()
        recovered = subprocess.run(['php', str(PROJECT / 'scripts/restore_backend.php'), str(archives[0]), '--restore'], env={**env, 'COSMOS_ADMIN_MAINTENANCE': '1'}, capture_output=True, text=True)
        assert recovered.returncode == 0, recovered.stderr
        assert 'PASS restored' in recovered.stdout
        assert 'Published welcome' in request(client(), base + 'public.php')[1]
        assert request(client(), base + 'media.php?id=1')[0] == 200
        print('PASS management: setup, CSRF, bookings/conflicts, prices, CMS drafts/publish, image upload, backup recovery, CSV, roles, revocation, login throttle, closed public gate')
    finally:
        server.terminate()
        server.wait(timeout=5)
