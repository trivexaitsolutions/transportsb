<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <a href="{{ route('dashboard') }}" class="flex items-center pr-8 text-base font-bold tracking-wide">XYZ TRANSPORT</a>

        <nav class="flex flex-1 items-stretch" data-keyboard-nav>
            <a href="{{ route('vouchers.index') }}" id="nav-voucher" data-nav-item aria-label="Voucher"
               class="flex items-center px-4 text-sm font-semibold outline-none focus:bg-white/20 focus:ring-2 focus:ring-inset focus:ring-amber-300 {{ request()->routeIs('vouchers.*') ? 'bg-white/15 text-white' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}">
                Voucher
            </a>

            <div class="relative" data-nav-dropdown>
                <button type="button" id="nav-ledgers" data-nav-item data-nav-toggle aria-label="Ledgers"
                    class="flex h-full items-center gap-2 px-4 text-sm font-semibold outline-none focus:bg-white/20 focus:ring-2 focus:ring-inset focus:ring-amber-300 {{ request()->routeIs('reports.customer-ledger') || request()->routeIs('reports.supplier-ledger') ? 'bg-white/15 text-white' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}">
                    Ledgers
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </button>
                <div class="absolute left-0 top-full z-50 hidden w-60 border border-slate-200 bg-white py-1 text-slate-800 shadow-xl" data-nav-menu>
                    <a href="{{ route('reports.customer-ledger') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Customer Ledger</a>
                    <a href="{{ route('reports.supplier-ledger') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Supplier Ledger</a>
                </div>
            </div>

            <div class="relative" data-nav-dropdown>
                <button type="button" id="nav-reports" data-nav-item data-nav-toggle aria-label="Reports"
                    class="flex h-full items-center gap-2 px-4 text-sm font-semibold outline-none focus:bg-white/20 focus:ring-2 focus:ring-inset focus:ring-amber-300 {{ request()->routeIs('reports.*') && !request()->routeIs('reports.customer-ledger') && !request()->routeIs('reports.supplier-ledger') ? 'bg-white/15 text-white' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}">
                    Reports
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </button>
                <div class="absolute left-0 top-full z-50 hidden w-60 border border-slate-200 bg-white py-1 text-slate-800 shadow-xl" data-nav-menu>
                    <a href="{{ route('reports.vouchers') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Voucher Register</a>
                    <a href="{{ route('reports.outstanding') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Outstanding</a>
                    <a href="{{ route('reports.profit') }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Profit Report</a>
                </div>
            </div>

            <div class="relative" data-nav-dropdown>
                <button type="button" id="nav-masters" data-nav-item data-nav-toggle aria-label="Masters"
                    class="flex h-full items-center gap-2 px-4 text-sm font-semibold outline-none focus:bg-white/20 focus:ring-2 focus:ring-inset focus:ring-amber-300 {{ request()->routeIs('masters.*') ? 'bg-white/15 text-white' : 'text-emerald-100 hover:bg-white/10 hover:text-white' }}">
                    Masters
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                </button>
                <div class="absolute left-0 top-full z-50 hidden w-64 border border-slate-200 bg-white py-1 text-slate-800 shadow-xl" data-nav-menu>
                    <a href="{{ route('masters.index', ['type' => 'customers']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Customers</a>
                    <a href="{{ route('masters.index', ['type' => 'suppliers']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Suppliers / Transporters</a>
                    <a href="{{ route('masters.index', ['type' => 'vehicle-types']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Vehicle Types</a>
                    <a href="{{ route('masters.index', ['type' => 'transport-names']) }}" data-nav-subitem class="block px-4 py-2.5 text-sm font-medium outline-none hover:bg-emerald-50 hover:text-emerald-800 focus:bg-amber-100">Transport Names</a>
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

@php
    $pageNavTarget = match (true) {
        request()->routeIs('vouchers.*') => '#nav-voucher',
        request()->routeIs('reports.customer-ledger'), request()->routeIs('reports.supplier-ledger') => '#nav-ledgers',
        request()->routeIs('masters.*') => '#nav-masters',
        request()->routeIs('reports.*') => '#nav-reports',
        default => '#nav-voucher',
    };
@endphp

<main class="app-main p-5" data-page-nav-target="{{ $pageNavTarget }}">@yield('content')</main>

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
        const main=document.querySelector('.app-main');
        const target=navSelector||main?.dataset.pageNavTarget||'#nav-voucher';
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
