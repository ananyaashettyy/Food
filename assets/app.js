const API = "api/products.php";

const $ = (sel) => document.querySelector(sel);
const tbody = $("#tbody");
const meta = $("#meta");
const modal = $("#modal");
const toast = $("#toast");
const categoryFilter = $("#category");

const form = $("#form");
const fields = {
  id: $("#id"),
  name: $("#name"),
  category: $("#category2"),
  price: $("#price"),
  is_available: $("#is_available"),
  description: $("#description"),
};

function fmtINR(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return value;
  return `₹${n.toFixed(2)}`;
}

function showToast(message, type = "ok") {
  toast.textContent = message;
  toast.classList.remove("ok", "err", "show");
  toast.classList.add(type === "err" ? "err" : "ok", "show");
  window.clearTimeout(showToast._t);
  showToast._t = window.setTimeout(() => toast.classList.remove("show"), 2200);
}

function openModal(title) {
  $("#modalTitle").textContent = title;
  modal.setAttribute("aria-hidden", "false");
}

function closeModal() {
  modal.setAttribute("aria-hidden", "true");
}

async function apiFetch(url, opts = {}) {
  const res = await fetch(url, {
    headers: { "Content-Type": "application/json" },
    ...opts,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || data.ok === false) {
    const msg =
      data?.error ||
      (data?.errors
        ? Object.values(data.errors).join(" ")
        : `Request failed (${res.status})`);
    throw new Error(msg);
  }
  return data;
}

function escapeHtml(s) {
  return String(s ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function setSelectOptions(selectEl, options, { includeAll = false, placeholder = null } = {}) {
  if (!selectEl) return;
  const current = String(selectEl.value ?? "");
  const normalized = (Array.isArray(options) ? options : [])
    .map((v) => String(v ?? "").trim())
    .filter(Boolean);
  const unique = Array.from(new Set(normalized)).sort((a, b) => a.localeCompare(b));

  selectEl.innerHTML = "";
  if (includeAll) {
    const opt = document.createElement("option");
    opt.value = "";
    opt.textContent = "All";
    selectEl.appendChild(opt);
  }
  if (placeholder != null) {
    const opt = document.createElement("option");
    opt.value = "";
    opt.textContent = String(placeholder);
    selectEl.appendChild(opt);
  }
  for (const c of unique) {
    const opt = document.createElement("option");
    opt.value = c;
    opt.textContent = c;
    selectEl.appendChild(opt);
  }

  if (current && unique.includes(current)) selectEl.value = current;
}

function ensureSelectHasValue(selectEl, value) {
  if (!selectEl) return;
  const v = String(value ?? "").trim();
  if (!v) return;
  if ([...selectEl.options].some((o) => o.value === v)) {
    selectEl.value = v;
    return;
  }
  const opt = document.createElement("option");
  opt.value = v;
  opt.textContent = v;
  selectEl.appendChild(opt);
  selectEl.value = v;
}

async function loadCategories() {
  const fallback = ["Beverage", "Biryani", "Burger", "Pizza"];
  try {
    const out = await apiFetch(`${API}?action=categories`);
    const cats = Array.isArray(out.data) ? out.data : [];
    setSelectOptions(categoryFilter, cats, { includeAll: true });
    setSelectOptions(fields.category, cats, { placeholder: "Select category" });
  } catch {
    setSelectOptions(categoryFilter, fallback, { includeAll: true });
    setSelectOptions(fields.category, fallback, { placeholder: "Select category" });
  }
}

function renderRows(rows) {
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="muted">No products found.</td></tr>`;
    return;
  }
  tbody.innerHTML = rows
    .map((p) => {
      const available = Number(p.is_available) === 1;
      const pill = available
        ? `<span class="pill pill--ok">● Available</span>`
        : `<span class="pill pill--no">● Not available</span>`;
      return `
        <tr>
          <td class="mono">${p.id}</td>
          <td>
            <div><strong>${escapeHtml(p.name)}</strong></div>
            <div class="muted">${escapeHtml((p.description || "").slice(0, 70))}${p.description && p.description.length > 70 ? "..." : ""}</div>
          </td>
          <td>${escapeHtml(p.category)}</td>
          <td class="mono">${fmtINR(p.price)}</td>
          <td>${pill}</td>
          <td class="mono">${escapeHtml(p.updated_at)}</td>
          <td>
            <div class="actions">
              <button class="btn" data-edit="${p.id}">Edit</button>
              <button class="btn btn--danger" data-del="${p.id}">Delete</button>
              <button class="btn btn--ghost" data-view="${p.id}">View</button>
            </div>
          </td>
        </tr>
      `;
    })
    .join("");
}

async function loadList() {
  const q = $("#q").value.trim();
  const category = String(categoryFilter.value || "").trim();
  const available = $("#available").value;
  const params = new URLSearchParams();
  if (q) params.set("q", q);
  if (category) params.set("category", category);
  if (available !== "") params.set("available", available);

  tbody.innerHTML = `<tr><td colspan="7" class="muted">Loading...</td></tr>`;
  try {
    const out = await apiFetch(`${API}?action=list&${params.toString()}`);
    const rows = out.data || [];
    meta.textContent = `${rows.length} item(s)`;
    renderRows(rows);
  } catch (e) {
    meta.textContent = "";
    tbody.innerHTML = `<tr><td colspan="7" class="muted">Failed to load: ${escapeHtml(e.message)}</td></tr>`;
  }
}

function resetForm() {
  fields.id.value = "";
  fields.name.value = "";
  fields.category.value = "";
  fields.price.value = "";
  fields.is_available.value = "1";
  fields.description.value = "";
}

async function fillFormForEdit(id) {
  const out = await apiFetch(`${API}?action=get&id=${encodeURIComponent(id)}`);
  const p = out.data;
  fields.id.value = p.id;
  fields.name.value = p.name ?? "";
  ensureSelectHasValue(fields.category, p.category ?? "");
  fields.price.value = p.price ?? "";
  fields.is_available.value = String(Number(p.is_available) === 1 ? 1 : 0);
  fields.description.value = p.description ?? "";
}

async function onSave(ev) {
  ev.preventDefault();
  const id = fields.id.value.trim();
  const payload = {
    name: fields.name.value.trim(),
    category: fields.category.value.trim(),
    price: fields.price.value.trim(),
    is_available: fields.is_available.value,
    description: fields.description.value.trim(),
  };

  // Basic client checks
  if (!payload.name || !payload.category || payload.price === "") {
    showToast("Name, Category and Price are required.", "err");
    return;
  }

  try {
    if (!id) {
      await apiFetch(`${API}?action=create`, { method: "POST", body: JSON.stringify(payload) });
      showToast("Product added.");
    } else {
      await apiFetch(`${API}?action=update&id=${encodeURIComponent(id)}`, { method: "PUT", body: JSON.stringify(payload) });
      showToast("Product updated.");
    }
    closeModal();
    await loadList();
  } catch (e) {
    showToast(e.message, "err");
  }
}

async function onDelete(id) {
  if (!confirm("Delete this product?")) return;
  try {
    await apiFetch(`${API}?action=delete&id=${encodeURIComponent(id)}`, { method: "DELETE" });
    showToast("Product deleted.");
    await loadList();
  } catch (e) {
    showToast(e.message, "err");
  }
}

async function onView(id) {
  try {
    const out = await apiFetch(`${API}?action=get&id=${encodeURIComponent(id)}`);
    const p = out.data;
    const msg = [
      `ID: ${p.id}`,
      `Name: ${p.name}`,
      `Category: ${p.category}`,
      `Price: ${fmtINR(p.price)}`,
      `Available: ${Number(p.is_available) === 1 ? "Yes" : "No"}`,
      `Updated: ${p.updated_at}`,
      ``,
      `Description: ${p.description || "-"}`,
    ].join("\n");
    alert(msg);
  } catch (e) {
    showToast(e.message, "err");
  }
}

// Events
$("#btnAdd").addEventListener("click", async () => {
  resetForm();
  openModal("Add Product");
});

$("#btnSearch").addEventListener("click", loadList);
$("#btnReset").addEventListener("click", () => {
  $("#q").value = "";
  categoryFilter.value = "";
  $("#available").value = "";
  loadList();
});

modal.addEventListener("click", (e) => {
  if (e.target?.dataset?.close) closeModal();
});
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && modal.getAttribute("aria-hidden") === "false") closeModal();
});

form.addEventListener("submit", onSave);

tbody.addEventListener("click", async (e) => {
  const editId = e.target?.dataset?.edit;
  const delId = e.target?.dataset?.del;
  const viewId = e.target?.dataset?.view;
  if (editId) {
    try {
      resetForm();
      await fillFormForEdit(editId);
      openModal(`Edit Product #${editId}`);
    } catch (err) {
      showToast(err.message, "err");
    }
    return;
  }
  if (delId) return onDelete(delId);
  if (viewId) return onView(viewId);
});

// Initial load
(async () => {
  await loadCategories();
  await loadList();
})();
