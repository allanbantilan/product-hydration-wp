"""Run Lighthouse against the local Docker site and save its mobile report."""
from pathlib import Path
import json
import os
import shutil
import subprocess
from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[1]
settings = dict(line.split("=", 1) for line in (root / ".env").read_text(encoding="utf-8").splitlines() if "=" in line)
url = f"http://localhost:{settings.get('WP_PORT', '8080')}"
report = root / "screenshots" / "lighthouse-mobile"
report.parent.mkdir(exist_ok=True)
with sync_playwright() as playwright:
    chrome = playwright.chromium.executable_path
env = os.environ.copy()
env["CHROME_PATH"] = chrome
subprocess.run([shutil.which("npx"), "--yes", "lighthouse", url, "--quiet", "--chrome-flags=--headless=new", "--output=json", "--output=html", f"--output-path={report}"], cwd=root, env=env, check=True)
data = json.loads((report.parent / (report.name + ".report.json")).read_text(encoding="utf-8"))
for key, value in data["categories"].items():
    print(f"{key}: {round(value['score'] * 100)}")
    if key in ("accessibility", "seo"):
        for ref in value["auditRefs"]:
            audit = data["audits"][ref["id"]]
            if ref["weight"] and audit.get("score") is not None and audit["score"] < 1:
                print(f"  {audit['title']}: {audit.get('displayValue', '')}")
