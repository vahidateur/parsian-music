# Parsian Music Academy ERP — Master Project Handoff

## Project Overview

Parsian Music is a private music academy management system (ERP / Admin Panel) for managing:

* students
* teachers
* instruments
* enrollments
* schedules
* attendance
* makeup classes
* room allocation
* payments (future)

Primary goal:

Build a production-ready internal admin panel for academy staff.

---

# Tech Stack

Backend:

* PHP 8.2+
* Laravel 13

Frontend:

* Blade
* Tailwind CSS
* Alpine.js optional (minimal)
* No Vue
* No React

Database:

* MySQL
* UTF8MB4 collation

Environment:

* Windows
* Laragon
* Shared hosting compatibility preferred

Architecture:

* Modular monolith
* Blade-first architecture
* Controller-based business logic
* No service layer yet
* No repository layer yet
* No API layer yet

---

# Coding Rules (STRICT)

1. Keep architecture simple and production-ready.
2. Prefer controller logic over service classes for now.
3. Avoid overengineering.
4. Blade only.
5. Tailwind only.
6. Avoid JavaScript unless necessary.
7. Small tasks preferred (1–2 files per task).
8. Never assume files were created—verify actual files.

---

# Business Rules

## Academy Rules

* Registration is manual (admin only)
* No public signup
* 3 physical classrooms

Room labels:

* Room 1
* Room 2
* Room 3

---

## Class Duration Rules

Default class duration:

* 30 minutes

Exceptions:

* Makeup class = 60 minutes
* Theory class = 60 minutes

Scheduling engine must support duration override per session.

---

## Enrollment Rules

One enrollment means:

Student learns one instrument with one teacher.

Constraints:

1. Duplicate active enrollment forbidden:

Same:

* student
* instrument
* active status

is not allowed.

2. Teacher must teach selected instrument.

Validated through:
teacher_instruments

3. Enrollment status values:

* active
* paused
* completed
* cancelled

4. Enrollment uses soft delete.

---

# Authentication System

Completed.

Role-based auth system exists.

Roles:

* admin
* teacher
* student

Current login:

* custom auth controller
* admin dashboard exists

No public registration.

---

# Database Schema

## users

Columns:

* id
* full_name
* phone (unique)
* email nullable
* password
* role
* is_active
* last_login_at nullable
* created_by nullable FK users.id
* timestamps

Notes:

* name column renamed to full_name
* email_verified_at removed

---

## students

Columns:

* id
* full_name
* phone
* parent_phone nullable
* status
* join_date
* notes nullable
* timestamps

Student model:

* join_date cast => date

Current CRUD:
COMPLETE

---

## teachers

Columns:

* id
* user_id nullable
* teacher_code
* full_name
* phone
* bio nullable
* status
* hire_date nullable
* notes nullable
* timestamps

Status indexed.

---

## instruments

Columns:

* id
* name unique
* slug unique
* is_active boolean default true
* timestamps

Examples:

* Piano
* Guitar
* Violin
* Vocal
* Music Theory

---

## teacher_instruments

Pivot table.

Columns:

* id
* teacher_id
* instrument_id
* skill_level
* is_primary
* timestamps

Constraints:

* unique(teacher_id, instrument_id)

Skill examples:

* advanced
* expert
* master

---

## student_enrollments

Core business table.

Columns:

* id
* student_id
* instrument_id
* teacher_id
* skill_level
* status
* started_at
* ended_at nullable
* notes nullable
* deleted_at (soft delete)
* timestamps

Indexes:

* composite student_id + status
* composite teacher_id + status

Model:
StudentEnrollment exists.

Relationships:

* belongsTo Student
* belongsTo Teacher
* belongsTo Instrument

SoftDeletes enabled.

---

## recurring_schedules

Weekly schedule template.

Columns:

* id
* enrollment_id
* weekday (0–6)
* start_time
* duration_minutes
* room
* is_active
* timestamps

Purpose:
Generate recurring weekly classes.

---

## class_sessions

Generated or manual sessions.

Columns:

* id
* enrollment_id
* recurring_schedule_id nullable
* session_date
* start_time
* duration_minutes
* status
* room
* notes nullable
* timestamps

Status examples:

* scheduled
* completed
* cancelled
* missed
* makeup
* rescheduled

Purpose:
Actual class occurrences.

---

# Completed Modules

## Completed

### Core Setup

* Laravel project
* DB connection
* migrations
* Breeze installed (partial use)

### Auth

* custom login
* role routing
* admin dashboard

### Students Module

DONE

Includes:

* Student model
* Student controller
* Routes
* Index view
* Create view
* Edit view

CRUD functional.

### Enrollment Backend

DONE

Includes:

* StudentEnrollment model
* StudentEnrollment controller
* duplicate validation
* teacher-instrument validation
* soft delete

### Scheduling Schema

DONE

Database design completed for:

* recurring schedules
* class sessions

---

# Known Technical Debt

1. routes/web.php becoming large

Future refactor:

* routes/admin.php

2. Controller validation duplication

Future refactor:

* FormRequest classes

3. No service layer yet

Acceptable for current project stage.

4. Search uses LIKE queries

Acceptable for current dataset.

---

# Pending Modules / Roadmap

## Sprint 6

Enrollment UI
Student profile integration

Pending:

* enrollments index page
* create enrollment form
* edit enrollment form

---

## Sprint 7

Teachers Module

Needs:

* teacher CRUD
* teacher instrument assignment UI

---

## Sprint 8

Scheduling Engine

Critical module.

Needs:

* recurring schedule UI
* auto session generation
* conflict detection
* room collision detection

---

## Sprint 9

Attendance System

Needs:

* attendance marking
* missed class logic
* makeup eligibility

---

## Sprint 10

Makeup / Reschedule Engine

Complex business logic.

Must support:

* missed classes
* compensatory sessions
* swaps between students
* manual overrides

---

## Sprint 11

Payments

Needs:

* invoices
* payments
* overdue tracking

---

# Current Next Task

Current recommended next task:

Sprint 6 — Enrollment UI

Create:

* admin/enrollments/index.blade.php
* create.blade.php
* edit.blade.php

Goal:
Allow admins to manage enrollments visually.

---

# Important Instruction for Any AI Continuing This Project

Before generating code:

1. Respect existing schema.
2. Do not redesign architecture unless explicitly requested.
3. Prefer small tasks (1–2 files).
4. Verify actual files instead of trusting tool logs.
5. Keep consistency with Laravel 13 + Blade + Tailwind.
