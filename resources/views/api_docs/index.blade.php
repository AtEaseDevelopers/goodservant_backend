<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Snoodle Mobile API Documentation</title>
<style>
    :root {
        --bg: #f5f6f8;
        --panel: #ffffff;
        --border: #e1e4e8;
        --text: #24292e;
        --muted: #6a737d;
        --primary: #0366d6;
        --get: #2f7d32;
        --post: #0366d6;
        --put: #b36b00;
        --delete: #d73a49;
        --code-bg: #f6f8fa;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        font-size: 14px;
    }
    header.topbar {
        position: sticky; top: 0; z-index: 20;
        background: #1a1f36; color: #fff;
        padding: 14px 24px;
        display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    }
    header.topbar h1 { font-size: 17px; margin: 0; font-weight: 600; }
    header.topbar .sub { color: #a0a8c0; font-size: 12px; }
    .token-box { margin-left: auto; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .token-box label { font-size: 12px; color: #c5cae9; }
    .token-box input {
        width: 260px; padding: 6px 10px; border-radius: 6px; border: 1px solid #3a4166;
        background: #262c4a; color: #fff; font-family: monospace; font-size: 12px;
    }
    .token-box .hint { font-size: 11px; color: #8890b5; }

    .layout { display: flex; align-items: flex-start; }
    nav.sidenav {
        position: sticky; top: 56px;
        width: 220px; flex-shrink: 0;
        height: calc(100vh - 56px);
        overflow-y: auto;
        padding: 16px 8px;
        border-right: 1px solid var(--border);
        background: var(--panel);
    }
    nav.sidenav a {
        display: block; padding: 6px 12px; color: var(--text); text-decoration: none;
        border-radius: 6px; font-size: 13px; margin-bottom: 2px;
    }
    nav.sidenav a:hover { background: var(--code-bg); }
    nav.sidenav .count { color: var(--muted); font-size: 11px; }

    main { flex: 1; padding: 24px 32px; max-width: 980px; }
    .intro { background: var(--panel); border: 1px solid var(--border); border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; }
    .intro p { margin: 4px 0; }
    .intro code { background: var(--code-bg); padding: 2px 6px; border-radius: 4px; }

    h2.category {
        font-size: 20px; margin: 32px 0 12px; padding-bottom: 6px;
        border-bottom: 2px solid var(--border);
    }
    .endpoint {
        background: var(--panel); border: 1px solid var(--border); border-radius: 8px;
        margin-bottom: 14px; overflow: hidden;
    }
    .endpoint-head {
        display: flex; align-items: center; gap: 10px; padding: 12px 16px; cursor: pointer;
    }
    .endpoint-head:hover { background: var(--code-bg); }
    .method {
        font-family: monospace; font-weight: 700; font-size: 12px; padding: 3px 8px;
        border-radius: 4px; color: #fff; min-width: 52px; text-align: center;
    }
    .method.GET { background: var(--get); }
    .method.POST { background: var(--post); }
    .path { font-family: monospace; font-size: 13px; font-weight: 600; }
    .desc { color: var(--muted); font-size: 13px; margin-left: 4px; }
    .badges { margin-left: auto; display: flex; gap: 6px; flex-shrink: 0; }
    .badge { font-size: 10px; padding: 2px 7px; border-radius: 10px; background: var(--code-bg); color: var(--muted); border: 1px solid var(--border); white-space: nowrap; }
    .badge.auth { color: #b36b00; border-color: #f0d9a8; background: #fff8ec; }
    .badge.trip { color: #7c3aed; border-color: #ddd0fb; background: #f7f3ff; }
    .badge.pdf { color: #d73a49; border-color: #f5c6cb; background: #fff0f1; }
    .chev { color: var(--muted); font-size: 12px; transition: transform .15s; }
    .endpoint.open .chev { transform: rotate(90deg); }

    .endpoint-body { display: none; padding: 0 16px 16px; border-top: 1px solid var(--border); }
    .endpoint.open .endpoint-body { display: block; }

    .section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin: 14px 0 6px; }
    table.params { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.params th, table.params td { text-align: left; padding: 5px 8px; border-bottom: 1px solid var(--border); vertical-align: top; }
    table.params th { color: var(--muted); font-weight: 600; font-size: 11px; text-transform: uppercase; }
    table.params td.name { font-family: monospace; white-space: nowrap; }
    table.params td.req { color: var(--delete); font-size: 11px; }
    table.params td.opt { color: var(--muted); font-size: 11px; }

    pre.code {
        background: var(--code-bg); border: 1px solid var(--border); border-radius: 6px;
        padding: 10px 12px; overflow-x: auto; font-size: 12px; line-height: 1.5; margin: 6px 0;
    }

    .tryit { margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--border); }
    .tryit-fields { display: flex; flex-direction: column; gap: 8px; margin-bottom: 10px; }
    .tryit-field label { display: block; font-size: 11px; color: var(--muted); margin-bottom: 3px; font-family: monospace; }
    .tryit-field input, .tryit-field textarea {
        width: 100%; padding: 7px 9px; border: 1px solid var(--border); border-radius: 6px;
        font-family: monospace; font-size: 12px; background: #fff;
    }
    .tryit-field textarea { min-height: 110px; resize: vertical; }
    .btn {
        background: var(--primary); color: #fff; border: none; border-radius: 6px;
        padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .btn:hover { opacity: .9; }
    .btn:disabled { opacity: .5; cursor: default; }
    .tryit-result { margin-top: 10px; }
    .tryit-status { font-family: monospace; font-size: 12px; margin-bottom: 6px; }
    .tryit-status.ok { color: var(--get); }
    .tryit-status.err { color: var(--delete); }

    .search-box { margin-bottom: 14px; }
    .search-box input {
        width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px;
        font-size: 13px;
    }
    .empty-state { color: var(--muted); padding: 40px; text-align: center; }

    @media (max-width: 860px) {
        nav.sidenav { display: none; }
        main { padding: 16px; }
    }
</style>
</head>
<body>

<header class="topbar">
    <div>
        <h1>Snoodle Mobile API</h1>
        <div class="sub">Driver app REST API reference &amp; live tester</div>
    </div>
    <div class="token-box">
        <label for="sessionToken">Session token</label>
        <input type="text" id="sessionToken" placeholder="paste value from /driver/login response">
        <span class="hint">saved in this browser only</span>
    </div>
</header>

<div class="layout">
    <nav class="sidenav" id="sidenav"></nav>
    <main>
        <div class="intro">
            <p><strong>Base URL:</strong> <code id="baseUrlText"></code></p>
            <p>Most endpoints require a <code>session</code> header, obtained from <code>POST /driver/login</code>. Paste it into the box at the top right — it's used automatically by every "Try it" panel below and remembered in this browser (not sent anywhere else).</p>
            <p>Every response follows the shape <code>{"result": true|false, "message": "&lt;line&gt;|&lt;key or text&gt;", "data": ...}</code>, except PDF-streaming endpoints (marked <span class="badge pdf">PDF</span>), which return a raw PDF file instead of JSON.</p>
        </div>
        <div class="search-box">
            <input type="text" id="searchBox" placeholder="Filter endpoints by path, method name, or description...">
        </div>
        <div id="content"></div>
    </main>
</div>

<script>
// ─────────────────────────────────────────────────────────────────────────
// Endpoint data - populated below. Each entry:
// { category, method, path, methodName, description, auth:{session,trip},
//   params:[{name,in:'body'|'query'|'path',type,required,note}],
//   exampleRequest: {...} | null, exampleResponse: {...} | string,
//   responseType: 'json' | 'pdf' }
// ─────────────────────────────────────────────────────────────────────────
const API_PREFIX = '/api/v1';
const endpoints = {!! json_encode($endpoints ?? []) !!};

const content = document.getElementById('content');
const sidenav = document.getElementById('sidenav');
document.getElementById('baseUrlText').textContent = window.location.origin + API_PREFIX;

function esc(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function groupByCategory(list) {
    const map = new Map();
    list.forEach(ep => {
        if (!map.has(ep.category)) map.set(ep.category, []);
        map.get(ep.category).push(ep);
    });
    return map;
}

function slug(s) { return s.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); }

function paramsTable(params, kind) {
    const rows = params.filter(p => p.in === kind);
    if (!rows.length) return '';
    return `<div class="section-title">${kind === 'body' ? 'Request body' : kind === 'query' ? 'Query parameters' : 'Path parameters'}</div>
    <table class="params"><thead><tr><th>Name</th><th>Type</th><th></th><th>Notes</th></tr></thead><tbody>
    ${rows.map(p => `<tr><td class="name">${esc(p.name)}</td><td>${esc(p.type)}</td><td class="${p.required ? 'req' : 'opt'}">${p.required ? 'required' : 'optional'}</td><td>${esc(p.note || '')}</td></tr>`).join('')}
    </tbody></table>`;
}

function renderEndpoint(ep, idx) {
    const badges = [];
    if (ep.auth && ep.auth.session) badges.push('<span class="badge auth">session</span>');
    if (ep.auth && ep.auth.trip) badges.push('<span class="badge trip">active trip</span>');
    if (ep.responseType === 'pdf') badges.push('<span class="badge pdf">PDF</span>');

    const bodyParams = ep.params.filter(p => p.in === 'body');
    const queryParams = ep.params.filter(p => p.in === 'query');
    const pathParams = ep.params.filter(p => p.in === 'path');

    const tryFields = [];
    pathParams.forEach(p => {
        tryFields.push(`<div class="tryit-field"><label>path: ${esc(p.name)}</label><input type="text" data-kind="path" data-name="${esc(p.name)}" placeholder="${esc(p.type)}"></div>`);
    });
    if (ep.method === 'GET' && queryParams.length) {
        queryParams.forEach(p => {
            tryFields.push(`<div class="tryit-field"><label>query: ${esc(p.name)}${p.required ? ' *' : ''}</label><input type="text" data-kind="query" data-name="${esc(p.name)}" placeholder="${esc(p.type)}"></div>`);
        });
    }
    if (ep.method !== 'GET') {
        const exampleJson = ep.exampleRequest ? JSON.stringify(ep.exampleRequest, null, 2) : '{}';
        tryFields.push(`<div class="tryit-field"><label>request body (JSON)</label><textarea data-kind="jsonbody">${esc(exampleJson)}</textarea></div>`);
    }

    const exampleReqBlock = ep.exampleRequest
        ? `<div class="section-title">Example request body</div><pre class="code">${esc(JSON.stringify(ep.exampleRequest, null, 2))}</pre>` : '';
    const exampleResBlock = ep.responseType === 'pdf'
        ? `<div class="section-title">Response</div><pre class="code">Binary PDF stream (Content-Type: application/pdf)</pre>`
        : `<div class="section-title">Example response</div><pre class="code">${esc(typeof ep.exampleResponse === 'string' ? ep.exampleResponse : JSON.stringify(ep.exampleResponse, null, 2))}</pre>`;

    const uid = 'ep' + idx;

    return `<div class="endpoint" id="${uid}">
        <div class="endpoint-head" onclick="toggleEndpoint('${uid}')">
            <span class="method ${ep.method}">${ep.method}</span>
            <span class="path">${esc(ep.path)}</span>
            <span class="desc">${esc(ep.description || '')}</span>
            <span class="badges">${badges.join('')}</span>
            <span class="chev">&#9656;</span>
        </div>
        <div class="endpoint-body">
            ${paramsTable(ep.params, 'path')}
            ${paramsTable(ep.params, 'query')}
            ${paramsTable(ep.params, 'body')}
            ${exampleReqBlock}
            ${exampleResBlock}
            ${ep.errors ? `<div class="section-title">Error responses</div><pre class="code">${esc(ep.errors)}</pre>` : ''}
            <div class="tryit">
                <div class="section-title">Try it</div>
                <div class="tryit-fields">${tryFields.join('') || '<span style="color:#6a737d;font-size:12px;">No parameters needed.</span>'}</div>
                <button class="btn" onclick="sendTry('${uid}', ${idx})">Send request</button>
                <div class="tryit-result" id="${uid}-result"></div>
            </div>
        </div>
    </div>`;
}

function toggleEndpoint(uid) {
    document.getElementById(uid).classList.toggle('open');
}

async function sendTry(uid, idx) {
    const ep = endpoints[idx];
    const container = document.getElementById(uid);
    const resultBox = document.getElementById(uid + '-result');
    const btn = container.querySelector('.btn');

    let path = ep.path;
    let query = new URLSearchParams();
    let jsonBody = null;

    container.querySelectorAll('.tryit-field [data-kind]').forEach(el => {
        const kind = el.dataset.kind;
        const name = el.dataset.name;
        if (kind === 'path') {
            path = path.replace('{' + name + '}', encodeURIComponent(el.value));
        } else if (kind === 'query') {
            if (el.value !== '') query.set(name, el.value);
        } else if (kind === 'jsonbody') {
            try { jsonBody = el.value.trim() ? JSON.parse(el.value) : {}; }
            catch (e) { resultBox.innerHTML = `<div class="tryit-status err">Invalid JSON in request body: ${esc(e.message)}</div>`; return; }
        }
    });

    let url = API_PREFIX + path;
    if ([...query.keys()].length) url += '?' + query.toString();

    const headers = { 'Accept': 'application/json' };
    const token = document.getElementById('sessionToken').value.trim();
    if (ep.auth && ep.auth.session && token) headers['session'] = token;
    if (ep.method !== 'GET') headers['Content-Type'] = 'application/json';

    btn.disabled = true;
    resultBox.innerHTML = '<div class="tryit-status">Sending...</div>';

    try {
        const resp = await fetch(url, {
            method: ep.method,
            headers,
            body: ep.method !== 'GET' ? JSON.stringify(jsonBody || {}) : undefined,
        });

        const statusClass = resp.ok ? 'ok' : 'err';
        const contentType = resp.headers.get('content-type') || '';

        if (contentType.includes('application/pdf')) {
            const blob = await resp.blob();
            const blobUrl = URL.createObjectURL(blob);
            window.open(blobUrl, '_blank');
            resultBox.innerHTML = `<div class="tryit-status ${statusClass}">HTTP ${resp.status} - PDF opened in a new tab</div>`;
        } else {
            const text = await resp.text();
            let pretty = text;
            try { pretty = JSON.stringify(JSON.parse(text), null, 2); } catch (e) {}
            resultBox.innerHTML = `<div class="tryit-status ${statusClass}">HTTP ${resp.status}</div><pre class="code">${esc(pretty)}</pre>`;
        }
    } catch (e) {
        resultBox.innerHTML = `<div class="tryit-status err">Request failed: ${esc(e.message)}</div>`;
    } finally {
        btn.disabled = false;
    }
}

function render(filterText) {
    const filtered = !filterText ? endpoints : endpoints.filter(ep => {
        const hay = (ep.path + ' ' + ep.methodName + ' ' + (ep.description || '') + ' ' + ep.category).toLowerCase();
        return hay.includes(filterText.toLowerCase());
    });

    if (!filtered.length) {
        content.innerHTML = '<div class="empty-state">No endpoints match your search.</div>';
        return;
    }

    const grouped = groupByCategory(filtered);
    let html = '';
    let globalIdx = 0;
    const idxMap = [];
    grouped.forEach((list, category) => {
        html += `<h2 class="category" id="cat-${slug(category)}">${esc(category)}</h2>`;
        list.forEach(ep => {
            const realIdx = endpoints.indexOf(ep);
            html += renderEndpoint(ep, realIdx);
        });
    });
    content.innerHTML = html;
}

function renderNav() {
    const grouped = groupByCategory(endpoints);
    let html = '';
    grouped.forEach((list, category) => {
        html += `<a href="#cat-${slug(category)}">${esc(category)} <span class="count">(${list.length})</span></a>`;
    });
    sidenav.innerHTML = html;
}

// persist session token
const savedToken = localStorage.getItem('snoodle_api_docs_token');
if (savedToken) document.getElementById('sessionToken').value = savedToken;
document.getElementById('sessionToken').addEventListener('input', function () {
    localStorage.setItem('snoodle_api_docs_token', this.value);
});

document.getElementById('searchBox').addEventListener('input', function () {
    render(this.value);
});

renderNav();
render('');
</script>
</body>
</html>
