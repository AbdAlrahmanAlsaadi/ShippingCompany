# Shipping Management System

A RESTful API backend for a multi-center shipping and logistics platform, built with Laravel 12. The system manages the full shipment lifecycle — from client booking through inter-center trailer transfers to final delivery — with role-based access control, real-time notifications, online payments, and performance reporting.

---

## Table of Contents

- [Overview](#overview)
- [Roles](#roles)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [Non-Functional Requirements (NFRs)](#non-functional-requirements-nfrs)
- [Getting Started](#getting-started)
- [API Structure](#api-structure)
- [License](#license)

---

## Overview

The Shipping Management System coordinates shipments across a network of logistics centers. A client creates a shipment, the nearest center is automatically assigned, drivers are offered pickup/delivery tasks, trailers transport shipments between centers, and the client pays online and rates the service after delivery.

---

## Roles

| Role | Description |
|------|-------------|
| **Super Admin** | Manages the entire platform: creates/updates centers, assigns and swaps center managers, blocks/unblocks users, and views system-wide KPIs. |
| **Center Manager** | Manages their center's drivers, shipments, and trailers; confirms shipment receipts; generates financial and operational reports. |
| **Driver** | Receives shipment offers, confirms pickup from client, hands over to center, and confirms final delivery to recipient. |
| **Client** | Books shipments, tracks status, confirms delivery by scanning QR/barcode, submits ratings and reports, and pays online via Stripe. |

---

## Key Features

### Shipment Lifecycle
- Client creates a shipment (recipient details + shipment details with type, weight, pieces).
- System auto-assigns the nearest origin and destination centers using geolocation.
- A unique barcode and QR code are generated per shipment.
- Shipment progresses through statuses: `pending → picked_up → at_center → in_transit → out_for_delivery → delivered`.
- Client confirms delivery by scanning the barcode.

### Driver Offer System
- Center managers dispatch shipment offers to nearby available drivers.
- Drivers accept or reject offers; acceptance triggers status transitions.
- Drivers confirm handover to center and final delivery to recipient.

### Trailer Management
- Center managers assign shipments to trailers and manage capacity (kg / m³).
- Trailers transfer between centers; arrival is confirmed by the receiving center.
- Incoming trailer lists and per-trailer shipment manifests are available.

### Payments
- Online payment via **Stripe Checkout** (session-based flow).
- Webhook handler for asynchronous payment confirmation.
- Payment status check endpoint.

### Ratings & Reports
- Clients rate completed shipments.
- Clients and super admins can submit, view, update, and delete reports.
- Center managers access financial reports, dashboard stats, and shipment summaries.

### KPIs & Analytics
- Super admin performance KPI endpoint.
- Center manager dashboard: total, delivered, and cancelled shipments; average delivery time; daily trend data.

### Authentication & Email Verification
- Token-based authentication via **Laravel Sanctum**.
- Email verification using numeric codes.
- Password reset via emailed verification code.

### Real-Time Notifications
- **Pusher** integration for broadcasting shipment events to connected clients.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 (PHP 8.2+) |
| Authentication | Laravel Sanctum |
| Authorisation | Spatie Laravel Permission |
| Database | MySQL (Eloquent ORM) |
| Payments | Stripe PHP SDK |
| Real-Time | Pusher |
| QR Codes | SimpleSoftwareIO Simple QrCode |
| PDF Export | barryvdh/laravel-dompdf |
| Excel Export | Maatwebsite Laravel Excel |
| Queue | Laravel Queue (database driver) |
| Testing | PHPUnit 11 |

---

## Non-Functional Requirements (NFRs)

### Security
- All protected endpoints require a valid **Sanctum bearer token**.
- Role-based middleware (`role:super_admin`, `role:center_manager`, `role:driver`, `role:client`) enforces least-privilege access on every route group.
- Email verification is mandatory before a client can use the system (`MustVerifyEmail`).
- Passwords are hashed using **bcrypt**.
- Stripe webhooks should be validated using the Stripe signature header.

### Scalability
- Stateless REST API design allows horizontal scaling behind a load balancer.
- Database jobs table enables queue-based background processing (e.g., email dispatch, offer distribution).
- Geolocation-based nearest-center and nearest-driver algorithms reduce manual dispatching overhead.

### Performance
- Sanctum token authentication avoids session state overhead on the server.
- Eager loading of relationships (centers, drivers, trailers) is used in report and KPI queries to avoid N+1 problems.
- Database indexes on foreign keys (center IDs, user IDs, shipment IDs) support performant lookups.

### Reliability
- Queue workers with retry logic handle transient failures in email and notification delivery.
- Shipment status transitions are sequential and enforced in service classes, preventing invalid state changes.
- Stripe webhook processing handles asynchronous payment events to avoid missed payment confirmations.

### Maintainability
- Service-layer architecture separates business logic from HTTP controllers (`AuthService`, `ShipmentCreationService`, `ShipmentDriverOfferService`, `KpiService`, etc.).
- Form Requests centralise input validation, keeping controllers lean.
- API Resource classes standardise JSON response shapes.
- Migrations provide a versioned, reproducible database schema.

### Observability
- Structured JSON responses with consistent `message` / `data` / `status` envelopes across all endpoints.
- Laravel Pail (dev) and queue listener provide real-time log streaming during development.

---

## Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- MySQL
- Node.js & npm (for Vite assets, if needed)

### Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy environment file and configure it
cp .env.example .env
php artisan key:generate

# 3. Configure your database, Pusher, and Stripe credentials in .env

# 4. Run migrations
php artisan migrate --seed

# 5. Start the development server with queue and log listeners
composer run dev
```

### Running Tests

```bash
composer test
```

---

## API Structure

All routes are prefixed with `/api`. Authentication uses `Authorization: Bearer <token>`.

| Prefix | Middleware | Description |
|--------|-----------|-------------|
| *(none)* | public | Sign up, sign in, password reset, email verification |
| *(none)* | `auth:sanctum, role:client` | Shipment creation, tracking, delivery confirmation, ratings, reports, payments |
| `super/` | `auth:sanctum, role:super_admin` | User management, center management, KPIs, reports |
| `centerManagement/` | `auth:sanctum, role:center_manager` | Driver management, shipments, trailers, reports |
| *(none)* | `auth:sanctum, role:driver` | Offer acceptance/rejection, pickup & delivery confirmation |

---

## License

This project is licensed under the [MIT license](https://opensource.org/licenses/MIT).
