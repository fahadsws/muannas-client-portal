(() => {
  const B = window.BOARD;
  const CLIENT = B.mode === 'client';
  const ME = B.me, AG = B.agency;
  const IS_OWNER = ME.role === 'owner';
  const IS_STAFF = ['owner', 'member'].includes(ME.role);
  const can = (perm) => (ME.perms || []).includes(perm);
  const $ = (s) => document.querySelector(s);
  const CSRF = document.querySelector('meta[name=csrf-token]').content;
  const NEXT_STATUS = ['todo', 'ready', 'done', 'live'];
  const LABEL = {todo: 'To do', ready: 'Ready', done: 'Done', live: 'Live'};
  const PAGE = 9, SHARE_PAGE = 8, WS_VISIBLE = 6;
  const MARK = document.querySelector('#splash .mark').outerHTML;
  const wait = (ms) => new Promise((r) => setTimeout(r, ms));
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));

  const S = {
    accts: B.accounts.slice(), ai: 0, data: null, cache: {}, page: 'board', tab: null, filter: 'all', view: 'cards',
    loading: 'all', animate: true, limit: PAGE, kl: {}, wsAll: false, loadingMore: false,
    tpls: null, team: null, teamLimit: 8, set: {sec: 'templates', draft: null, sample: true}, docs: {tid: null, tabs: null, status: 'all', notes: true, sample: null},
  };
  try { S.view = localStorage.getItem('view') || 'cards'; const last = +localStorage.getItem('ws'); const i = S.accts.findIndex((a) => a.id === last); if (i >= 0) S.ai = i; } catch (e) {}

  // ── icons ────────────────────────────────────────────────
  const P = {
    x: '<path d="M6 6l12 12M18 6L6 18"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>', plus: '<path d="M12 5v14M5 12h14"/>',
    bell: '<path d="M6 8a6 6 0 0112 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 003.4 0"/>',
    share: '<path d="M4 12v7a1 1 0 001 1h14a1 1 0 001-1v-7"/><path d="M16 6l-4-4-4 4M12 2v13"/>',
    more: '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
    edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 113 3L7 19l-4 1 1-4z"/>',
    lock: '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 018 0v3"/>', unlock: '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 017.5-1.9"/>',
    trash: '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
    cards: '<rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/>',
    board: '<rect x="3" y="3" width="5" height="18" rx="1.5"/><rect x="10" y="3" width="5" height="12" rx="1.5"/><rect x="17" y="3" width="4" height="15" rx="1.5"/>',
    image: '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M4 17l5-4.5 4 3.5 3-2.5 4 3.5"/>',
    out: '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>', menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    doc: '<path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
    cal: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>', clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    mic: '<rect x="9" y="3" width="6" height="12" rx="3"/><path d="M5 11a7 7 0 0014 0M12 18v3"/>', film: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 4v16M17 4v16M3 9h4M3 15h4M17 9h4M17 15h4"/>',
    arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>', inbox: '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13l3.5 7v6a2 2 0 01-2 2H4a2 2 0 01-2-2v-6z"/>',
    check: '<path d="M5 12l5 5 9-10"/>', eye: '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    users: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0113 0"/><path d="M16 4.6a3.5 3.5 0 010 6.8M18 14a6.5 6.5 0 013.5 6"/>',
    cog: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/>',
    dl: '<path d="M12 3v12M7 10l5 5 5-5M4 20h16"/>', copy: '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/>',
    folder: '<path d="M3 6a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>',
  };
  const ic = (n, c = '') => `<svg class="i ${c}" viewBox="0 0 24 24">${P[n]}</svg>`;

  // ── progress bar / toast / api ───────────────────────────
  const NP = {n: 0, t: null};
  const np = {
    start() { NP.n++; const b = $('#nprog'); b.classList.add('on'); b.style.width = '12%'; clearInterval(NP.t); NP.t = setInterval(() => { const w = parseFloat(b.style.width) || 0; b.style.width = Math.min(w + (90 - w) * 0.18, 90) + '%'; }, 140); },
    done() { if (--NP.n > 0) return; clearInterval(NP.t); const b = $('#nprog'); b.style.width = '100%'; setTimeout(() => { b.classList.remove('on'); b.style.width = '0'; }, 280); },
  };
  function toast(msg, ok = true) {
    const t = $('#toast'); t.innerHTML = (ok ? '<i>✓</i>' : '') + esc(msg); t.classList.add('on');
    clearTimeout(toast.t); toast.t = setTimeout(() => t.classList.remove('on'), 2400);
  }
  async function api(method, url, body, form) {
    const headers = {'X-CSRF-TOKEN': CSRF, Accept: 'application/json'};
    if (CLIENT) headers['X-Share-Token'] = B.token;
    let payload;
    if (form) payload = form; else if (body !== undefined) { headers['Content-Type'] = 'application/json'; payload = JSON.stringify(body); }
    np.start();
    try {
      const res = await fetch('/api/' + url, {method, headers, body: payload});
      if (!res.ok) {
        let msg = 'Something went wrong';
        try { const j = await res.json(); msg = j.errors ? Object.values(j.errors)[0][0] : (j.message || msg); } catch (e) {}
        if (res.status === 423) msg = 'This item is locked — unlock it first.';
        toast(msg, false); throw new Error(msg);
      }
      return await res.json();
    } finally { np.done(); }
  }

  // ── helpers ──────────────────────────────────────────────
  const GRADS = ['#7c3aed,#c13bd8', '#2563eb,#7c3aed', '#0d9488,#2563eb', '#e11d74,#f97316', '#4f46e5,#06b6d4'];
  const initials = (n) => String(n).split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase();
  const grad = (n) => GRADS[[...String(n)].reduce((a, c) => a + c.charCodeAt(0), 0) % GRADS.length];
  const avatar = (n, cls = '') => `<span class="av ${cls}" style="background:linear-gradient(135deg,${grad(n)})">${esc(initials(n))}</span>`;
  const acct = () => S.accts[S.ai];
  const perm = () => (S.data && S.data.perm) || (acct() && acct().perm) || 'view';
  const canEdit = () => perm() === 'edit';
  const canComment = () => ['edit', 'comment'].includes(perm());
  const tabs = () => S.data.tabs;
  const curTab = () => tabs().find((t) => t.id === S.tab) || tabs()[0];
  const countable = (d) => d.tabs.filter((t) => !(t.meta && t.meta.overview)).flatMap((t) => t.items);
  function stats(d) {
    const items = countable(d); const c = {todo: 0, ready: 0, done: 0, live: 0};
    items.forEach((i) => c[i.status]++);
    const total = items.length, fin = c.done + c.live;
    return {c, total, fin, pct: total ? Math.round((fin / total) * 100) : 0};
  }
  const findItem = (id) => tabs().flatMap((t) => t.items).find((i) => i.id === +id);
  const cache = (d) => { S.cache[d.id] = d; };

  // ── skeletons ────────────────────────────────────────────
  const skCards = (n = 6) => `<div class="cards">${Array.from({length: n}, () => `<div class="skc"><div class="sk im"></div><div class="sb"><div class="sk l" style="width:35%"></div><div class="sk l" style="width:90%"></div><div class="sk l" style="width:70%"></div><div class="sk l" style="width:45%"></div></div></div>`).join('')}</div>`;
  const skHead = () => `<div class="hd"><div class="t"><div class="sk" style="width:46px;height:46px;border-radius:8px"></div><div><div class="sk" style="width:220px;height:22px;margin-bottom:8px"></div><div class="sk l" style="width:150px"></div></div></div></div><div class="kpis">${'<div class="sk k"></div>'.repeat(4)}</div>`;
  const skPage = () => `<div class="pagehead"><div><div class="sk" style="width:200px;height:26px;margin-bottom:8px"></div><div class="sk l" style="width:300px"></div></div></div><div class="sk" style="height:300px;border-radius:8px"></div>`;

  // ── sidebar & top bar ────────────────────────────────────
  function renderSide() {
    if (CLIENT) { $('#side').innerHTML = ''; return; }
    const shown = S.wsAll ? S.accts : S.accts.slice(0, WS_VISIBLE);
    const ws = shown.map((a, i) => {
      const d = S.cache[a.id]; const p = d ? stats(d).pct : null;
      return `<button class="ws ${S.page === 'board' && i === S.ai ? 'on' : ''}" data-ai="${i}">${avatar(a.name)}<span><b>${esc(a.name)}</b><small>${a.perm === 'edit' ? (d ? stats(d).total + ' items' : 'Workspace') : 'Shared with you'}</small></span><span class="pct">${p === null ? '' : p + '%'}</span></button>`;
    }).join('');
    const more = S.accts.length > WS_VISIBLE ? `<button class="wsmore" data-a="wsall">${S.wsAll ? 'Show fewer' : `Show all ${S.accts.length}`}</button>` : '';
    const nn = S.data ? (S.data.notes_total ?? S.data.notes.length) : 0;
    $('#side').innerHTML = `<div class="agbox">${avatar(AG.name)}<div><b>${esc(AG.name)}</b><small>${esc(B.brand)} workspace</small></div></div>
      <div class="slabel-row"><div class="slabel">Workspaces</div>${can('workspaces.create') ? `<button class="ibtn" data-a="newws" title="New workspace">${ic('plus')}</button>` : ''}</div>
      <div class="nav">${ws || '<div style="padding:6px 10px;font-size:12px;color:var(--mute)">No workspaces yet</div>'}${more}</div>
      <div class="slabel">Manage</div>
      <div class="nav">
        <button class="nbtn ${S.page === 'board' ? 'on' : ''}" data-page="board">${ic('cards')}Campaign board</button>
        <button class="nbtn" data-a="notes" ${acct() ? '' : 'disabled'}>${ic('bell')}Team notes ${nn ? `<span class="badge">${nn}</span>` : ''}</button>
        <a class="nbtn" href="/help" style="text-decoration:none">${ic('doc')}Help guide</a>
        ${can('documents.export') ? `<button class="nbtn ${S.page === 'docs' ? 'on' : ''}" data-page="docs">${ic('doc')}Documents</button>` : ''}
        ${IS_STAFF ? `<a class="nbtn" href="/team" style="text-decoration:none">${ic('users')}Team</a>` : ''}
        ${can('roles.manage') ? `<a class="nbtn" href="/roles" style="text-decoration:none">${ic('cog')}Roles & permissions</a>` : ''}
        ${can('settings.manage') || can('templates.manage') ? `<button class="nbtn ${S.page === 'settings' ? 'on' : ''}" data-page="settings">${ic('cog')}Settings</button>` : ''}
      </div>
      <div class="sfoot">${avatar(ME.name)}<div class="who"><b>${esc(ME.name)}</b><small>${esc(ME.role_name || (ME.role === 'owner' ? 'Owner' : 'Guest'))}</small></div>
        <form method="post" action="/logout" style="margin:0"><input type="hidden" name="_token" value="${CSRF}"><button class="ibtn" title="Log out">${ic('out')}</button></form></div>
      ${ME.impersonating ? `<form method="post" action="/impersonate/stop" style="margin:12px 0 0"><input type="hidden" name="_token" value="${CSRF}"><button class="btn" style="width:100%;justify-content:center">Exit to admin console</button></form>` : ''}`;
  }

  const PAGE_NAME = {board: 'Campaign board', docs: 'Documents', team: 'Team', settings: 'Settings'};
  function renderBar() {
    const t = S.data && S.page === 'board' ? curTab() : null;
    const nn = S.data ? (S.data.notes_total ?? S.data.notes.length) : 0;
    const board = S.page === 'board' && acct();
    $('#bar').innerHTML = `<button class="ibtn burger" data-a="burger">${ic('menu')}</button>
      ${CLIENT ? `<span class="logo" style="margin-right:6px">${MARK}<span class="wordmark">${esc(AG.name)}</span></span>` : ''}
      <div class="crumb hide-s">${CLIENT ? '' : `<span>${esc(AG.name)}</span><span class="sep">/</span>`}${S.page === 'board' ? `<span>${esc(acct() ? acct().name : 'Workspaces')}</span>${t ? `<span class="sep">/</span><b>${esc(t.label)}</b>` : ''}` : `<b>${PAGE_NAME[S.page]}</b>`}</div>
      <div class="spacer"></div>
      ${acct() ? `<button class="search" data-a="cmd">${ic('search')}<span>Search or jump to…</span><span class="kbd">Ctrl K</span></button>` : ''}
      ${board ? `<button class="btn" data-a="notes">${ic('bell')}<span class="hide-s">${canEdit() ? 'Notes' : 'Comments'}</span>${nn ? `<span class="badge">${nn}</span>` : ''}</button>` : ''}
      ${board && canEdit() && !CLIENT && can('sharing.manage') ? `<button class="btn hide-s" data-a="share">${ic('share')}Share</button>` : ''}
      ${board && canEdit() && !CLIENT ? `<button class="btn primary" data-a="addrow">${ic('plus')}<span class="hide-s">Add row</span></button>
        <button class="ibtn" data-a="more" title="More">${ic('more')}</button>` : ''}`;
  }

  // ── board page shell ─────────────────────────────────────
  function ensureShell() {
    if (!$('#view')) $('#content').innerHTML = '<div id="head"></div><div id="tabs" class="tabs"></div><div id="tb"></div><div id="view"></div>';
  }

  function renderHead() {
    if (S.loading === 'all') { $('#head').innerHTML = skHead(); return; }
    const a = acct(); const st = stats(S.data); const docs = S.data.docs || [];
    $('#head').innerHTML = `<div class="hd">
      <div class="t">${avatar(a.name)}<div><h1>${esc(a.name)}</h1><p>Campaign system board · content calendar &amp; approvals</p></div></div>
      <div class="docs">${CLIENT ? '<span class="pill-live"><i></i>Live progress</span>' : ''}
        ${docs.length || canEdit() ? '<span class="dl">Docs</span>' : ''}
        ${docs.map((d) => `<a class="chipl" href="${esc(d.url)}" target="_blank" rel="noopener">${ic('link' in P ? 'doc' : 'doc')}${esc(d.title)}</a>`).join('')}
        ${canEdit() ? `<button class="btn sm ghost" data-a="adddoc">${ic('plus')}Add link</button>` : ''}</div></div>
      <div class="kpis">
        <div class="kpi"><small>${ic('cal')}Total items</small><b data-count="${st.total}">0</b><div class="sub">${st.pct}% complete</div><div class="meter"><i style="width:${st.pct}%"></i></div></div>
        <div class="kpi"><small><i class="dot todo"></i>To do</small><b data-count="${st.c.todo}">0</b><div class="sub">Waiting to start</div><div class="meter"><i class="m" style="width:${st.total ? (st.c.todo / st.total) * 100 : 0}%"></i></div></div>
        <div class="kpi"><small><i class="dot ready"></i>Ready</small><b data-count="${st.c.ready}">0</b><div class="sub">Ready for approval</div><div class="meter"><i class="p" style="width:${st.total ? (st.c.ready / st.total) * 100 : 0}%"></i></div></div>
        <div class="kpi"><small><i class="dot done"></i>Done &amp; live</small><b data-count="${st.fin}">0</b><div class="sub">${st.c.live} live now</div><div class="meter"><i class="g" style="width:${st.total ? (st.fin / st.total) * 100 : 0}%"></i></div></div></div>`;
    document.querySelectorAll('[data-count]').forEach(countUp);
  }
  function countUp(el) {
    const to = +el.dataset.count;
    if (!S.animate) { el.textContent = to; return; }
    const t0 = performance.now(), d = 600;
    const step = (t) => { const k = Math.min((t - t0) / d, 1); el.textContent = Math.round(to * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(step); };
    requestAnimationFrame(step);
  }

  function renderTabs() {
    const el = $('#tabs');
    if (S.loading === 'all') { el.className = ''; el.innerHTML = `<div class="sk" style="width:640px;max-width:100%;height:42px;border-radius:5px"></div>`; return; }
    el.className = 'tabs';
    const cur = curTab();
    el.innerHTML = tabs().map((t) => {
      const n = t.items.length;
      return `<button class="tab ${t.id === cur.id ? 'on' : ''}" data-tab="${t.id}">${esc(t.label)}${n ? `<span class="n">${n}</span>` : ''}${canEdit() && t.kind !== 'strategy' && !(t.meta && t.meta.overview) && t.id === cur.id ? `<span class="x" data-deltab="${t.id}" title="Remove tab">×</span>` : ''}</button>`;
    }).join('') + (canEdit() ? `<button class="tab" data-a="addtab" title="Add tab" style="padding:0 10px">${ic('plus')}</button>` : '');
  }

  function renderTb() {
    if (S.loading === 'all') { $('#tb').innerHTML = `<div class="tb"><div><div class="sk" style="width:240px;height:20px;margin-bottom:8px"></div><div class="sk l" style="width:180px"></div></div></div>`; return; }
    const t = curTab(); const m = t.meta || {};
    if (t.kind === 'strategy') { $('#tb').innerHTML = `<div class="tb"><div><h2>${esc(m.title || 'Strategy')}</h2><p>The rules every piece of content follows.</p></div>${canEdit() ? `<div class="tbr"><button class="btn" data-a="editstrategy">${ic('edit')}Edit strategy</button></div>` : ''}</div>`; return; }
    const cnt = {all: t.items.length, todo: 0, ready: 0, done: 0, live: 0}; t.items.forEach((i) => cnt[i.status]++);
    $('#tb').innerHTML = `<div class="tb"><div><h2>${esc(m.title || t.label)}</h2>${m.subtitle ? `<p>${esc(m.subtitle)}</p>` : ''}</div>
      <div class="tbr"><div class="seg">${['all', ...NEXT_STATUS].map((f) => `<button class="${S.filter === f ? 'on' : ''}" data-f="${f}">${f === 'todo' ? 'To do' : f}<span class="c">${cnt[f]}</span></button>`).join('')}</div>
      <div class="seg v"><button class="${S.view === 'cards' ? 'on' : ''}" data-v="cards" title="Card view">${ic('cards')}</button><button class="${S.view === 'board' ? 'on' : ''}" data-v="board" title="Board by status">${ic('board')}</button></div></div></div>`;
  }

  // ── content views ────────────────────────────────────────
  const plats = (p) => (p || []).map((x) => `<span class="${x}">${x}</span>`).join('');
  const stagger = (i) => `style="animation-delay:${Math.min(i, 14) * 28}ms"`;
  const noteBox = (it) => `<textarea class="note" rows="1" data-note="${it.id}" ${canComment() ? '' : 'readonly'} placeholder="${canEdit() ? 'Add a note…' : canComment() ? 'Leave a comment for the agency…' : 'No notes'}">${esc(it.data.notes || '')}</textarea>`;
  const actions = (it) => canEdit() ? `<div class="cact"><button data-edit="${it.id}" title="Edit">${ic('edit')}</button><button data-lock="${it.id}" class="${it.locked ? 'on' : ''}" title="${it.locked ? 'Unlock' : 'Lock'}">${ic(it.locked ? 'lock' : 'unlock')}</button><button class="del" data-del="${it.id}" title="Delete">${ic('trash')}</button></div>` : '';
  const spill = (it) => `<button class="spill ${it.status}" data-cycle="${it.id}" ${canEdit() ? '' : 'disabled'}><i class="dot ${it.status}"></i>${LABEL[it.status]}</button>`;

  function imgBlock(it, chip, inner) {
    const up = canEdit() && !inner;
    return `<div class="img ${up ? 'up' : ''}" ${up ? `data-up="${it.id}"` : ''}>${it.image ? `<img src="${esc(it.image)}" alt="">` : (inner ?? `${ic('image')}<span>${canEdit() ? 'Upload image' : 'No image yet'}</span>`)}${actions(it)}${spill(it)}<span class="lbl-chip">${esc(chip)}</span></div>`;
  }
  function postCard(it, withHook, i) {
    const d = it.data;
    const story = d.story && d.story.length ? `<div class="story"><div class="lbl">Story ideas</div><ul>${d.story.map((s) => `<li>${esc(s)}</li>`).join('')}</ul></div>` : '';
    return `<div class="card" data-id="${it.id}" ${stagger(i)}>${imgBlock(it, d.label || '')}<div class="body">
      ${d.day ? `<div class="day">${esc(d.day)}</div>` : ''}${withHook && d.hook ? `<div class="hook">${esc(d.hook)}</div>` : ''}
      <div class="cap ${(d.caption || '').length > 140 ? 'long' : ''}">${esc(d.caption)}</div>${story}
      ${d.visual ? `<div class="lbl">Visual direction</div><div class="vis">${esc(d.visual)}</div>` : ''}
      <div class="meta"><div class="plats">${plats(d.platforms)}</div><div class="time">${d.time ? ic('clock') + esc(d.time) : ''}${it.locked ? `<span class="lockbadge">${ic('lock')}</span>` : ''}</div></div>${noteBox(it)}</div></div>`;
  }
  function reelCard(it, i) {
    const d = it.data;
    const line = (l, v, cls) => v ? `<div class="lbl">${l}</div><div class="${cls || 'vis'}">${esc(v)}</div>` : '';
    return `<div class="card reel" data-id="${it.id}" ${stagger(i)}>${imgBlock(it, d.label || '', `<div class="pill"><b>${esc(d.format || 'REEL')}</b><i>${esc(d.pillar || '')}</i></div>`)}<div class="body">
      <div class="cap">${esc(d.caption)}</div>${line('Script', d.script, 'script')}${line('Delivery note', d.delivery)}${line('Visual direction', d.visual)}${line('End card / CTA', d.endcard)}${noteBox(it)}</div></div>`;
  }
  function epCard(it, i) {
    const d = it.data;
    return `<div class="ep" data-id="${it.id}" ${stagger(i)}><div class="hdr"><span class="lab">${esc(d.label)}</span><button class="status-tag ${it.status}" data-cycle="${it.id}" ${canEdit() ? '' : 'disabled'}>${LABEL[it.status]}</button></div>${actions(it)}
      <div class="ttl">${esc(d.caption)}</div><div class="hk">${esc(d.hook)}</div><div class="dur">${ic('mic')}${esc(d.duration)}${it.locked ? `<span class="lockbadge" style="margin-left:auto">${ic('lock')}</span>` : ''}</div>${noteBox(it)}</div>`;
  }
  function kanban(t) {
    return `<div class="kan">${NEXT_STATUS.map((s) => {
      const list = t.items.filter((i) => i.status === s); const lim = S.kl[s] || 6; const vis = list.slice(0, lim);
      return `<div class="kcol" data-col="${s}"><h4><i class="dot ${s}"></i>${LABEL[s]}<span>${list.length}</span></h4>
        ${vis.map((it, i) => `<div class="kc" ${canEdit() ? 'draggable="true"' : ''} data-id="${it.id}" data-kc="${it.id}" ${stagger(i)}><div class="k1"><span>${esc(it.data.label || '')}</span><span class="plats">${plats(it.data.platforms)}</span></div><p>${esc(it.data.caption)}</p>${canEdit() ? `<div class="cact" style="position:static;opacity:1;transform:none;margin-top:2px"><button data-edit="${it.id}">${ic('edit')}</button><button data-lock="${it.id}" class="${it.locked ? 'on' : ''}">${ic(it.locked ? 'lock' : 'unlock')}</button></div>` : ''}</div>`).join('') || '<div class="kempty">Nothing here</div>'}
        ${list.length > lim ? `<button class="btn sm" style="width:100%;justify-content:center" data-kmore="${s}">Load ${Math.min(6, list.length - lim)} more</button>` : ''}</div>`;
    }).join('')}</div>`;
  }

  function strategyView(t) {
    const m = t.meta || {}; const ed = canEdit();
    const H = (title, kind, n) => `<div class="sec">${title}${ed ? `<button class="addlink" data-a="editstrategy" data-focus="${kind}">${ic('plus')}${n ? 'Add / edit' : 'Add'}</button>` : ''}</div>`;
    const empty = (txt, kind) => ed ? `<button class="semp" data-a="editstrategy" data-focus="${kind}">${ic('plus')}${txt}</button>` : '<p class="sub-note">Not added yet.</p>';
    const doN = (m.do || []).length + (m.dont || []).length, pN = (m.pillars || []).length, aN = (m.arc || []).length, mN = (m.mix || []).length;
    if (!doN && !pN && !aN && !mN && !ed) return `<div class="empty"><div class="eic">${ic('doc')}</div><h3>No strategy written yet</h3><p>The agency has not added a strategy yet.</p></div>`;
    if (!doN && !pN && !aN && !mN) return `<div class="empty"><div class="eic">${ic('doc')}</div><h3>No strategy written yet</h3><p>Add the brand voice, content pillars and campaign arc so everyone works from the same brief.</p>
      <button class="btn primary" data-a="editstrategy">${ic('plus')}Start the strategy</button> <a class="btn" href="/help#strategy" style="text-decoration:none">How it works</a></div>`;
    return H('Brand voice', 'do', doN) + (doN ? `<div class="panel voice"><div><h4 class="do"><i>✓</i>Do</h4><ul>${(m.do || []).map((x) => `<li>${esc(x)}</li>`).join('') || '<li class="muted">None yet</li>'}</ul></div><div><h4 class="dont"><i>✕</i>Don't</h4><ul>${(m.dont || []).map((x) => `<li>${esc(x)}</li>`).join('') || '<li class="muted">None yet</li>'}</ul></div></div>` : empty('Add the first rule', 'do'))
      + H('Content pillars', 'pillars', pN) + (pN ? `<div class="pillars">${m.pillars.map((p, i) => `<div class="pillar"><span class="no">0${i + 1}</span><b>${esc(p[0])}</b><p>${esc(p[1] || '')}</p></div>`).join('')}</div>` : empty('Add the first pillar', 'pillars'))
      + H(esc(m.arc_title || 'Campaign arc'), 'arc', aN) + (aN ? `<div class="panel arc">${m.arc.map((a) => `<div><small>${esc(a[0])}</small><b>${esc(a[1] || '')}</b><p>${esc(a[2] || '')}</p></div>`).join('')}</div>` : empty('Add the first week', 'arc'))
      + H('Content mix', 'mix', mN) + (mN ? `<div class="mix">${m.mix.map((x) => `<div><b>${esc(x[2])}</b><span>${esc(x[0])}</span><small>${esc(x[1] || '')}</small></div>`).join('')}</div>` : empty('Add a content type', 'mix'));
  }

  const emptyState = (filtered) => `<div class="empty"><div class="eic">${ic(filtered ? 'search' : 'inbox')}</div><h3>${filtered ? 'Nothing matches this filter' : 'No posts here yet'}</h3>
    <p>${filtered ? 'Try another status.' : canEdit() ? 'Add the first row to start planning.' : 'The agency is still preparing this section.'}</p>${!filtered && canEdit() ? `<button class="btn primary" data-a="addrow">${ic('plus')}Add row</button>` : ''}</div>`;

  const moreBtn = (shown, total, tag = 'data-a="loadmore"') => total > shown ? `<div class="more"><div><button class="btn" ${tag} ${S.loadingMore ? 'disabled' : ''}>${S.loadingMore ? '<i class="spin" style="border-color:rgba(0,0,0,.18);border-top-color:var(--accent)"></i>Loading…' : 'Load more'}</button><small>Showing ${shown} of ${total}</small></div></div>` : (total > PAGE ? `<div class="more"><small>Showing all ${total}</small></div>` : '');

  function renderView() {
    const v = $('#view'); if (!v) return;
    v.className = 'view' + (S.animate ? ' anim' : '');
    if (S.loading) { v.innerHTML = skCards(6); return; }
    const t = curTab();
    if (t.kind === 'strategy') v.innerHTML = strategyView(t);
    else {
      let items = t.items; const filtered = S.filter !== 'all';
      if (t.kind === 'grid' && filtered) { const rows = new Set(items.filter((i) => i.status === S.filter).map((i) => i.position)); items = items.filter((i) => rows.has(i.position)); }
      else if (filtered) items = items.filter((i) => i.status === S.filter);
      if (S.view === 'board' && t.items.length) v.innerHTML = kanban(t);
      else if (!items.length) v.innerHTML = emptyState(filtered && t.items.length);
      else {
        const vis = items.slice(0, S.limit);
        if (t.kind === 'grid') { const cols = (t.meta && t.meta.columns) || ['One', 'Two', 'Three']; v.innerHTML = `<div class="colhead">${cols.map((c) => `<span>${esc(c)}</span>`).join('')}</div><div class="cards">${vis.map((i, k) => postCard(i, false, k)).join('')}</div>`; }
        else if (t.kind === 'reels') v.innerHTML = `<div class="cards">${vis.map(reelCard).join('')}</div>`;
        else if (t.kind === 'podcasts') v.innerHTML = `<div class="eps">${vis.map(epCard).join('')}</div>`;
        else v.innerHTML = `<div class="cards">${vis.map((i, k) => postCard(i, true, k)).join('')}</div>`;
        v.insertAdjacentHTML('beforeend', moreBtn(vis.length, items.length));
        if (canEdit() && !CLIENT) v.insertAdjacentHTML('beforeend', `<div style="margin-top:18px"><button class="btn" data-a="addrow">${ic('plus')}Add row</button></div>`);
      }
    }
    v.querySelectorAll('textarea.note').forEach(fit);
    S.animate = false;
  }
  function fit(el) { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; }

  // ── pages ────────────────────────────────────────────────
  function renderBoardPage() {
    if (!acct()) {
      $('#content').innerHTML = `<div class="empty" style="margin-top:40px"><div class="eic">${ic('folder')}</div><h3>${can('workspaces.create') ? 'Create your first workspace' : 'Nothing shared with you yet'}</h3><p>${can('workspaces.create') ? 'A workspace is one client or brand. Each has its own strategy, calendar and approvals.' : 'When an agency shares a workspace with you it will appear here.'}</p>${can('workspaces.create') ? `<button class="btn primary" data-a="newws">${ic('plus')}New workspace</button>` : ''}</div>`;
      return;
    }
    ensureShell(); renderHead(); renderTabs(); renderTb(); renderView();
  }

  function render() {
    renderSide(); renderBar();
    if (S.page === 'board') renderBoardPage();
    else if (S.page === 'docs') renderDocs();
    else if (S.page === 'team') renderTeam();
    else if (S.page === 'settings') renderSettings();
  }
  const renderBoardParts = () => { renderBar(); ensureShell(); renderHead(); renderTabs(); renderTb(); renderView(); renderSide(); };

  // ── navigation with loading states ───────────────────────
  async function goTab(id) {
    if (S.page !== 'board') { S.page = 'board'; S.tab = id; S.animate = true; return render(); }
    if (id === S.tab || S.loading) return;
    S.tab = id; S.filter = 'all'; S.limit = PAGE; S.kl = {}; S.animate = true; S.loading = 'view'; history.replaceState(null, '', '#' + id);
    renderBar(); renderTabs(); renderTb(); renderView(); np.start();
    await wait(320); np.done(); S.loading = null; renderBoardParts();
  }
  async function loadAccount(i) {
    S.ai = i; S.filter = 'all'; S.limit = PAGE; S.kl = {}; S.animate = true; S.loading = 'all'; S.page = 'board';
    try { localStorage.setItem('ws', acct().id); } catch (e) {}
    $('#side').classList.remove('open');
    $('#content').innerHTML = ''; renderSide(); renderBar(); ensureShell(); renderHead(); renderTabs(); renderTb(); renderView();
    const [d] = await Promise.all([api('GET', 'accounts/' + acct().id), wait(600)]);
    S.data = d; cache(d); S.tab = tabs()[0].id; S.loading = null; history.replaceState(null, '', '#' + S.tab); renderBoardParts();
  }
  async function goPage(p) {
    if (p === S.page) return;
    $('#side').classList.remove('open'); $('#drawer').classList.remove('on');
    if (p === 'board') { return S.data ? (S.page = 'board', S.animate = true, S.loading = null, render()) : (S.page = 'board', render()); }
    S.page = p; S.animate = true; renderSide(); renderBar(); $('#content').innerHTML = skPage();
    if (p === 'team') { const [t] = await Promise.all([api('GET', 'team'), wait(350)]); S.team = t; S.teamLimit = 8; }
    else { const [t] = await Promise.all([S.tpls ? Promise.resolve(S.tpls) : api('GET', 'templates'), wait(350)]); S.tpls = t; initDocState(); }
    if (S.page === p) render();
  }

  // ── popover ──────────────────────────────────────────────
  function closePop() { document.querySelectorAll('.pop').forEach((p) => p.remove()); }
  function openPop(anchor, items) {
    closePop();
    const p = document.createElement('div'); p.className = 'pop';
    p.innerHTML = items.map((it, i) => it === '-' ? '<hr>' : `<button data-pi="${i}" class="${it.danger ? 'danger' : ''} ${it.on ? 'on' : ''}">${it.icon ? ic(it.icon) : ''}${esc(it.label)}</button>`).join('');
    document.body.appendChild(p);
    const r = anchor.getBoundingClientRect();
    p.style.top = Math.min(r.bottom + 6, innerHeight - p.offsetHeight - 10) + 'px';
    p.style.left = Math.max(10, Math.min(r.right - p.offsetWidth, innerWidth - p.offsetWidth - 10)) + 'px';
    p.onclick = (e) => { const b = e.target.closest('[data-pi]'); if (!b) return; closePop(); items[+b.dataset.pi].fn(); };
  }

  // ── modals ───────────────────────────────────────────────
  function openModal(html, onReady) { $('#modal').className = 'modal'; $('#modal').innerHTML = html; $('#scrim').classList.add('on'); onReady && onReady($('#modal')); }
  const closeModal = () => $('#scrim').classList.remove('on');
  const mhead = (t) => `<header><h3>${esc(t)}</h3><button class="ibtn" data-close>✕</button></header>`;
  function confirmBox(title, text, okLabel, cb, danger = true) {
    openModal(`${mhead(title)}<form onsubmit="return false"><p style="margin:0;color:var(--ink2);font-size:14px">${esc(text)}</p>
      <div class="mfoot"><button type="button" class="btn" data-close>Cancel</button><button type="button" class="btn ${danger ? 'solid-danger' : 'primary'}" id="okbtn">${esc(okLabel)}</button></div></form>`,
      (m) => m.querySelector('#okbtn').onclick = () => { closeModal(); cb(); });
  }
  function promptBox(title, fields, okLabel, cb) {
    openModal(`${mhead(title)}<form>${fields.map((f) => f.type === 'select'
      ? `<div class="fld"><label>${esc(f.label)}</label><select name="${f.name}">${f.options.map((o) => `<option value="${o[0]}">${esc(o[1])}</option>`).join('')}</select></div>`
      : f.type === 'check' ? `<label class="opt"><input type="checkbox" name="${f.name}"> ${esc(f.label)}</label>`
      : `<div class="fld"><label>${esc(f.label)}</label><input type="text" name="${f.name}" placeholder="${esc(f.ph || '')}" value="${esc(f.val || '')}"></div>`).join('')}
      <div class="mfoot"><button type="button" class="btn" data-close>Cancel</button><button class="btn primary">${esc(okLabel)}</button></div></form>`,
      (m) => { const i = m.querySelector('input[type=text]'); i && i.focus(); m.querySelector('form').onsubmit = (e) => { e.preventDefault(); const fd = new FormData(e.target); const o = Object.fromEntries(fd); fields.filter((f) => f.type === 'check').forEach((f) => o[f.name] = fd.has(f.name)); closeModal(); cb(o); }; });
  }
  const genPass = () => { const c = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'; return Array.from({length: 11}, () => c[Math.floor(Math.random() * c.length)]).join(''); };

  function editModal(it) {
    const t = tabs().find((x) => x.id === it.tab_id); const d = it.data;
    const f = (label, name, val, type) => type === 'area' ? `<div class="fld"><label>${label}</label><textarea name="${name}">${esc(val || '')}</textarea></div>` : `<div class="fld"><label>${label}</label><input type="text" name="${name}" value="${esc(val || '')}"></div>`;
    const status = `<div class="fld"><label>Status</label><select name="status">${NEXT_STATUS.map((s) => `<option value="${s}" ${it.status === s ? 'selected' : ''}>${LABEL[s]}</option>`).join('')}</select></div>`;
    let body;
    if (t.kind === 'podcasts') body = f('Episode title', 'caption', d.caption) + f('Hook', 'hook', d.hook, 'area') + `<div class="two">${f('Duration', 'duration', d.duration)}${f('Pillar', 'pillar', d.pillar)}</div>${status}`;
    else if (t.kind === 'reels') body = f('Hook / title', 'caption', d.caption, 'area') + `<div class="two">${f('Format', 'format', d.format)}${f('Pillar', 'pillar', d.pillar)}</div>` + f('Script', 'script', d.script, 'area') + f('Delivery note', 'delivery', d.delivery) + f('Visual direction', 'visual', d.visual) + f('End card / CTA', 'endcard', d.endcard) + status;
    else body = (t.kind === 'posts' ? f('Hook label', 'hook', d.hook) : '') + f('Caption', 'caption', d.caption, 'area') + f('Visual direction', 'visual', d.visual) + `<div class="two">${f('Day', 'day', d.day)}${f('Time', 'time', d.time)}</div>
      <div class="fld"><label>Platforms</label><div class="checks">${['IG', 'LI', 'FB'].map((p) => `<label><input type="checkbox" name="p_${p}" ${(d.platforms || []).includes(p) ? 'checked' : ''}>${p}</label>`).join('')}</div></div>${d.story ? f('Story ideas (one per line)', 'story', d.story.join('\n'), 'area') : ''}${status}`;
    openModal(`${mhead('Edit · ' + (d.label || ''))}<form>${body}<div class="mfoot"><button type="button" class="btn" data-close>Cancel</button><button class="btn primary">Save changes</button></div></form>`,
      (m) => m.querySelector('form').onsubmit = async (e) => {
        e.preventDefault(); const o = Object.fromEntries(new FormData(e.target)); const data = {};
        Object.keys(o).forEach((k) => { if (k !== 'status' && !k.startsWith('p_') && k !== 'story') data[k] = o[k]; });
        if (t.kind !== 'podcasts' && t.kind !== 'reels') data.platforms = ['IG', 'LI', 'FB'].filter((p) => 'p_' + p in o);
        if ('story' in o) data.story = o.story.split('\n').map((s) => s.trim()).filter(Boolean);
        const out = await api('PATCH', 'items/' + it.id, {status: o.status, data}); Object.assign(it, out); closeModal(); renderBoardParts(); toast('Changes saved');
      });
  }

  // ── Strategy editor: add / remove rows, no special syntax ──
  const EX = {
    do: ['Precise, measured, editorial', 'One idea per post — no hedging', 'Name the problem before the solution'],
    dont: ['Motivational filler or inspirational fluff', 'Hustle praise or grind culture', 'Passive voice or corporate hedging'],
    pillars: [['Dependency', 'The founder in every decision, and how to dismantle it'], ['Structure', 'Workarounds become the process'], ['Clarity', 'The hidden cost of unclear briefs and rework']],
    arc_title: 'Campaign arc — May to June',
    arc: [['Week 1', 'Hook & provocation', 'Name the problem. No solution yet.'], ['Week 2', 'Insight & reframe', 'Reveal the real cause.'], ['Week 3', 'Solution & CTA', 'Structure as the answer.']],
    mix: [['Campaign posts', 'May + June', '10'], ['Casual reels', 'Bridge week', '5'], ['Podcast episodes', 'Director series', '15']],
  };
  const SPEC = {
    do: {title: 'Do', hint: 'Rules every post should follow', add: 'Add a rule', ph: ['e.g. One idea per post']},
    dont: {title: "Don't", hint: 'Things to avoid', add: 'Add a rule', ph: ['e.g. No hustle or grind culture']},
    pillars: {title: 'Content pillars', hint: 'The 3–6 themes all posts come from', add: 'Add a pillar', ph: ['Pillar name', 'What it covers'], w: '220px 1fr'},
    arc: {title: 'Campaign arc', hint: 'How the story builds over the weeks', add: 'Add a week', ph: ['Week 1', 'Title', 'What happens'], w: '110px 200px 1fr'},
    mix: {title: 'Content mix', hint: 'How much of each content type you will make', add: 'Add a content type', ph: ['Label', 'Note (optional)', '10'], w: '1fr 1fr 90px'},
  };

  function sRow(kind, vals = []) {
    const sp = SPEC[kind]; const single = kind === 'do' || kind === 'dont';
    const inputs = sp.ph.map((ph, i) => `<input type="text" data-c="${i}" placeholder="${esc(ph)}" value="${esc(vals[i] ?? '')}" ${kind === 'mix' && i === 2 ? 'inputmode="numeric"' : ''}>`).join('');
    return `<div class="srow" style="grid-template-columns:${single ? '1fr' : sp.w} 34px">${inputs}<button type="button" class="srm" title="Remove" data-srm>${ic('x')}</button></div>`;
  }

  function strategyModal(focus) {
    const t = tabs().find((x) => x.kind === 'strategy'); const m = t.meta || {};
    const data = {do: (m.do || []).map((x) => [x]), dont: (m.dont || []).map((x) => [x]), pillars: m.pillars || [], arc: m.arc || [], mix: m.mix || []};
    const sec = (kind) => `<section class="ssec" id="ss-${kind}" data-kind="${kind}">
        <div class="ssh"><div><b>${SPEC[kind].title}</b><small>${SPEC[kind].hint}</small></div><span class="cnt"></span></div>
        <div class="srows">${data[kind].map((v) => sRow(kind, v)).join('')}</div>
        <button type="button" class="addbtn" data-sadd="${kind}">${ic('plus')}${SPEC[kind].add}</button></section>`;
    openModal(`${mhead('Strategy')}<form>
      <p class="sub-note" style="margin:0">The brief every post follows. Use the <b>Add</b> buttons to add as many lines as you need — blank lines are ignored.</p>
      <div class="s2col">${sec('do')}${sec('dont')}</div>
      ${sec('pillars')}
      <section class="ssec" id="ss-arcwrap"><div class="fld" style="margin:0 0 12px"><label>Campaign arc title</label><input type="text" id="s-arc-title" value="${esc(m.arc_title || '')}" placeholder="e.g. Campaign arc — May to June"></div>${sec('arc').replace('<section class="ssec"', '<section class="ssec inner"')}</section>
      ${sec('mix')}
      <div class="mfoot sfoot2"><span style="display:flex;gap:8px;align-items:center"><button type="button" class="btn sm" id="sex">Fill with example</button><a class="btn sm ghost" href="/help#strategy" target="_blank" style="text-decoration:none">Help ↗</a></span>
        <span style="display:flex;gap:8px"><button type="button" class="btn" data-close>Cancel</button><button class="btn primary">Save strategy</button></span></div></form>`,
      (md) => {
        md.classList.add('wide');
        const count = () => md.querySelectorAll('.ssec[data-kind]').forEach((s) => {
          const n = [...s.querySelectorAll('.srow')].filter((r) => [...r.querySelectorAll('input')].some((i) => i.value.trim())).length;
          s.querySelector('.cnt').textContent = n ? `${n} added` : 'Nothing yet';
          s.classList.toggle('isempty', !s.querySelector('.srow'));
        });
        const add = (kind, vals, focusIt = true) => {
          const box = md.querySelector(`#ss-${kind} .srows`); box.insertAdjacentHTML('beforeend', sRow(kind, vals));
          const row = box.lastElementChild; if (kind === 'arc' && !vals) row.querySelector('input').value = `Week ${box.children.length}`;
          if (focusIt) { const inp = row.querySelectorAll('input')[kind === 'arc' && !vals ? 1 : 0]; inp.focus(); row.scrollIntoView({block: 'nearest'}); } count();
        };
        md.addEventListener('click', (e) => {
          const a = e.target.closest('[data-sadd]'); if (a) return add(a.dataset.sadd);
          const r = e.target.closest('[data-srm]'); if (r) { r.closest('.srow').remove(); count(); }
        });
        md.addEventListener('keydown', (e) => { // Enter in a rule box adds the next rule
          if (e.key !== 'Enter' || !e.target.matches('.srow input')) return;
          e.preventDefault(); const kind = e.target.closest('.ssec[data-kind]').dataset.kind;
          if (e.target.value.trim()) add(kind);
        });
        md.addEventListener('input', count);
        md.querySelector('#sex').onclick = () => {
          ['do', 'dont', 'pillars', 'arc', 'mix'].forEach((k) => { md.querySelector(`#ss-${k} .srows`).innerHTML = (k === 'do' || k === 'dont' ? EX[k].map((x) => [x]) : EX[k]).map((v) => sRow(k, v)).join(''); });
          md.querySelector('#s-arc-title').value = EX.arc_title; count(); toast('Example filled in — edit it, then save');
        };
        md.querySelector('form').onsubmit = async (e) => {
          e.preventDefault();
          const rows = (k) => [...md.querySelectorAll(`#ss-${k} .srow`)].map((r) => [...r.querySelectorAll('input')].map((i) => i.value.trim())).filter((v) => v.some(Boolean));
          const meta = {
            do: rows('do').map((v) => v[0]), dont: rows('dont').map((v) => v[0]),
            pillars: rows('pillars'), arc_title: md.querySelector('#s-arc-title').value.trim() || 'Campaign arc', arc: rows('arc'),
            mix: rows('mix').map((v) => [v[0], v[1], v[2]]),
          };
          const btn = md.querySelector('button.primary'); btn.disabled = true; btn.innerHTML = '<i class="spin"></i>Saving…';
          try { const out = await api('PATCH', 'tabs/' + t.id, {meta}); t.meta = out.meta; closeModal(); renderBoardParts(); toast('Strategy saved'); }
          finally { btn.disabled = false; btn.textContent = 'Save strategy'; }
        };
        count();
        if (focus && md.querySelector(`#ss-${focus}`)) { const s = md.querySelector(`#ss-${focus}`); s.scrollIntoView({block: 'center'}); s.classList.add('flashs'); if (!s.querySelector('.srow')) add(focus); else s.querySelector('input').focus(); }
      });
  }

  // ── workspace dialogs ────────────────────────────────────
  function newWorkspace() {
    promptBox('New workspace', [{label: 'Workspace / client name', name: 'name', ph: 'e.g. Acme Consulting'}, {type: 'check', label: 'Start with sample content (good for demos)', name: 'sample'}], 'Create workspace', async (o) => {
      if (!o.name.trim()) return;
      const w = await api('POST', 'workspaces', {name: o.name, sample: o.sample});
      S.accts.push(w); toast('Workspace created'); loadAccount(S.accts.length - 1);
    });
  }

  async function shareModal() {
    const a = acct(); let sh = S.data.share; let people = null; let tab = 'link';
    const draw = () => {
      const url = location.origin + '/share/' + sh.token;
      const body = tab === 'link'
        ? `<form onsubmit="return false">
            <div class="row"><div><b>Anyone with the link</b><small>A private link for ${esc(a.name)}'s client. No login needed.</small></div><label class="sw"><input type="checkbox" id="shen" ${sh.enabled ? 'checked' : ''}><i></i></label></div>
            ${sh.enabled ? `<div class="fld"><label>Link permission</label><select id="shperm"><option value="comment" ${sh.perm === 'comment' ? 'selected' : ''}>Can view &amp; comment</option><option value="view" ${sh.perm === 'view' ? 'selected' : ''}>View only</option></select></div>
            <div class="fld"><label>Client link</label><div class="copyrow"><input type="text" readonly value="${esc(url)}" onclick="this.select()"><button type="button" class="btn" id="cplink">${ic('copy')}Copy</button></div></div>
            <div class="row"><div><b>Regenerate link</b><small>The old link will stop working immediately.</small></div><button type="button" class="btn sm" id="regen">Regenerate</button></div>` : '<p class="sub-note">Link sharing is off. Turn it on to give your client a private link.</p>'}
            <div class="mfoot"><a class="btn" href="${esc(url)}" target="_blank" ${sh.enabled ? '' : 'style="display:none"'}>${ic('eye')}Preview client view</a><button type="button" class="btn primary" data-close>Done</button></div></form>`
        : `<form onsubmit="return false">
            <div class="fld"><label>Invite someone to this workspace</label>
              <div class="two"><input type="text" id="pn" placeholder="Name"><input type="text" id="pe" placeholder="Email"></div></div>
            <div class="copyrow"><select id="pp" style="flex:1;border:1px solid var(--border);border-radius:4px;padding:9px 11px;font-size:13px;background:var(--surface)"><option value="comment">Can comment</option><option value="edit">Can edit</option></select><button type="button" class="btn primary" id="padd">Invite</button></div>
            <div id="pnew"></div>
            <div style="margin-top:6px">${people === null ? '<div class="sk" style="height:46px"></div>' : people.length ? people.map((p) => `<div class="row"><div class="who" style="display:flex;gap:10px;align-items:center">${avatar(p.name)}<div><b>${esc(p.name)}</b><small>${esc(p.email)}</small></div></div><div style="display:flex;gap:8px;align-items:center"><span class="stag ${p.perm === 'edit' ? 'edit' : 'grey'}">${p.perm === 'edit' ? 'Can edit' : 'Can comment'}</span><button type="button" class="ibtn" data-rp="${p.id}" title="Remove">${ic('trash')}</button></div></div>`).join('') : '<p class="sub-note">Nobody has been invited yet.</p>'}</div>
            <div class="mfoot"><button type="button" class="btn primary" data-close>Done</button></div></form>`;
      openModal(`${mhead('Share · ' + a.name)}<div class="mtabs"><button data-st="link" class="${tab === 'link' ? 'on' : ''}">Link</button><button data-st="people" class="${tab === 'people' ? 'on' : ''}">People</button></div>${body}`, (m) => {
        m.querySelectorAll('[data-st]').forEach((b) => b.onclick = async () => { tab = b.dataset.st; draw(); if (tab === 'people' && people === null) { people = await api('GET', `accounts/${a.id}/people`); draw(); } });
        const up = async (patch) => { sh = await api('PATCH', `accounts/${a.id}/share`, patch); S.data.share = sh; draw(); };
        const en = m.querySelector('#shen'); en && (en.onchange = () => up({enabled: en.checked}));
        const pe = m.querySelector('#shperm'); pe && (pe.onchange = () => up({perm: pe.value}));
        const cp = m.querySelector('#cplink'); cp && (cp.onclick = () => { navigator.clipboard && navigator.clipboard.writeText(url0()); toast('Link copied'); });
        const rg = m.querySelector('#regen'); rg && (rg.onclick = () => up({regenerate: true}));
        const pa = m.querySelector('#padd');
        pa && (pa.onclick = async () => {
          const o = {name: m.querySelector('#pn').value.trim(), email: m.querySelector('#pe').value.trim(), perm: m.querySelector('#pp').value};
          if (!o.name || !o.email) return toast('Enter a name and email', false);
          pa.disabled = true; let r; try { r = await api('POST', `accounts/${a.id}/people`, o); } catch (x) { pa.disabled = false; return; }
          people = (people || []).filter((p) => p.id !== r.id).concat([r]); draw();
          if (r.password) m2cred(r);
        });
        m.querySelectorAll('[data-rp]').forEach((b) => b.onclick = async () => { await api('DELETE', `accounts/${a.id}/people/${b.dataset.rp}`); people = people.filter((p) => p.id !== +b.dataset.rp); draw(); toast('Access removed'); });
      });
    };
    const url0 = () => location.origin + '/share/' + sh.token;
    const m2cred = (r) => { const el = $('#pnew'); if (el) el.innerHTML = `<div class="cred" style="margin-top:12px"><b>${esc(r.name)} can now sign in</b><span>Email: <code>${esc(r.email)}</code></span><span>Temporary password: <code>${esc(r.password)}</code></span><span class="sub-note">Share these details securely — the password is shown only once.</span></div>`; };
    draw();
  }

  // ── team page ────────────────────────────────────────────
  function renderTeam() {
    const rows = S.team.slice(0, S.teamLimit);
    $('#content').innerHTML = `<div class="pagehead"><div><h1>Team</h1><p>People in ${esc(AG.name)} who can manage every workspace.</p></div>${IS_OWNER ? `<button class="btn primary" data-a="addmember">${ic('plus')}Add team member</button>` : ''}</div>
      <table class="tbl"><thead><tr><th>Member</th><th>Email</th><th>Role</th><th></th></tr></thead><tbody>
      ${rows.map((m) => `<tr><td><div class="who">${avatar(m.name)}<div><b>${esc(m.name)}${m.you ? ' <span style="color:var(--mute);font-weight:400">(you)</span>' : ''}</b></div></div></td><td>${esc(m.email)}</td>
        <td><span class="stag ${m.role === 'owner' ? 'edit' : 'grey'}">${m.role === 'owner' ? 'Owner' : 'Member'}</span></td>
        <td><div class="acts">${IS_OWNER || m.you ? `<button class="btn sm" data-tedit="${m.id}">${ic('edit')}Edit</button>` : ''}${IS_OWNER && m.role === 'member' ? `<button class="btn sm danger" data-rm="${m.id}">Remove</button>` : ''}</div></td></tr>`).join('')}</tbody></table>
      ${moreBtn(rows.length, S.team.length, 'data-a="teammore"')}
      <p class="sub-note" style="margin-top:18px">To give a <b>client</b> access to a single workspace, open the workspace and use <b>Share → People</b>.</p>`;
  }

  function editMember(m) {
    openModal(`${mhead('Edit team member')}<form>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:2px">${avatar(m.name)}<div><b>${esc(m.name)}</b><div class="sub-note">${m.role === 'owner' ? 'Agency owner' : 'Team member'}${m.you ? ' · this is you' : ''}</div></div></div>
      <div class="two"><div class="fld"><label>Name</label><input type="text" name="name" value="${esc(m.name)}" required></div>
        <div class="fld"><label>Email (login)</label><input type="text" name="email" value="${esc(m.email)}" required></div></div>
      <div class="fld"><label>New password <span style="font-weight:400">· leave blank to keep the current one</span></label>
        <div class="copyrow"><input type="text" name="password" placeholder="At least 8 characters" autocomplete="new-password"><button type="button" class="btn" id="rg2">Generate</button></div></div>
      <p class="sub-note" style="margin:0">${m.role === 'owner' ? 'The owner role cannot be changed here.' : 'Team members can manage every workspace but not agency settings.'}</p>
      <div class="mfoot"><button type="button" class="btn" data-close>Cancel</button><button class="btn primary">Save changes</button></div></form>`, (md) => {
      md.querySelector('#rg2').onclick = () => { md.querySelector('[name=password]').value = genPass(); };
      md.querySelector('form').onsubmit = async (e) => {
        e.preventDefault(); const o = Object.fromEntries(new FormData(e.target)); if (!o.password) delete o.password;
        const btn = md.querySelector('button.primary'); btn.disabled = true; btn.innerHTML = '<i class="spin"></i>Saving…';
        try {
          const r = await api('PATCH', 'team/' + m.id, o);
          Object.assign(m, r); if (r.you) { ME.name = r.name; ME.email = r.email; renderSide(); }
          closeModal(); renderTeam(); toast('Team member updated');
          if (o.password) openModal(`${mhead('Password changed')}<form onsubmit="return false"><div class="cred"><span><b>${esc(r.name)}</b> signs in at <b>${location.origin}/login</b></span><span>Email: <code>${esc(r.email)}</code></span><span>New password: <code>${esc(o.password)}</code></span></div><div class="mfoot"><button class="btn primary" data-close>Done</button></div></form>`);
        } catch (x) { btn.disabled = false; btn.textContent = 'Save changes'; }
      };
    });
  }

  // ── documents module ─────────────────────────────────────
  function initDocState() {
    if (!S.docs.tid || !S.tpls.some((t) => t.id === S.docs.tid)) S.docs.tid = S.tpls[0] ? S.tpls[0].id : null;
    if (!S.set.draft && S.tpls[0]) S.set.draft = JSON.parse(JSON.stringify(S.tpls[0]));
  }
  function docQS(src) {
    const a = acct(); const p = new URLSearchParams({account: a.id});
    if (src.cfg) { p.set('cfg', JSON.stringify(src.cfg)); p.set('name', src.name); } else p.set('template', src.tid);
    if (src.tabs) p.set('tabs', src.tabs.join(','));
    if (src.status && src.status !== 'all') p.set('status', src.status);
    if (src.notes !== undefined) p.set('notes', src.notes ? 1 : 0);
    if (src.sample === true) p.set('sample', 1); else if (src.sample === false) p.set('sample', 0);
    return p.toString();
  }
  function iframeBox(qs, id) {
    return `<div class="previewbox"><div class="pl" id="${id}-pl"><div style="text-align:center;color:var(--mute)"><i class="spin" style="border-color:rgba(0,0,0,.15);border-top-color:var(--accent);margin:0 auto 10px;display:block;width:22px;height:22px"></i>Rendering preview…</div></div><iframe id="${id}" src="/docs/preview?${qs}" onload="document.getElementById('${id}-pl').classList.add('hide')"></iframe></div>`;
  }
  function refreshFrame(id, qs) {
    const f = $('#' + id); if (!f) return; $('#' + id + '-pl').classList.remove('hide'); f.src = '/docs/preview?' + qs;
  }

  function renderDocs() {
    if (!acct()) { $('#content').innerHTML = `<div class="empty" style="margin-top:40px"><div class="eic">${ic('doc')}</div><h3>Create a workspace first</h3><p>Documents are generated from a workspace's data.</p>${can('workspaces.create') ? `<button class="btn primary" data-a="newws">${ic('plus')}New workspace</button>` : ''}</div>`; return; }
    const d = S.docs; const ts = S.data ? tabs() : [];
    const hasItems = ts.some((t) => t.items.length);
    if (d.sample === null && !hasItems) d.sample = true;
    $('#content').innerHTML = `<div class="pagehead"><div><h1>Documents</h1><p>Generate reports from <b>${esc(acct().name)}</b>'s live data — as PDF, Word or spreadsheet.</p></div>
      ${can('templates.manage') ? `<button class="btn" data-page="settings" data-sec="templates">${ic('cog')}Manage templates</button>` : ''}</div>
      <div class="docgrid"><div class="sidepanel">
        <div class="fld"><label>Template</label><select id="dtpl">${S.tpls.map((t) => `<option value="${t.id}" ${t.id === d.tid ? 'selected' : ''}>${esc(t.name)}</option>`).join('')}</select></div>
        <div class="fld"><label>Include sections</label><div class="chk">${ts.map((t) => `<label><input type="checkbox" data-dt="${t.id}" ${!d.tabs || d.tabs.includes(t.id) ? 'checked' : ''}>${esc(t.label)}<span style="margin-left:auto;color:var(--faint);font-size:12px">${t.items.length || ''}</span></label>`).join('')}</div></div>
        <div class="fld"><label>Status</label><select id="dst"><option value="all">All statuses</option>${NEXT_STATUS.map((s) => `<option value="${s}" ${d.status === s ? 'selected' : ''}>${LABEL[s]} only</option>`).join('')}</select></div>
        <label class="opt"><input type="checkbox" id="dnotes" ${d.notes ? 'checked' : ''}> Include notes &amp; comments</label>
        ${hasItems ? `<label class="opt"><input type="checkbox" id="dsample" ${d.sample ? 'checked' : ''}> Preview with sample data</label>` : '<p class="sub-note" style="margin:0">This workspace is empty, so sample data is shown.</p>'}
        <div><h3 style="margin-bottom:8px">Export</h3><div class="exp"><button class="btn" data-exp="pdf">${ic('dl')}PDF</button><button class="btn" data-exp="doc">${ic('dl')}Word</button><button class="btn" data-exp="csv">${ic('dl')}CSV</button></div></div>
      </div><div><div class="pbar"><span>Live preview — exactly what you'll export</span></div>${iframeBox(docQS(d), 'dframe')}</div></div>`;
  }
  let docT = null;
  const docRefresh = () => { clearTimeout(docT); docT = setTimeout(() => refreshFrame('dframe', docQS(S.docs)), 250); };

  async function exportDoc(fmt, btn) {
    const old = btn.innerHTML; btn.innerHTML = '<i class="spin" style="border-color:rgba(0,0,0,.18);border-top-color:var(--accent)"></i>'; btn.disabled = true; np.start();
    try {
      const res = await fetch(`/docs/export/${fmt}?` + docQS(S.docs), {headers: {Accept: '*/*'}});
      if (!res.ok) throw new Error('export failed');
      const blob = await res.blob(); const cd = res.headers.get('Content-Disposition') || ''; const name = (cd.match(/filename="?([^";]+)"?/) || [])[1] || `document.${fmt}`;
      const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = name; document.body.appendChild(a); a.click(); a.remove(); setTimeout(() => URL.revokeObjectURL(a.href), 4000);
      toast(fmt.toUpperCase() + ' downloaded');
    } catch (e) { toast('Could not create the document', false); } finally { np.done(); btn.innerHTML = old; btn.disabled = false; }
  }

  // ── settings page ────────────────────────────────────────
  const SECTIONS = [['strategy', 'Strategy brief'], ['kpis', 'Progress summary'], ['charts', 'Charts'], ['tabs', 'Content by section'], ['calendar', 'Content calendar'], ['approval', 'Client approval sheet']];
  const LAYOUT_SVG = {
    normal: '<svg viewBox="0 0 42 30"><rect x="3" y="4" width="36" height="3" rx="1" fill="currentColor" opacity=".7"/><rect x="3" y="11" width="36" height="2" rx="1" fill="currentColor" opacity=".35"/><rect x="3" y="16" width="36" height="2" rx="1" fill="currentColor" opacity=".35"/><rect x="3" y="21" width="36" height="2" rx="1" fill="currentColor" opacity=".35"/></svg>',
    graph: '<svg viewBox="0 0 42 30"><rect x="4" y="16" width="6" height="10" fill="currentColor" opacity=".7"/><rect x="14" y="8" width="6" height="18" fill="currentColor" opacity=".7"/><rect x="24" y="12" width="6" height="14" fill="currentColor" opacity=".7"/><rect x="34" y="4" width="5" height="22" fill="currentColor" opacity=".4"/></svg>',
    detailed: '<svg viewBox="0 0 42 30"><rect x="3" y="3" width="36" height="11" rx="2" fill="none" stroke="currentColor" opacity=".6"/><rect x="3" y="17" width="36" height="11" rx="2" fill="none" stroke="currentColor" opacity=".6"/><rect x="6" y="6" width="18" height="2" fill="currentColor" opacity=".6"/><rect x="6" y="20" width="22" height="2" fill="currentColor" opacity=".6"/></svg>',
  };

  function renderSettings() {
    let sec = S.set.sec;
    if (sec === 'profile' && !can('settings.manage')) sec = 'templates';
    if (sec === 'templates' && !can('templates.manage') && can('settings.manage')) sec = 'profile';
    S.set.sec = sec;
    $('#content').innerHTML = `<div class="pagehead"><div><h1>Settings</h1><p>Agency profile and document templates.</p></div></div>
      <div class="tabs" style="margin-bottom:22px">
        ${can('settings.manage') ? `<button class="tab ${sec === 'profile' ? 'on' : ''}" data-sec="profile">Agency profile</button>` : ''}
        ${can('templates.manage') ? `<button class="tab ${sec === 'templates' ? 'on' : ''}" data-sec="templates">Document templates</button>` : ''}
      </div>
      <div id="setbody"></div>`;
    sec === 'profile' ? settingsProfile() : settingsTemplates();
  }

  function settingsProfile() {
    $('#setbody').innerHTML = `<div class="card-p"><h3>Agency profile</h3><p>Your name and brand colour appear across the workspace, client links and documents.</p>
      <form id="pf" style="display:grid;gap:16px;max-width:460px"><div class="fld"><label>Agency name</label><input type="text" name="name" value="${esc(AG.name)}" required></div>
      <div class="fld"><label>Brand colour</label><div class="colorrow"><input type="color" name="brand_color" value="${esc(AG.color)}"><span class="sub-note">Used for buttons, highlights and document headers.</span></div></div>
      <div><button class="btn primary">Save profile</button></div></form></div>`;
    const col = $('#pf [name=brand_color]'); col.oninput = () => document.documentElement.style.setProperty('--accent', col.value);
    $('#pf').onsubmit = async (e) => { e.preventDefault(); const o = Object.fromEntries(new FormData(e.target)); const r = await api('PATCH', 'agency', o); AG.name = r.name; AG.color = r.color; document.documentElement.style.setProperty('--accent', r.color); renderSide(); renderBar(); toast('Profile saved'); };
  }

  const SECTION_HELP = {strategy: 'Brand voice, pillars, campaign arc', kpis: 'Totals and completion at a glance', charts: 'Status and platform graphs', tabs: 'A table or cards for each tab', calendar: 'Day-by-day publishing schedule', approval: 'Items waiting for approval, with sign-off'};
  const LAYOUT_NAME = {normal: 'Normal', graph: 'Graph', detailed: 'Detailed'};
  const LAYOUT_HELP = {normal: 'Compact tables — simple and clean.', graph: 'Tables plus status and platform charts.', detailed: 'Full cards with visual direction and notes.'};

  function settingsTemplates() {
    const dr = S.set.draft; const a = acct(); const c = dr ? dr.config : null;
    const list = S.tpls.map((t) => `<button class="titem ${dr && dr.id === t.id ? 'on' : ''}" data-tpl="${t.id}"><b>${esc(t.name)}</b><small><span class="lay">${LAYOUT_NAME[t.config.layout]}</span>${t.config.sections.length} section${t.config.sections.length === 1 ? '' : 's'} · ${t.config.orientation}</small></button>`).join('')
      + (dr && !dr.id ? `<button class="titem on"><b>${esc(dr.name || 'New template')}</b><small><span class="lay">Unsaved</span>not saved yet</small></button>` : '');

    const editor = !dr ? `<div class="empty" style="margin:18px"><div class="eic">${ic('doc')}</div><h3>No template selected</h3><p>Pick one on the left or create a new template.</p></div>` : `
      <div class="thead2"><input type="text" id="tname" value="${esc(dr.name)}" placeholder="Template name" aria-label="Template name"></div>
      <div class="tbody">
        <div class="grp"><h4>Style</h4>
          <div class="layouts">${['normal', 'graph', 'detailed'].map((l) => `<button type="button" data-lay="${l}" class="${c.layout === l ? 'on' : ''}">${LAYOUT_SVG[l]}${LAYOUT_NAME[l]}</button>`).join('')}</div>
          <p class="sub-note" style="margin:8px 0 0">${LAYOUT_HELP[c.layout]}</p></div>
        <div class="grp"><h4>Sections <span>${c.sections.length} selected</span></h4>
          <div class="seclist">${SECTIONS.map((x) => `<label class="secrow"><input type="checkbox" data-sec2="${x[0]}" ${c.sections.includes(x[0]) ? 'checked' : ''}><span><b>${x[1]}</b><small>${SECTION_HELP[x[0]]}</small></span></label>`).join('')}</div></div>
        <div class="grp"><h4>Page</h4>
          <div class="two"><div class="fld"><label>Orientation</label><select id="tori" data-search="99"><option value="portrait" ${c.orientation === 'portrait' ? 'selected' : ''}>Portrait</option><option value="landscape" ${c.orientation === 'landscape' ? 'selected' : ''}>Landscape</option></select></div>
          <div class="fld"><label>Paper</label><select id="tpap" data-search="99"><option value="a4" ${c.paper === 'a4' ? 'selected' : ''}>A4</option><option value="letter" ${c.paper === 'letter' ? 'selected' : ''}>Letter</option></select></div></div></div>
        <div class="grp"><h4>Content</h4>
          <div class="row"><div><b>Show charts</b><small>Used when the Charts section is on</small></div><label class="sw"><input type="checkbox" id="tcharts" ${c.charts ? 'checked' : ''}><i></i></label></div>
          <div class="row"><div><b>Include notes &amp; comments</b><small>Adds a notes column or line</small></div><label class="sw"><input type="checkbox" id="tnotes" ${c.show_notes ? 'checked' : ''}><i></i></label></div></div>
        <div class="grp" style="border:0"><h4>Header &amp; footer</h4>
          <div class="fld"><label>Header text</label><input type="text" id="thead" value="${esc(c.header || '')}" placeholder="e.g. Confidential — for client review"></div>
          <div class="fld" style="margin-top:12px"><label>Footer text <span style="font-weight:400">· {brand} = platform name</span></label><input type="text" id="tfoot" value="${esc(c.footer || '')}"></div></div>
      </div>
      <div class="tfoot2"><button type="button" class="btn primary" id="tsave">Save template</button>${dr.id ? `<button type="button" class="btn" id="tdup">Duplicate</button><span class="sp"></span><button type="button" class="btn danger" id="tdel">Delete</button>` : ''}</div>`;

    const preview = !a ? `<div class="empty" style="margin:18px"><h3>Create a workspace to preview</h3><p>Previews use a workspace's data.</p></div>`
      : dr ? `<div class="phead2"><div><b>Live preview</b><small>${esc(a.name)}</small></div><label class="opt" style="margin:0"><input type="checkbox" id="tsample" ${S.set.sample ? 'checked' : ''}> Use sample data</label></div>${iframeBox(tplQS(), 'tframe')}`
      : '';

    $('#setbody').innerHTML = `<div class="tplws">
      <aside class="tcol tlist"><div class="thead3"><b>Templates</b><button class="btn sm" data-a="newtpl">${ic('plus')}New</button></div><div class="titems">${list}</div></aside>
      <section class="tcol tedit">${editor}</section>
      <section class="tcol tprev">${preview}</section></div>`;
  }
  const tplQS = () => { const dr = S.set.draft; return docQS({cfg: dr.config, name: dr.name || 'Template preview', sample: S.set.sample ? true : undefined, notes: dr.config.show_notes}); };
  let tplT = null;
  const tplRefresh = () => { clearTimeout(tplT); tplT = setTimeout(() => acct() && refreshFrame('tframe', tplQS()), 300); };

  // ── notes drawer (latest first-load, "load earlier" for history) ──
  const noteHtml = (n) => `<div class="msg ${n.role}" data-nid="${n.id}">${avatar(n.author)}<div class="bub"><small><b>${esc(n.author)}</b><span>${esc(n.at)}</span></small><p>${esc(n.body)}</p></div></div>`;
  const NOTE_STEP = 10;

  function renderDrawer() {
    const notes = S.data.notes; const total = S.data.notes_total ?? notes.length; const left = Math.max(0, total - notes.length);
    const list = notes.map(noteHtml).join('') || `<div class="empty" style="padding:34px 14px"><div class="eic">${ic('bell')}</div><h3>No notes yet</h3><p>Messages between the agency and client appear here.</p></div>`;
    const more = left > 0 ? `<button class="loadold" id="loadold" type="button">${ic('clock')}Load earlier notes<span>${left} more</span></button>` : (total > NOTE_STEP ? '<div class="allold">Start of conversation</div>' : '');
    const guest = CLIENT;
    $('#drawer').innerHTML = `<header><div><h3>${canEdit() ? 'Team notes' : 'Notes & comments'}</h3><small>${esc(acct().name)} · ${total} note${total === 1 ? '' : 's'}</small></div><button class="ibtn" data-closedrawer>✕</button></header>
      <div class="dlist" id="dlist">${more}<div id="nlist" style="display:flex;flex-direction:column;gap:14px">${list}</div></div>
      ${canComment() ? `<form class="dform" id="noteform">${guest ? '<input name="author" placeholder="Your name" maxlength="60">' : ''}<textarea name="body" placeholder="Write a note…" required></textarea><button class="btn primary" style="justify-content:center">Post note</button></form>` : '<div class="dform"><span class="sub-note">This link is view-only.</span></div>'}`;
    const dl = $('#dlist'); dl.scrollTop = dl.scrollHeight;

    const lo = $('#loadold');
    if (lo) lo.onclick = async () => {
      if (lo.disabled) return; lo.disabled = true; lo.classList.add('loading'); lo.innerHTML = '<i class="spin" style="border-color:rgba(0,0,0,.18);border-top-color:var(--ink)"></i>Loading earlier notes…';
      const oldest = S.data.notes[0];
      try {
        const [r] = await Promise.all([api('GET', `accounts/${acct().id}/notes?before=${oldest.id}&limit=${NOTE_STEP}`), wait(450)]);
        const prevH = dl.scrollHeight, prevTop = dl.scrollTop;
        S.data.notes = [...r.notes, ...S.data.notes]; S.data.notes_total = r.total;
        $('#nlist').insertAdjacentHTML('afterbegin', r.notes.map(noteHtml).join(''));
        const remaining = Math.max(0, r.total - S.data.notes.length);
        if (remaining > 0) { lo.disabled = false; lo.classList.remove('loading'); lo.innerHTML = `${ic('clock')}Load earlier notes<span>${remaining} more</span>`; }
        else lo.outerHTML = '<div class="allold">Start of conversation</div>';
        dl.scrollTop = prevTop + (dl.scrollHeight - prevH); // keep the reader where they were
      } catch (e) { lo.disabled = false; lo.classList.remove('loading'); lo.innerHTML = `${ic('clock')}Try again`; }
    };

    const nf = $('#noteform'); nf && (nf.onsubmit = async (e) => {
      e.preventDefault(); const o = Object.fromEntries(new FormData(e.target)); if (!o.body.trim()) return;
      const btn = nf.querySelector('button'); btn.disabled = true;
      try {
        const r = await api('POST', 'accounts/' + acct().id + '/notes', o);
        S.data.notes.push(r.note); S.data.notes_total = r.total;
        const nl = $('#nlist'); if (nl.querySelector('.empty')) nl.innerHTML = '';
        nl.insertAdjacentHTML('beforeend', noteHtml(r.note)); nf.querySelector('textarea').value = ''; dl.scrollTop = dl.scrollHeight;
        renderBar(); renderSide(); toast('Note posted');
      } finally { btn.disabled = false; }
    });
  }

  // ── command palette ──────────────────────────────────────
  const CK = {sel: 0, list: []};
  function openCmd() {
    if (!acct()) return;
    $('#cmd').innerHTML = `<div class="in">${ic('search')}<input id="cmdin" placeholder="Search posts, jump to a tab, or run a command…" autocomplete="off"><span class="kbd">Esc</span></div><div class="list" id="cmdlist"></div><div class="foot"><span>↑↓ navigate</span><span>↵ select</span><span>Esc close</span></div>`;
    $('#cmdk').classList.add('on'); const inp = $('#cmdin'); inp.focus(); cmdList('');
    inp.oninput = () => cmdList(inp.value);
    inp.onkeydown = (e) => {
      if (e.key === 'ArrowDown') { CK.sel = Math.min(CK.sel + 1, CK.list.length - 1); paintCmd(); e.preventDefault(); }
      else if (e.key === 'ArrowUp') { CK.sel = Math.max(CK.sel - 1, 0); paintCmd(); e.preventDefault(); }
      else if (e.key === 'Enter') { const it = CK.list[CK.sel]; if (it) { closeCmd(); it.fn(); } }
    };
  }
  const closeCmd = () => $('#cmdk').classList.remove('on');
  function cmdList(q) {
    q = q.trim().toLowerCase(); const out = [];
    const add = (g, icon, label, sub, fn) => { if (!q || (label + ' ' + sub).toLowerCase().includes(q)) out.push({g, icon, label, sub, fn}); };
    if (S.data) tabs().forEach((t) => add('Jump to', t.kind === 'reels' ? 'film' : t.kind === 'podcasts' ? 'mic' : 'cal', t.label, `${t.items.length} items`, () => goTab(t.id)));
    if (!CLIENT) S.accts.forEach((a, i) => add('Workspaces', 'arrow', a.name, 'Open workspace', () => loadAccount(i)));
    if (!CLIENT && IS_STAFF) { if (can('documents.export')) add('Go to', 'doc', 'Documents', 'Reports & exports', () => goPage('docs')); add('Go to', 'users', 'Team', 'People & roles', () => { location.href = '/team'; }); if (can('roles.manage')) add('Go to', 'cog', 'Roles & permissions', '', () => { location.href = '/roles'; }); if (can('settings.manage') || can('templates.manage')) add('Go to', 'cog', 'Settings', 'Templates & profile', () => goPage('settings')); }
    add('Actions', 'bell', 'Open notes', '', () => { renderDrawer(); $('#drawer').classList.add('on'); });
    add('Actions', 'board', 'Switch to board view', 'Kanban by status', () => setView('board'));
    add('Actions', 'cards', 'Switch to card view', '', () => setView('cards'));
    if (canEdit() && !CLIENT) { add('Actions', 'plus', 'Add row to this tab', '', () => act('addrow')); add('Actions', 'share', 'Share workspace', '', () => act('share')); }
    if (q && S.data) { let n = 0; tabs().forEach((t) => t.items.forEach((it) => { if (n < 8 && (it.data.caption || '').toLowerCase().includes(q)) { n++; out.push({g: 'Posts', icon: 'doc', label: (it.data.caption || '').slice(0, 70), sub: t.label + ' · ' + (it.data.label || ''), fn: () => jumpTo(it)}); } })); }
    CK.list = out; CK.sel = 0; paintCmd();
  }
  function paintCmd() {
    const l = $('#cmdlist'); if (!l) return;
    if (!CK.list.length) { l.innerHTML = '<div class="none">No results</div>'; return; }
    let g = ''; l.innerHTML = CK.list.map((it, i) => { const h = it.g !== g ? `<div class="grp">${it.g}</div>` : ''; g = it.g; return `${h}<button class="it ${i === CK.sel ? 'sel' : ''}" data-ci="${i}"><span class="ic">${ic(it.icon)}</span>${esc(it.label)}<small>${esc(it.sub)}</small></button>`; }).join('');
    const s = l.querySelector('.sel'); if (s) s.scrollIntoView({block: 'nearest'});
    l.onclick = (e) => { const b = e.target.closest('[data-ci]'); if (b) { const it = CK.list[+b.dataset.ci]; closeCmd(); it.fn(); } };
  }
  async function jumpTo(it) {
    await goTab(it.tab_id); S.view = 'cards'; S.limit = 9999; renderTb(); renderView();
    const el = document.querySelector(`[data-id="${it.id}"]`);
    if (el) { el.scrollIntoView({behavior: 'smooth', block: 'center'}); el.classList.add('flash'); setTimeout(() => el.classList.remove('flash'), 1700); }
  }

  // ── actions ──────────────────────────────────────────────
  function setView(v) { S.view = v; S.animate = true; S.limit = PAGE; S.kl = {}; try { localStorage.setItem('view', v); } catch (e) {} renderTb(); renderView(); }

  async function act(a, el) {
    const t = S.data && S.page === 'board' ? curTab() : null;
    switch (a) {
      case 'burger': return $('#side').classList.toggle('open');
      case 'cmd': return openCmd();
      case 'notes': renderDrawer(); return $('#drawer').classList.add('on');
      case 'wsall': S.wsAll = !S.wsAll; return renderSide();
      case 'newws': return newWorkspace();
      case 'more': return openPop(el, [
        {icon: 'plus', label: 'Add tab', fn: () => act('addtab')},
        ...(can('workspaces.edit') ? [{icon: 'edit', label: 'Rename workspace', fn: () => promptBox('Rename workspace', [{label: 'Name', name: 'name', val: acct().name}], 'Save', async (o) => { const r = await api('PATCH', 'workspaces/' + acct().id, {name: o.name}); acct().name = r.name; S.data.name = r.name; renderBoardParts(); toast('Renamed'); })}] : []),
        ...(can('workspaces.delete') ? ['-', {icon: 'trash', label: 'Delete workspace', danger: true, fn: () => confirmBox('Delete workspace', `Permanently delete ${acct().name} and all of its content?`, 'Delete workspace', async () => { const id = acct().id; await api('DELETE', 'workspaces/' + id); S.accts.splice(S.ai, 1); delete S.cache[id]; S.data = null; toast('Workspace deleted'); if (S.accts.length) loadAccount(0); else { S.ai = 0; S.page = 'board'; render(); } })}] : []),
      ]);
      case 'share': return shareModal();
      case 'addrow': {
        if (!t || t.kind === 'strategy') return toast('Pick a month or campaign tab first', false);
        const out = await api('POST', 'tabs/' + t.id + '/items'); t.items.push(...out); S.view = 'cards'; S.limit = Math.max(S.limit, 9999); renderBoardParts();
        const last = document.querySelector(`[data-id="${out[out.length - 1].id}"]`); if (last) { last.scrollIntoView({behavior: 'smooth', block: 'center'}); last.classList.add('flash'); }
        return toast('Row added');
      }
      case 'addtab': return promptBox('Add tab', [{label: 'Tab name', name: 'label', ph: 'e.g. November 2026'}, {type: 'select', label: 'Layout', name: 'kind', options: [['posts', 'Posts (cards with hook)'], ['grid', 'Grid (three columns)'], ['reels', 'Reels (scripts)'], ['podcasts', 'Podcast episodes']]}], 'Add tab', async (o) => {
        if (!o.label.trim()) return;
        const r = await api('POST', `accounts/${acct().id}/tabs`, {label: o.label, kind: o.kind}); S.data = r.payload; cache(S.data); S.tab = r.tab; S.animate = true; S.filter = 'all'; S.limit = PAGE; renderBoardParts(); toast('Tab added');
      });
      case 'adddoc': return promptBox('Add master doc link', [{label: 'Title', name: 'title', ph: 'Brand guidelines'}, {label: 'URL', name: 'url', ph: 'https://…'}], 'Add link', async (o) => {
        const docs = [...(S.data.docs || []), {title: o.title || 'Doc', url: o.url}]; S.data.docs = await api('PUT', 'accounts/' + acct().id + '/docs', {docs}); renderHead();
      });
      case 'editstrategy': return strategyModal(el && el.dataset ? el.dataset.focus : undefined);
      case 'loadmore': S.loadingMore = true; renderView(); await wait(350); S.loadingMore = false; S.limit += PAGE; return renderView();
      case 'teammore': S.teamLimit += 8; return renderTeam();
      case 'addmember': return openModal(`${mhead('Add team member')}<form>
          <div class="two"><div class="fld"><label>Name</label><input type="text" name="name" required></div><div class="fld"><label>Email (login)</label><input type="text" name="email" required></div></div>
          <div class="fld"><label>Password</label><div class="copyrow"><input type="text" name="password" value="${genPass()}"><button type="button" class="btn" id="rg">Generate</button></div></div>
          <p class="sub-note" style="margin:0">Team members can manage every workspace, but not agency settings or other members.</p>
          <div class="mfoot"><button type="button" class="btn" data-close>Cancel</button><button class="btn primary">Add member</button></div></form>`, (m) => {
        m.querySelector('#rg').onclick = () => m.querySelector('[name=password]').value = genPass();
        m.querySelector('form').onsubmit = async (e) => {
          e.preventDefault(); const o = Object.fromEntries(new FormData(e.target)); const r = await api('POST', 'team', o);
          S.team.push(r); closeModal(); renderTeam();
          openModal(`${mhead('Member added')}<form onsubmit="return false"><div class="cred"><span><b>${esc(r.name)}</b> can sign in at <b>${location.origin}/login</b></span><span>Email: <code>${esc(r.email)}</code></span><span>Password: <code>${esc(r.password)}</code></span></div><div class="mfoot"><button class="btn primary" data-close>Done</button></div></form>`);
        };
      });
      case 'newtpl': { S.set.draft = {id: null, name: 'New template', config: {layout: 'normal', orientation: 'portrait', paper: 'a4', sections: ['kpis', 'tabs'], charts: true, show_notes: true, header: '', footer: 'Prepared with {brand}'}}; return renderSettings(); }
    }
  }

  // ── events ───────────────────────────────────────────────
  document.addEventListener('click', async (e) => {
    if (!e.target.closest('.pop')) closePop();
    const el = e.target.closest('[data-a],[data-ai],[data-page],[data-tab],[data-f],[data-v],[data-cycle],[data-edit],[data-lock],[data-del],[data-up],[data-deltab],[data-close],[data-closedrawer],[data-kmore],[data-rm],[data-tedit],[data-exp],[data-sec],[data-tpl],[data-lay],#tsave,#tdup,#tdel');
    if (!el) { if (e.target.id === 'scrim') closeModal(); if (e.target.id === 'cmdk') closeCmd(); if (!e.target.closest('#side,.burger')) $('#side').classList.remove('open'); return; }
    const D = el.dataset;

    if ('close' in D) return closeModal();
    if ('closedrawer' in D) return $('#drawer').classList.remove('on');
    if (D.sec !== undefined && D.page === undefined) { S.set.sec = D.sec; return renderSettings(); }
    if (D.page) { if (D.sec) S.set.sec = D.sec; if (D.page === S.page) return renderSettings && S.page === 'settings' ? renderSettings() : undefined; return goPage(D.page); }
    if (D.a) { e.stopPropagation(); return act(D.a, el); }
    if (D.ai !== undefined) return loadAccount(+D.ai);
    if (D.exp) return exportDoc(D.exp, el);
    if (D.tedit) return editMember(S.team.find((x) => x.id === +D.tedit));
    if (D.rm) { const m = S.team.find((x) => x.id === +D.rm); return confirmBox('Remove member', `${m.name} will lose access to ${AG.name}.`, 'Remove', async () => { await api('DELETE', 'team/' + D.rm); S.team = S.team.filter((x) => x.id !== +D.rm); renderTeam(); toast('Member removed'); }); }
    if (D.tpl) { S.set.draft = JSON.parse(JSON.stringify(S.tpls.find((t) => t.id === +D.tpl))); return renderSettings(); }
    if (D.lay) { S.set.draft.config.layout = D.lay; const c = S.set.draft.config; if (D.lay === 'graph' && !c.sections.includes('charts')) c.sections.push('charts'); renderSettings(); return; }
    if (el.id === 'tsave') {
      const dr = S.set.draft; dr.name = $('#tname').value.trim() || 'Untitled template';
      const r = await api(dr.id ? 'PUT' : 'POST', dr.id ? 'templates/' + dr.id : 'templates', {name: dr.name, config: dr.config});
      const i = S.tpls.findIndex((t) => t.id === r.id); if (i >= 0) S.tpls[i] = r; else S.tpls.push(r); S.set.draft = JSON.parse(JSON.stringify(r)); S.docs.tid = S.docs.tid || r.id; renderSettings(); return toast('Template saved');
    }
    if (el.id === 'tdup') { const dr = JSON.parse(JSON.stringify(S.set.draft)); dr.id = null; dr.name += ' (copy)'; S.set.draft = dr; return renderSettings(); }
    if (el.id === 'tdel') return confirmBox('Delete template', `Delete "${S.set.draft.name}"?`, 'Delete', async () => { await api('DELETE', 'templates/' + S.set.draft.id); S.tpls = S.tpls.filter((t) => t.id !== S.set.draft.id); S.set.draft = S.tpls[0] ? JSON.parse(JSON.stringify(S.tpls[0])) : null; if (S.docs.tid && !S.tpls.some((t) => t.id === S.docs.tid)) S.docs.tid = S.tpls[0] ? S.tpls[0].id : null; renderSettings(); toast('Template deleted'); });
    if (D.deltab) { e.stopPropagation(); return confirmBox('Remove tab', 'This removes the tab and all of its posts.', 'Remove', async () => { S.data = await api('DELETE', 'tabs/' + D.deltab); cache(S.data); S.tab = tabs()[0].id; S.animate = true; renderBoardParts(); toast('Tab removed'); }); }
    if (D.tab) return goTab(+D.tab);
    if (D.f) { S.filter = D.f; S.limit = PAGE; S.kl = {}; S.animate = true; renderTb(); return renderView(); }
    if (D.v) return setView(D.v);
    if (D.kmore) { S.kl[D.kmore] = (S.kl[D.kmore] || 6) + 6; return renderView(); }
    if (D.cycle) {
      if (!canEdit()) return; e.stopPropagation(); const it = findItem(D.cycle);
      return openPop(el, NEXT_STATUS.map((s) => ({label: LABEL[s], on: it.status === s, icon: s === it.status ? 'check' : null, fn: async () => { const out = await api('PATCH', 'items/' + it.id, {status: s}); Object.assign(it, out); cache(S.data); renderHead(); renderSide(); renderTb(); renderView(); toast(`Marked ${LABEL[s].toLowerCase()}`); }})));
    }
    if (D.edit) return editModal(findItem(D.edit));
    if (D.lock) { const it = findItem(D.lock); const out = await api('PATCH', 'items/' + it.id, {locked: !it.locked}); Object.assign(it, out); toast(it.locked ? 'Locked — protected from edits' : 'Unlocked'); return renderView(); }
    if (D.del) {
      const it = findItem(D.del); const grid = tabs().find((t) => t.id === it.tab_id).kind === 'grid';
      return confirmBox('Delete', grid ? 'This deletes the whole row of three posts.' : 'Delete this item? This can’t be undone.', 'Delete', async () => { const out = await api('DELETE', 'items/' + it.id); tabs().forEach((t) => t.items = t.items.filter((i) => !out.deleted.includes(i.id))); renderBoardParts(); toast('Deleted'); });
    }
    if (D.up) {
      if (e.target.closest('.spill') || e.target.closest('.cact')) return;
      const input = document.createElement('input'); input.type = 'file'; input.accept = 'image/*';
      input.onchange = async () => { if (!input.files[0]) return; const fd = new FormData(); fd.append('image', input.files[0]); const it = findItem(D.up); const out = await api('POST', 'items/' + it.id + '/image', undefined, fd); Object.assign(it, out); renderView(); toast('Image uploaded'); };
      return input.click();
    }
  });

  document.addEventListener('input', (e) => {
    const t = e.target;
    if (t.matches('textarea.note')) fit(t);
    if (S.page === 'settings' && S.set.draft && t.closest('.tplws')) {
      const c = S.set.draft.config;
      if (t.id === 'thead') c.header = t.value; if (t.id === 'tfoot') c.footer = t.value; if (t.id === 'tname') S.set.draft.name = t.value;
      tplRefresh();
    }
  });
  document.addEventListener('change', async (e) => {
    const t = e.target;
    if (t.matches('textarea.note')) {
      const id = +t.dataset.note; const it = findItem(id); await api('PATCH', 'items/' + id, {data: {notes: t.value}}); it.data.notes = t.value; return toast('Note saved');
    }
    if (S.page === 'docs') {
      const d = S.docs;
      if (t.id === 'dtpl') d.tid = +t.value;
      else if (t.dataset.dt) { const all = tabs().map((x) => x.id); const cur = d.tabs || all.slice(); const id = +t.dataset.dt; d.tabs = t.checked ? [...new Set([...cur, id])] : cur.filter((x) => x !== id); if (d.tabs.length === all.length) d.tabs = null; if (d.tabs && !d.tabs.length) { d.tabs = null; t.checked = true; toast('Keep at least one section', false); } }
      else if (t.id === 'dst') d.status = t.value; else if (t.id === 'dnotes') d.notes = t.checked; else if (t.id === 'dsample') d.sample = t.checked; else return;
      return docRefresh();
    }
    if (S.page === 'settings' && S.set.draft) {
      const c = S.set.draft.config;
      if (t.dataset.sec2) { c.sections = SECTIONS.map((s) => s[0]).filter((k) => (k === t.dataset.sec2 ? t.checked : c.sections.includes(k))); }
      else if (t.id === 'tori') c.orientation = t.value; else if (t.id === 'tpap') c.paper = t.value; else if (t.id === 'tcharts') c.charts = t.checked; else if (t.id === 'tnotes') c.show_notes = t.checked;
      else if (t.id === 'tsample') S.set.sample = t.checked; else return;
      tplRefresh();
    }
  });
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); return $('#cmdk').classList.contains('on') ? closeCmd() : openCmd(); }
    if (e.key === 'Escape') { closeModal(); closeCmd(); closePop(); $('#drawer').classList.remove('on'); }
    if (e.key === '/' && !e.target.matches('input,textarea,select')) { e.preventDefault(); openCmd(); }
  });

  // drag & drop on board view
  let dragId = null;
  document.addEventListener('dragstart', (e) => { const k = e.target.closest('[data-kc]'); if (!k) return; dragId = +k.dataset.kc; k.classList.add('drag'); e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', dragId); });
  document.addEventListener('dragend', () => { document.querySelectorAll('.drag').forEach((x) => x.classList.remove('drag')); document.querySelectorAll('.kcol.over').forEach((x) => x.classList.remove('over')); });
  document.addEventListener('dragover', (e) => { const c = e.target.closest('.kcol'); if (!c || dragId === null) return; e.preventDefault(); document.querySelectorAll('.kcol.over').forEach((x) => x !== c && x.classList.remove('over')); c.classList.add('over'); });
  document.addEventListener('drop', async (e) => {
    const c = e.target.closest('.kcol'); if (!c || dragId === null) return; e.preventDefault();
    const it = findItem(dragId); const s = c.dataset.col; dragId = null;
    if (!it || it.status === s) return renderView();
    const prev = it.status; it.status = s; renderView(); renderHead(); renderTb(); renderSide();
    try { Object.assign(it, await api('PATCH', 'items/' + it.id, {status: s})); toast(`Moved to ${LABEL[s]}`); } catch (x) { it.status = prev; renderView(); renderHead(); renderTb(); renderSide(); }
  });

  // ── boot ─────────────────────────────────────────────────
  (async () => {
    document.documentElement.style.setProperty('--accent', AG.color);
    const saved = +location.hash.slice(1);
    const qp = new URLSearchParams(location.search); const wantWs = +qp.get('ws'); const wantPage = qp.get('page');
    if (wantWs) { const wi = S.accts.findIndex((a) => a.id === wantWs); if (wi >= 0) S.ai = wi; }
    if (acct()) {
      const [d] = await Promise.all([api('GET', 'accounts/' + acct().id), wait(800)]);
      S.data = d; cache(d); S.tab = saved && tabs().some((t) => t.id === saved) ? saved : tabs()[0].id;
    } else await wait(700);
    S.loading = null; S.animate = true; render();
    if (wantPage === 'docs' && can('documents.export')) goPage('docs');
    else if (wantPage === 'settings' && (can('settings.manage') || can('templates.manage'))) goPage('settings');
    $('#splash').classList.add('hide'); setTimeout(() => $('#splash').remove(), 600);
  })();
})();
