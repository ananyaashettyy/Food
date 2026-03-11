<?php
declare(strict_types=1);
$css_v = (string)(@filemtime(__DIR__ . '/assets/style.css') ?: '1');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Food Delivery Admin - Products</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= htmlspecialchars($css_v, ENT_QUOTES, 'UTF-8') ?>" />
  </head>
  <body>
    <header class="topbar">
      <div class="container topbar__inner">
        <div>
          <h1 class="brand">Food Delivery Admin</h1>
        </div>
        <div class="topbar__actions">
          <button class="btn btn--primary" id="btnAdd">+ Add Product</button>
        </div>
      </div>
    </header>

    <main class="container">
      <section class="card filters">
        <div class="filters__row">
          <label class="field">
            <span class="field__label">Search</span>
            <input id="q" class="input" placeholder="Name or category..." />
          </label>
          <label class="field">
            <span class="field__label">Category</span>
            <select id="category" class="input">
              <option value="">All</option>
            </select>
          </label>
          <label class="field">
            <span class="field__label">Availability</span>
            <select id="available" class="input">
              <option value="">All</option>
              <option value="1">Available</option>
              <option value="0">Not available</option>
            </select>
          </label>
          <div class="filters__buttons">
            <button class="btn" id="btnSearch">Search</button>
            <button class="btn btn--ghost" id="btnReset">Reset</button>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="table__header">
          <h2>Products</h2>
          <div class="muted" id="meta"></div>
        </div>
        <div class="table__wrap">
          <table class="table" id="table">
            <thead>
              <tr>
                <th style="width: 64px;">ID</th>
                <th>Name</th>
                <th style="width: 130px;">Category</th>
                <th style="width: 110px;">Price</th>
                <th style="width: 120px;">Available</th>
                <th style="width: 180px;">Updated</th>
                <th style="width: 170px;">Actions</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr><td colspan="7" class="muted">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <div class="modal" id="modal" aria-hidden="true">
      <div class="modal__backdrop" data-close="1"></div>
      <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal__header">
          <h3 id="modalTitle">Add Product</h3>
          <button class="iconbtn" title="Close" data-close="1">✕</button>
        </div>

        <form id="form" class="modal__body">
          <input type="hidden" id="id" />

          <div class="grid">
            <label class="field">
              <span class="field__label">Name *</span>
              <input id="name" class="input" required maxlength="120" placeholder="e.g., Paneer Tikka Pizza" />
            </label>
            <label class="field">
              <span class="field__label">Category *</span>
              <select id="category2" class="input" required></select>
            </label>
            <label class="field">
              <span class="field__label">Price (₹) *</span>
              <input id="price" class="input" required inputmode="decimal" placeholder="e.g., 199" />
            </label>
            <label class="field">
              <span class="field__label">Available</span>
              <select id="is_available" class="input">
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </label>
          </div>

          <label class="field">
            <span class="field__label">Description</span>
            <textarea id="description" class="input" rows="4" placeholder="Short product details..."></textarea>
          </label>

          <div class="modal__footer">
            <button type="button" class="btn btn--ghost" data-close="1">Cancel</button>
            <button type="submit" class="btn btn--primary" id="btnSave">Save</button>
          </div>
        </form>
      </div>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"></div>

    <script src="assets/app.js"></script>
  </body>
</html>
