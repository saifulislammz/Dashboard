---
trigger: model_decision
description: Apply when starting or structuring a new pure-PHP (no Laravel) SaaS project — defines required stack (PHP, Composer, MySQL, Redis, Tailwind, Alpine.js, Tabler, ApexCharts) and folder architecture for all new modules/features.
---

# AGR-PHP-001 — Pure PHP SaaS Stack

## Core

- PHP 8.3+, Composer + PSR-4 autoloading.
- Architecture: MVC + Service Layer + Repository Pattern, API-first design.

## Frontend

- Tailwind CSS, Alpine.js, Fetch API (no heavy JS frameworks).

## UI/Components

- Tabler Dashboard (admin UI), Grid.js (tables), ApexCharts (charts), Lucide Icons, SweetAlert2/Notyf (alerts/toasts).

## Data Layer

- MySQL 8+, Redis (cache/session).

## Project Structure

Controllers/ Models/ Services/ Repositories/ Middleware/ Helpers/

## Non-negotiables

- Modular architecture — feature-based, no repeated framework-like boilerplate.
- Scalable design: must run on shared hosting and scale up to VPS without rewrite.
- Low resource usage (CPU/RAM/queries) by default.
- Clean, maintainable, DRY codebase — no reinventing routing/ORM/etc. repeatedly per module.
