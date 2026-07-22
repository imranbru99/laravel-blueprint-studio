# Laravel Blueprint Studio

**Visual scaffolding for modern Laravel apps.**

Stop writing the same CRUD boilerplate by hand. Laravel Blueprint Studio gives you a premium visual UI to design models, fields, migrations, controllers, form requests, Blade views, and routes — then generates production-ready code in one click.

Built with **Tailwind CSS** + **Alpine.js** (CDN) · Compatible with **Laravel 10 / 11 / 12 / 13**

---

## Why Blueprint Studio?

Backend CRUD work is repetitive: model → migration → fillable → validation → controller → views → routes → folder structure for admin/user areas. This package turns that into a **visible, clickable workflow**:

| Manual backend task | With Blueprint Studio |
|---------------------|------------------------|
| Create Eloquent model | Type the name visually |
| Write migration columns | Add fields in a table UI (`id` + `timestamps` locked by default) |
| Sync `$fillable` / `$casts` | Auto-updated from your fields |
| Build FormRequest rules | Generated from field types |
| Write resource controller | One click (User / Admin / Guest folders) |
| Blade index/create/edit/show | Auto from migration fields + Tailwind forms |
| Register routes | Auto-appended to `routes/web.php` |
| Keep admin vs user structure | Select base → folders + URLs match |
| Track what you generated | Full visual history |

---

## Features

### Visual builder
- Premium dark / light studio UI
- Multi-model workspace — work on several models at once
- Live field editor with types: string, text, integer, boolean, decimal, date, email, password, foreignId, enum, JSON, UUID, and more
- Paste multiple field rows at once
- Import Laravel Blueprint-style YAML drafts

### Selective generation (all ON by default)
Uncheck anything you don’t want — skipped at generate time:

- **Model** — Eloquent class + fillable/casts  
- **Migration** — table with `id`, your columns, `timestamps`  
- **Controller** — full resource CRUD + FormRequests  
- **Route** — auto register in `routes/web.php`  
- **View** — `index`, `create`, `edit`, `show`, `_form`

### User / Admin / Guest bases
Pick a base and Studio creates the matching folders + routes:

| Base | Controller | Views | Requests | URL |
|------|------------|-------|----------|-----|
| **User** | `app/Http/Controllers/User/` | `resources/views/user/` | `app/Http/Requests/User/` | `/user/{resource}` |
| **Admin** | `app/Http/Controllers/Admin/` | `resources/views/admin/` | `app/Http/Requests/Admin/` | `/admin/{resource}` |
| **Guest** | `app/Http/Controllers/Guest/` | `resources/views/guest/` | `app/Http/Requests/Guest/` | `/guest/{resource}` |

### Smart defaults
- Layout auto-created (`layouts/app`) with Tailwind + Alpine CDN if missing
- Soft deletes option per model
- Validation rules derived from field types
- Upsert-safe: regenerating updates existing model/migration instead of duplicating
- Environment guard — disabled outside local/dev/staging unless forced
- Generation history with reload-into-builder support

### Generated stack (example: `Product` + Admin)
```
app/Models/Product.php
database/migrations/xxxx_create_products_table.php
app/Http/Controllers/Admin/ProductController.php
app/Http/Requests/Admin/StoreProductRequest.php
app/Http/Requests/Admin/UpdateProductRequest.php
resources/views/admin/products/index.blade.php
resources/views/admin/products/create.blade.php
resources/views/admin/products/edit.blade.php
resources/views/admin/products/show.blade.php
resources/views/admin/products/_form.blade.php
routes/web.php   ← Route::prefix('admin')->... resource
```

---

## Requirements

- PHP **8.1+**
- Laravel **10 / 11 / 12 / 13**

---

## Installation

### Path package (local development)

