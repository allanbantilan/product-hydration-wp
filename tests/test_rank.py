"""Check the actual referral rank query against tie and referral cases."""
from pathlib import Path
import re
import sqlite3
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[1]
ET.parse(root / "assets" / "bottle.svg")
source = (root / "template-parts" / "landing-waitlist.php").read_text(encoding="utf-8")
match = re.search(r'SELECT COUNT\(\*\) FROM \(SELECT w\.id.*?id < %d\)', source)
assert match, "rank query missing"
query = match.group().replace("$table", "waitlist").replace("%d", "?")
db = sqlite3.connect(":memory:")
db.execute("CREATE TABLE waitlist (id INTEGER PRIMARY KEY, referred_by INTEGER)")
db.executemany("INSERT INTO waitlist VALUES (?, ?)", [(1, None), (2, None), (3, None), (4, 2), (5, 2)])

def rank(member, referrals):
    return 1 + db.execute(query, (referrals, referrals, member)).fetchone()[0]

assert rank(2, 2) == 1
assert rank(1, 0) == 2
assert rank(3, 0) == 3
db.execute("INSERT INTO waitlist VALUES (6, 3)")
assert rank(3, 1) == 2
print("Referral ranking checks passed")
