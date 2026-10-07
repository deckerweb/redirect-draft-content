/* Copyright 2026 David Decker – DECKERWEB. SPDX-License-Identifier: GPL-2.0-or-later */
(() => {
 'use strict';
 const safeUrl = value => { try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) && !url.username && !url.password ? url.href : ''; } catch (_) { return ''; } };
 document.querySelectorAll('.rdc-post-type-block').forEach(block => {
  const select = block.querySelector('.rdc-target-select');
  const custom = block.querySelector('.rdc-custom-url');
  const preview = block.querySelector('.rdc-preview-link');
  const empty = block.querySelector('.rdc-preview-empty');
  const search = block.querySelector('.rdc-search');
  const more = block.querySelector('.rdc-more');
  const searchButton = block.querySelector('.rdc-search-button');
  const status = block.querySelector('.rdc-search-status');
  let page = 1, controller;
  const update = () => {
   const isCustom = block.querySelector('.rdc-mode-radio:checked')?.value === 'custom';
   block.querySelector('.rdc-row-existing').hidden = isCustom;
   block.querySelector('.rdc-row-custom').hidden = !isCustom;
   const url = safeUrl(isCustom ? custom.value.trim() : select.selectedOptions[0]?.dataset.permalink || '');
   preview.hidden = !url;
   if (url) preview.href = url; else preview.removeAttribute('href');
   empty.style.display = url ? 'none' : 'block';
  };
  block.addEventListener('change', update);
  custom.addEventListener('input', update);
  const load = async append => {
   controller?.abort(); controller = new AbortController();
   const requestedPage = append ? page + 1 : 1;
   status.textContent = ddwRdc.loading;
   more.disabled = true;
   try {
    const body = new URLSearchParams({action:'ddw_rdc_search', nonce:ddwRdc.nonce, post_type:block.dataset.posttype, term:search.value, page:String(requestedPage)});
    const response = await fetch(ddwRdc.ajax, {method:'POST', credentials:'same-origin', body, signal:controller.signal});
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error();
    if (!append) {
     [...select.options].forEach(option => { if (option.value !== '0' && !option.selected) option.remove(); });
    }
    result.data.items.forEach(item => {
     if ([...select.options].some(option => option.value === String(item.id))) return;
     const option = new Option(item.label, item.id); option.dataset.permalink = safeUrl(item.url); select.add(option);
    });
    page = requestedPage; more.hidden = !result.data.more; status.textContent = ddwRdc.count; update();
   } catch (error) { if (error.name !== 'AbortError') status.textContent = ddwRdc.error; }
   finally { more.disabled = false; }
  };
  searchButton.addEventListener('click', () => load(false));
  search.addEventListener('keydown', event => { if(event.key === 'Enter') { event.preventDefault(); load(false); } });
  more.addEventListener('click', () => load(true));
  update();
 });
 const dialog = document.getElementById('rdc-history');
 const open = document.getElementById('rdc-history-open');
 open?.addEventListener('click', () => dialog.showModal());
 document.getElementById('rdc-history-close')?.addEventListener('click', () => dialog.close());
 dialog?.addEventListener('close', () => open.focus());
})();
