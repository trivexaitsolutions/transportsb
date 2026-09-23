<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'XYZ Transport')</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slim-select@2.12.1/dist/slimselect.css">
    <script src="https://cdn.jsdelivr.net/npm/slim-select@2.12.1/dist/slimselect.min.js"></script>
    @stack('styles')
    @include('partials.desktop-responsive-styles')
    <style>
        .ss-main { min-height: 2.5rem; border-radius: 0 !important; border-color: #cbd5e1 !important; box-shadow: none !important; }
        .ss-main:focus { border-color: #047857 !important; box-shadow: 0 0 0 2px #d1fae5 !important; }
        .ss-content { border-radius: 0 !important; z-index: 180 !important; }
        .app-topbar { overflow: visible; }
        .global-selector{position:fixed;inset:0;z-index:260;background:rgba(15,23,42,.55);display:flex;align-items:center;justify-content:center;padding:1rem}
        .global-selector.hidden{display:none!important}.global-selector-card{width:min(680px,96vw);background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}
        .global-selector-list{max-height:340px;overflow:auto;border:1px solid #cbd5e1}.selector-item{display:block;width:100%;padding:.7rem .85rem;text-align:left;border-bottom:1px solid #e2e8f0;background:white;font-weight:700}.selector-item.active,.selector-item:focus{background:#d1fae5;outline:none}.selector-add-btn{height:36px;border:1px solid #047857;background:#ecfdf5;color:#065f46;padding:0 12px;font-weight:900}.selector-add-btn.hidden{display:none!important}.quick-master-card{width:min(860px,97vw);max-height:92vh;overflow:auto;background:#fff;border:1px solid #64748b;box-shadow:0 24px 70px rgba(15,23,42,.35)}.quick-master-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.quick-master-field.wide{grid-column:1/-1}.quick-master-field span{display:block;margin-bottom:.25rem;font-size:.68rem;font-weight:900;text-transform:uppercase;color:#475569}.quick-master-input{width:100%;height:2.4rem;border:1px solid #94a3b8;background:#fff;padding:0 .6rem;outline:none}.quick-master-textarea{width:100%;min-height:72px;border:1px solid #94a3b8;background:#fff;padding:.55rem;outline:none}.quick-master-input:focus,.quick-master-textarea:focus{border-color:#047857;box-shadow:0 0 0 2px #d1fae5}@media(max-width:700px){.quick-master-grid{grid-template-columns:1fr}}
        .app-toast-stack{position:fixed;top:4.25rem;right:1rem;z-index:500;display:flex;width:min(420px,calc(100vw - 2rem));flex-direction:column;gap:.55rem;pointer-events:none}
        .app-toast{pointer-events:auto;display:flex;align-items:flex-start;gap:.7rem;border:1px solid #cbd5e1;background:#fff;padding:.8rem .9rem;box-shadow:0 14px 35px rgba(15,23,42,.22);font-size:.82rem;font-weight:800;line-height:1.35;color:#0f172a;animation:appToastIn .16s ease-out}
        .app-toast.error{border-left:5px solid #dc2626}.app-toast.success{border-left:5px solid #059669}.app-toast.warning{border-left:5px solid #d97706}.app-toast.info{border-left:5px solid #2563eb}
        .app-toast-icon{font-size:1rem;line-height:1.1}.app-toast-message{flex:1}.app-toast-close{border:0;background:transparent;color:#475569;font-size:1.05rem;font-weight:900;line-height:1;cursor:pointer}
        @keyframes appToastIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        @media (max-width: 900px) {
            body { padding-top: 7rem !important; }
            .app-topbar { height: auto !important; min-height: 7rem; }
            .app-topbar > div { flex-wrap: wrap; padding: .35rem .65rem !important; }
            .app-topbar nav { order: 3; width: 100%; flex-basis: 100%; overflow-x: auto; }
            .app-topbar nav > * { flex: 0 0 auto; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 pt-14 text-slate-900">
<header class="app-topbar fixed inset-x-0 top-0 z-50 h-14 bg-emerald-950 text-white shadow-sm">
    <div class="flex h-full items-stretch px-5">
        <a href="{{ route('sale.orders.index') }}" class="flex items-center pr-8 text-base font-bold tracking-wide">XYZ TRANSPORT</a>

        <nav class="flex flex-1 items-stretch" data-keyboard-nav>
            <div class="relative" data-nav-dropdown>
                <button type="button" id="nav-sale" data-nav-item data-nav-toggle aria-label="Sale"
                    class="flex h-full items-center gap-2 {{ request()->routeIs('sale.*') ? 'bg-white/15' : '' }} px-4 text-sm font-semibold text-white outline-none focus:bg-white/20 focus:ring-2 focus:ring-inset focus:ring-amber-300">
                    Sale
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </button>
                <div class="absolute left-0 top-full z-50 hidden w-64 border border-slate-200 bg-white py-1 text-slate-800 shadow-xl" data-nav-menu>
                    <a href="{{ route('sale.orders.index') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">SO / Sales Order</a>
                    <a href="{{ route('sale.vouchers.index') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Voucher</a>
                    <a href="{{ route('sale.billing.index') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Bill / Acknowledgement</a>
                </div>
            </div>

            <div class="relative" data-nav-dropdown>
                <button type="button" id="nav-payments" data-nav-item data-nav-toggle aria-label="Payments"
                    class="flex h-full items-center gap-2 {{ request()->routeIs('payments.*') || request()->routeIs('ledgers.*') ? 'bg-white/15' : '' }} px-4 text-sm font-semibold text-white outline-none focus:bg-white/20 focus:ring-2 focus:ring-inset focus:ring-amber-300">
                    Payments
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </button>
                <div class="absolute left-0 top-full z-50 hidden w-64 border border-slate-200 bg-white py-1 text-slate-800 shadow-xl" data-nav-menu>
                    <a href="{{ route('payments.suppliers.index') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Supplier Payment</a>
                    <a href="{{ route('payments.customers.index') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Customer Receipt</a>
                    <a href="{{ route('payments.bank.index') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Bank</a>
                    <div class="my-1 border-t border-slate-200"></div>
                    <a href="{{ route('ledgers.suppliers.index') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Supplier Ledger</a>
                    <a href="{{ route('ledgers.customers.index') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Customer Ledger</a>
                </div>
            </div>

            <div class="relative" data-nav-dropdown>
                <button type="button" id="nav-masters" data-nav-item data-nav-toggle aria-label="Masters"
                    class="flex h-full items-center gap-2 {{ request()->routeIs('masters.*') ? 'bg-white/15' : '' }} px-4 text-sm font-semibold text-white outline-none focus:bg-white/20 focus:ring-2 focus:ring-inset focus:ring-amber-300">
                    Masters
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </button>
                <div class="absolute left-0 top-full z-50 hidden w-64 border border-slate-200 bg-white py-1 text-slate-800 shadow-xl" data-nav-menu>
                    <a href="{{ route('masters.index', ['type' => 'customers']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Customers</a>
                    <a href="{{ route('masters.index', ['type' => 'suppliers']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Suppliers / Transporters</a>
                    <a href="{{ route('masters.index', ['type' => 'vehicle-types']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Vehicle Types</a>
                    <a href="{{ route('masters.index', ['type' => 'transport-names']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Transport Names</a>
                    <a href="{{ route('masters.index', ['type' => 'gst-rates']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">GST Master</a>
                    <a href="{{ route('masters.index', ['type' => 'banks']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Bank Master</a>
                    <a href="{{ route('masters.so-series.index') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">SO Number Series</a>
                    <a href="{{ route('masters.settings') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Settings</a>
                </div>
            </div>
        </nav>

        <div class="flex items-center gap-3 pl-4">
            <span class="text-sm text-emerald-100">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" class="border border-white/30 px-3 py-1.5 text-xs font-semibold hover:bg-white/10">Logout</button>
            </form>
        </div>
    </div>
</header>

<main class="app-main p-5" data-page-nav-target="@yield('page-nav-target', '#nav-masters')">@yield('content')</main>

<div id="globalSelector" class="global-selector hidden" role="dialog" aria-modal="true" aria-labelledby="globalSelectorTitle">
    <div class="global-selector-card">
        <div class="flex items-center justify-between bg-emerald-950 px-5 py-3 text-white">
            <h2 id="globalSelectorTitle" class="font-black">Select</h2>
            <button type="button" data-selector-close class="text-2xl">×</button>
        </div>
        <div class="p-4">
            <div class="mb-3 flex gap-2">
                <input id="globalSelectorSearch" class="master-input flex-1" placeholder="Type to search..." autocomplete="off">
                <button type="button" id="globalSelectorAdd" class="selector-add-btn hidden shrink-0 whitespace-nowrap">+ Add New</button>
            </div>
            <div id="globalSelectorList" class="global-selector-list"></div>
            <div class="mt-2 text-xs font-semibold text-slate-500">↑ ↓ Navigate · Enter Select · Insert Add New · Esc Close</div>
        </div>
    </div>
</div>

<div id="quickMasterModal" class="global-selector hidden" role="dialog" aria-modal="true" aria-labelledby="quickMasterTitle">
    <div class="quick-master-card">
        <div class="flex items-center justify-between bg-emerald-950 px-5 py-3 text-white">
            <h2 id="quickMasterTitle" class="font-black">Add New</h2>
            <button type="button" id="quickMasterClose" class="text-2xl">×</button>
        </div>
        <form id="quickMasterForm" class="p-4">
            <div id="quickMasterFields"></div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" id="quickMasterCancel" class="border border-slate-400 px-4 py-2 font-bold">Cancel</button>
                <button type="submit" id="quickMasterSave" class="bg-emerald-800 px-5 py-2 font-black text-white">Save</button>
            </div>
            <div class="mt-2 text-right text-xs font-semibold text-slate-500">Enter Next Field · Ctrl+S Save · Esc Back to Selector</div>
        </form>
    </div>
</div>

@php
    $appToastFlashes = array_values(array_filter([
        session('success') ? ['message' => session('success'), 'type' => 'success'] : null,
        session('error') ? ['message' => session('error'), 'type' => 'error'] : null,
        session('warning') ? ['message' => session('warning'), 'type' => 'warning'] : null,
        session('status') ? ['message' => session('status'), 'type' => 'info'] : null,
    ]));
    $appToastValidationErrors = $errors->all();
@endphp
<div id="appToastStack" class="app-toast-stack" aria-live="polite" aria-atomic="true"></div>

<script>
window.AppToast = function (message, type = 'info', timeout = 4200) {
    const stack = document.getElementById('appToastStack');
    if (!stack || !message) return;
    const toast = document.createElement('div');
    const safeType = ['error','success','warning','info'].includes(type) ? type : 'info';
    toast.className = 'app-toast ' + safeType;
    toast.setAttribute('role', safeType === 'error' ? 'alert' : 'status');
    const icon = safeType === 'error' ? '⚠' : (safeType === 'success' ? '✓' : (safeType === 'warning' ? '!' : 'ℹ'));
    toast.innerHTML = `<span class="app-toast-icon">${icon}</span><span class="app-toast-message"></span><button type="button" class="app-toast-close" aria-label="Close notification">×</button>`;
    toast.querySelector('.app-toast-message').textContent = String(message);
    const remove = () => { if (toast.parentNode) toast.remove(); };
    toast.querySelector('.app-toast-close').addEventListener('click', remove);
    stack.appendChild(toast);
    window.setTimeout(remove, timeout);
};
window.AppNotify = window.AppToast;
window.AppToastNext = function (message, type = 'success') {
    try { sessionStorage.setItem('xyz_app_toast', JSON.stringify({message, type})); } catch (e) {}
};
document.addEventListener('DOMContentLoaded', function () {
    try {
        const pending = JSON.parse(sessionStorage.getItem('xyz_app_toast') || 'null');
        sessionStorage.removeItem('xyz_app_toast');
        if (pending?.message) window.AppToast?.(pending.message, pending.type || 'info');
    } catch (e) {}
    const flashes = @json($appToastFlashes);
    flashes.forEach(item => window.AppToast?.(item.message, item.type));
    const validationErrors = @json($appToastValidationErrors);
    validationErrors.forEach(message => window.AppToast?.(message, 'error', 5500));
});
</script>
<script src="{{ asset('js/master-selector.js') }}?v={{ filemtime(public_path('js/master-selector.js')) }}"></script>
@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[data-slim-select]').forEach(function (select) {
        if (select.dataset.slimReady) return;
        try { new SlimSelect({ select: select, settings: { allowDeselect: true, placeholderText: select.dataset.placeholder || 'Select' } }); select.dataset.slimReady = '1'; } catch (e) {}
    });

    const nav = document.querySelector('[data-keyboard-nav]');
    if (!nav) return;
    const topItems = Array.from(nav.querySelectorAll(':scope > [data-nav-item], :scope > [data-nav-dropdown] > [data-nav-item]'));
    const dropdowns = Array.from(nav.querySelectorAll('[data-nav-dropdown]'));
    const parts = d => ({ toggle: d.querySelector('[data-nav-toggle]'), menu: d.querySelector('[data-nav-menu]'), items: Array.from(d.querySelectorAll('[data-nav-subitem]')) });
    const setOpen = (d, open) => { const p=parts(d); p.menu.classList.toggle('hidden', !open); p.toggle.setAttribute('aria-expanded', String(open)); };
    const closeAll = (except=null) => dropdowns.forEach(d => { if (d!==except) setOpen(d,false); });
    const focusTop = idx => { if (!topItems.length) return; idx=Math.max(0,Math.min(idx,topItems.length-1)); closeAll(); topItems[idx].focus(); };

    dropdowns.forEach(d => {
        const p=parts(d);
        p.toggle.addEventListener('click', e => { e.preventDefault(); const open=p.menu.classList.contains('hidden'); closeAll(open?d:null); setOpen(d,open); });
        d.addEventListener('mouseenter', () => { closeAll(d); setOpen(d,true); });
        d.addEventListener('mouseleave', () => { if (!d.contains(document.activeElement)) setOpen(d,false); });
    });

    nav.addEventListener('keydown', e => {
        const sub=e.target.closest('[data-nav-subitem]');
        const top=e.target.closest('[data-nav-item]');
        if ((sub||top) && ['ArrowDown','ArrowUp','ArrowLeft','ArrowRight','Enter','Escape'].includes(e.key)) e.stopPropagation();
        if (sub) {
            const d=sub.closest('[data-nav-dropdown]'), p=parts(d), i=p.items.indexOf(sub), pi=topItems.indexOf(p.toggle);
            if (e.key==='ArrowDown'){e.preventDefault();p.items[Math.min(i+1,p.items.length-1)]?.focus();}
            else if (e.key==='ArrowUp'){e.preventDefault(); if(i===0){setOpen(d,false);p.toggle.focus();}else p.items[i-1]?.focus();}
            else if (e.key==='ArrowRight'){e.preventDefault();focusTop(pi+1);}
            else if (e.key==='ArrowLeft'){e.preventDefault();focusTop(pi-1);}
            else if (e.key==='Escape'){e.preventDefault();setOpen(d,false);p.toggle.focus();}
            return;
        }
        if (!top) return;
        const i=topItems.indexOf(top), d=top.closest('[data-nav-dropdown]');
        if (e.key==='ArrowRight'){e.preventDefault();focusTop(i+1);}
        else if (e.key==='ArrowLeft'){e.preventDefault();focusTop(i-1);}
        else if (d && (e.key==='ArrowDown'||e.key==='Enter')){e.preventDefault();closeAll(d);setOpen(d,true);parts(d).items[0]?.focus();}
    });
    document.addEventListener('click', e => { if (!nav.contains(e.target)) closeAll(); });

    window.AppPageExit = function (navSelector=null) {
        if (typeof window.AppBeforePageExit === 'function' && window.AppBeforePageExit() === false) return;
        const main=document.querySelector('.app-main');
        const target=navSelector||main?.dataset.pageNavTarget||'#nav-masters';
        document.querySelectorAll('.app-workspace').forEach(w=>w.classList.add('hidden'));
        main?.classList.add('is-page-exited');
        setTimeout(()=>document.querySelector(target)?.focus(),0);
    };

    document.addEventListener('keydown', function (e) {
        if (e.key!=='Escape'||e.defaultPrevented||e.ctrlKey||e.altKey||e.metaKey) return;
        if (e.target instanceof Element && e.target.closest('[data-keyboard-nav]')) return;
        const openOverlay=Array.from(document.querySelectorAll('[role="dialog"], .fixed.inset-0')).some(el=>!el.classList.contains('hidden')&&el.getClientRects().length);
        if (openOverlay) return;
        e.preventDefault(); window.AppPageExit();
    });
});
</script>
</body>
</html>
