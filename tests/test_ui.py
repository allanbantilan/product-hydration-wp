"""Check key browser interactions and save local site screenshots."""
from pathlib import Path
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[1]
settings = dict(line.split("=", 1) for line in (root / ".env").read_text(encoding="utf-8").splitlines() if "=" in line)
url = f"http://localhost:{settings.get('WP_PORT', '8080')}"
shots = root / "screenshots"
shots.mkdir(exist_ok=True)

with sync_playwright() as playwright:
    browser = playwright.chromium.launch(headless=True)
    desktop = browser.new_page(viewport={"width": 1440, "height": 900}, device_scale_factor=1)
    errors = []
    desktop.on("pageerror", lambda error: errors.append(str(error)))
    desktop.goto(url, wait_until="domcontentloaded")
    desktop.screenshot(path=str(shots / "desktop.png"), full_page=True)
    assert desktop.title() == "Hydration Product Launch"
    assert desktop.evaluate("document.documentElement.scrollWidth <= innerWidth"), "desktop horizontal overflow"
    hero_copy = desktop.locator(".hero-copy").bounding_box()
    hero_button = desktop.locator(".hero .elementor-button").bounding_box()
    assert abs(hero_copy["x"] - hero_button["x"]) < 12, f"hero CTA is not left aligned: {hero_copy['x']}, {hero_button['x']}"
    desktop.get_by_role("button", name="Switch to dark mode").click()
    assert desktop.evaluate("document.documentElement.dataset.theme") == "dark"
    desktop.screenshot(path=str(shots / "dark.png"), full_page=True)
    desktop.get_by_role("button", name="Yearly · save 20%").click()
    assert desktop.locator("#price-value").inner_text() == "$9.60"
    desktop.get_by_text("When does early access open?").click()
    assert desktop.locator(".faq-list details[open]").count() == 1

    mobile = browser.new_page(viewport={"width": 390, "height": 844}, device_scale_factor=1)
    mobile.on("pageerror", lambda error: errors.append(str(error)))
    mobile.goto(url, wait_until="domcontentloaded")
    mobile.screenshot(path=str(shots / "mobile.png"), full_page=True)
    assert mobile.evaluate("document.documentElement.scrollWidth <= innerWidth"), mobile.evaluate("() => ({width: innerWidth, scroll: document.documentElement.scrollWidth, offenders: [...document.querySelectorAll('*')].filter(el => el.getBoundingClientRect().right > innerWidth + 1).slice(0, 8).map(el => [el.tagName, el.className, Math.round(el.getBoundingClientRect().right)])})")
    assert mobile.get_by_role("link", name="Join the waitlist").count() >= 1
    for width in (375, 768, 1024):
        mobile.set_viewport_size({"width": width, "height": 844})
        assert mobile.evaluate("document.documentElement.scrollWidth <= innerWidth"), f"horizontal overflow at {width}px"
    reduced = browser.new_page(reduced_motion="reduce")
    reduced.goto(url, wait_until="domcontentloaded")
    assert reduced.locator(".reveal").first.evaluate("el => getComputedStyle(el).opacity") == "1"
    assert not errors, f"browser errors: {errors}"
    browser.close()

print("Browser checks passed; screenshots saved in screenshots/")
