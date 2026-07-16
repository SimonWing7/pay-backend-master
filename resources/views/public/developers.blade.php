<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edfundo Pay — Developer Documentation</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <style>
        :root {
            --purple: #3d01bd;
            --cyan: #00bdff;
            --navy: #000026;
            --sidebar-w: 260px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8f9fc;
            color: #1e1e2e;
            line-height: 1.6;
        }

        /* ── Top bar ── */
        .topbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            background: #ffffff;
            border-bottom: 1px solid #eef0f5;
            height: 60px;
            display: flex; align-items: center; padding: 0 24px;
            gap: 16px;
        }
        .topbar-logo {
            display: flex; align-items: center; gap: 10px;
            font-weight: 800; font-size: 16px; color: var(--navy);
            text-decoration: none;
        }
        .topbar-logo-mark {
            width: 30px; height: 30px; border-radius: 8px;
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 13px; font-weight: 800;
        }
        .topbar-badge {
            font-size: 11px; font-weight: 600; padding: 2px 8px;
            border-radius: 20px; color: var(--purple);
            background: rgba(61,1,189,0.08);
        }
        .topbar-right {
            margin-left: auto; display: flex; align-items: center; gap: 12px;
        }
        .topbar-link {
            font-size: 13px; font-weight: 600; color: #6b7280;
            text-decoration: none; transition: color 0.15s;
        }
        .topbar-link:hover { color: var(--purple); }
        .topbar-cta {
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            color: white; font-size: 13px; font-weight: 700;
            padding: 7px 16px; border-radius: 8px; text-decoration: none;
            transition: opacity 0.15s;
        }
        .topbar-cta:hover { opacity: 0.88; }

        /* ── Layout ── */
        .layout { display: flex; padding-top: 60px; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w); flex-shrink: 0;
            position: fixed; top: 60px; bottom: 0; left: 0;
            background: #ffffff; border-right: 1px solid #eef0f5;
            overflow-y: auto; padding: 24px 0;
        }
        .sidebar-section { margin-bottom: 8px; }
        .sidebar-label {
            font-size: 10px; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; color: #9ca3af;
            padding: 8px 20px 4px;
        }
        .sidebar-link {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 20px; font-size: 13px; font-weight: 500;
            color: #4b5563; text-decoration: none;
            border-left: 2px solid transparent;
            transition: all 0.15s;
        }
        .sidebar-link:hover { color: var(--purple); background: rgba(61,1,189,0.04); }
        .sidebar-link.active {
            color: var(--purple); font-weight: 600;
            border-left-color: var(--purple);
            background: rgba(61,1,189,0.06);
        }
        .sidebar-link .method {
            font-size: 9px; font-weight: 700; padding: 1px 5px;
            border-radius: 3px; flex-shrink: 0; font-family: 'JetBrains Mono', monospace;
        }
        .method-post { background: #ecfdf5; color: #059669; }
        .method-get  { background: #eff6ff; color: #2563eb; }

        /* ── Main content ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1; max-width: 860px;
            padding: 48px 48px 96px;
        }

        /* ── Sections ── */
        .doc-section { margin-bottom: 72px; scroll-margin-top: 80px; }
        h1 { font-size: 32px; font-weight: 800; color: var(--navy); margin-bottom: 12px; }
        h2 { font-size: 22px; font-weight: 700; color: var(--navy); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #eef0f5; }
        h3 { font-size: 16px; font-weight: 700; color: var(--navy); margin: 24px 0 10px; }
        p  { font-size: 14px; color: #4b5563; margin-bottom: 14px; }
        ul, ol { font-size: 14px; color: #4b5563; padding-left: 20px; margin-bottom: 14px; }
        li { margin-bottom: 5px; }
        a  { color: var(--purple); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, rgba(61,1,189,0.05), rgba(0,189,255,0.05));
            border: 1px solid rgba(61,1,189,0.12);
            border-radius: 14px; padding: 32px 36px; margin-bottom: 48px;
        }
        .hero h1 { margin-bottom: 8px; }
        .hero p  { font-size: 15px; color: #374151; margin-bottom: 20px; }
        .hero-pills { display: flex; gap: 10px; flex-wrap: wrap; }
        .pill {
            font-size: 12px; font-weight: 600; padding: 5px 12px;
            border-radius: 20px; border: 1px solid #e5e7eb; background: white;
            color: #374151; display: flex; align-items: center; gap: 6px;
        }
        .pill-dot { width: 6px; height: 6px; border-radius: 50%; background: #10b981; }

        /* ── Endpoint header ── */
        .endpoint-header {
            display: flex; align-items: center; gap: 10px;
            background: #f8f9fc; border: 1px solid #eef0f5;
            border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;
            font-family: 'JetBrains Mono', monospace;
        }
        .endpoint-method {
            font-size: 12px; font-weight: 700; padding: 3px 8px;
            border-radius: 5px; flex-shrink: 0;
        }
        .ep-post { background: #ecfdf5; color: #059669; }
        .ep-get  { background: #eff6ff; color: #2563eb; }
        .endpoint-path { font-size: 14px; color: #1e1e2e; font-weight: 500; }

        /* ── Code blocks ── */
        pre[class*="language-"] {
            border-radius: 10px; margin: 0 0 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px; line-height: 1.6;
            background: #1e1e2e !important;
        }
        code:not([class]) {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px; background: #f3f4f6; color: #374151;
            padding: 2px 6px; border-radius: 4px;
        }

        /* ── Tables ── */
        .param-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
        .param-table th {
            text-align: left; padding: 8px 12px; background: #f8f9fc;
            border: 1px solid #eef0f5; font-weight: 600; color: #374151; font-size: 12px;
        }
        .param-table td { padding: 9px 12px; border: 1px solid #eef0f5; color: #4b5563; vertical-align: top; }
        .param-table tr:hover td { background: #fafafa; }
        .req { color: #dc2626; font-weight: 600; font-size: 11px; }
        .opt { color: #9ca3af; font-size: 11px; }
        .type { font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #7c3aed; }

        /* ── Callout boxes ── */
        .callout {
            display: flex; gap: 12px; padding: 14px 16px;
            border-radius: 10px; margin-bottom: 20px; font-size: 13px;
        }
        .callout-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .callout-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .callout-icon { flex-shrink: 0; font-size: 15px; margin-top: 1px; }

        /* ── Event badge ── */
        .event-badge {
            display: inline-flex; align-items: center; gap: 6px;
            font-family: 'JetBrains Mono', monospace; font-size: 12px;
            font-weight: 500; background: #f3f4f6; color: #374151;
            padding: 4px 10px; border-radius: 6px; margin-bottom: 12px;
        }

        /* ── Error table ── */
        .status { font-family: 'JetBrains Mono', monospace; font-weight: 600; font-size: 13px; }
        .s-401 { color: #dc2626; }
        .s-404 { color: #d97706; }
        .s-422 { color: #7c3aed; }
        .s-201 { color: #059669; }
        .s-200 { color: #059669; }

        /* ── Tabs ── */
        .code-tabs { margin-bottom: 16px; }
        .code-tab-btns { display: flex; gap: 2px; margin-bottom: 0; }
        .code-tab-btn {
            font-size: 12px; font-weight: 600; padding: 6px 14px;
            border: none; border-radius: 6px 6px 0 0; cursor: pointer;
            background: #2d2d3f; color: #9ca3af; font-family: 'JetBrains Mono', monospace;
            transition: all 0.1s;
        }
        .code-tab-btn.active { background: #1e1e2e; color: #f8f8f2; }
        .code-tab-panel { display: none; }
        .code-tab-panel.active { display: block; }
        .code-tab-panel pre { border-radius: 0 10px 10px 10px; margin-bottom: 0; }
    </style>
</head>
<body>

{{-- Top bar --}}
<header class="topbar">
    <a href="{{ url('/') }}" class="topbar-logo">
        <div class="topbar-logo-mark">E</div>
        Edfundo Pay
    </a>
    <span class="topbar-badge">API v1</span>
    <nav class="topbar-right">
        <a href="{{ route('merchant.login') }}" class="topbar-link">Merchant Login</a>
        <a href="{{ route('merchant.settings.api-keys') }}" class="topbar-cta">Get API Key →</a>
    </nav>
</header>

<div class="layout">

    {{-- Sidebar --}}
    <aside class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-label">Getting Started</div>
            <a href="#overview"        class="sidebar-link active">Overview</a>
            <a href="#authentication"  class="sidebar-link">Authentication</a>
            <a href="#base-url"        class="sidebar-link">Base URL & Formats</a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Payment Links</div>
            <a href="#create-link" class="sidebar-link">
                <span class="method method-post">POST</span> Create Payment Link
            </a>
            <a href="#get-link" class="sidebar-link">
                <span class="method method-get">GET</span> Get Payment Link
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Payments</div>
            <a href="#list-payments" class="sidebar-link">
                <span class="method method-get">GET</span> List Payments
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Webhooks</div>
            <a href="#webhook-overview"  class="sidebar-link">Overview</a>
            <a href="#webhook-events"    class="sidebar-link">Events</a>
            <a href="#webhook-verify"    class="sidebar-link">Verifying Signatures</a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Embed Button</div>
            <a href="#embed-overview" class="sidebar-link">Overview</a>
            <a href="#embed-usage"    class="sidebar-link">Usage</a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Reference</div>
            <a href="#errors"   class="sidebar-link">Error Codes</a>
            <a href="#statuses" class="sidebar-link">Payment Statuses</a>
        </div>
    </aside>

    {{-- Main --}}
    <main class="main">

        {{-- Hero --}}
        <div class="hero">
            <h1>Edfundo Pay API</h1>
            <p>Accept UAE Open Banking payments on your website or platform. Create payment links from your server, redirect customers to the hosted checkout page, and receive real-time webhook notifications when payments complete.</p>
            <div class="hero-pills">
                <span class="pill"><span class="pill-dot"></span> UAE Open Banking (Al Tareq)</span>
                <span class="pill">AED only</span>
                <span class="pill">REST + JSON</span>
                <span class="pill">HTTPS only</span>
            </div>
        </div>

        {{-- Overview --}}
        <section class="doc-section" id="overview">
            <h2>Overview</h2>
            <p>Edfundo Pay is a UAE-regulated Open Banking payment platform. The API lets your server programmatically create payment sessions. Customers are then redirected to a hosted checkout page where they authenticate with their bank and complete the payment — no card details, no PCI scope.</p>
            <p>The typical integration flow is:</p>
            <ol>
                <li>Your server calls <code>POST /api/v1/payment-links</code> with the amount and description.</li>
                <li>The API returns a <code>payment_url</code>. Redirect your customer to that URL.</li>
                <li>The customer completes payment on the Edfundo hosted page via their bank.</li>
                <li>Edfundo redirects the customer to your <code>return_url</code> and fires a webhook to your server.</li>
            </ol>
        </section>

        {{-- Authentication --}}
        <section class="doc-section" id="authentication">
            <h2>Authentication</h2>
            <p>All API requests must include your secret API key in the <code>Authorization</code> header using the <strong>Bearer</strong> scheme.</p>
            <pre><code class="language-http">Authorization: Bearer epk_live_a1b2c3d4e5f6...</code></pre>
            <div class="callout callout-warn">
                <span class="callout-icon">⚠️</span>
                <span>API keys must be kept on your server. Never include them in browser JavaScript, mobile apps, or commit them to source control. Use environment variables.</span>
            </div>
            <p>Generate and manage API keys from <strong>Settings → API & Integrations</strong> in your merchant dashboard. Each key is shown only once when created. You can hold up to 5 active keys and revoke any individually.</p>
        </section>

        {{-- Base URL --}}
        <section class="doc-section" id="base-url">
            <h2>Base URL & Formats</h2>
            <p>All API endpoints are under:</p>
            <pre><code class="language-http">https://pay.edfundo.com/api/v1</code></pre>
            <ul>
                <li>All request bodies must be <code>Content-Type: application/json</code>.</li>
                <li>All responses are JSON.</li>
                <li>Amounts are decimals in <strong>AED</strong> (e.g. <code>150.00</code>). Maximum single payment is AED 50,000.</li>
                <li>Timestamps are ISO 8601 (e.g. <code>2026-06-28T10:00:00+00:00</code>).</li>
                <li>Payment link IDs are UUIDs (e.g. <code>ebc78a63-abfd-4aa7-bd42-69c765b7759a</code>).</li>
            </ul>
        </section>

        {{-- Create Payment Link --}}
        <section class="doc-section" id="create-link">
            <h2>Create a Payment Link</h2>
            <div class="endpoint-header">
                <span class="endpoint-method ep-post">POST</span>
                <span class="endpoint-path">/api/v1/payment-links</span>
            </div>
            <p>Creates a new payment session. Returns a <code>payment_url</code> to redirect your customer to.</p>

            <h3>Request body</h3>
            <table class="param-table">
                <thead><tr><th>Parameter</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>amount</code></td><td class="type">number</td><td><span class="req">Required</span></td><td>Payment amount in AED. Min 0.01, max 50,000.</td></tr>
                    <tr><td><code>description</code></td><td class="type">string</td><td><span class="req">Required</span></td><td>What the payment is for. Shown on the checkout page. Max 255 characters.</td></tr>
                    <tr><td><code>return_url</code></td><td class="type">string</td><td><span class="opt">Optional</span></td><td>URL to redirect the customer to after successful payment. Receives <code>?status=paid&payment_link_id={id}</code>.</td></tr>
                    <tr><td><code>cancel_url</code></td><td class="type">string</td><td><span class="opt">Optional</span></td><td>URL to redirect the customer to if payment fails. Receives <code>?status=failed&payment_link_id={id}</code>.</td></tr>
                    <tr><td><code>customer.name</code></td><td class="type">string</td><td><span class="opt">Optional</span></td><td>Customer's full name.</td></tr>
                    <tr><td><code>customer.email</code></td><td class="type">string</td><td><span class="opt">Optional</span></td><td>Customer's email address.</td></tr>
                    <tr><td><code>customer.mobile</code></td><td class="type">string</td><td><span class="opt">Optional</span></td><td>Customer's mobile number, including country code (e.g. +971501234567).</td></tr>
                </tbody>
            </table>

            <h3>Example request</h3>
            <pre><code class="language-bash">curl -X POST https://pay.edfundo.com/api/v1/payment-links \
  -H "Authorization: Bearer epk_live_..." \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 150.00,
    "description": "Swimming lesson - Term 2",
    "return_url": "https://yoursite.com/orders/123/success",
    "cancel_url": "https://yoursite.com/orders/123/failed",
    "customer": {
      "name": "Ahmed Al Mansouri",
      "email": "ahmed@example.com",
      "mobile": "+971501234567"
    }
  }'</code></pre>

            <h3>Response <span class="status s-201">201 Created</span></h3>
            <pre><code class="language-json">{
  "data": {
    "id": "ebc78a63-abfd-4aa7-bd42-69c765b7759a",
    "payment_url": "https://pay.edfundo.com/invoice/ebc78a63-abfd-4aa7-bd42-69c765b7759a",
    "amount": 150.00,
    "currency": "AED",
    "description": "Swimming lesson - Term 2",
    "status": "pending",
    "return_url": "https://yoursite.com/orders/123/success",
    "cancel_url": "https://yoursite.com/orders/123/failed",
    "customer": {
      "name": "Ahmed Al Mansouri",
      "email": "ahmed@example.com",
      "mobile": "+971501234567"
    },
    "paid_at": null,
    "created_at": "2026-06-28T10:00:00+00:00"
  }
}</code></pre>
            <p>Redirect your customer to the <code>payment_url</code> immediately after receiving this response.</p>
        </section>

        {{-- Get Payment Link --}}
        <section class="doc-section" id="get-link">
            <h2>Get a Payment Link</h2>
            <div class="endpoint-header">
                <span class="endpoint-method ep-get">GET</span>
                <span class="endpoint-path">/api/v1/payment-links/{id}</span>
            </div>
            <p>Retrieves a payment link by its ID (UUID). Use this to poll the status of a payment, though webhooks are the preferred approach.</p>

            <h3>Path parameters</h3>
            <table class="param-table">
                <thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>id</code></td><td class="type">string (UUID)</td><td>The payment link ID returned when the link was created.</td></tr>
                </tbody>
            </table>

            <h3>Example request</h3>
            <pre><code class="language-bash">curl https://pay.edfundo.com/api/v1/payment-links/ebc78a63-abfd-4aa7-bd42-69c765b7759a \
  -H "Authorization: Bearer epk_live_..."</code></pre>

            <h3>Response <span class="status s-200">200 OK</span></h3>
            <pre><code class="language-json">{
  "data": {
    "id": "ebc78a63-abfd-4aa7-bd42-69c765b7759a",
    "payment_url": "https://pay.edfundo.com/invoice/ebc78a63-abfd-4aa7-bd42-69c765b7759a",
    "amount": 150.00,
    "currency": "AED",
    "description": "Swimming lesson - Term 2",
    "status": "paid",
    "customer": {
      "name": "Ahmed Al Mansouri",
      "email": "ahmed@example.com",
      "mobile": "+971501234567"
    },
    "paid_at": "2026-06-28T10:05:22+00:00",
    "created_at": "2026-06-28T10:00:00+00:00"
  }
}</code></pre>
        </section>

        {{-- List Payments --}}
        <section class="doc-section" id="list-payments">
            <h2>List Payments</h2>
            <div class="endpoint-header">
                <span class="endpoint-method ep-get">GET</span>
                <span class="endpoint-path">/api/v1/payments</span>
            </div>
            <p>Returns a paginated list of payments for your account, newest first. Useful for reconciliation and reporting.</p>

            <h3>Query parameters</h3>
            <table class="param-table">
                <thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>status</code></td><td class="type">string</td><td>Filter by status: <code>pending</code>, <code>complete</code>, or <code>failed</code>.</td></tr>
                    <tr><td><code>date_from</code></td><td class="type">date (YYYY-MM-DD)</td><td>Return payments on or after this date.</td></tr>
                    <tr><td><code>date_to</code></td><td class="type">date (YYYY-MM-DD)</td><td>Return payments on or before this date.</td></tr>
                    <tr><td><code>per_page</code></td><td class="type">integer</td><td>Results per page. Default 20, max 100.</td></tr>
                </tbody>
            </table>

            <h3>Example request</h3>
            <pre><code class="language-bash">curl "https://pay.edfundo.com/api/v1/payments?status=complete&date_from=2026-06-01" \
  -H "Authorization: Bearer epk_live_..."</code></pre>

            <h3>Response <span class="status s-200">200 OK</span></h3>
            <pre><code class="language-json">{
  "data": [
    {
      "id": 42,
      "payment_link_id": "ebc78a63-abfd-4aa7-bd42-69c765b7759a",
      "amount": 150.00,
      "currency": "AED",
      "description": "Swimming lesson - Term 2",
      "status": "complete",
      "customer": {
        "name": "Ahmed Al Mansouri",
        "email": "ahmed@example.com",
        "mobile": "+971501234567"
      },
      "paid_at": "2026-06-28T10:05:22+00:00",
      "created_at": "2026-06-28T10:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3
  }
}</code></pre>
        </section>

        {{-- Webhooks Overview --}}
        <section class="doc-section" id="webhook-overview">
            <h2>Webhooks</h2>
            <p>Edfundo sends a signed HTTP POST to your configured webhook URL whenever a payment changes status. Webhooks are the most reliable way to update your system — they fire even if the customer closes their browser before being redirected back to your site.</p>
            <p>Configure your webhook URL in <strong>Settings → Business Profile</strong>. A secret is auto-generated when you save a URL for the first time.</p>
            <div class="callout callout-info">
                <span class="callout-icon">ℹ️</span>
                <span>Your endpoint must return a <strong>2xx HTTP status</strong> within 10 seconds. Failed deliveries are logged but not automatically retried in the current version.</span>
            </div>
        </section>

        {{-- Webhook Events --}}
        <section class="doc-section" id="webhook-events">
            <h2>Webhook Events</h2>

            <div class="event-badge">⚡ payment.completed</div>
            <p>Fired when a payment is successfully confirmed by the bank.</p>
            <pre><code class="language-json">{
  "event": "payment.completed",
  "created_at": "2026-06-28T10:05:22+00:00",
  "data": {
    "payment_link_id": "ebc78a63-abfd-4aa7-bd42-69c765b7759a",
    "payment_id": 42,
    "amount": 150.00,
    "currency": "AED",
    "status": "paid",
    "description": "Swimming lesson - Term 2",
    "customer": {
      "name": "Ahmed Al Mansouri",
      "email": "ahmed@example.com",
      "mobile": "+971501234567"
    },
    "paid_at": "2026-06-28T10:05:22+00:00"
  }
}</code></pre>

            <div class="event-badge">❌ payment.failed</div>
            <p>Fired when a payment is declined or cancelled by the customer.</p>
            <pre><code class="language-json">{
  "event": "payment.failed",
  "created_at": "2026-06-28T10:04:11+00:00",
  "data": {
    "payment_link_id": "ebc78a63-abfd-4aa7-bd42-69c765b7759a",
    "payment_id": 42,
    "amount": 150.00,
    "currency": "AED",
    "status": "failed",
    "description": "Swimming lesson - Term 2",
    "customer": { ... },
    "paid_at": null
  }
}</code></pre>
        </section>

        {{-- Webhook Verification --}}
        <section class="doc-section" id="webhook-verify">
            <h2>Verifying Webhook Signatures</h2>
            <p>Every webhook request includes an <code>X-Edfundo-Signature</code> header. Always verify this before processing the event — it proves the request genuinely came from Edfundo and the payload hasn't been tampered with.</p>
            <p>The signature is computed as <code>sha256=HMAC-SHA256(raw_request_body, webhook_secret)</code>.</p>

            <div class="code-tabs">
                <div class="code-tab-btns">
                    <button class="code-tab-btn active" onclick="switchTab(this, 'verify-php')">PHP</button>
                    <button class="code-tab-btn" onclick="switchTab(this, 'verify-node')">Node.js</button>
                    <button class="code-tab-btn" onclick="switchTab(this, 'verify-python')">Python</button>
                </div>
                <div id="verify-php" class="code-tab-panel active">
                    <pre><code class="language-php">&lt;?php
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_EDFUNDO_SIGNATURE'] ?? '';
$secret    = getenv('EDFUNDO_WEBHOOK_SECRET');

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($payload, true);

if ($event['event'] === 'payment.completed') {
    $paymentLinkId = $event['data']['payment_link_id'];
    $amount        = $event['data']['amount'];
    // Mark the order as paid in your system
}

http_response_code(200);
echo 'OK';</code></pre>
                </div>
                <div id="verify-node" class="code-tab-panel">
                    <pre><code class="language-javascript">const crypto = require('crypto');

app.post('/webhooks/edfundo', express.raw({ type: 'application/json' }), (req, res) => {
    const signature = req.headers['x-edfundo-signature'];
    const secret    = process.env.EDFUNDO_WEBHOOK_SECRET;
    const expected  = 'sha256=' + crypto
        .createHmac('sha256', secret)
        .update(req.body)
        .digest('hex');

    if (!crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(signature))) {
        return res.status(401).send('Invalid signature');
    }

    const event = JSON.parse(req.body);

    if (event.event === 'payment.completed') {
        const { payment_link_id, amount } = event.data;
        // Mark the order as paid in your system
    }

    res.status(200).send('OK');
});</code></pre>
                </div>
                <div id="verify-python" class="code-tab-panel">
                    <pre><code class="language-python">import hmac, hashlib, os
from flask import Flask, request

app = Flask(__name__)

@app.route('/webhooks/edfundo', methods=['POST'])
def webhook():
    payload   = request.get_data()
    signature = request.headers.get('X-Edfundo-Signature', '')
    secret    = os.environ['EDFUNDO_WEBHOOK_SECRET'].encode()

    expected = 'sha256=' + hmac.new(secret, payload, hashlib.sha256).hexdigest()

    if not hmac.compare_digest(expected, signature):
        return 'Invalid signature', 401

    event = request.get_json()

    if event['event'] == 'payment.completed':
        payment_link_id = event['data']['payment_link_id']
        amount          = event['data']['amount']
        # Mark the order as paid in your system

    return 'OK', 200</code></pre>
                </div>
            </div>

            <div class="callout callout-warn">
                <span class="callout-icon">⚠️</span>
                <span>Always use a <strong>constant-time comparison</strong> (e.g. <code>hash_equals</code> in PHP, <code>timingSafeEqual</code> in Node) to prevent timing attacks. Never use <code>===</code> or <code>==</code> to compare signatures.</span>
            </div>
        </section>

        {{-- Embed Overview --}}
        <section class="doc-section" id="embed-overview">
            <h2>Embed Button</h2>
            <p>The Edfundo Pay embed script renders a branded "Pay by Bank" button on your checkout page. It's a single JavaScript file with no dependencies — just drop it in and point it at a payment URL.</p>
            <div class="callout callout-info">
                <span class="callout-icon">ℹ️</span>
                <span>The embed script does <strong>not</strong> call the Edfundo API directly. Your server creates the payment link first and passes the resulting <code>payment_url</code> to the script via a <code>data-</code> attribute. This keeps your API key server-side only.</span>
            </div>
        </section>

        {{-- Embed Usage --}}
        <section class="doc-section" id="embed-usage">
            <h2>Using the Embed Button</h2>

            <h3>Step 1 — Your server creates the payment link</h3>
            <pre><code class="language-php">&lt;?php
// In your checkout controller (Laravel example)
$response = Http::withToken(env('EDFUNDO_API_KEY'))
    ->post('https://pay.edfundo.com/api/v1/payment-links', [
        'amount'      => 150.00,
        'description' => 'Swimming lesson - Term 2',
        'return_url'  => route('checkout.success', $order->id),
        'cancel_url'  => route('checkout.failed',  $order->id),
        'customer'    => [
            'name'   => $customer->name,
            'email'  => $customer->email,
            'mobile' => $customer->mobile,
        ],
    ]);

$paymentUrl = $response->json('data.payment_url');</code></pre>

            <h3>Step 2 — Render the button in your template</h3>
            <pre><code class="language-html">&lt;div
  data-edfundo-pay
  data-payment-url="@{{ $paymentUrl }}"
  data-amount="150.00"
  data-currency="AED"
&gt;&lt;/div&gt;
&lt;script src="https://pay.edfundo.com/js/embed.js" async&gt;&lt;/script&gt;</code></pre>

            <h3>Button attributes</h3>
            <table class="param-table">
                <thead><tr><th>Attribute</th><th>Required</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>data-edfundo-pay</code></td><td><span class="req">Required</span></td><td>Marks the container for the embed script to find.</td></tr>
                    <tr><td><code>data-payment-url</code></td><td><span class="req">Required</span></td><td>The <code>payment_url</code> returned by the API.</td></tr>
                    <tr><td><code>data-amount</code></td><td><span class="opt">Optional</span></td><td>Numeric amount — shown in the button label (e.g. "Pay AED 150.00 by Bank").</td></tr>
                    <tr><td><code>data-currency</code></td><td><span class="opt">Optional</span></td><td>Currency code. Defaults to <code>AED</code>.</td></tr>
                    <tr><td><code>data-label</code></td><td><span class="opt">Optional</span></td><td>Fully custom button label, overrides the auto-generated text.</td></tr>
                </tbody>
            </table>
        </section>

        {{-- Errors --}}
        <section class="doc-section" id="errors">
            <h2>Error Codes</h2>
            <p>All errors return a JSON body with a <code>message</code> field. Validation errors also include an <code>errors</code> object.</p>
            <table class="param-table">
                <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
                <tbody>
                    <tr><td><span class="status s-401">401</span></td><td>Missing or invalid API key. Check your <code>Authorization: Bearer</code> header.</td></tr>
                    <tr><td><span class="status s-404">404</span></td><td>Payment link not found, or it belongs to a different merchant account.</td></tr>
                    <tr><td><span class="status s-422">422</span></td><td>Validation failed. The <code>errors</code> field lists which parameters are invalid.</td></tr>
                </tbody>
            </table>

            <h3>Example validation error</h3>
            <pre><code class="language-json">{
  "message": "Validation failed.",
  "errors": {
    "amount": ["The amount field is required."],
    "description": ["The description field is required."]
  }
}</code></pre>
        </section>

        {{-- Statuses --}}
        <section class="doc-section" id="statuses">
            <h2>Payment Statuses</h2>
            <table class="param-table">
                <thead><tr><th>Status</th><th>Applies to</th><th>Meaning</th></tr></thead>
                <tbody>
                    <tr><td><code>pending</code></td><td>Payment link</td><td>Link created, awaiting customer payment.</td></tr>
                    <tr><td><code>paid</code></td><td>Payment link</td><td>Payment confirmed by the bank.</td></tr>
                    <tr><td><code>failed</code></td><td>Payment link</td><td>Payment was declined or cancelled.</td></tr>
                    <tr><td><code>archived</code></td><td>Payment link</td><td>Link has been archived by the merchant.</td></tr>
                    <tr><td><code>pending</code></td><td>Payment</td><td>Payment initiated but not yet confirmed.</td></tr>
                    <tr><td><code>complete</code></td><td>Payment</td><td>Bank confirmed the transfer.</td></tr>
                    <tr><td><code>failed</code></td><td>Payment</td><td>Bank declined or customer cancelled.</td></tr>
                </tbody>
            </table>
        </section>

    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-http.min.js"></script>
<script>
// Tab switcher
function switchTab(btn, panelId) {
    var tabs = btn.parentElement;
    var container = tabs.parentElement;
    tabs.querySelectorAll('.code-tab-btn').forEach(function(b) { b.classList.remove('active'); });
    container.querySelectorAll('.code-tab-panel').forEach(function(p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById(panelId).classList.add('active');
}

// Active sidebar link on scroll
var sections = document.querySelectorAll('.doc-section');
var links    = document.querySelectorAll('.sidebar-link');

var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
        if (entry.isIntersecting) {
            var id = entry.target.id;
            links.forEach(function(l) {
                l.classList.toggle('active', l.getAttribute('href') === '#' + id);
            });
        }
    });
}, { rootMargin: '-20% 0px -70% 0px' });

sections.forEach(function(s) { observer.observe(s); });
</script>
</body>
</html>
