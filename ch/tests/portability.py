# Senast uppdaterad 2026-10-12 10:00 (Asia/Bangkok)
import http.cookiejar
import os
import pathlib
import re
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

PROJECT = pathlib.Path(__file__).resolve().parents[1]


def port():
    with socket.socket() as listener:
        listener.bind(("127.0.0.1", 0))
        return listener.getsockname()[1]


def check(document_root, prefix):
    number = port()
    base = f"http://127.0.0.1:{number}{prefix}"
    process = subprocess.Popen(
        ["php", "-S", f"127.0.0.1:{number}", "-t", str(document_root)],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
        env={**os.environ, "COSMOS_DB_PATH": "/does/not/exist.sqlite"},
    )
    try:
        opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        for _ in range(50):
            try:
                html = opener.open(base + "login.php", timeout=1).read().decode()
                break
            except urllib.error.URLError:
                time.sleep(0.05)
        else:
            raise AssertionError("server did not start")
        csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)
        body = urllib.parse.urlencode({"word": "huset", "csrf": csrf, "next": prefix + "sv/book/"}).encode()
        with opener.open(urllib.request.Request(base + "login.php", data=body), timeout=3) as response:
            assert response.status == 200 and response.url.endswith(prefix + "sv/book/")
            assert b"COSMOS_RUNTIME_BOOKING" not in response.read()
        for path in ("en/", "th/gallery/", "sv/book/", "assets/photos/photo-01.webp"):
            with opener.open(base + path, timeout=3) as response:
                assert response.status == 200, (prefix, path)
        try:
            opener.open(urllib.request.Request(base + "booking_api.php?action=inquire", data=b"{}", method="POST"), timeout=3)
            raise AssertionError("booking unexpectedly open")
        except urllib.error.HTTPError as error:
            assert error.code == 503
        print("PASS URL base", prefix)
    finally:
        process.terminate()
        process.wait(timeout=5)


check(PROJECT, "/")
with tempfile.TemporaryDirectory(prefix="cosmos-url-") as temporary:
    root = pathlib.Path(temporary)
    (root / "ch").symlink_to(PROJECT, target_is_directory=True)
    (root / "alpha").mkdir()
    (root / "alpha" / "beta").symlink_to(PROJECT, target_is_directory=True)
    check(root, "/ch/")
    check(root, "/alpha/beta/")
