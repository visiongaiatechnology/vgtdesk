import re

with open('assets/css/desktop.css', 'r', encoding='utf-8') as f:
    content = f.read()

# Find all selectors and their blocks containing "win10" or "vgt-start"
pattern = re.compile(r'([^{}]*\{[^{}]*\})')
matches = pattern.findall(content)

print("--- Matches for win10 or vgt-start ---")
for match in matches:
    if 'win10' in match or 'vgt-start' in match:
        print(match.strip())
        print("-" * 40)
