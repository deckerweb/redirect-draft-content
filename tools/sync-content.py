"""Synchronize public author story, FAQs and history. Run with --check to detect drift."""
from pathlib import Path
import json, re, sys
root=Path(__file__).resolve().parents[1]
story=json.loads((root/'content/about.json').read_text())
faq=json.loads((root/'content/faq.json').read_text())
history=json.loads((root/'history.json').read_text())
translations=json.loads((root/'content/changelog-de.json').read_text())
changed=[]
def save(path,text):
 if path.read_text()!=text:
  changed.append(str(path.relative_to(root)))
  if '--check' not in sys.argv:path.write_text(text)
for lang,name in [('en','README.md'),('de','README-de.md')]:
 de=lang=='de';path=root/name;text=path.read_text();data=story[lang]
 text=re.sub(r'### '+re.escape(data['heading'])+r'\n\n.*?(?=\n\n\*\*Version)',f"### {data['heading']}\n\n{data['text']}",text,flags=re.S)
 questions='\n\n'.join(f"### {entry[lang]['question']}\n\n{entry[lang]['answer']}"for entry in faq)
 text=re.sub(r'## FAQ\n\n.*?(?=\n\n\[Complete FAQ|\n\n\[Vollständige FAQ)',f'## FAQ\n\n{questions}',text,flags=re.S)
 heading='Änderungsverlauf'if de else'Changelog'
 releases=[]
 for release in history:
  rows=['### '+release['version']+(' · '+release['date'] if release.get('date') else ''),'']
  for key,items in release['changes'].items():
   for item in items:rows.append('- **'+(translations[key]if de else key)+'** '+(translations[item]if de else item))
  releases.append('\n'.join(rows))
 rendered='\n\n'.join(releases)
 text=re.sub(r'## '+heading+r'\n\n.*?(?=\n\n## Project and support|\n\n## Projekt und Unterstützung)',f'## {heading}\n\n{rendered}',text,flags=re.S)
 save(path,text)
 fpath=root/'docs'/('FAQ-Deutsch.md'if de else'FAQ-English.md')
 ftext=fpath.read_text();marker='## Einstieg und Alltag'if de else'## Getting started and daily use'
 groups=[('Einstieg und Alltag' if de else 'Getting started and daily use',faq[:4]),('Umstellung und Multisite' if de else 'Migration and Multisite',faq[4:6]),('Daten und Deinstallation' if de else 'Data and uninstall',faq[6:])]
 grouped='\n\n'.join('## '+title+'\n\n'+'\n\n'.join(f"### {entry[lang]['question']}\n\n{entry[lang]['answer']}"for entry in entries)for title,entries in groups)
 save(fpath,ftext.split(marker)[0]+grouped+'\n')
 hpath=root/'docs'/('Changelog-Deutsch.md'if de else'Changelog-English.md')
 htext=hpath.read_text();save(hpath,htext.split('### ')[0]+rendered+'\n')
 wpath=root/('readme-de.txt'if de else'readme.txt');wtext=wpath.read_text();wtext=wtext.split('== Frequently Asked Questions ==')[0]+'== Frequently Asked Questions ==\n'+'\n\n'.join('= '+entry[lang]['question']+' =\n'+entry[lang]['answer']for entry in faq)+'\n\n== Changelog ==\n'
 for release in history:
  wtext+='= '+release['version']+' =\n'+'\n'.join('* '+(translations[key]if de else key)+' '+(translations[item]if de else item)for key,items in release['changes'].items()for item in items)+'\n'
 save(wpath,wtext)
index=root/'index.md';text=index.read_text()
for lang in ['en','de']:
 data=story[lang];text=re.sub(r'## '+re.escape(data['heading'])+r'\n\n.*?(?=\n\n## )',f"## {data['heading']}\n\n{data['text']}",text,flags=re.S)
save(index,text)
print(('Content drift: 'if '--check'in sys.argv else'Updated: ')+', '.join(changed)if changed else'All public content is synchronized.')
if '--check'in sys.argv and changed:sys.exit(1)
