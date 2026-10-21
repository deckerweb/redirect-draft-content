"""Generate shared wiki navigation and local export. Never publishes."""
from pathlib import Path
import json,re,sys
root=Path(__file__).resolve().parents[1]
data=json.loads((root/'content/wiki.json').read_text())
repo='https://github.com/deckerweb/'+data['slug'];wiki=repo+'/wiki/'
sidebar='\n\n'.join('## '+lang['heading']+'\n\n'+'\n'.join('- ['+label+']('+wiki+target+')' for label,target in lang['items'])for lang in data['languages'])+'\n'
footer=f"""**{data['name']} · Documentation / Dokumentation**

[Wiki home / Wiki-Startseite]({wiki}Home) · [Repository]({repo}) · [Downloads & Releases]({repo}/releases)

[Help & bugs / Hilfe & Fehler]({repo}/issues) · [Discussions / Austausch]({repo}/discussions) · [Security / Sicherheit]({repo}/security/policy)

© {data['year']} David Decker – DECKERWEB.  
Plugin: [GPL v2 or later / GPL v2 oder höher](https://www.gnu.org/licenses/gpl-2.0.html).
"""
(root/'docs/_Sidebar.md').write_text(sidebar)
(root/'docs/_Footer.md').write_text(footer)
if len(sys.argv)>1:
 dest=Path(sys.argv[1]);dest.mkdir(parents=True,exist_ok=True)
 for p in (root/'docs').glob('*.md'):
  if p.stem not in ['Home','English','Deutsch','FAQ-English','FAQ-Deutsch','Changelog-English','Changelog-Deutsch','Glossary','_Sidebar','_Footer']:continue
  text=p.read_text()
  def link(m):
   target=m[2]
   if target.startswith('../'):return m[1]+repo+'/blob/main/'+target[3:]+')'
   match=re.fullmatch(r'([A-Za-z_-]+)\.md(#[^ ]*)?',target)
   return m[1]+wiki+match[1]+(match[2]or'')+')' if match else m[0]
  text=re.sub(r'(\[[^\]]*\]\()([^)]*)\)',link,text)
  (dest/p.name).write_text(text)
 print('Prepared '+str(dest))
