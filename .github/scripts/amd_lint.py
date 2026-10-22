# Falha se algum amd/build/*.min.js nao for AMD nomeado (ES module ou define()
# anonimo): o Moodle concatena todos num pacote so e UM arquivo assim quebra
# todo o JS do site (incidente 22/10/2026, theme_moove). Uso: python3 amd_lint.py
import re, subprocess, sys
files = subprocess.run(['git', 'ls-files', '*/amd/build/*.min.js'], capture_output=True, text=True, check=True).stdout.split()
lead = re.compile(r'^(\s+|/\*.*?\*/|//[^\n]*\n)*', re.S)
bad = []
for f in files:
    with open(f, encoding='utf-8', errors='replace') as h:
        head = h.read(65536)
    code = head[lead.match(head).end():]
    if re.match(r'(import|export)\b', code) or re.match(r'define\s*\(\s*[\[f]', code):
        bad.append(f)  # ES module, or anonymous define() (breaks the concatenated bundle)
print(f'{len(files)} amd/build files checked, {len(bad)} not named-AMD (ES module / anonymous define)')
for f in bad:
    print(f'::error file={f}::ES module or anonymous define() in amd/build: Moodle bundles all modules, needs define(\"component/module\", ...) - build with grunt amd')
sys.exit(1 if bad else 0)
