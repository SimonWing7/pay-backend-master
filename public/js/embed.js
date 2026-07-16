/**
 * Edfundo Pay — Embed Script
 *
 * Renders a branded "Pay by Bank" button linked to an Edfundo payment page.
 *
 * Usage:
 *   <div
 *     data-edfundo-pay
 *     data-payment-url="https://yourapp.com/invoice/UUID"
 *     data-amount="150.00"
 *     data-currency="AED"
 *   ></div>
 *   <script src="https://yourapp.com/js/embed.js" async></script>
 *
 * Attributes:
 *   data-payment-url  (required) The payment page URL returned by POST /api/v1/payment-links
 *   data-amount       (optional) Numeric amount — shown in the button label
 *   data-currency     (optional) Currency code, defaults to AED
 *   data-label        (optional) Fully custom button label, overrides the auto-generated one
 */
(function () {
  'use strict';

  // ------------------------------------------------------------------
  // Styles — injected once into <head>
  // ------------------------------------------------------------------
  var STYLE_ID = 'edfundo-embed-styles';
  var STYLES = [
    '.edfundo-btn {',
    '  display: inline-flex;',
    '  align-items: center;',
    '  justify-content: center;',
    '  gap: 10px;',
    '  background: linear-gradient(135deg, #3d01bd 0%, #00bdff 100%);',
    '  color: #ffffff;',
    '  border: none;',
    '  border-radius: 10px;',
    '  padding: 14px 24px;',
    '  font-size: 15px;',
    '  font-weight: 700;',
    '  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;',
    '  cursor: pointer;',
    '  min-width: 220px;',
    '  width: 100%;',
    '  box-sizing: border-box;',
    '  text-decoration: none;',
    '  transition: opacity 0.15s ease, transform 0.1s ease;',
    '  -webkit-font-smoothing: antialiased;',
    '}',
    '.edfundo-btn:hover  { opacity: 0.88; transform: translateY(-1px); }',
    '.edfundo-btn:active { opacity: 0.95; transform: translateY(0);    }',
    '.edfundo-btn.loading { opacity: 0.7; cursor: not-allowed; pointer-events: none; }',
    '.edfundo-btn svg { flex-shrink: 0; }',
    '.edfundo-badge {',
    '  display: inline-flex;',
    '  align-items: center;',
    '  gap: 5px;',
    '  font-size: 11px;',
    '  font-weight: 600;',
    '  color: #9ca3af;',
    '  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;',
    '  margin-top: 8px;',
    '}',
    '.edfundo-badge svg { opacity: 0.7; }',
    '.edfundo-wrap { width: 100%; }',
  ].join('\n');

  // ------------------------------------------------------------------
  // SVG icons (inline, no external dependencies)
  // ------------------------------------------------------------------
  var ICON_BANK = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="22" x2="21" y2="22"/><line x1="6" y1="18" x2="6" y2="11"/><line x1="10" y1="18" x2="10" y2="11"/><line x1="14" y1="18" x2="14" y2="11"/><line x1="18" y1="18" x2="18" y2="11"/><polygon points="12 2 2 7 22 7"/></svg>';
  var ICON_LOCK = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
  var ICON_SPINNER = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/></path></svg>';

  // ------------------------------------------------------------------
  // Inject stylesheet once
  // ------------------------------------------------------------------
  function injectStyles() {
    if (document.getElementById(STYLE_ID)) return;
    var style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = STYLES;
    document.head.appendChild(style);
  }

  // ------------------------------------------------------------------
  // Build and mount a single button
  // ------------------------------------------------------------------
  function mountButton(container) {
    var paymentUrl = container.getAttribute('data-payment-url');
    var amount     = container.getAttribute('data-amount');
    var currency   = container.getAttribute('data-currency') || 'AED';
    var customLabel = container.getAttribute('data-label');

    if (!paymentUrl) {
      console.error('[Edfundo] Missing data-payment-url on', container);
      return;
    }

    // Build label
    var label;
    if (customLabel) {
      label = customLabel;
    } else if (amount) {
      var formatted = parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      label = 'Pay ' + currency + ' ' + formatted + ' by Bank';
    } else {
      label = 'Pay by Bank';
    }

    // Wrapper
    var wrap = document.createElement('div');
    wrap.className = 'edfundo-wrap';

    // Button
    var btn = document.createElement('button');
    btn.className = 'edfundo-btn';
    btn.setAttribute('type', 'button');
    btn.setAttribute('aria-label', label);
    btn.innerHTML = ICON_BANK + '<span>' + label + '</span>';

    btn.addEventListener('click', function () {
      btn.classList.add('loading');
      btn.innerHTML = ICON_SPINNER + '<span>Redirecting…</span>';
      window.location.href = paymentUrl;
    });

    // "Secured by Edfundo" badge
    var badge = document.createElement('div');
    badge.className = 'edfundo-badge';
    badge.innerHTML = ICON_LOCK + 'Secured by Edfundo Pay';

    wrap.appendChild(btn);
    wrap.appendChild(badge);
    container.appendChild(wrap);
  }

  // ------------------------------------------------------------------
  // Find all containers and mount
  // ------------------------------------------------------------------
  function init() {
    injectStyles();
    var containers = document.querySelectorAll('[data-edfundo-pay]');
    for (var i = 0; i < containers.length; i++) {
      mountButton(containers[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
