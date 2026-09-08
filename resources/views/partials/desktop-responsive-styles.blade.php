<style id="fruitbilling-desktop-responsive-styles">
    :root {
        --fb-control-height: 2.85rem;
        --fb-table-font: 1.00rem;
        --fb-table-head-font: 0.90rem;
        --fb-cell-x: 0.58rem;
        --fb-cell-y: 0.52rem;
        --fb-page-pad: 1.00rem;
    }

    html,
    body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    /* Accessibility/readability pass for the client's senior operator.
       Font weight increases globally; responsive tables still use fixed widths,
       wrapping and min-width:0 so the larger text cannot widen the page. */
    .app-main,
    .app-workspace {
        font-weight: 600;
        font-size: 1rem;
        line-height: 1.32;
    }

    .app-main h1,
    .app-main h2,
    .app-main h3,
    .app-main label,
    .app-main button,
    .app-workspace label,
    .app-workspace button {
        font-weight: 700;
    }

    .app-main input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
    .app-main select,
    .app-main textarea,
    .app-workspace input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
    .app-workspace select,
    .app-workspace textarea {
        font-weight: 700;
    }

    .app-responsive-table td {
        font-weight: 600;
    }

    .app-responsive-table th {
        font-weight: 800;
    }

    .app-main .text-\[9px\],
    .app-main .text-\[10px\],
    .app-main .text-\[11px\],
    .app-workspace .text-\[9px\],
    .app-workspace .text-\[10px\],
    .app-workspace .text-\[11px\] {
        font-size: 0.75rem !important;
        font-weight: 700 !important;
    }


    /* Senior-operator readability: use a genuinely larger base size.
       Fixed table layout + wrapping below keep every page inside the viewport. */
    .app-main label,
    .app-workspace label {
        font-size: 0.95rem;
        line-height: 1.25;
    }

    /* ESC page-exit state: keep navbar visible and make the main panel blank. */
    .app-main.is-page-exited > * {
        display: none !important;
    }

    .app-main,
    .app-main > *,
    .app-main form,
    .app-main .flex,
    .app-main .grid,
    .app-workspace,
    .app-workspace > * {
        min-width: 0;
    }

    .app-main img,
    .app-main video,
    .app-main canvas,
    .app-main svg {
        max-width: 100%;
    }

    .app-main input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
    .app-main select,
    .app-main textarea,
    .app-workspace input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
    .app-workspace select,
    .app-workspace textarea {
        min-width: 0;
        max-width: 100%;
    }

    .app-main .overflow-x-auto,
    .app-workspace .overflow-x-auto,
    .app-workspace .overflow-auto {
        max-width: 100%;
        scrollbar-width: thin;
        scrollbar-color: #94a3b8 transparent;
    }

    .app-responsive-table {
        width: 100%;
        max-width: 100%;
    }

    .app-responsive-table th,
    .app-responsive-table td {
        vertical-align: middle;
    }

    /* Purchase sheet must fit every desktop viewport, including 1920+ screens.
       The old Tailwind w-* column classes total more than the viewport once
       Freight/Remark/Total/Action are all present, which creates a horizontal
       scrollbar on wide monitors where the compact breakpoint is not active. */
    @media screen and (min-width: 1000px) {
        .app-main .overflow-x-auto,
        .app-workspace .overflow-x-auto,
        .app-workspace .overflow-auto {
            overflow-x: hidden !important;
        }

        /* Larger fonts must never make any desktop table wider than viewport. */
        .app-responsive-table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed !important;
        }

        .app-responsive-table th,
        .app-responsive-table td {
            min-width: 0 !important;
            overflow-wrap: anywhere;
        }

        .app-purchase-table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed !important;
        }

        .app-purchase-table th,
        .app-purchase-table td {
            min-width: 0 !important;
            max-width: none !important;
            overflow: hidden;
        }

        .app-purchase-table th {
            white-space: normal !important;
            overflow-wrap: anywhere;
        }

        .app-purchase-table td button,
        .app-purchase-table td input,
        .app-purchase-table td select,
        .app-purchase-table td textarea {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            box-sizing: border-box;
        }

        /* 13 physical columns; Date (#2) remains hidden. Visible total = 100%. */
        .app-purchase-table th:nth-child(1) { width: 5% !important; }
        .app-purchase-table th:nth-child(2) { width: 0 !important; }
        .app-purchase-table th:nth-child(3) { width: 14% !important; }
        .app-purchase-table th:nth-child(4) { width: 17% !important; }
        .app-purchase-table th:nth-child(5) { width: 6% !important; }
        .app-purchase-table th:nth-child(6) { width: 6% !important; }
        .app-purchase-table th:nth-child(7) { width: 6% !important; }
        .app-purchase-table th:nth-child(8) { width: 6% !important; }
        .app-purchase-table th:nth-child(9) { width: 7% !important; }
        .app-purchase-table th:nth-child(10) { width: 7% !important; }
        .app-purchase-table th:nth-child(11) { width: 12% !important; }
        .app-purchase-table th:nth-child(12) { width: 8% !important; }
        .app-purchase-table th:nth-child(13) { width: 6% !important; }
    }

    /* Desktop monitor compaction. Covers 1600/1440/1366 class viewports without changing workflow. */
    @media screen and (max-width: 1799px) and (min-width: 1000px) {
        :root {
            --fb-control-height: 2.50rem;
            --fb-table-font: 0.92rem;
            --fb-table-head-font: 0.82rem;
            --fb-cell-x: 0.30rem;
            --fb-cell-y: 0.38rem;
            --fb-page-pad: 0.65rem;
        }

        .app-topbar > div {
            padding-left: 0.65rem !important;
            padding-right: 0.65rem !important;
        }

        .app-topbar > div > a:first-child {
            padding-right: 0.9rem !important;
            font-size: 1.00rem !important;
            white-space: nowrap;
        }

        .app-topbar [data-nav-item] {
            padding-left: 0.62rem !important;
            padding-right: 0.62rem !important;
            gap: 0.28rem !important;
            font-size: 0.90rem !important;
            white-space: nowrap;
        }

        .app-topbar [data-nav-item] svg {
            width: 0.78rem !important;
            height: 0.78rem !important;
        }

        .app-topbar [data-nav-menu] {
            width: 12.5rem !important;
        }

        .app-topbar [data-nav-subitem] {
            padding: 0.48rem 0.68rem !important;
            font-size: 0.90rem !important;
        }

        .app-topbar > div > div:last-child {
            gap: 0.55rem !important;
            white-space: nowrap;
        }

        .app-topbar > div > div:last-child > span:not(.hidden) {
            font-size: 0.84rem !important;
        }

        .app-topbar form button {
            padding: 0.35rem 0.55rem !important;
            font-size: 0.86rem !important;
        }

        .app-main {
            padding: var(--fb-page-pad) !important;
        }

        .app-main h1,
        .app-main .text-xl,
        .app-workspace .text-xl,
        .app-workspace .text-lg {
            font-size: 1.22rem !important;
            line-height: 1.2 !important;
        }

        .app-main h2,
        .app-main h3 {
            line-height: 1.2 !important;
        }

        .app-main .text-lg {
            font-size: 1.08rem !important;
        }

        .app-main .text-sm,
        .app-workspace .text-sm {
            font-size: 0.94rem !important;
        }

        .app-main .text-xs,
        .app-workspace .text-xs,
        .app-main .text-\[9px\],
        .app-main .text-\[10px\],
        .app-main .text-\[11px\],
        .app-workspace .text-\[9px\],
        .app-workspace .text-\[10px\],
        .app-workspace .text-\[11px\] {
            font-size: 0.82rem !important;
            font-weight: 700 !important;
        }

        .app-main .p-6,
        .app-main .p-5,
        .app-workspace .p-6,
        .app-workspace .p-5 {
            padding: 0.72rem !important;
        }

        .app-main .p-4,
        .app-workspace .p-4 {
            padding: 0.60rem !important;
        }

        .app-main .px-6,
        .app-main .px-5,
        .app-main .px-4,
        .app-workspace .px-6,
        .app-workspace .px-5,
        .app-workspace .px-4 {
            padding-left: 0.62rem !important;
            padding-right: 0.62rem !important;
        }

        .app-main .py-5,
        .app-main .py-4,
        .app-main .py-3,
        .app-workspace .py-5,
        .app-workspace .py-4,
        .app-workspace .py-3 {
            padding-top: 0.48rem !important;
            padding-bottom: 0.48rem !important;
        }

        .app-main .gap-6,
        .app-main .gap-5,
        .app-main .gap-4,
        .app-workspace .gap-6,
        .app-workspace .gap-5,
        .app-workspace .gap-4 {
            gap: 0.62rem !important;
        }

        .app-main .gap-3,
        .app-workspace .gap-3 {
            gap: 0.45rem !important;
        }

        .app-main .mt-5,
        .app-main .mt-4,
        .app-workspace .mt-5,
        .app-workspace .mt-4 {
            margin-top: 0.58rem !important;
        }

        .app-main .mb-5,
        .app-main .mb-4,
        .app-workspace .mb-5,
        .app-workspace .mb-4 {
            margin-bottom: 0.58rem !important;
        }

        .app-main input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        .app-main select,
        .app-workspace input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        .app-workspace select {
            min-height: var(--fb-control-height) !important;
            height: var(--fb-control-height) !important;
            padding-top: 0.28rem !important;
            padding-bottom: 0.28rem !important;
            padding-left: 0.48rem !important;
            padding-right: 0.48rem !important;
            font-size: 0.94rem !important;
            font-weight: 700 !important;
        }

        .app-main textarea,
        .app-workspace textarea {
            padding: 0.42rem 0.50rem !important;
            font-size: 0.94rem !important;
            font-weight: 700 !important;
        }

        .app-main button,
        .app-workspace button,
        .app-main a[class*="px-"],
        .app-workspace a[class*="px-"] {
            font-size: 0.90rem;
            font-weight: 700;
        }

        .app-main .h-10,
        .app-main .h-9,
        .app-workspace .h-10,
        .app-workspace .h-9 {
            min-height: var(--fb-control-height) !important;
            height: var(--fb-control-height) !important;
        }

        .app-main .w-10,
        .app-main .w-9,
        .app-workspace .w-10,
        .app-workspace .w-9 {
            width: var(--fb-control-height) !important;
        }

        .app-workspace > div:first-child {
            padding-top: 0.48rem !important;
            padding-bottom: 0.48rem !important;
        }

        .app-workspace > div:first-child > div:first-child {
            gap: 0.62rem !important;
        }

        /* Remove the artificial desktop minimum widths that create scroll bars. */
        .app-responsive-table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed !important;
        }

        .app-responsive-table th,
        .app-responsive-table td {
            min-width: 0 !important;
            padding: var(--fb-cell-y) var(--fb-cell-x) !important;
            font-size: var(--fb-table-font) !important;
            line-height: 1.16 !important;
            white-space: normal !important;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        .app-responsive-table th {
            width: auto !important;
            font-size: var(--fb-table-head-font) !important;
            line-height: 1.12 !important;
            letter-spacing: 0.015em !important;
        }

        .app-responsive-table td .truncate,
        .app-responsive-table td button.truncate {
            min-width: 0 !important;
            max-width: 100% !important;
        }

        .app-responsive-table input:not([type="checkbox"]):not([type="radio"]),
        .app-responsive-table select {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            min-height: 1.98rem !important;
            height: 1.98rem !important;
            padding: 0.22rem 0.28rem !important;
            font-size: 0.86rem !important;
            font-weight: 700 !important;
        }

        .app-responsive-table button {
            min-width: 0 !important;
            max-width: 100% !important;
            font-size: 0.86rem !important;
            font-weight: 700 !important;
            line-height: 1.12 !important;
        }

        .app-responsive-table .min-h-12,
        .app-responsive-table .min-h-10 {
            min-height: 1.98rem !important;
        }

        /* Entry table proportions. Only layout widths change; fields and data attributes stay untouched. */
        .app-entry-table th {
            width: auto !important;
        }

        .app-sale-table th:nth-child(1) { width: 5% !important; }
        .app-sale-table th:nth-child(2) { width: 0 !important; }
        .app-sale-table th:nth-child(3) { width: 16% !important; }
        .app-sale-table th:nth-child(4) { width: 25% !important; }
        .app-sale-table th:nth-child(5) { width: 7% !important; }
        .app-sale-table th:nth-child(6) { width: 8% !important; }
        .app-sale-table th:nth-child(7) { width: 8% !important; }
        .app-sale-table th:nth-child(8) { width: 7% !important; }
        .app-sale-table th:nth-child(9) { width: 8% !important; }
        .app-sale-table th:nth-child(10) { width: 10% !important; }
        .app-sale-table th:nth-child(11) { width: 6% !important; }

        /* Purchase has 13 physical columns (Date stays hidden). Keep the
           visible columns at an exact 100% total so adding Freight never
           forces the keyboard-driven sheet wider than the viewport. */
        .app-purchase-table th:nth-child(1) { width: 5% !important; }
        .app-purchase-table th:nth-child(2) { width: 0 !important; }
        .app-purchase-table th:nth-child(3) { width: 14% !important; }
        .app-purchase-table th:nth-child(4) { width: 17% !important; }
        .app-purchase-table th:nth-child(5) { width: 6% !important; }
        .app-purchase-table th:nth-child(6) { width: 6% !important; }
        .app-purchase-table th:nth-child(7) { width: 6% !important; }
        .app-purchase-table th:nth-child(8) { width: 6% !important; }
        .app-purchase-table th:nth-child(9) { width: 7% !important; }
        .app-purchase-table th:nth-child(10) { width: 7% !important; }
        .app-purchase-table th:nth-child(11) { width: 12% !important; }
        .app-purchase-table th:nth-child(12) { width: 8% !important; }
        .app-purchase-table th:nth-child(13) { width: 6% !important; }

        .app-payment-table th:nth-child(1) { width: 5% !important; }
        .app-payment-table th:nth-child(2) { width: 11% !important; }
        .app-payment-table th:nth-child(3) { width: 20% !important; }
        .app-payment-table th:nth-child(4) { width: 11% !important; }
        .app-payment-table th:nth-child(5) { width: 14% !important; }
        .app-payment-table th:nth-child(6) { width: 18% !important; }
        .app-payment-table th:nth-child(7) { width: 16% !important; }
        .app-payment-table th:nth-child(8) { width: 5% !important; }

        .app-cold-send-table th { width: auto !important; }
        .app-cold-send-table th:nth-child(1) { width: 3.5% !important; }
        .app-cold-send-table th:nth-child(2) { width: 15% !important; }
        .app-cold-send-table th:nth-child(3) { width: 11% !important; }
        .app-cold-send-table th:nth-child(4) { width: 8.5% !important; }
        .app-cold-send-table th:nth-child(5) { width: 6% !important; }
        .app-cold-send-table th:nth-child(6) { width: 6.5% !important; }
        .app-cold-send-table th:nth-child(7) { width: 6.5% !important; }
        .app-cold-send-table th:nth-child(8) { width: 7% !important; }
        .app-cold-send-table th:nth-child(9) { width: 7% !important; }
        .app-cold-send-table th:nth-child(10) { width: 8% !important; }
        .app-cold-send-table th:nth-child(11) { width: 7% !important; }
        .app-cold-send-table th:nth-child(12) { width: 7.5% !important; }
        .app-cold-send-table th:nth-child(13) { width: 6.5% !important; }

        .app-cold-receive-table th { width: auto !important; }
        .app-cold-receive-table th:nth-child(1) { width: 4% !important; }
        .app-cold-receive-table th:nth-child(2) { width: 16% !important; }
        .app-cold-receive-table th:nth-child(3) { width: 12% !important; }
        .app-cold-receive-table th:nth-child(4) { width: 7% !important; }
        .app-cold-receive-table th:nth-child(5) { width: 7% !important; }
        .app-cold-receive-table th:nth-child(6) { width: 8% !important; }
        .app-cold-receive-table th:nth-child(7) { width: 9% !important; }
        .app-cold-receive-table th:nth-child(8) { width: 8% !important; }
        .app-cold-receive-table th:nth-child(9) { width: 9% !important; }
        .app-cold-receive-table th:nth-child(10) { width: 8% !important; }
        .app-cold-receive-table th:nth-child(11) { width: 8% !important; }
        .app-cold-receive-table th:nth-child(12) { width: 4% !important; }

        .app-lot-report-table th,
        .app-stock-report-table th,
        .app-report-table th,
        .app-bank-report-table th,
        .app-bank-master-table th,
        .app-bank-transaction-table th,
        .app-master-list-table th {
            width: auto !important;
        }

        .app-lot-report-table th:nth-child(3),
        .app-stock-report-table th:nth-child(3),
        .app-report-table th:nth-child(3) {
            width: 18% !important;
        }

        .app-report-table th:nth-child(3) { width: 25% !important; }

        .app-bank-report-table th:nth-child(1) { width: 9% !important; }
        .app-bank-report-table th:nth-child(2) { width: 11% !important; }
        .app-bank-report-table th:nth-child(3) { width: 40% !important; }
        .app-bank-report-table th:nth-child(4),
        .app-bank-report-table th:nth-child(5),
        .app-bank-report-table th:nth-child(6) { width: 13.33% !important; }

        .app-bank-master-table th:nth-child(2),
        .app-bank-master-table th:nth-child(3) { width: 20% !important; }
        .app-bank-master-table th:nth-child(4) { width: 17% !important; }

        .app-bank-transaction-table th:nth-child(3) { width: 24% !important; }
        .app-bank-transaction-table th:nth-child(5) { width: 32% !important; }

        .app-master-list-table th:nth-child(2) { width: 25% !important; }

        /* Search/filter toolbars must shrink instead of forcing the page wider. */
        .app-main .w-96,
        .app-main .w-80,
        .app-workspace .w-96,
        .app-workspace .w-80 {
            width: min(21rem, 29vw) !important;
            max-width: 100% !important;
        }

        .app-main .w-72,
        .app-workspace .w-72 {
            width: min(17rem, 23vw) !important;
        }

        /* Keep selector/filter modals inside the effective desktop viewport. */
        .app-main .fixed.inset-0 > div,
        .app-workspace .fixed.inset-0 > div {
            max-width: calc(100vw - 2rem) !important;
            max-height: calc(100vh - 2rem) !important;
        }
    }

    /* Extra compaction for smaller desktop/laptop viewports. No structural/card conversion. */
    @media screen and (max-width: 1280px) and (min-width: 1000px) {
        :root {
            --fb-control-height: 2.10rem;
            --fb-table-font: 0.74rem;
            --fb-table-head-font: 0.67rem;
            --fb-cell-x: 0.20rem;
            --fb-cell-y: 0.32rem;
            --fb-page-pad: 0.50rem;
        }

        .app-topbar [data-nav-item] {
            padding-left: 0.48rem !important;
            padding-right: 0.48rem !important;
            font-size: 0.86rem !important;
        }

        .app-topbar > div > a:first-child {
            padding-right: 0.60rem !important;
            font-size: 0.90rem !important;
        }

        .app-topbar > div > div:last-child > span {
            display: none !important;
        }

        .app-responsive-table input:not([type="checkbox"]):not([type="radio"]),
        .app-responsive-table select,
        .app-responsive-table .min-h-12,
        .app-responsive-table .min-h-10 {
            min-height: 1.90rem !important;
            height: 1.90rem !important;
        }

        .app-workspace > div:first-child .gap-x-5,
        .app-workspace > div:first-child .gap-5 {
            column-gap: 0.45rem !important;
        }
    }

    /* Below desktop width retain safe scrolling rather than breaking keyboard-driven tables. */
    @media screen and (max-width: 999px) {
        .app-main {
            padding: 0.65rem !important;
        }

        .app-main .overflow-x-auto,
        .app-workspace .overflow-auto,
        .app-workspace .overflow-x-auto {
            overflow-x: auto;
        }
    }

    /* Senior-operator readability pass.
       IMPORTANT: widths remain fixed/percentage based and overflow-x stays hidden,
       so larger text wraps vertically instead of creating a horizontal scrollbar. */
    @media screen and (min-width: 1000px) {
        .app-main,
        .app-workspace {
            font-size: 1rem !important;
            line-height: 1.32 !important;
        }

        .app-main .text-xs,
        .app-workspace .text-xs {
            font-size: 0.88rem !important;
            line-height: 1.25 !important;
        }

        .app-main .text-sm,
        .app-workspace .text-sm {
            font-size: 0.96rem !important;
            line-height: 1.28 !important;
        }

        .app-main .text-\[9px\],
        .app-main .text-\[10px\],
        .app-main .text-\[11px\],
        .app-main .text-\[12px\],
        .app-main .text-\[13px\],
        .app-workspace .text-\[9px\],
        .app-workspace .text-\[10px\],
        .app-workspace .text-\[11px\],
        .app-workspace .text-\[12px\],
        .app-workspace .text-\[13px\] {
            font-size: 0.84rem !important;
            line-height: 1.24 !important;
            font-weight: 700 !important;
        }

        .app-main h1,
        .app-main .text-xl,
        .app-workspace .text-xl {
            font-size: 1.28rem !important;
            line-height: 1.22 !important;
            font-weight: 800 !important;
        }

        .app-main h2,
        .app-main h3,
        .app-main .text-lg,
        .app-workspace .text-lg {
            font-size: 1.08rem !important;
            line-height: 1.25 !important;
            font-weight: 800 !important;
        }

        .app-main input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        .app-main select,
        .app-main textarea,
        .app-main button,
        .app-workspace input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        .app-workspace select,
        .app-workspace textarea,
        .app-workspace button {
            font-size: 0.93rem !important;
            line-height: 1.22 !important;
            font-weight: 700 !important;
        }

        .app-responsive-table td {
            font-size: 0.86rem !important;
            line-height: 1.20 !important;
            font-weight: 700 !important;
            white-space: normal !important;
            overflow-wrap: anywhere;
        }

        .app-responsive-table th {
            font-size: 0.90rem !important;
            line-height: 1.16 !important;
            font-weight: 800 !important;
            white-space: normal !important;
            overflow-wrap: anywhere;
        }

        .app-responsive-table input:not([type="checkbox"]):not([type="radio"]),
        .app-responsive-table select,
        .app-responsive-table textarea,
        .app-responsive-table button {
            font-size: 0.84rem !important;
            font-weight: 700 !important;
        }

        .app-topbar > div > a:first-child {
            font-size: 1rem !important;
            font-weight: 800 !important;
        }

        .app-topbar [data-nav-item] {
            font-size: 1.00rem !important;
            font-weight: 700 !important;
        }

        .app-topbar [data-nav-subitem] {
            font-size: 1.00rem !important;
            font-weight: 700 !important;
        }

        /* The tables remain hard-limited to the viewport despite larger fonts. */
        .app-main .overflow-x-auto,
        .app-workspace .overflow-x-auto,
        .app-workspace .overflow-auto {
            overflow-x: hidden !important;
        }

        .app-responsive-table,
        .app-entry-table,
        .app-sale-table,
        .app-purchase-table,
        .app-payment-table,
        .app-bank-report-table,
        .app-master-list-table {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            table-layout: fixed !important;
        }
    }

    @media screen and (max-width: 1280px) and (min-width: 1000px) {
        .app-main,
        .app-workspace {
            font-size: 0.94rem !important;
        }

        .app-main .text-xs,
        .app-workspace .text-xs,
        .app-main .text-\[9px\],
        .app-main .text-\[10px\],
        .app-main .text-\[11px\],
        .app-main .text-\[12px\],
        .app-main .text-\[13px\],
        .app-workspace .text-\[9px\],
        .app-workspace .text-\[10px\],
        .app-workspace .text-\[11px\],
        .app-workspace .text-\[12px\],
        .app-workspace .text-\[13px\] {
            font-size: 0.94rem !important;
        }

        .app-responsive-table td,
        .app-responsive-table input:not([type="checkbox"]):not([type="radio"]),
        .app-responsive-table select,
        .app-responsive-table textarea,
        .app-responsive-table button {
            font-size: 0.94rem !important;
        }

        .app-responsive-table th {
            font-size: 0.72rem !important;
        }

        .app-topbar [data-nav-item],
        .app-topbar [data-nav-subitem] {
            font-size: 0.90rem !important;
        }
    }

    /* PDF.js modal: canvases are normal DOM content, therefore ESC is always
       caught by the parent application even after clicking/scrolling the PDF. */
    #app-pdf-preview-modal .app-pdfjs-page canvas {
        max-width: calc(100vw - 5rem) !important;
        height: auto !important;
    }

    @media print {
        @page {
            size: A5 portrait;
            margin: 0;
        }

        body.pdf-preview-printing {
            padding: 0 !important;
            margin: 0 !important;
            background: #fff !important;
            overflow: visible !important;
        }

        body.pdf-preview-printing > *:not(#app-pdf-preview-modal) {
            display: none !important;
        }

        body.pdf-preview-printing #app-pdf-preview-modal {
            position: static !important;
            inset: auto !important;
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
            background: #fff !important;
            overflow: visible !important;
        }

        body.pdf-preview-printing #app-pdf-preview-modal > div {
            width: 100% !important;
            max-width: none !important;
            height: auto !important;
            border: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        body.pdf-preview-printing [data-pdf-preview-toolbar],
        body.pdf-preview-printing #app-pdf-preview-status {
            display: none !important;
        }

        body.pdf-preview-printing #app-pdf-preview-viewer {
            display: block !important;
            overflow: visible !important;
            padding: 0 !important;
            background: #fff !important;
        }

        body.pdf-preview-printing .app-pdfjs-page {
            display: block !important;
            margin: 0 auto !important;
            box-shadow: none !important;
            break-after: page;
            page-break-after: always;
        }

        body.pdf-preview-printing .app-pdfjs-page:last-child {
            break-after: auto;
            page-break-after: auto;
        }

        body.pdf-preview-printing .app-pdfjs-page canvas {
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
        }
    }

</style>