In your Laravel app `composer.json`:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../laravel-blueprint-studio",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "imrandevbd/laravel-blueprint-studio": "*"
  }
}
```

```bash
composer update imrandevbd/laravel-blueprint-studio
php artisan vendor:publish --tag=blueprint-studio-config
php artisan migrate
```

### From Packagist / GitHub

```bash
composer require imrandevbd/laravel-blueprint-studio
php artisan vendor:publish --tag=blueprint-studio-config
php artisan migrate
```

Or from GitHub directly:

```bash
composer config repositories.laravel-blueprint-studio vcs https://github.com/imranbru99/laravel-blueprint-studio
composer require imrandevbd/laravel-blueprint-studio:dev-main
php artisan vendor:publish --tag=blueprint-studio-config
php artisan migrate
```

---

## Quick start

1. Open **`/blueprint-studio`**
2. Add model(s) on the left (e.g. `Product`, `Order`)
3. Edit fields on the right
4. Choose **User / Admin / Guest**
5. Keep (or uncheck) Model · Migration · Controller · Route · View
6. Click **Generate this model** or **Generate all**
7. Run `php artisan migrate`

---

## How the generate flow works

When you submit (with components selected):

```
1. Layout ensure (if views/controller need it)
2. Model          → app/Models
3. Migration      → database/migrations
4. FormRequests   → app/Http/Requests/{User|Admin|Guest}
5. Controller     → app/Http/Controllers/{User|Admin|Guest}
6. Views          → resources/views/{user|admin|guest}/{resource}
7. Route          → routes/web.php (prefixed group)
8. History        → recorded in blueprint_studio_history
```

Unselected components are **skipped**.

---

## Configuration

Publish config:

```bash
php artisan vendor:publish --tag=blueprint-studio-config
```

Key options in `config/blueprint-studio.php`:

| Key | Purpose |
|-----|---------|
| `route_prefix` | Studio URL (default `blueprint-studio`) |
| `middleware` | Add `auth` in shared environments |
| `allowed_environments` | Where the UI is allowed |
| `controller_bases` | User / Admin / Guest paths & prefixes |
| `auto_routes` | Auto-write `routes/web.php` |
| `field_types` | Visual field catalog + validation |
| `layout.path` | Blade layout for generated views |

### Environment

```env
BLUEPRINT_STUDIO_ENABLED=true
BLUEPRINT_STUDIO_PREFIX=blueprint-studio
BLUEPRINT_STUDIO_FORCE=false
BLUEPRINT_STUDIO_MIDDLEWARE=auth
BLUEPRINT_STUDIO_AUTO_ROUTES=true
```

---

## Security note

Blueprint Studio **writes PHP and Blade into your app**. Use it behind auth, keep it on local/staging, and leave it disabled in production unless you intentionally force-enable it with strong middleware.

---

## Package structure

```
laravel-blueprint-studio/
├── config/blueprint-studio.php
├── database/migrations/
├── resources/views/studio/     ← Visual UI
├── routes/web.php
└── src/
    ├── Http/Controllers/
    ├── Services/               ← Generators + orchestrator
    ├── Support/
    └── Models/
```

---

## License

MIT © [Imran Dev BD](https://imrandev.bd/)

---

## Developed by Imran Dev BD

If you have any issue, need help, or want custom Laravel work — contact me anytime.

| | |
|---|---|
| 🌐 **Portfolio** | [imrandev.bd](https://imrandev.bd/) |
| 💼 **LinkedIn** | [linkedin.com/in/imranbru99](https://linkedin.com/in/imranbru99) |
| 🐙 **GitHub** | [github.com/imranbru99](https://github.com/imranbru99) |
| 🐦 **X / Twitter** | [@imrandev_bd](https://x.com/imrandev_bd) |
| 📺 **YouTube** | [@ImranDevBD](https://youtube.com/@ImranDevBD) |
| 📸 **Instagram** | [@imranbru99](https://instagram.com/imranbru99) |
| 📘 **Facebook** | [ExpertImranDev](https://facebook.com/ExpertImranDev) |
| 🎵 **TikTok** | [@imrandev_bd](https://tiktok.com/@imrandev_bd) |
| 🧵 **Threads** | [@imranbru99](https://www.threads.net/@imranbru99) |
| 📌 **Pinterest** | [@imrandev_bd](https://pinterest.com/imrandev_bd) |
| 💬 **WhatsApp** | [+880 1576-918420](https://wa.me/8801576918420) |
| 📧 **Email** | [me@imrandev.bd](mailto:me@imrandev.bd) |
| 🔗 **All Links** | [linktr.ee/ExpertImranDev](https://linktr.ee/ExpertImranDev) |

**Any issue? Contact me please → [imrandev.bd/contact](https://imrandev.bd/contact)**
