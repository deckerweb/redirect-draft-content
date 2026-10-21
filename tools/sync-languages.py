"""Compile host-owned English, German and formal German translation resources."""
from pathlib import Path
import json,re,struct,gettext
root=Path(__file__).resolve().parents[1]
strings=set()
for p in [root/'redirect-draft-content.php',root/'includes/updates.php',root/'includes/updater-translations.php',root/'includes/history-translations.php']:
 strings.update(re.findall(r"(?:__|esc_html__|esc_html_e|esc_attr__)\(\s*'((?:\\.|[^'\\])*)'\s*,\s*'redirect-draft-content'",p.read_text()))
for release in json.loads((root/'history.json').read_text()):
 for category,items in release['changes'].items():strings.add(category);strings.update(items)
version=re.search(r"define\( 'DDW_RDC_VERSION', '([^']+)'",(root/'redirect-draft-content.php').read_text())[1]
for loc in ['de_DE','de_DE_formal']:
 source=json.loads((root/f'content/translations-{loc}.json').read_text());missing=strings-set(source)
 if missing:raise ValueError(sorted(missing))
 messages={k:source[k]for k in sorted(strings)}
 messages['']=f'Project-Id-Version: Redirect Draft Content {version}\nLanguage: {loc}\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\nPlural-Forms: nplurals=2; plural=(n != 1);\n'
 target=root/f'languages/redirect-draft-content-{loc}'
 target.with_suffix('.po').write_text('\n\n'.join('msgid '+json.dumps(k,ensure_ascii=False)+'\nmsgstr '+json.dumps(v,ensure_ascii=False)for k,v in sorted(messages.items()))+'\n')
 n=len(messages);ids=vals=b'';it=[];vt=[];offset=28+16*n
 for k,v in sorted(messages.items()):
  kb=k.encode();vb=v.encode();it.append((len(kb),offset+len(ids)));vt.append((len(vb),len(vals)));ids+=kb+b'\0';vals+=vb+b'\0'
 data=struct.pack('<7I',0x950412de,0,n,28,28+8*n,0,0)+b''.join(struct.pack('<2I',*x)for x in it)+b''.join(struct.pack('<2I',ln,offset+len(ids)+pos)for ln,pos in vt)+ids+vals
 target.with_suffix('.mo').write_bytes(data)
 with target.with_suffix('.mo').open('rb')as f:gettext.GNUTranslations(f)
(root/'languages/redirect-draft-content.pot').write_text('msgid ""\nmsgstr ""\n"Content-Type: text/plain; charset=UTF-8\\n"\n\n'+'\n\n'.join('msgid '+json.dumps(k,ensure_ascii=False)+'\nmsgstr ""'for k in sorted(strings))+'\n')
print(str(len(strings))+' host messages compiled in Du and Sie; Library catalogues remain in the same host domain.')
