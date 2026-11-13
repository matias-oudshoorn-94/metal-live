// Metal Live · panel de administración (sin dependencias)

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
const money = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });

// ---------- Menú lateral en móvil
const sidebarBtn = $('[data-toggle-sidebar]');
const setSidebar = (open) => {
  document.body.classList.toggle('nav-open', open);
  sidebarBtn?.setAttribute('aria-expanded', String(open));
};
sidebarBtn?.addEventListener('click', () => setSidebar(!document.body.classList.contains('nav-open')));
$('[data-close-sidebar]')?.addEventListener('click', () => setSidebar(false));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setSidebar(false); });

// ---------- Confirmaciones (data-confirm en formularios) y bloqueo de doble envío
document.addEventListener('submit', (e) => {
  const form = e.target;
  const msg = form.dataset.confirm;
  if (msg && !window.confirm(msg)) {
    e.preventDefault();
    return;
  }
  const btn = e.submitter ?? $('button[type=submit]', form);
  if (btn && !('noLock' in form.dataset)) {
    // Al deshabilitar el botón se perdería su name/value: se copia a un campo oculto.
    if (btn.name) {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = btn.name;
      hidden.value = btn.value;
      form.append(hidden);
    }
    setTimeout(() => { btn.disabled = true; }, 0);
  }
});

// ---------- Alertas que se ocultan solas
$$('[data-autohide]').forEach((el) => setTimeout(() => { el.hidden = true; }, 6000));

// ---------- Mostrar/ocultar según el valor de otro campo
// <div data-show-when="payment_type=cheque,echeq">
const conditionals = $$('[data-show-when]');
const evaluate = () => {
  for (const el of conditionals) {
    const [name, values] = el.dataset.showWhen.split('=');
    const form = el.closest('form') ?? document;
    const field = $(`[name="${name}"]:checked`, form) ?? $(`select[name="${name}"]`, form);
    const show = Boolean(field) && values.split(',').includes(field.value);
    el.hidden = !show;
    $$('input, select, textarea', el).forEach((input) => { input.disabled = !show; });
  }
};
if (conditionals.length) {
  document.addEventListener('change', evaluate);
  evaluate();
}

// ---------- Buscador sobre selects largos: <input data-filter-select="#patient_id">
$$('[data-filter-select]').forEach((input) => {
  const select = $(input.dataset.filterSelect);
  if (!select) return;
  input.addEventListener('input', () => {
    const term = input.value.trim().toLowerCase();
    let first = null;
    for (const opt of select.options) {
      if (!opt.value) continue;
      const match = !term || opt.text.toLowerCase().includes(term);
      opt.hidden = !match;
      if (match && !first) first = opt;
    }
    const current = select.selectedOptions[0];
    if (term && first && (!current || !current.value || current.hidden)) select.value = first.value;
  });
});

// ---------- Buscador con lista desplegable sobre un <select data-combobox>
// El select queda oculto y sigue siendo el que se envía (y el que dispara "change"),
// así que la lógica que ya escucha al select funciona igual.
const COMBO_MAX = 60;
const fold = (t) => t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
let comboSeq = 0;

