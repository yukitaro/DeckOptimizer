import re
import csv

# Input and output paths
input_path = "archetypes_raw.txt"
output_path = "mtg_archetypes_seed.csv"

# Read and parse lines
with open(input_path, "r", encoding="utf-8") as f:
    lines = f.readlines()

pattern = r'<option value="(\d+)">([^<]+)</option>'
rows = []

for line in lines:
    match = re.search(pattern, line.strip())
    if match:
        archetype_id, name = match.groups()
        rows.append({
            "name": name.strip(),
            "format": "Pauper",  # default for now
            "description": "",
            "criteria": "",
            "archetype_source_site": "mtgdecks",
            "archetype_source_site_id": archetype_id
        })

# Write to CSV
with open(output_path, "w", newline="", encoding="utf-8") as f:
    writer = csv.DictWriter(f, fieldnames=[
        "name", "format", "description", "criteria",
        "archetype_source_site", "archetype_source_site_id"
    ])
    writer.writeheader()
    writer.writerows(rows)

print(f"✅ CSV written to {output_path} with {len(rows)} archetypes.")

