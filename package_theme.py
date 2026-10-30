"""Build the installable WordPress theme ZIP from the current source files."""
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile

root = Path(__file__).resolve().parent
files = ["style.css", "functions.php", "front-page.php", "index.php", "elementor-page.json", "install-elementor.php", "template-parts/landing-voices.php", "template-parts/landing-waitlist.php", "assets/site.js", "assets/bottle.svg", "assets/fonts/dm-sans-latin.woff2", "assets/fonts/manrope-latin.woff2", "assets/fonts/LICENSE-dmsans.txt", "assets/fonts/LICENSE-manrope.txt"]
with ZipFile(root / "hydration-launch.zip", "w", ZIP_DEFLATED) as archive:
    for file in files:
        archive.write(root / file, f"hydration-launch/{file}")
with ZipFile(root / "hydration-launch.zip") as archive:
    assert archive.testzip() is None
print("Updated hydration-launch.zip")
