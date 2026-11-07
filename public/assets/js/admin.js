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
  $('select, input', body.lastElementChild)?.focus();
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
  const price = $('[data-price]', row);
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
    const price = parseFloat(($('[data-price]', tr)?.value || '0').replace(',', '.'));
    if (!Number.isNaN(qty) && !Number.isNaN(price)) total += qty * price;
  });
  totalEl.textContent = money.format(total);
}
document.addEventListener('input', (e) => {
  if (e.target.matches('[data-qty], [data-price]')) recalc();
});
recalc();

// Comisión por defecto según el vendedor elegido (si no se editó a mano).
const commission = $('[name="commission_pct"]');
commission?.addEventListener('input', () => { commission.dataset.touched = '1'; });
$('select[data-seller]')?.addEventListener('change', (e) => {
  const pct = e.target.selectedOptions[0]?.dataset.commission;
  if (commission && pct !== undefined && !commission.dataset.touched) commission.value = pct;
});
