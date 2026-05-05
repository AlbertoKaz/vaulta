# Vaulta

**Vaulta** is a multi-tenant SaaS application designed to manage personal and professional collections with precision and structure.

It allows users to organize items into collections, assign tags, upload images, track value, and collaborate within shared workspaces.

---

## 🚀 Features

* Multi-workspace system (multi-tenant architecture)
* Role-based access (Owner, Admin, Member)
* Collections and items management
* Tagging system with filters
* Image upload with gallery, cover selection and drag & drop reorder
* Item valuation (purchase price vs estimated value)
* CSV export with active filters
* Activity logging system
* Responsive UI with dark mode support

---

## 🧠 Technical Highlights

* Built with **Laravel 13 + Livewire**
* Clean domain modeling (Workspace → Collections → Items)
* Policies and Gates for authorization
* Scoped queries per workspace
* Reactive UI with Livewire (no SPA needed)
* File uploads handled with Livewire
* Modular and scalable architecture

---

## 📸 Screenshots

*Add your screenshots here*

* Dashboard
* Collections
* Members
* Items (with images, filters, edit mode)

---

## ⚙️ Installation

```bash
git clone https://github.com/AlbertoKaz/vaulta.git
cd vaulta

docker compose up -d

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan storage:link
```

Then open:

```text
http://localhost
```

---

## 👤 Demo Users

| Name    | Email                                             | Password  |
| ------- | ------------------------------------------------- | --------- |
| Alberto | [alberto@example.com](mailto:alberto@example.com) | password1 |
| Judith  | [judith@example.com](mailto:judith@example.com)   | password2 |
| John    | [john@example.com](mailto:john@example.com)       | password3 |
| Lucy    | [lucy@example.com](mailto:lucy@example.com)       | password4 |
| Mike    | [mike@example.com](mailto:mike@example.com)       | password5 |

---

## 📦 Demo Data

The project includes realistic seed data:

* Multiple workspaces with different owners
* Collections grouped by theme (art, gaming, collectibles)
* Items with tags and images
* Fully functional filters and relationships

---

## 🧩 Project Purpose

Vaulta is an ongoing SaaS project. This repository contains the V1 portfolio release.

* Real SaaS architecture
* Multi-tenant design patterns
* Clean backend structure in Laravel
* Full-stack development using Livewire

---

## 📄 License

This project represents **Vaulta V1**, a portfolio-ready version of a larger professional SaaS concept.

It has been developed to showcase architecture, product thinking and full-stack capabilities using Laravel and Livewire.

Vaulta is an evolving project, and future iterations are planned with more advanced features, scalability improvements and refined user experience.

This repository is shared for demonstration and portfolio purposes only.

