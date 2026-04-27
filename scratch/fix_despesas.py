import sys
import os

path = r'c:\xampp\htdocs\importadorV2\pages\despesas.php'
with open(path, 'r', encoding='utf-8', errors='ignore') as f:
    lines = f.readlines()

new_css = """<style>
    /* ===== Modern Design System ===== */
    :root {
        --card-radius: 24px;
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }
    .page-header-modern { margin: 1.5rem 0 2rem; display: flex; justify-content: space-between; align-items: center; }
    .page-title-modern h1 { font-size: 1.75rem; font-weight: 800; color: #1e293b; margin: 0; }
    .metrics-grid-modern { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem; }
    .metric-card-modern { background: #fff; border-radius: var(--card-radius); padding: 1.25rem; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 1rem; }
    .metric-icon-modern { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
    .m-blue { background: #eff6ff; color: #3b82f6; }
    .m-green { background: #f0fdf4; color: #22c55e; }
    .m-orange { background: #fff7ed; color: #f59e0b; }
    .m-red { background: #fef2f2; color: #ef4444; }
    .m-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; }
    .m-value { font-size: 1.15rem; font-weight: 800; color: #1e293b; }
</style>
"""

# Replace old style block (lines 9 to 928 approx)
# In the array, index 9 is line 10
filtered_lines = lines[:9] + [new_css] + lines[928:]

with open(path, 'w', encoding='utf-8') as f:
    f.writelines(filtered_lines)

print("Sucesso: Estilo modernizado!")
