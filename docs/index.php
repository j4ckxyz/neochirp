<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chirp API Docs</title>
    <style>
        :root {
            --bg:        #0e0e10;
            --surface:   #18181b;
            --border:    #2a2a2f;
            --accent:    #1AD063;
            --accent-dim:#0f7a3a;
            --text:      #e4e4e7;
            --muted:     #71717a;
            --code-bg:   #101014;
            --get:       #3b82f6;
            --post:      #22c55e;
            --red:       #ef4444;
            --yellow:    #f0c040;
            --sidebar-w: 260px;
            --header-h:  56px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 15px;
            line-height: 1.65;
        }

        /* ── Top bar ────────────────────────────────── */
        #topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--header-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            z-index: 100;
        }
        #topbar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.05em;
            text-decoration: none;
            color: var(--text);
        }
        #topbar .logo img { width: 26px; height: 26px; }
        #topbar .logo span { color: var(--accent); }
        #topbar .badge {
            background: var(--accent);
            color: #000;
            font-size: 0.68em;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
            letter-spacing: 0.04em;
        }
        #topbar nav {
            margin-left: auto;
            display: flex;
            gap: 20px;
        }
        #topbar nav a {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.15s;
        }
        #topbar nav a:hover { color: var(--text); }

        /* ── Layout ─────────────────────────────────── */
        #layout {
            display: flex;
            padding-top: var(--header-h);
            min-height: 100vh;
        }

        /* ── Sidebar ─────────────────────────────────── */
        #sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            position: fixed;
            top: var(--header-h);
            bottom: 0;
            overflow-y: auto;
            border-right: 1px solid var(--border);
            padding: 24px 0;
            scrollbar-width: thin;
            scrollbar-color: var(--border) transparent;
        }
        #sidebar .nav-section {
            padding: 0 16px 4px 20px;
            font-size: 0.7em;
            font-weight: 700;
            letter-spacing: 0.1em;
            color: var(--muted);
            text-transform: uppercase;
            margin-top: 20px;
            margin-bottom: 4px;
        }
        #sidebar a {
            display: block;
            padding: 5px 16px 5px 20px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.88em;
            border-left: 2px solid transparent;
            transition: color 0.12s, border-color 0.12s;
        }
        #sidebar a:hover { color: var(--text); }
        #sidebar a.active {
            color: var(--accent);
            border-left-color: var(--accent);
        }
        #sidebar .method-tag {
            display: inline-block;
            font-size: 0.72em;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 4px;
            margin-right: 6px;
            vertical-align: middle;
            line-height: 1.4;
        }
        .tag-get  { background: #1d3a6e; color: #60a5fa; }
        .tag-post { background: #14532d; color: #4ade80; }

        /* ── Main content ────────────────────────────── */
        #content {
            margin-left: var(--sidebar-w);
            flex: 1;
            max-width: 860px;
            padding: 48px 48px 80px;
        }

        /* ── Sections ────────────────────────────────── */
        .section { margin-bottom: 72px; }

        h1 {
            font-size: 2em;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        h1 .accent { color: var(--accent); }

        h2 {
            font-size: 1.35em;
            font-weight: 700;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
            scroll-margin-top: calc(var(--header-h) + 20px);
        }

        h3 {
            font-size: 1em;
            font-weight: 600;
            color: var(--muted);
            margin: 24px 0 8px;
            scroll-margin-top: calc(var(--header-h) + 20px);
        }

        p { color: #a1a1aa; margin-bottom: 12px; }
        p a, .inline-link { color: var(--accent); text-decoration: none; }
        p a:hover { text-decoration: underline; }

        /* ── Endpoint cards ─────────────────────────── */
        .endpoint {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 28px;
            overflow: hidden;
        }
        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            background: #1c1c20;
        }
        .method {
            font-size: 0.78em;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 6px;
            letter-spacing: 0.06em;
            flex-shrink: 0;
        }
        .method.get  { background: #1d3a6e; color: #60a5fa; }
        .method.post { background: #14532d; color: #4ade80; }
        .endpoint-path {
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 0.95em;
            color: var(--text);
        }
        .endpoint-desc {
            margin-left: auto;
            font-size: 0.82em;
            color: var(--muted);
        }
        .endpoint-body { padding: 16px 20px; }
        .endpoint-body p { margin-bottom: 8px; }

        /* ── Params table ────────────────────────────── */
        .params-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 16px;
            font-size: 0.86em;
        }
        .params-table th {
            text-align: left;
            padding: 6px 12px;
            color: var(--muted);
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }
        .params-table td {
            padding: 7px 12px;
            border-bottom: 1px solid #1f1f24;
            vertical-align: top;
        }
        .params-table tr:last-child td { border-bottom: none; }
        .params-table code {
            background: var(--code-bg);
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #c4b5fd;
        }
        .badge-required {
            background: #3b0a0a;
            color: #f87171;
            font-size: 0.72em;
            padding: 1px 6px;
            border-radius: 4px;
            margin-left: 4px;
        }
        .badge-optional {
            background: #1a1a1a;
            color: var(--muted);
            font-size: 0.72em;
            padding: 1px 6px;
            border-radius: 4px;
            margin-left: 4px;
        }

        /* ── Code blocks ─────────────────────────────── */
        .code-block {
            background: var(--code-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            margin: 12px 0;
            overflow: hidden;
        }
        .code-block-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 0.75em;
            color: var(--muted);
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        .copy-btn {
            background: none;
            border: 1px solid var(--border);
            color: var(--muted);
            padding: 3px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
            transition: color 0.12s, border-color 0.12s;
        }
        .copy-btn:hover { color: var(--text); border-color: var(--muted); }
        .copy-btn.copied { color: var(--accent); border-color: var(--accent); }
        pre {
            padding: 16px 18px;
            overflow-x: auto;
            font-family: 'SFMono-Regular', Consolas, 'Courier New', monospace;
            font-size: 0.84em;
            line-height: 1.65;
        }

        /* Syntax highlighting via CSS */
        .tok-key    { color: #c4b5fd; }   /* purple – JSON keys / param names */
        .tok-str    { color: #86efac; }   /* green  – strings */
        .tok-num    { color: #fb923c; }   /* orange – numbers / booleans */
        .tok-cmt    { color: #52525b; font-style: italic; }
        .tok-kw     { color: #60a5fa; }   /* blue   – bash keywords / method names */
        .tok-url    { color: #fde68a; }   /* yellow – URLs */
        .tok-hdr    { color: #f472b6; }   /* pink   – headers */

        /* ── Response shape ─────────────────────────── */
        .response-label {
            font-size: 0.78em;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin: 14px 0 6px;
        }

        /* ── Info boxes ──────────────────────────────── */
        .info-box {
            border-left: 3px solid var(--accent);
            background: #0f2a1a;
            border-radius: 0 8px 8px 0;
            padding: 12px 16px;
            margin: 16px 0;
            font-size: 0.88em;
            color: #a1a1aa;
        }
        .info-box.warn {
            border-left-color: var(--yellow);
            background: #1e1a0a;
        }
        .info-box strong { color: var(--text); }

        /* ── Rate limit table ────────────────────────── */
        .rate-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88em;
            margin: 12px 0;
        }
        .rate-table th {
            text-align: left;
            padding: 8px 12px;
            border-bottom: 1px solid var(--border);
            color: var(--muted);
        }
        .rate-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #1c1c20;
        }

        /* ── Hero section ────────────────────────────── */
        .hero {
            background: linear-gradient(135deg, #0f2a1a 0%, #101014 60%);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px 36px;
            margin-bottom: 48px;
        }
        .hero h1 { margin-bottom: 10px; }
        .hero p { font-size: 1.05em; color: #a1a1aa; max-width: 520px; }
        .hero-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 20px;
        }
        .chip {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 0.82em;
            color: var(--muted);
        }
        .chip strong { color: var(--text); }

        /* ── Scrollbar ───────────────────────────────── */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        /* ── Responsive ──────────────────────────────── */
        @media (max-width: 768px) {
            #sidebar { display: none; }
            #content { margin-left: 0; padding: 24px 20px 60px; }
        }
    </style>
</head>
<body>

<!-- ─── Top Bar ─────────────────────────────────────────────────────────── -->
<div id="topbar">
    <a href="/" class="logo">
        <img src="/src/images/icons/chirp.svg" alt="Chirp">
        Chirp<span>&nbsp;API</span>
    </a>
    <span class="badge">v1</span>
    <nav>
        <a href="/">← Back to Chirp</a>
        <a href="/settings/api/">Get API Key</a>
    </nav>
</div>

<div id="layout">

<!-- ─── Sidebar ─────────────────────────────────────────────────────────── -->
<nav id="sidebar">
    <div class="nav-section">Getting started</div>
    <a href="#intro">Introduction</a>
    <a href="#local-quickstart">⚡ Local quick-start</a>
    <a href="#auth">Authentication</a>
    <a href="#rate-limits">Rate limits</a>
    <a href="#errors">Errors</a>

    <div class="nav-section">Feeds</div>
    <a href="#feed-latest"><span class="method-tag tag-get">GET</span>Latest</a>
    <a href="#feed-hot"><span class="method-tag tag-get">GET</span>What's Hot</a>

    <div class="nav-section">Chirps</div>
    <a href="#chirp-get"><span class="method-tag tag-get">GET</span>Get Chirp</a>
    <a href="#chirp-replies"><span class="method-tag tag-get">GET</span>Get Replies</a>
    <a href="#chirp-likers"><span class="method-tag tag-get">GET</span>Who Liked</a>
    <a href="#chirp-rechipers"><span class="method-tag tag-get">GET</span>Who Rechirped</a>
    <a href="#chirp-post"><span class="method-tag tag-post">POST</span>Post Chirp</a>
    <a href="#chirp-reply"><span class="method-tag tag-post">POST</span>Reply</a>
    <a href="#chirp-like"><span class="method-tag tag-post">POST</span>Like / Unlike</a>
    <a href="#chirp-rechirp"><span class="method-tag tag-post">POST</span>Rechirp / Undo</a>

    <div class="nav-section">Users</div>
    <a href="#user-get"><span class="method-tag tag-get">GET</span>Get User</a>

    <div class="nav-section">Notifications</div>
    <a href="#notif-get"><span class="method-tag tag-get">GET</span>Get Notifications</a>
</nav>

<!-- ─── Main Content ─────────────────────────────────────────────────────── -->
<main id="content">

    <!-- Hero -->
    <div class="hero">
        <h1>Chirp <span class="accent">API</span> Reference</h1>
        <p>A simple REST API for reading and writing to Chirp. All endpoints are authenticated, return JSON, and follow predictable conventions.</p>
        <div class="hero-chips">
            <span class="chip"><strong>Base URL</strong> /api/v1</span>
            <span class="chip"><strong>Format</strong> JSON</span>
            <span class="chip"><strong>Auth</strong> Bearer token</span>
            <span class="chip"><strong>Read limit</strong> 300 req/hr</span>
            <span class="chip"><strong>Write limit</strong> 100 req/hr</span>
            <span class="chip"><strong>via field</strong> included in all chirp objects</span>
        </div>
    </div>

    <!-- ── Introduction ──────────────────────────────────────────────────── -->
    <div class="section" id="intro">
        <h2>Introduction</h2>
        <p>The Chirp API lets you build bots, integrations, and third-party clients. Every request must include an API key as a Bearer token. Responses are always <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#c4b5fd;">application/json</code>.</p>

        <div class="info-box">
            <strong>Running locally?</strong> Use <code>http://localhost:8080</code> — no HTTPS needed. Replace the base URL in any example below. The API works identically in dev mode.
        </div>

        <div class="info-box warn" id="local-quickstart">
            <strong>⚡ Local quick-start</strong><br>
            Start the server, then make your first request:
            <div class="code-block" style="margin-top:10px;">
                <div class="code-block-header"><span>BASH</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                <pre><span class="tok-cmt"># 1. Start the PHP dev server</span>
php -d display_errors=Off -S localhost:8080 -t /path/to/chirp

<span class="tok-cmt"># 2. Generate a key at http://localhost:8080/settings/api/
#    then paste it below</span>

<span class="tok-cmt"># 3. Test — note: http (not https), and the full Authorization header</span>
<span class="tok-kw">curl</span> http://localhost:8080/api/v1/feed/latest.php \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key_here"</span></pre>
            </div>
        </div>
    </div>

    <!-- ── Authentication ────────────────────────────────────────────────── -->
    <div class="section" id="auth">
        <h2>Authentication</h2>
        <p>All endpoints require a personal API key passed in the <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#f472b6;">Authorization</code> header.</p>

        <h3>1. Generate a key</h3>
        <p>Go to <a href="/settings/api/">Settings → API Keys</a>, enter a name for your key, and click <strong>Generate key</strong>. Your key is shown exactly once — copy it immediately.</p>
        <p>Keys look like: <code style="background:var(--code-bg);padding:2px 8px;border-radius:4px;color:#86efac;">chirp_a3f9b2c1d4e5f6789abcdef0123456789abcdef01</code></p>

        <h3>2. Use the key</h3>
        <p>Pass it as an <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#f472b6;">Authorization</code> header with the prefix <strong><code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#86efac;">Bearer </code></strong> (with a space). The header name and prefix are both required — just the key alone will not work.</p>

        <div class="code-block">
            <div class="code-block-header"><span>CORRECT FORMAT</span></div>
            <pre><span class="tok-cmt">✓  Authorization: Bearer chirp_your_key_here</span>

<span class="tok-cmt">✗  chirp_your_key_here             ← missing header name
✗  Authorization: chirp_your_key   ← missing "Bearer " prefix
✗  Bearer chirp_your_key           ← missing "Authorization:" name</span></pre>
        </div>

        <div class="code-block">
            <div class="code-block-header">
                <span>CURL — LOCAL</span>
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
            </div>
            <pre><span class="tok-kw">curl</span> <span class="tok-url">http://localhost:8080/api/v1/feed/latest.php</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key_here"</span></pre>
        </div>

        <div class="code-block">
            <div class="code-block-header">
                <span>CURL — PRODUCTION</span>
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
            </div>
            <pre><span class="tok-kw">curl</span> <span class="tok-url">https://chirp.j4ck.xyz/api/v1/feed/latest.php</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key_here"</span></pre>
        </div>

        <div class="code-block">
            <div class="code-block-header">
                <span>JAVASCRIPT</span>
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
            </div>
            <pre><span class="tok-kw">const</span> BASE    = <span class="tok-str">'http://localhost:8080'</span>;  <span class="tok-cmt">// or https://chirp.j4ck.xyz</span>
<span class="tok-kw">const</span> API_KEY = <span class="tok-str">'chirp_your_key_here'</span>;

<span class="tok-kw">const</span> res = <span class="tok-kw">await</span> fetch(<span class="tok-str">`${BASE}/api/v1/feed/latest.php`</span>, {
  headers: { <span class="tok-key">'Authorization'</span>: <span class="tok-str">`Bearer ${API_KEY}`</span> }
});
<span class="tok-kw">const</span> posts = <span class="tok-kw">await</span> res.json();</pre>
        </div>

        <div class="code-block">
            <div class="code-block-header">
                <span>PYTHON</span>
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
            </div>
            <pre><span class="tok-kw">import</span> requests

BASE    = <span class="tok-str">"http://localhost:8080"</span>   <span class="tok-cmt"># or https://chirp.j4ck.xyz</span>
API_KEY = <span class="tok-str">"chirp_your_key_here"</span>
HDR     = {<span class="tok-str">"Authorization"</span>: <span class="tok-str">f"Bearer {API_KEY}"</span>}

r = requests.get(<span class="tok-str">f"{BASE}/api/v1/feed/latest.php"</span>, headers=HDR)
posts = r.json()</pre>
        </div>
    </div>

    <!-- ── Rate Limits ────────────────────────────────────────────────────── -->
    <div class="section" id="rate-limits">
        <h2>Rate limits</h2>
        <p>Rate limits are enforced per API key, per rolling hour.</p>
        <table class="rate-table">
            <thead><tr><th>Type</th><th>Limit</th><th>Applies to</th></tr></thead>
            <tbody>
                <tr><td>Read</td><td>300 req / hour</td><td>GET endpoints</td></tr>
                <tr><td>Write</td><td>100 req / hour</td><td>POST endpoints (post, reply, like, rechirp)</td></tr>
            </tbody>
        </table>
        <p>When you hit the limit the API returns <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#f87171;">429 Too Many Requests</code> with a message showing when the limit resets.</p>
        <div class="code-block">
            <div class="code-block-header"><span>RESPONSE — 429</span></div>
            <pre>{
  <span class="tok-key">"error"</span>: <span class="tok-str">"Rate limit exceeded (100 req/hour). Resets at 15:00 UTC"</span>
}</pre>
        </div>
    </div>

    <!-- ── Errors ─────────────────────────────────────────────────────────── -->
    <div class="section" id="errors">
        <h2>Errors</h2>
        <p>All errors return a JSON object with an <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#c4b5fd;">error</code> key and an appropriate HTTP status code.</p>
        <table class="params-table">
            <thead><tr><th>Code</th><th>Meaning</th></tr></thead>
            <tbody>
                <tr><td><code>400</code></td><td>Bad request — missing or invalid parameter</td></tr>
                <tr><td><code>401</code></td><td>Unauthorized — missing or invalid API key</td></tr>
                <tr><td><code>404</code></td><td>Not found — chirp or user does not exist</td></tr>
                <tr><td><code>405</code></td><td>Method not allowed — wrong HTTP verb</td></tr>
                <tr><td><code>429</code></td><td>Rate limit exceeded</td></tr>
            </tbody>
        </table>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!--  FEEDS                                                              -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <h2>Feeds</h2>

        <!-- GET /feed/latest -->
        <div class="endpoint" id="feed-latest">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/feed/latest.php</span>
                <span class="endpoint-desc">Latest chirps, newest first</span>
            </div>
            <div class="endpoint-body">
                <p>Returns all chirps sorted by timestamp descending. The authenticated user's like/rechirp state is included.</p>
                <h3>Query parameters</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>offset</code><span class="badge-optional">optional</span></td><td>integer</td><td>Pagination offset. Default <code>0</code>.</td></tr>
                        <tr><td><code>limit</code><span class="badge-optional">optional</span></td><td>integer</td><td>Results per page, max <code>50</code>. Default <code>20</code>.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">"https://chirp.j4ck.xyz/api/v1/feed/latest.php?offset=0&limit=20"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span></pre>
                </div>
                <div class="response-label">Response — array of chirp objects</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>[
  {
    <span class="tok-key">"id"</span>: <span class="tok-num">42</span>,
    <span class="tok-key">"chirp"</span>: <span class="tok-str">"Hello world!"</span>,
    <span class="tok-key">"timestamp"</span>: <span class="tok-num">1712000000</span>,
    <span class="tok-key">"type"</span>: <span class="tok-str">"post"</span>,
    <span class="tok-key">"via"</span>: <span class="tok-str">"Chirp Web"</span>,
    <span class="tok-key">"username"</span>: <span class="tok-str">"alice"</span>,
    <span class="tok-key">"name"</span>: <span class="tok-str">"Alice"</span>,
    <span class="tok-key">"profilePic"</span>: <span class="tok-str">"https://…/alice.jpg"</span>,
    <span class="tok-key">"isVerified"</span>: <span class="tok-num">false</span>,
    <span class="tok-key">"like_count"</span>: <span class="tok-num">7</span>,
    <span class="tok-key">"rechirp_count"</span>: <span class="tok-num">2</span>,
    <span class="tok-key">"reply_count"</span>: <span class="tok-num">1</span>,
    <span class="tok-key">"liked_by_me"</span>: <span class="tok-num">false</span>,
    <span class="tok-key">"rechirped_by_me"</span>: <span class="tok-num">false</span>
  }
]</pre>
                </div>
            </div>
        </div>

        <!-- GET /feed/hot -->
        <div class="endpoint" id="feed-hot">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/feed/hot.php</span>
                <span class="endpoint-desc">Trending chirps by velocity score</span>
            </div>
            <div class="endpoint-body">
                <p>Returns chirps from the past 7 days ranked by a velocity algorithm: <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#c4b5fd;">score = (likes + rechirps×2 + replies) / (age_hours + 2)^1.5</code>. Posts from users you follow get a <strong>1.5× boost</strong>.</p>
                <h3>Query parameters</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>offset</code><span class="badge-optional">optional</span></td><td>integer</td><td>Pagination offset. Default <code>0</code>.</td></tr>
                        <tr><td><code>limit</code><span class="badge-optional">optional</span></td><td>integer</td><td>Results per page, max <code>50</code>. Default <code>20</code>.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">"https://chirp.j4ck.xyz/api/v1/feed/hot.php"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span></pre>
                </div>
                <p>Response shape is identical to <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#c4b5fd;">/feed/latest.php</code>.</p>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!--  CHIRPS                                                             -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <h2>Chirps</h2>

        <!-- GET /chirp/getChirp -->
        <div class="endpoint" id="chirp-get">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/chirp/getChirp.php</span>
                <span class="endpoint-desc">Fetch a single chirp by ID</span>
            </div>
            <div class="endpoint-body">
                <h3>Query parameters</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>id</code><span class="badge-required">required</span></td><td>integer</td><td>ID of the chirp to fetch.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">"https://chirp.j4ck.xyz/api/v1/chirp/getChirp.php?id=42"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span></pre>
                </div>
                <div class="response-label">Response — chirp object</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>{
  <span class="tok-key">"id"</span>: <span class="tok-num">42</span>,
  <span class="tok-key">"chirp"</span>: <span class="tok-str">"Hello world!"</span>,
  <span class="tok-key">"timestamp"</span>: <span class="tok-num">1712000000</span>,
  <span class="tok-key">"type"</span>: <span class="tok-str">"post"</span>,
  <span class="tok-key">"via"</span>: <span class="tok-str">"Chirp Web"</span>,
  <span class="tok-key">"parent"</span>: <span class="tok-num">null</span>,
  <span class="tok-key">"username"</span>: <span class="tok-str">"alice"</span>,
  <span class="tok-key">"name"</span>: <span class="tok-str">"Alice"</span>,
  <span class="tok-key">"profilePic"</span>: <span class="tok-str">"https://…"</span>,
  <span class="tok-key">"isVerified"</span>: <span class="tok-num">false</span>,
  <span class="tok-key">"like_count"</span>: <span class="tok-num">7</span>,
  <span class="tok-key">"rechirp_count"</span>: <span class="tok-num">2</span>,
  <span class="tok-key">"reply_count"</span>: <span class="tok-num">1</span>,
  <span class="tok-key">"liked_by_me"</span>: <span class="tok-num">false</span>,
  <span class="tok-key">"rechirped_by_me"</span>: <span class="tok-num">false</span>
}</pre>
                </div>
            </div>
        </div>

        <!-- GET /chirp/getReplies -->
        <div class="endpoint" id="chirp-replies">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/chirp/getReplies.php</span>
                <span class="endpoint-desc">Get replies to a chirp</span>
            </div>
            <div class="endpoint-body">
                <h3>Query parameters</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>id</code><span class="badge-required">required</span></td><td>integer</td><td>ID of the parent chirp.</td></tr>
                        <tr><td><code>offset</code><span class="badge-optional">optional</span></td><td>integer</td><td>Pagination offset. Default <code>0</code>.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">"https://chirp.j4ck.xyz/api/v1/chirp/getReplies.php?id=42"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span></pre>
                </div>
                <p>Response is an array of chirp objects (same shape as the feed), where <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#c4b5fd;">type</code> is <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#86efac;">"reply"</code>.</p>
            </div>
        </div>

        <!-- GET /likes/getLikes -->
        <div class="endpoint" id="chirp-likers">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/likes/getLikes.php</span>
                <span class="endpoint-desc">Get users who liked a chirp</span>
            </div>
            <div class="endpoint-body">
                <h3>Query parameters</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>id</code><span class="badge-required">required</span></td><td>integer</td><td>ID of the chirp.</td></tr>
                        <tr><td><code>offset</code><span class="badge-optional">optional</span></td><td>integer</td><td>Pagination offset. Default <code>0</code>.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">"http://localhost:8080/api/v1/likes/getLikes.php?id=42"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span></pre>
                </div>
                <div class="response-label">Response — array of user objects</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>[
  {
    <span class="tok-key">"id"</span>: <span class="tok-num">1</span>,
    <span class="tok-key">"username"</span>: <span class="tok-str">"alice"</span>,
    <span class="tok-key">"name"</span>: <span class="tok-str">"Alice"</span>,
    <span class="tok-key">"profilePic"</span>: <span class="tok-str">"https://…"</span>,
    <span class="tok-key">"bio"</span>: <span class="tok-str">"Just chirping along."</span>,
    <span class="tok-key">"isVerified"</span>: <span class="tok-num">false</span>,
    <span class="tok-key">"follower_count"</span>: <span class="tok-num">142</span>
  }
]</pre>
                </div>
            </div>
        </div>

        <!-- GET /rechirps/getRechirps -->
        <div class="endpoint" id="chirp-rechipers">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/rechirps/getRechirps.php</span>
                <span class="endpoint-desc">Get users who rechirped a chirp</span>
            </div>
            <div class="endpoint-body">
                <h3>Query parameters</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>id</code><span class="badge-required">required</span></td><td>integer</td><td>ID of the chirp.</td></tr>
                        <tr><td><code>offset</code><span class="badge-optional">optional</span></td><td>integer</td><td>Pagination offset. Default <code>0</code>.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">"http://localhost:8080/api/v1/rechirps/getRechirps.php?id=42"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span></pre>
                </div>
                <div class="response-label">Response — array of user objects</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>[
  {
    <span class="tok-key">"id"</span>: <span class="tok-num">1</span>,
    <span class="tok-key">"username"</span>: <span class="tok-str">"alice"</span>,
    <span class="tok-key">"name"</span>: <span class="tok-str">"Alice"</span>,
    <span class="tok-key">"profilePic"</span>: <span class="tok-str">"https://…"</span>,
    <span class="tok-key">"bio"</span>: <span class="tok-str">"Just chirping along."</span>,
    <span class="tok-key">"isVerified"</span>: <span class="tok-num">false</span>,
    <span class="tok-key">"follower_count"</span>: <span class="tok-num">142</span>
  }
]</pre>
                </div>
            </div>
        </div>

        <!-- POST /chirp/post -->
        <div class="endpoint" id="chirp-post">
            <div class="endpoint-header">
                <span class="method post">POST</span>
                <span class="endpoint-path">/api/v1/chirp/post.php</span>
                <span class="endpoint-desc">Publish a new chirp</span>
            </div>
            <div class="endpoint-body">
                <div class="info-box warn">
                    <strong>Write endpoint.</strong> Counts against your 100 req/hour write quota.
                </div>
                <h3>Request body (JSON)</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>text</code><span class="badge-required">required</span></td><td>string</td><td>The content of the chirp. Max 510 characters.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">https://chirp.j4ck.xyz/api/v1/chirp/post.php</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Content-Type: application/json"</span> \
  <span class="tok-hdr">-d</span> <span class="tok-str">'{"text": "Hello from the API!"}'</span></pre>
                </div>
                <div class="response-label">Response</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>{ <span class="tok-key">"id"</span>: <span class="tok-num">43</span>, <span class="tok-key">"ok"</span>: <span class="tok-num">true</span> }</pre>
                </div>
                <div class="code-block">
                    <div class="code-block-header"><span>PYTHON EXAMPLE</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">import</span> requests

r = requests.post(
    <span class="tok-str">"https://chirp.j4ck.xyz/api/v1/chirp/post.php"</span>,
    json={<span class="tok-str">"text"</span>: <span class="tok-str">"Hello from Python!"</span>},
    headers={<span class="tok-str">"Authorization"</span>: <span class="tok-str">f"Bearer {API_KEY}"</span>}
)
print(r.json())  <span class="tok-cmt"># {'id': 43, 'ok': True}</span></pre>
                </div>
            </div>
        </div>

        <!-- POST /chirp/reply -->
        <div class="endpoint" id="chirp-reply">
            <div class="endpoint-header">
                <span class="method post">POST</span>
                <span class="endpoint-path">/api/v1/chirp/reply.php</span>
                <span class="endpoint-desc">Reply to an existing chirp</span>
            </div>
            <div class="endpoint-body">
                <div class="info-box warn">
                    <strong>Write endpoint.</strong> Replying via the API is fully supported. The parent post's author will receive a reply notification automatically.
                </div>
                <h3>Request body (JSON)</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>text</code><span class="badge-required">required</span></td><td>string</td><td>Reply text. Max 510 characters.</td></tr>
                        <tr><td><code>parent_id</code><span class="badge-required">required</span></td><td>integer</td><td>ID of the chirp to reply to.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">https://chirp.j4ck.xyz/api/v1/chirp/reply.php</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Content-Type: application/json"</span> \
  <span class="tok-hdr">-d</span> <span class="tok-str">'{"text": "Great post!", "parent_id": 42}'</span></pre>
                </div>
                <div class="response-label">Response</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>{ <span class="tok-key">"id"</span>: <span class="tok-num">44</span>, <span class="tok-key">"ok"</span>: <span class="tok-num">true</span> }</pre>
                </div>
            </div>
        </div>

        <!-- POST /chirp/like -->
        <div class="endpoint" id="chirp-like">
            <div class="endpoint-header">
                <span class="method post">POST</span>
                <span class="endpoint-path">/api/v1/chirp/like.php</span>
                <span class="endpoint-desc">Like or unlike a chirp (toggle)</span>
            </div>
            <div class="endpoint-body">
                <p>Calling this endpoint on an already-liked chirp will <strong>unlike</strong> it. It's a toggle.</p>
                <h3>Request body (JSON)</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>chirp_id</code><span class="badge-required">required</span></td><td>integer</td><td>ID of the chirp to like/unlike.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">https://chirp.j4ck.xyz/api/v1/chirp/like.php</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Content-Type: application/json"</span> \
  <span class="tok-hdr">-d</span> <span class="tok-str">'{"chirp_id": 42}'</span></pre>
                </div>
                <div class="response-label">Response</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>{
  <span class="tok-key">"liked"</span>: <span class="tok-num">true</span>,       <span class="tok-cmt">// false if you just unliked</span>
  <span class="tok-key">"like_count"</span>: <span class="tok-num">8</span>
}</pre>
                </div>
            </div>
        </div>

        <!-- POST /chirp/rechirp -->
        <div class="endpoint" id="chirp-rechirp">
            <div class="endpoint-header">
                <span class="method post">POST</span>
                <span class="endpoint-path">/api/v1/chirp/rechirp.php</span>
                <span class="endpoint-desc">Rechirp or undo a rechirp (toggle)</span>
            </div>
            <div class="endpoint-body">
                <p>Calling this endpoint on an already-rechirped chirp will undo the rechirp. Toggle behaviour, same as like.</p>
                <h3>Request body (JSON)</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>chirp_id</code><span class="badge-required">required</span></td><td>integer</td><td>ID of the chirp to rechirp/undo.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">https://chirp.j4ck.xyz/api/v1/chirp/rechirp.php</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Content-Type: application/json"</span> \
  <span class="tok-hdr">-d</span> <span class="tok-str">'{"chirp_id": 42}'</span></pre>
                </div>
                <div class="response-label">Response</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>{
  <span class="tok-key">"rechirped"</span>: <span class="tok-num">true</span>,
  <span class="tok-key">"rechirp_count"</span>: <span class="tok-num">3</span>
}</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!--  USERS                                                              -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <h2>Users</h2>

        <div class="endpoint" id="user-get">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/user/getUser.php</span>
                <span class="endpoint-desc">Get a user's public profile</span>
            </div>
            <div class="endpoint-body">
                <h3>Query parameters</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>username</code><span class="badge-required">required</span></td><td>string</td><td>The username to look up (without the @).</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">"https://chirp.j4ck.xyz/api/v1/user/getUser.php?username=alice"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span></pre>
                </div>
                <div class="response-label">Response</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>{
  <span class="tok-key">"id"</span>: <span class="tok-num">1</span>,
  <span class="tok-key">"username"</span>: <span class="tok-str">"alice"</span>,
  <span class="tok-key">"name"</span>: <span class="tok-str">"Alice"</span>,
  <span class="tok-key">"profilePic"</span>: <span class="tok-str">"https://…"</span>,
  <span class="tok-key">"bio"</span>: <span class="tok-str">"Just chirping along."</span>,
  <span class="tok-key">"isVerified"</span>: <span class="tok-num">false</span>,
  <span class="tok-key">"created_at"</span>: <span class="tok-str">"2024-01-15 10:30:00"</span>,
  <span class="tok-key">"follower_count"</span>: <span class="tok-num">142</span>,
  <span class="tok-key">"following_count"</span>: <span class="tok-num">38</span>,
  <span class="tok-key">"chirp_count"</span>: <span class="tok-num">57</span>
}</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!--  NOTIFICATIONS                                                      -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <h2>Notifications</h2>

        <div class="endpoint" id="notif-get">
            <div class="endpoint-header">
                <span class="method get">GET</span>
                <span class="endpoint-path">/api/v1/notifications/get.php</span>
                <span class="endpoint-desc">Get your grouped notifications</span>
            </div>
            <div class="endpoint-body">
                <p>Returns notifications grouped by <code style="background:var(--code-bg);padding:1px 6px;border-radius:4px;color:#c4b5fd;">(type, chirp_id)</code>, most recent first. Multiple actors for the same event are collapsed into one entry (e.g. "Alice and 3 others liked your chirp").</p>
                <h3>Query parameters</h3>
                <table class="params-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                    <tbody>
                        <tr><td><code>offset</code><span class="badge-optional">optional</span></td><td>integer</td><td>Pagination offset. Default <code>0</code>.</td></tr>
                    </tbody>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>EXAMPLE REQUEST</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre><span class="tok-kw">curl</span> <span class="tok-url">"https://chirp.j4ck.xyz/api/v1/notifications/get.php"</span> \
  <span class="tok-hdr">-H</span> <span class="tok-str">"Authorization: Bearer chirp_your_key"</span></pre>
                </div>
                <div class="response-label">Response</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JSON</span></div>
                    <pre>[
  {
    <span class="tok-key">"type"</span>: <span class="tok-str">"like"</span>,           <span class="tok-cmt">// "like" | "rechirp" | "reply"</span>
    <span class="tok-key">"chirp_id"</span>: <span class="tok-num">42</span>,
    <span class="tok-key">"chirp_text"</span>: <span class="tok-str">"Hello world!"</span>,
    <span class="tok-key">"actor_count"</span>: <span class="tok-num">4</span>,
    <span class="tok-key">"unread"</span>: <span class="tok-num">true</span>,
    <span class="tok-key">"latest_ts"</span>: <span class="tok-num">1712001234</span>,
    <span class="tok-key">"actors"</span>: [               <span class="tok-cmt">// up to 2 most recent actors</span>
      {
        <span class="tok-key">"username"</span>: <span class="tok-str">"bob"</span>,
        <span class="tok-key">"name"</span>: <span class="tok-str">"Bob"</span>,
        <span class="tok-key">"profilePic"</span>: <span class="tok-str">"https://…"</span>,
        <span class="tok-key">"isVerified"</span>: <span class="tok-num">false</span>
      }
    ],
    <span class="tok-key">"reply"</span>: <span class="tok-num">null</span>            <span class="tok-cmt">// populated only for type "reply"</span>
  }
]</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Full example ──────────────────────────────────────────────────── -->
    <div class="section">
        <h2>Complete example</h2>
        <p>Fetch the latest feed, like the first post, and post a reply — all in Python.</p>
        <div class="code-block">
            <div class="code-block-header"><span>PYTHON</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
            <pre><span class="tok-kw">import</span> requests

BASE = <span class="tok-str">"http://localhost:8080/api/v1"</span>   <span class="tok-cmt"># swap for https://chirp.j4ck.xyz/api/v1 in prod</span>
KEY  = <span class="tok-str">"chirp_your_key_here"</span>
HDR  = {<span class="tok-str">"Authorization"</span>: <span class="tok-str">f"Bearer {KEY}"</span>}

<span class="tok-cmt"># 1. Fetch latest feed</span>
feed = requests.get(<span class="tok-str">f"{BASE}/feed/latest.php"</span>, headers=HDR).json()
first = feed[<span class="tok-num">0</span>]
print(<span class="tok-str">f"First chirp by @{first['username']}: {first['chirp']}"</span>)

<span class="tok-cmt"># 2. Like it</span>
like = requests.post(
    <span class="tok-str">f"{BASE}/chirp/like.php"</span>,
    json={<span class="tok-str">"chirp_id"</span>: first[<span class="tok-str">"id"</span>]},
    headers=HDR
).json()
print(<span class="tok-str">f"Liked: {like['liked']} | Total likes: {like['like_count']}"</span>)

<span class="tok-cmt"># 3. Reply to it</span>
reply = requests.post(
    <span class="tok-str">f"{BASE}/chirp/reply.php"</span>,
    json={<span class="tok-str">"text"</span>: <span class="tok-str">"Great chirp!"</span>, <span class="tok-str">"parent_id"</span>: first[<span class="tok-str">"id"</span>]},
    headers=HDR
).json()
print(<span class="tok-str">f"Reply posted with id {reply['id']}"</span>)</pre>
        </div>
    </div>

</main>
</div>

<script>
// ── Copy buttons ──────────────────────────────────────────────────────────────
function copyCode(btn) {
    const pre = btn.closest('.code-block').querySelector('pre');
    navigator.clipboard.writeText(pre.innerText).then(() => {
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 1800);
    });
}

// ── Active sidebar link on scroll ─────────────────────────────────────────────
(function () {
    const links = document.querySelectorAll('#sidebar a[href^="#"]');
    const targets = Array.from(links).map(l => document.querySelector(l.getAttribute('href'))).filter(Boolean);

    function update() {
        let current = '';
        const scrollY = window.scrollY + 80;
        targets.forEach(t => { if (t.offsetTop <= scrollY) current = '#' + t.id; });
        links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === current));
    }

    window.addEventListener('scroll', update, { passive: true });
    update();
})();
</script>

</body>
</html>
