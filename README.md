# Food Delivery Admin (Mini Project)

Web-based admin panel to **add / update / delete / view** food products stored in **MySQL** (XAMPP).

## Tech
- Backend: PHP (XAMPP) + MySQL
- Frontend: HTML + CSS + JavaScript (Fetch API)

## Setup (XAMPP)
1. Copy this folder to XAMPP `htdocs` (example: `C:\xampp\htdocs\Food`).
2. Start **Apache** + **MySQL** in XAMPP Control Panel.
3. Open phpMyAdmin: `http://localhost/phpmyadmin`
4. Import `db.sql` (creates DB + table + sample products).
5. Edit DB credentials in `db.php` if needed.
6. Open: `http://localhost/Food/`

## Result (after execution)
- You'll see a **Products** table (seed items from `db.sql`).
- Click **Add Product** to insert a new product.
- Use **Edit / Delete / View** buttons on each row.
- Search + filter by category/availability at the top.

## Files
- `index.php` - Admin UI
- `db.php` - MySQL connection
- `api/products.php` - CRUD API (JSON)
- `assets/style.css` - UI styles
- `assets/app.js` - UI logic (fetch CRUD)
- `db.sql` - Database + seed
