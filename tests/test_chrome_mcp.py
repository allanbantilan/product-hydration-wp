"""Exercise the homepage through an isolated Chrome DevTools MCP server."""
from pathlib import Path
from queue import Queue
from threading import Thread
import json
import re
import subprocess

root = Path(__file__).resolve().parents[1]
settings = dict(line.split("=", 1) for line in (root / ".env").read_text(encoding="utf-8").splitlines() if "=" in line)
base = f"http://localhost:{settings.get('WP_PORT', '8080')}"
process = subprocess.Popen(
    ["npx.cmd", "-y", "chrome-devtools-mcp@1.10.1", "--isolated", "--headless", "--no-usage-statistics"],
    stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.DEVNULL, text=True, encoding="utf-8", bufsize=1,
)
lines = Queue()
Thread(target=lambda: [lines.put(line) for line in process.stdout], daemon=True).start()
next_id = 0

def request(method, params):
    global next_id
    next_id += 1
    process.stdin.write(json.dumps({"jsonrpc": "2.0", "id": next_id, "method": method, "params": params}) + "\n")
    process.stdin.flush()
    while True:
        message = json.loads(lines.get(timeout=90))
        if message.get("id") == next_id:
            assert "error" not in message, message
            return message["result"]

def tool(name, **arguments):
    result = request("tools/call", {"name": name, "arguments": arguments})
    assert not result.get("isError"), result
    return "\n".join(item["text"] for item in result.get("content", []) if item["type"] == "text")

try:
    request("initialize", {"protocolVersion": "2025-03-26", "capabilities": {}, "clientInfo": {"name": "local-site-check", "version": "1"}})
    process.stdin.write(json.dumps({"jsonrpc": "2.0", "method": "notifications/initialized"}) + "\n")
    process.stdin.flush()
    page = tool("new_page", url=base)
    match = re.search(r"(\d+): .*http://localhost", page)
    assert match, page
    page_id = int(match.group(1))
    snapshot = tool("take_snapshot", pageId=page_id)
    assert "Feel good." in snapshot and "Join the early access list." in snapshot
    desktop = tool("evaluate_script", pageId=page_id, function="() => ({sections: document.querySelectorAll('main section').length, elementor: !!document.querySelector('.elementor'), overflow: document.documentElement.scrollWidth > innerWidth, price: document.querySelector('#price-value').textContent})")
    assert '"sections":8' in desktop and '"elementor":true' in desktop and '"overflow":false' in desktop, desktop
    interactions = tool("evaluate_script", pageId=page_id, function="() => { document.querySelector('.theme-toggle').click(); document.querySelector('[data-billing=yearly]').click(); document.querySelector('.faq-list summary').click(); return {theme: document.documentElement.dataset.theme, price: document.querySelector('#price-value').textContent, faq: document.querySelector('.faq-list details').open}; }")
    assert '"theme":"dark"' in interactions and '"price":"$9.60"' in interactions and '"faq":true' in interactions, interactions
    tool("emulate", pageId=page_id, viewport="390x844x1,mobile,touch")
    mobile = tool("evaluate_script", pageId=page_id, function="() => ({overflow: document.documentElement.scrollWidth > innerWidth, form: !!document.querySelector('form input[name=aera_nonce]'), countdown: document.querySelector('[data-days]').textContent})")
    assert '"overflow":false' in mobile and '"form":true' in mobile, mobile
    console = tool("list_console_messages", pageId=page_id, types=["error"])
    assert "<no console messages found>" in console, console
    print("Chrome MCP desktop/mobile, Elementor, theme, pricing, FAQ, form, and countdown checks passed")
finally:
    process.stdin.close()
    try:
        process.wait(timeout=10)
    except subprocess.TimeoutExpired:
        process.terminate()
        process.wait(timeout=10)
