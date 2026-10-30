"""Confirm the local homepage is editable in Elementor."""
from pathlib import Path
import subprocess
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[1]
settings = dict(line.split("=", 1) for line in (root / ".env").read_text(encoding="utf-8").splitlines() if "=" in line)
base = f"http://localhost:{settings.get('WP_PORT', '8080')}"
page_id = subprocess.check_output(["docker", "compose", "run", "--rm", "cli", "wp", "option", "get", "page_on_front"], cwd=root, text=True).strip()
assert page_id.isdigit()

with sync_playwright() as playwright:
    browser = playwright.chromium.launch(headless=True)
    page = browser.new_page()
    page.goto(base + "/wp-login.php")
    page.locator("#user_login").fill("admin")
    page.locator("#user_pass").fill(settings["ADMIN_PASSWORD"])
    page.locator("#wp-submit").click()
    page.goto(base + f"/wp-admin/post.php?post={page_id}&action=elementor", wait_until="domcontentloaded")
    page.locator("#elementor-panel").wait_for(timeout=60000)
    assert page.locator("#elementor-preview-iframe").count() == 1, "Elementor preview missing"
    page.frame_locator("#elementor-preview-iframe").locator(".elementor-widget-html").first.wait_for(timeout=60000)
    assert page.frame_locator("#elementor-preview-iframe").locator(".elementor-widget-html").count() >= 7, "Editable section widgets missing"
    assert page.frame_locator("#elementor-preview-iframe").locator(".elementor-widget-shortcode").count() == 2, "Dynamic widgets missing"
    assert page.frame_locator("#elementor-preview-iframe").locator(".hero .elementor-widget-heading").count() == 2, "Native hero headings missing"
    assert page.frame_locator("#elementor-preview-iframe").locator(".hero .elementor-widget-button").count() == 1, "Native hero CTA missing"
    assert "elementor" in page.url, "Elementor editor did not load"
    browser.close()

print("Elementor editor opened with its preview iframe")