const enhanceCombobox = (select) => {
  if (select.dataset.comboReady) return;
  select.dataset.comboReady = '1';
  const options = [...select.options].filter((o) => o.value).map((o) => ({ opt: o, key: fold(o.text) }));
  const listId = `combo-list-${++comboSeq}`;
  const splitCode = 'comboboxCode' in select.dataset; // "CÓDIGO · nombre": el código se muestra aparte

  const wrap = document.createElement('div');
  wrap.className = 'combo';
  const input = document.createElement('input');
  input.type = 'text';
  input.className = 'combo-input';
  input.autocomplete = 'off';
  input.spellcheck = false;
  input.placeholder = select.dataset.placeholder || 'Buscar…';
  input.setAttribute('role', 'combobox');
  input.setAttribute('aria-autocomplete', 'list');
  input.setAttribute('aria-expanded', 'false');
  input.setAttribute('aria-controls', listId);
  input.setAttribute('aria-label', select.getAttribute('aria-label') || 'Buscar');
  if (select.id) { input.id = select.id; select.removeAttribute('id'); } // el <label for> apunta al buscador
  if (select.required) { input.required = true; select.required = false; }
  if (select.hasAttribute('aria-invalid')) input.setAttribute('aria-invalid', select.getAttribute('aria-invalid'));
  const list = document.createElement('ul');
  list.className = 'combo-list';
  list.id = listId;
  list.setAttribute('role', 'listbox');
  list.hidden = true;
  wrap.append(input, list);
  select.before(wrap);
  select.hidden = true;
  select.tabIndex = -1;

  let shown = [];
  let active = -1;
  const label = () => (select.selectedOptions[0]?.value ? select.selectedOptions[0].text : '');
  input.value = label();

  const place = () => {
    // position: fixed para que no lo recorte el scroll horizontal de las tablas.
    const r = input.getBoundingClientRect();
    const below = window.innerHeight - r.bottom;
    list.style.left = `${r.left}px`;
    list.style.minWidth = `${r.width}px`;
    list.style.maxWidth = `${Math.max(r.width, Math.min(560, window.innerWidth - r.left - 12))}px`;
    if (below < 220 && r.top > below) {
      list.style.top = '';
      list.style.bottom = `${window.innerHeight - r.top + 4}px`;
      list.style.maxHeight = `${Math.min(320, r.top - 12)}px`;
    } else {
      list.style.bottom = '';
      list.style.top = `${r.bottom + 4}px`;
      list.style.maxHeight = `${Math.min(320, below - 12)}px`;
    }
  };
  const setActive = (i) => {
    active = i;
    [...list.children].forEach((li, n) => li.classList.toggle('is-active', n === i));
    const li = list.children[i];
    if (li?.getAttribute('role') === 'option') {
      input.setAttribute('aria-activedescendant', li.id);
      li.scrollIntoView({ block: 'nearest' });
    } else input.removeAttribute('aria-activedescendant');
  };
  const close = () => {
    list.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
  };
  const render = () => {
    const terms = fold(input.value.trim()).split(/\s+/).filter(Boolean);
    const matches = options.filter(({ key }) => terms.every((t) => key.includes(t)));
    shown = matches.slice(0, COMBO_MAX).map(({ opt }) => opt);
    list.replaceChildren(...shown.map((opt, i) => {
      const li = document.createElement('li');
      li.id = `${listId}-${i}`;
      li.setAttribute('role', 'option');
      li.setAttribute('aria-selected', String(opt.selected));
      const [code, ...rest] = opt.text.split(' · ');
      if (splitCode && rest.length) {
        const c = document.createElement('span');
        c.className = 'combo-code';
        c.textContent = code;
        const n = document.createElement('span');
        n.textContent = rest.join(' · ');
        li.append(c, n);
      } else li.textContent = opt.text;
      return li;
    }));
    if (!shown.length) {
      const li = document.createElement('li');
      li.className = 'combo-empty';
      li.textContent = 'Sin resultados';
      list.append(li);
    } else if (matches.length > COMBO_MAX) {
      const li = document.createElement('li');
      li.className = 'combo-more';
      li.textContent = `${matches.length - COMBO_MAX} resultados más: seguí escribiendo para acotar`;
      list.append(li);
    }
    list.hidden = false;
    input.setAttribute('aria-expanded', 'true');
    place();
    setActive(shown.length && terms.length ? 0 : -1);
  };
  const choose = (opt) => {
    const changed = select.value !== (opt?.value ?? '');
    select.value = opt?.value ?? '';
    input.value = label();
    close();
    if (changed) select.dispatchEvent(new Event('change', { bubbles: true }));
  };

  input.addEventListener('focus', () => { input.select(); render(); });
  input.addEventListener('input', render);
  input.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (list.hidden) { render(); return; }
      if (!shown.length) return;
      const step = e.key === 'ArrowDown' ? 1 : -1;
      setActive((active + step + shown.length) % shown.length);
    } else if (e.key === 'Enter') {
      if (!list.hidden) e.preventDefault(); // Enter elige, no envía el formulario
      if (!list.hidden && shown[active]) choose(shown[active]);
    } else if (e.key === 'Escape' && !list.hidden) {
      e.preventDefault();
      e.stopPropagation();
      input.value = label();
      close();
    }
  });
  list.addEventListener('mousedown', (e) => {
    e.preventDefault(); // evita el blur del input antes del click
    const li = e.target.closest('[role=option]');
    if (li) choose(shown[[...list.children].indexOf(li)]);
  });
  input.addEventListener('blur', () => {
    // Al salir: si se borró el texto, queda sin selección; si no, vuelve a lo elegido.
    if (!input.value.trim()) choose(null);
    else { input.value = label(); close(); }
  });
  window.addEventListener('resize', () => { if (!list.hidden) place(); });
  document.addEventListener('scroll', () => { if (!list.hidden) place(); }, true);
};
const enhanceComboboxes = (root = document) => $$('select[data-combobox]', root).forEach(enhanceCombobox);
enhanceComboboxes();

