# Gift Finder
**Gift Finder** is a comprehensive OpenCart extension designed to **filter** and discover gift suggestions based on various **criteria** for **OpenCart** stores.
The module provides an intuitive filter-based interface for customers to find the perfect gift ideas quickly and efficiently.
---
## Features
- Fully responsive gift suggestion interface with filter-based navigation.
- AJAX-powered filtering with no page reloads for seamless experience.
- SEO-friendly URL structure for better search engine visibility.
- Admin panel for managing **Gifts** and **Filters**.
- Custom database tables for efficient data organization.
- Pagination system for browsing through multiple gift suggestions.
- Mobile-optimized interface for all device compatibility.
- Easy installation with included SQL setup files.
- Compatible with all OpenCart themes and responsive layouts.
---
## Project Structure
```plaintext
Gift Finder
│
├── admin
│    ├── controller
│    │    └── gift.php
│    ├── language
│    │    └── gift.php
│    ├── model
│    │    └── gift.php
│    └── view
│         ├── gift_form.twig
│         └── gift_list.twig
│
├── catalog
│    ├── controller
│    │    └── gift_finder.php
│    ├── language
│    │    └── gift_finder.php
│    ├── model
│    │    └── gift_finder.php
│    └── view
│         ├── css
│         │    └── gift_finder.css
│         ├── js
│         │    └── gift_finder.js
│         ├── gift_finder.twig
│         ├── gift_finder_items.twig
│         └── gift_finder_pagination.twig
│
├── sql
│    ├── oc_gift.sql
│    ├── oc_gift_description.sql
│    └── oc_gift_filter.sql
```
## Technologies Used
- **PHP** — core programming language for the extension.
- **JavaScript/jQuery** — for AJAX filtering and dynamic interface.
- **CSS** — custom styling for responsive design.
- **MySQL** — database management for gift items and relationships.
- **Twig** — template engine for OpenCart views.
- **OpenCart MVC** — follows OpenCart's Model-View-Controller pattern.
- **AJAX** — for seamless filter updates without page reloads.
- **Responsive Design** — mobile-friendly interface that adapts to all devices.
