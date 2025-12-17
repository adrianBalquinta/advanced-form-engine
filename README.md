# advanced-form-engine

- Plugin development - PHP 8 OOP design patterns - full-stack architecture, data modeling, and API integrations.

## 🧠 Architecture Overview

- Advanced Form Engine is designed as a **modular, event-driven WordPress plugin**, intentionally structured to resemble larger PHP applications rather than traditional monolithic WordPress plugins.

- The goal of this architecture is to keep **business logic decoupled**, **features extensible**, and **integrations replaceable**, while remaining fully compatible with WordPress core APIs.

---

## 🧩 High-Level Design

- The plugin is organized into clear, responsibility-driven layers:


src/
├── Admin/          → WordPress admin UI (forms list, editor, settings)
├── Frontend/       → Shortcodes, rendering, submission handling
├── Forms/          → Form entities and repositories
├── Submissions/    → Submission persistence (custom DB table)
├── Notifications/  → Integration strategies (Slack, Email, Webhooks)
├── Events/         → Domain events
├── Core/           → Plugin bootstrap, service container, event dispatcher
└── Settings/       → Plugin configuration storage

--- 

- Each layer has a single responsibility and communicates with others through interfaces and events, not direct dependencies.

## 🔧 Dependency Injection & Service Container

- All core services are registered during plugin bootstrap using a lightweight service container.

- Examples of container-managed services include:

    - Form repositories

    - Submission repositories

    - Event dispatcher

    - Notification manager

    - Settings handler

    - Frontend shortcode handler

- This avoids:

    - Global state

    - Hard-coded dependencies

    - Tight coupling between unrelated components

    - It also improves maintainability, testability, and long-term extensibility.

## 🔁 Event-Driven Submissions (Observer Pattern)

- When a form submission is successfully saved, the plugin emits a domain-level event:

    - afe.submission.created


- This event contains:

    - The form ID

    - The submission ID

    - Sanitized submission data

- The submission logic does not know which integrations will react to this event.

- Instead, listeners subscribe via an internal EventDispatcher, implementing the Observer pattern.

- This allows new behaviors (notifications, logging, integrations) to be added without modifying the submission workflow.

# 🔌 Notifications via Strategy Pattern

- All notification integrations implement a shared interface:

    - NotifierInterface

- Current notifier strategies include:

    - Slack Incoming Webhook

    - Email notifications (via wp_mail)

    - Custom JSON Webhook (HTTP POST)

- Each notifier:

    - Determines internally whether it is enabled

    - Reacts independently to submission events

    - Can be added or removed without impacting other integrations


# 🌉 WordPress ↔ Domain Bridge

- WordPress hooks are used only as entry points, not as the core architectural layer.

- For example:

    - A WordPress action (do_action('afe/submission_created')) is triggered after saving a submission.

- This action is translated into a domain event.

- Internal observers respond to the event independently.

- This keeps WordPress-specific concerns isolated and prevents framework lock-in inside business logic.

## 🔐 Security & Data Handling

- The plugin follows WordPress security best practices throughout:

    - Nonce verification for all form submissions.

    - Capability checks for admin actions.

    - Field-level sanitization and validation.

    - JSON-based storage for flexible submission schemas.

    - Integration secrets (webhook URLs, notification emails) are stored centrally through the Settings layer.

## 🚀 Why This Architecture?

- This architecture was chosen to demonstrate:

    - Scalable plugin design beyond procedural hooks.

    - Clean separation of concerns.

    - OPP patterns (Repository, Strategy, Observer)

    - Long-term maintainability and extensibility