// ---------- Filtro rápido de tablas: <input data-table-filter="#tabla">
$$('[data-table-filter]').forEach((input) => {
  const table = $(input.dataset.tableFilter);
  input.addEventListener('input', () => {
    const term = input.value.trim().toLowerCase();
    $$('tbody tr', table).forEach((tr) => { tr.hidden = Boolean(term) && !tr.textContent.toLowerCase().includes(term); });
  });
});

// ---------- Filas repetibles (ítems de cotización y consumo)
// <template data-row-template="items"> + <tbody data-rows="items"> + <button data-add-row="items">
let rowSeq = Date.now();
const addRow = (key) => {
  const tpl = $(`template[data-row-template="${key}"]`);
  const body = $(`[data-rows="${key}"]`);
  if (!tpl || !body) return;
  const fragment = tpl.content.cloneNode(true);
  const index = String(rowSeq++);
  $$('[name]', fragment).forEach((el) => { el.name = el.name.replace('__INDEX__', index); });
  body.append(fragment);
  const row = body.lastElementChild;
  enhanceComboboxes(row);
  $('.combo-input, input:not([type=hidden]), select:not([hidden])', row)?.focus();
  recalc();
};
document.addEventListener('click', (e) => {
  const add = e.target.closest('[data-add-row]');
  if (add) {
    e.preventDefault();
    addRow(add.dataset.addRow);
    return;
  }
  const remove = e.target.closest('[data-remove-row]');
  if (remove) {
    e.preventDefault();
    remove.closest('tr')?.remove();
    recalc();
  }
});

// Al elegir material en la cotización, completa descripción y precio.
document.addEventListener('change', (e) => {
  const select = e.target.closest('select[data-material]');
  if (!select) return;
  const opt = select.selectedOptions[0];
  const row = select.closest('tr');
  if (!opt?.value || !row) return;
  const desc = $('[data-desc]', row);
  const price = $('input[data-price]', row);
  if (desc) desc.value = opt.dataset.name ?? '';
  if (price && opt.dataset.price) price.value = opt.dataset.price;
  recalc();
});

function recalc() {
  const totalEl = $('[data-quote-total]');
  if (!totalEl) return;
  let total = 0;
  $$('[data-rows="items"] tr').forEach((tr) => {
    const qty = parseFloat($('[data-qty]', tr)?.value || '0');
    const price = parseFloat(($('input[data-price]', tr)?.value || '0').replace(',', '.'));
    if (!Number.isNaN(qty) && !Number.isNaN(price)) total += qty * price;
  });
  totalEl.textContent = money.format(total);
}
document.addEventListener('input', (e) => {
  if (e.target.matches('input[data-qty], input[data-price]')) recalc();
});
recalc();

// Comisión por defecto según el vendedor elegido (si no se editó a mano).
const commission = $('[name="commission_pct"]');
commission?.addEventListener('input', () => { commission.dataset.touched = '1'; });
$('select[data-seller]')?.addEventListener('change', (e) => {
  const pct = e.target.selectedOptions[0]?.dataset.commission;
  if (commission && pct !== undefined && !commission.dataset.touched) commission.value = pct;
});
