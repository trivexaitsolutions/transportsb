(function () {
    const modal = document.getElementById('globalSelector');
    if (!modal) return;

    const title = document.getElementById('globalSelectorTitle');
    const search = document.getElementById('globalSelectorSearch');
    const list = document.getElementById('globalSelectorList');
    const addBtn = document.getElementById('globalSelectorAdd');
    const quick = document.getElementById('quickMasterModal');
    const quickTitle = document.getElementById('quickMasterTitle');
    const quickForm = document.getElementById('quickMasterForm');
    const quickFields = document.getElementById('quickMasterFields');
    const quickSave = document.getElementById('quickMasterSave');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const addLabels = {'customers': 'Customer', 'suppliers': 'Supplier / Transporter', 'vehicle-types': 'Vehicle Type', 'transport-names': 'Transport Name', 'gst-rates': 'GST Rate'};
    let state = {type: null, url: null, onSelect: null, opener: null, items: [], active: 0, timer: null, title: 'Select', allowAdd: true, quickOnly: false, requestId: 0};

    async function responseData(response, fallback) {
        const type = response.headers.get('content-type') || '';
        if (!type.includes('application/json')) throw new Error(fallback);
        return response.json();
    }

    function close() {
        modal.classList.add('hidden');
        state.opener?.focus?.();
    }

    function render() {
        list.innerHTML = '';
        if (!state.items.length) {
            list.innerHTML = '<div style="padding:18px;color:#64748b">No matching records.</div>';
            return;
        }
        state.items.forEach((item, i) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'selector-item' + (i === state.active ? ' active' : '');
            button.textContent = item.label || item.name;
            button.addEventListener('click', () => choose(i));
            list.appendChild(button);
        });
    }

    function choose(i) {
        const item = state.items[i];
        if (!item) return;
        state.onSelect?.(item, {source: 'selector'});
        close();
    }

    async function load() {
        const requestId = ++state.requestId;
        const q = encodeURIComponent(search.value || '');
        const base = state.url || ('/masters/options/' + encodeURIComponent(state.type));
        const separator = base.includes('?') ? '&' : '?';
        try {
            const response = await fetch(base + separator + 'q=' + q, {headers: {Accept: 'application/json'}});
            const data = await responseData(response, 'Unable to load records.');
            if (!response.ok) throw new Error(data.message || 'Unable to load records.');
            if (requestId !== state.requestId) return;
            state.items = data.items || [];
            state.active = 0;
            render();
        } catch (error) {
            if (requestId !== state.requestId) return;
            list.innerHTML = '<div style="padding:18px;color:#b91c1c">Unable to load records.</div>';
            window.AppToast?.(error.message || 'Unable to load records.', 'error');
        }
    }

    function canAdd() {
        return state.allowAdd !== false && !!addLabels[state.type];
    }

    function syncAddButton() {
        const enabled = canAdd();
        addBtn.classList.toggle('hidden', !enabled);
        addBtn.textContent = enabled ? '+ Add ' + addLabels[state.type] : '+ Add New';
        enabled ? addBtn.setAttribute('aria-label', 'Add ' + addLabels[state.type]) : addBtn.removeAttribute('aria-label');
    }

    function syncCustomerNote(prefill = false) {
        if (state.type !== 'customers') return;
        const rcm = quickForm.querySelector('[name=is_government_employee]');
        const note = quickForm.querySelector('[name=bill_note]');
        const wrap = note?.closest('[data-field-wrap]');
        if (!rcm || !wrap || !note) return;
        wrap.classList.toggle('hidden', !rcm.checked);
        note.disabled = !rcm.checked;
        if (rcm.checked && prefill && !note.value.trim()) note.value = note.dataset.defaultValue || 'GST @5% WILL BE PAID BY SERVICE USER UNDER RCM (If Applicable)';
    }

    function quickInputs() {
        return Array.from(quickForm.querySelectorAll('[data-master-field]')).filter(input => !input.disabled && input.offsetParent !== null);
    }

    async function openQuick() {
        if (!canAdd()) return;
        addBtn.disabled = true;
        try {
            const response = await fetch('/masters/form/' + encodeURIComponent(state.type), {headers: {Accept: 'application/json'}});
            const data = await responseData(response, 'Unable to open add form.');
            if (!response.ok) throw new Error(data.message || 'Unable to open add form.');
            quickTitle.textContent = 'Add ' + (data.singular || addLabels[state.type] || 'New');
            quickFields.innerHTML = data.html || '';
            quickForm.querySelectorAll('[data-create-hidden="1"]').forEach(wrap => {
                wrap.classList.add('hidden');
                const input = wrap.querySelector('[data-master-field]');
                if (input) input.disabled = true;
            });
            const active = quickForm.querySelector('[name=is_active]');
            if (active) active.checked = true;
            const name = quickForm.querySelector('[name=name]');
            if (name && search.value.trim()) name.value = search.value.trim();
            const rcm = quickForm.querySelector('[name=is_government_employee]');
            rcm?.addEventListener('change', () => syncCustomerNote(true));
            syncCustomerNote(false);
            modal.classList.add('hidden');
            quick.classList.remove('hidden');
            setTimeout(() => quickInputs().find(input => input.type !== 'checkbox')?.focus(), 20);
        } catch (error) {
            window.AppToast?.(error.message || 'Unable to open add form.', 'error', 5500);
            if (state.quickOnly) close();
        } finally {
            addBtn.disabled = false;
        }
    }

    function closeQuick(backToSelector = !state.quickOnly) {
        quick.classList.add('hidden');
        quickFields.innerHTML = '';
        if (backToSelector) {
            modal.classList.remove('hidden');
            setTimeout(() => search.focus(), 20);
        } else {
            close();
        }
    }

    async function saveQuick() {
        if (!canAdd() || quickSave.disabled) return;
        quickSave.disabled = true;
        quickSave.textContent = 'Saving...';
        try {
            const response = await fetch('/masters/' + encodeURIComponent(state.type), {
                method: 'POST',
                headers: {Accept: 'application/json', 'X-CSRF-TOKEN': csrf},
                body: new FormData(quickForm),
            });
            const data = await responseData(response, 'Unable to add record.');
            if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'Unable to add record.');
            quick.classList.add('hidden');
            quickFields.innerHTML = '';
            window.AppToast?.(data.message || 'Record added successfully.', 'success');
            if (data.item) {
                state.items = [data.item, ...state.items.filter(item => String(item.id) !== String(data.item.id))];
                state.active = 0;
                state.onSelect?.(data.item, {source: 'quick-add'});
                modal.classList.add('hidden');
                setTimeout(() => state.opener?.isConnected && state.opener.focus?.(), 0);
            } else {
                modal.classList.remove('hidden');
                await load();
                search.focus();
            }
        } catch (error) {
            window.AppToast?.(error.message || 'Unable to add record.', 'error', 5500);
        } finally {
            quickSave.disabled = false;
            quickSave.textContent = 'Save';
        }
    }

    window.MasterSelector = {
        open(opts) {
            state.type = opts.type || null;
            state.url = opts.url || null;
            state.onSelect = opts.onSelect;
            state.opener = opts.opener || document.activeElement;
            state.title = opts.title || 'Select';
            state.allowAdd = opts.allowAdd !== false;
            state.quickOnly = opts.forceAdd === true;
            title.textContent = state.title;
            search.value = opts.search || '';
            syncAddButton();
            if (state.quickOnly) {
                openQuick();
                return;
            }
            modal.classList.remove('hidden');
            load().then(() => search.focus());
        },
    };

    modal.querySelector('[data-selector-close]')?.addEventListener('click', close);
    modal.addEventListener('mousedown', event => { if (event.target === modal) close(); });
    addBtn?.addEventListener('click', openQuick);
    search.addEventListener('input', () => { clearTimeout(state.timer); state.timer = setTimeout(load, 180); });
    modal.addEventListener('keydown', event => {
        event.stopPropagation();
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        else if (event.key === 'Insert') { event.preventDefault(); if (canAdd()) openQuick(); }
        else if (event.key === 'ArrowDown') { event.preventDefault(); state.active = Math.min(state.active + 1, state.items.length - 1); render(); list.children[state.active]?.scrollIntoView({block: 'nearest'}); }
        else if (event.key === 'ArrowUp') { event.preventDefault(); state.active = Math.max(state.active - 1, 0); render(); list.children[state.active]?.scrollIntoView({block: 'nearest'}); }
        else if (event.key === 'Enter' && event.target !== addBtn) { event.preventDefault(); choose(state.active); }
    });
    document.getElementById('quickMasterClose')?.addEventListener('click', () => closeQuick());
    document.getElementById('quickMasterCancel')?.addEventListener('click', () => closeQuick());
    quick?.addEventListener('mousedown', event => { if (event.target === quick) closeQuick(); });
    quickForm?.addEventListener('submit', event => { event.preventDefault(); event.stopPropagation(); saveQuick(); });
    quickForm?.addEventListener('keydown', event => {
        event.stopPropagation();
        if (event.key === 'Escape') { event.preventDefault(); closeQuick(); return; }
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); saveQuick(); return; }
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
            const inputs = quickInputs();
            const index = inputs.indexOf(event.target);
            if (index < 0) return;
            event.preventDefault();
            if (index < inputs.length - 1) inputs[index + 1].focus(); else saveQuick();
        }
    });
})();
