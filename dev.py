"""Start the local WordPress site and activate this theme."""
from pathlib import Path
import secrets
import socket
import subprocess
import time

root = Path(__file__).resolve().parent
env_file = root / ".env"
if not env_file.exists():
    env_file.write_text(
        "DB_PASSWORD=" + secrets.token_urlsafe(24) + "\n"
        + "DB_ROOT_PASSWORD=" + secrets.token_urlsafe(24) + "\n"
        + "ADMIN_PASSWORD=" + secrets.token_urlsafe(18) + "\n"
        + "WP_PORT=8080\n"
        + "AERA_MAILCHIMP_API_KEY=\nAERA_MAILCHIMP_AUDIENCE_ID=\n",
        encoding="utf-8",
    )

settings = dict(line.split("=", 1) for line in env_file.read_text(encoding="utf-8").splitlines() if "=" in line)
password = settings["ADMIN_PASSWORD"]
port = settings.get("WP_PORT", "8080")
requested_port = port

running = subprocess.run(["docker", "compose", "ps", "--status", "running", "--services"], cwd=root, capture_output=True, text=True, check=True).stdout.splitlines()
if "wordpress" not in running:
    with socket.socket() as probe:
        try:
            probe.bind(("127.0.0.1", int(port)))
        except OSError:
            with socket.socket() as free_port:
                free_port.bind(("127.0.0.1", 0))
                port = str(free_port.getsockname()[1])
            if "WP_PORT=" in env_file.read_text(encoding="utf-8"):
                lines = [f"WP_PORT={port}" if line.startswith("WP_PORT=") else line for line in env_file.read_text(encoding="utf-8").splitlines()]
                env_file.write_text("\n".join(lines) + "\n", encoding="utf-8")
            else:
                with env_file.open("a", encoding="utf-8") as file:
                    file.write(f"WP_PORT={port}\n")
            print(f"Port {requested_port} is occupied; using {port} instead.", flush=True)
url = f"http://localhost:{port}"

def compose(*args, check=True):
    result = subprocess.run(["docker", "compose", *args], cwd=root)
    if check and result.returncode:
        raise SystemExit(f"Docker Compose failed with exit code {result.returncode}.")
    return result

compose("up", "-d")
for _ in range(30):
    if compose("exec", "-T", "wordpress", "test", "-f", "/var/www/html/wp-config.php", check=False).returncode == 0:
        break
    time.sleep(2)
else:
    raise SystemExit("WordPress did not initialize; run docker compose logs wordpress")

compose("exec", "-T", "--user", "www-data", "wordpress", "mkdir", "-p", "/var/www/html/wp-content/uploads")
wp = lambda *args, check=True: compose("run", "--rm", "cli", "wp", *args, check=check)
if wp("core", "is-installed", check=False).returncode != 0:
    wp("core", "install", f"--url={url}", "--title=Hydration Product Launch", "--admin_user=admin", f"--admin_password={password}", "--admin_email=admin@example.test", "--skip-email")
wp("theme", "activate", "hydration-launch")
if wp("plugin", "is-installed", "elementor", check=False).returncode != 0:
    wp("plugin", "install", "elementor", "--activate")
else:
    wp("plugin", "activate", "elementor")
wp("eval-file", "wp-content/themes/hydration-launch/install-elementor.php")
wp("option", "update", "blogname", "Hydration Product Launch")
wp("option", "update", "home", url)
wp("option", "update", "siteurl", url)
wp("rewrite", "structure", "/%postname%/")
print(f"Site: {url}")
print(f"Admin: {url}/wp-admin (user: admin; password: .env ADMIN_PASSWORD)")
