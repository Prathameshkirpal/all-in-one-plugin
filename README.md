# 🧩 All-in-One Plugin – Modular WordPress Enhancement Suite

The **All-in-One Plugin** is a modular plugin framework designed to enhance WordPress functionality across SEO, UX, schema, and content automation.

Each feature is a self-contained **subplugin**, and you can toggle them independently in the plugin settings.

---

## 🚀 Features and Subplugins

### 1. 📌 SEO Metabox
- Adds a custom meta box for SEO title and slug.
- Auto-updates post slug from the meta field on first publish.
- Slug updates avoid infinite loops via hook removal.

#### ✅ Post Meta Keys:
| Key               | Description                 |
|------------------    |-----------------------------|
| `_seo_title`         | Custom SEO title            |
| `_seo_slug_url`      | Custom post slug (used to update `post_name`) |
| `_seo_canonnical_url`| Custom canonical URl

_seo_canonnical_url : If it is empty then the default canocial will be created. if not it will check validation on edit page and save the proper url and will replace the canonical url with this custom canonical url.
---

### 2. 🗺 Sitemap
- Enables sitemap creation for post, category, tag.
- Toggle per type and set custom filename.
- Flushes rewrite rules only once using flag.

#### ✅ Options:
| Key                          | Description                          |
|-----------------------------|--------------------------------------|
| `maiop_sitemap_enable_post` | Whether to enable post sitemap       |
| `maiop_sitemap_name_post`   | Filename for post sitemap            |
| `maiop_sitemap_flushed`     | Rewrite rules flush flag             |

---

### 3. 🧭 Breadcrumb
- Adds visual breadcrumb trail on all pages except home/front.
- Auto-loads via `wp_head` after header (not shortcode-dependent).
- Injects breadcrumb schema when active.

---

### 4. 🧠 Schema Generator
- Injects JSON-LD schema dynamically in `<head>`.
- Supports:
  - Article
  - Website
  - Organization
  - Breadcrumb (if subplugin active)
- Uses WordPress functions and `_seo_title` for context.

---

### 5. 📚 Also Read (Contextual Recommendations)
- On first publish only, injects related post links to post content.
- Based on **same categories** and **older date**.
- Uses inline CSS with arrow bullets.
- Styled `<hr>`, responsive block, black/red hover text.

#### ✅ Meta Keys:
| Key                      | Description                        |
|-------------------------|------------------------------------|
| `_also_read_injected`   | Prevents multiple injections       |

---

### 6. 📬 Contact Us
- Automatically creates a `contact-us` page if not already present.
- Fully functional **frontend-only** form (JS-driven "success" alert).
- Responsive, styled with inline CSS.

#### ✅ Page Setup:
| Field         | Value             |
|---------------|-------------------|
| `post_type`   | `page`            |
| `post_name`   | `contact-us`      |
| `post_title`  | `Contact Us`      |
| `post_status` | `publish`         |
| `post_content`| HTML Form Content |

---

## ⚙ Plugin Structure

- Each subplugin is in `/classes/class-{subplugin}.php`
- Settings toggles are saved under:  
  `option_name: maiop_enabled_plugins`

#### Example:
```php
get_option('maiop_enabled_plugins', array());
// Returns: [ 'seo-metaboxes', 'schema', 'breadcrumb', ... ]


// Developer Refrence.
| Component        | Type    | Key / Hook / Meta        |
| ---------------- | ------- | ------------------------ |
| Enabled Plugins  | option  | `maiop_enabled_plugins`  |
| SEO Title        | meta    | `_seo_title`             |
| SEO Slug         | meta    | `_seo_slug_url`          |
| Sitemap Settings | options | `maiop_sitemap_enable_*` |
| Also Read Flag   | meta    | `_also_read_injected`    |
| Rewrite Flush    | option  | `maiop_sitemap_flushed`  |
| Contact Page     | WP Page | slug `contact-us`        |

📦 Plugin Management
To add or remove subplugins:

Drop or create files in /classes/class-{plugin-slug}.php

Slug becomes your class: class {Plugin_Slug} (dash converted to underscore, capitalized)

It is autoloaded based on toggled entries in settings.

📦 Plugin Management
To add or remove subplugins:

Drop or create files in /classes/class-{plugin-slug}.php

Slug becomes your class: class {Plugin_Slug} (dash converted to underscore, capitalized)

It is autoloaded based on toggled entries in settings.