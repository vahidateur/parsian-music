# Engineering Constitution

**Status:** Active
**Version:** 1.0
**Applies to:** All requirements, designs, implementation tasks, code, tests, documentation, reviews, and pull requests in this repository.

> This document defines the mandatory engineering rules for the project. All requirements, designs, implementations, reviews, and pull requests must comply with this Constitution. Changes to this document require explicit approval.

## Authority and Change Control

1. The approved specification is the source of truth for feature or bugfix scope and behavior.
2. This Constitution governs the engineering process and quality gates around that specification.
3. Requirements, Design, and Tasks must remain consistent. If they conflict, stop and report the inconsistency; do not guess.
4. Do not add, remove, rewrite, or weaken a constitutional rule automatically.
5. Any requested work outside the approved specification must not be implemented or merged. Report it as a future feature, improvement, or follow-up specification.
6. Every implementation task must be traceable to an approved Requirement, Design section, and Task.

## Chapter 1 — Development Process

1. Use the approved sequence: Requirements → Design → Tasks → Implementation → Testing → Documentation → Review → Merge.
2. Execute one coherent module or task at a time; respect task dependencies and approved ownership boundaries.
3. For each new area, use the established delivery cycle: Blueprint, Implementation, Review, and Freeze.
4. A frozen area must not receive architectural or behavioral changes without an approved new phase or bugfix.
5. Do not begin implementation when a required specification artifact or traceability mapping is missing.
6. The Orchestrator coordinates task status, dependencies, ownership, and cross-agent integration; implementation agents implement and validate only their assigned work.

## Chapter 2 — Requirements and Scope

1. Implement only approved requirements. Do not treat optimization, cleanup, or discovery of technical debt as permission to expand scope.
2. Preserve existing behavior except where the approved specification explicitly requires a change.
3. For Bugfix specifications, change only the implementation necessary to resolve the approved bug. Keep optimization local to the affected implementation.
4. For Feature specifications, implement only the approved feature and its explicit acceptance criteria.
5. If a requested change cannot be mapped to a Requirement, Design section, and Task, stop implementation and report it.
6. If an implementation reveals a missing requirement, unresolved behavior, or architectural limitation, stop and request clarification or an approved change proposal.
7. Out-of-scope discoveries must remain unmodified and be documented separately as follow-up work.

## Chapter 3 — Architecture and Ownership

### Architecture Enforcement

1. The approved architecture is immutable during implementation.
2. Do not introduce architectural patterns, move business logic across layers, bypass domain boundaries, or duplicate domain logic.
3. Do not create new Services, Repositories, DTOs, Traits, Helpers, Components, Events, Resources, Policies, Requests, Enums, or other abstractions unless the approved Design explicitly requires them.
4. Reuse existing services and domain logic. Extend an existing abstraction when it already owns the responsibility.
5. If the approved architecture cannot satisfy the requirement, stop implementation and create an Architecture Change Proposal. Continue only after explicit approval.
6. Controllers remain thin. Business logic belongs in the existing domain/application layer, not in Blade or controllers.

### File Ownership Enforcement

1. Each source file has exactly one owning implementation agent for a task or wave.
2. An agent must not edit a file owned by another agent.
3. If another owned file is required, create a dependency request naming the owner, file, requested change, and reason; wait for resolution.
4. The Orchestrator is the only component allowed to coordinate and merge cross-agent work.

### Laravel Convention First

Prefer Laravel's idiomatic solutions over custom infrastructure when they satisfy the approved Design: Form Requests, Policies, the Service Container, Events, Eloquent relations, Collections, API Resources, Queues, Notifications, Configuration, Localization, and framework validation.

## Chapter 4 — Implementation Rules

### Consistency Before Creation

Before creating any new implementation type, search the project for an equivalent responsibility. Reuse it or extend it when appropriate; do not create parallel implementations with overlapping names or behavior.

### Readability and Self-Documentation

1. Write production code for humans first. Code must communicate intent before implementation details.
2. Prefer descriptive names for classes, methods, variables, DTOs, events, jobs, and commands. Avoid non-standard abbreviations.
3. Prefer explicit code, guard clauses, early returns, shallow nesting, small single-responsibility methods, and simple algorithms.
4. Avoid large methods, magic numbers, magic strings, duplicated logic, hidden side effects, implicit behavior, unnecessary abstractions, and clever tricks.
5. Every business rule must have one authoritative implementation or documentation location.
6. Document every non-obvious decision immediately above the relevant code.
7. Every public method and every non-trivial private method must have a concise documentation block describing applicable purpose, responsibility, inputs, outputs, business rules, and side effects.
8. Simple self-explanatory methods do not need redundant documentation blocks.
9. Comments must explain why, intent, constraints, or business context—not restate what the code plainly does.
10. Use clear section headers for major phases of long methods, such as validation, resource loading, conflict detection, persistence, and audit recording.
11. If a method is approximately 40–50 logical lines or longer, consider extracting cohesive responsibilities without creating unnecessary abstractions.
12. Code must be understandable by a new developer without AI assistance or reverse-engineering the entire project.

### Security, Data, and Framework Boundaries

1. Validate all external input with the appropriate Form Request or established validation boundary.
2. Use authorization through Policies or Gates.
3. Use Eloquent or parameterized Query Builder operations; never concatenate raw SQL.
4. Use escaped output by default and never expose secrets in code, views, logs, or responses.
5. Use migrations for schema changes and explicit foreign keys, indexes, delete behavior, timestamps, and soft deletes where required by the approved Design.
6. Use eager loading for known relations and keep queries out of Blade.
7. Use transactions for multi-step state changes; keep them short and atomic.
8. Keep monetary values in the smallest integer unit required by the project domain.
9. Use queues for approved heavy or deferred work.

### Refactoring and Deletion

1. Refactor only when required by the approved implementation or necessary to preserve its correctness.
2. Do not refactor unrelated technical debt during a feature or bugfix.
3. Never delete existing code unless removal is explicitly required, the code is verified unused, and all related tests pass.
4. Prefer deprecation, replacement, or migration over deletion.
5. If removal may affect backward compatibility, document the reason and migration path before deleting.
6. Unrelated technical debt must be documented as a follow-up recommendation, not modified in the current work.

## Chapter 5 — Code Quality Priorities

Unless the approved specification explicitly requires otherwise, optimize in this order:

1. Readability
2. Maintainability
3. Correctness
4. Testability
5. Performance

The simplest correct implementation is preferred over the most clever implementation. If code becomes difficult to explain, simplify it before optimizing it.

### Readability Review

Before marking a task complete, review:

- Can the method be understood in under one minute?
- Can any method be split into smaller responsibilities without unnecessary abstraction?
- Are names self-explanatory?
- Is every business rule easy to locate?
- Is logic duplicated?
- Is coupling hidden?
- Is complexity necessary and justified?

Any identified readability problem must be fixed or documented as an explicit blocker before completion.

## Chapter 6 — Documentation and Project Records

1. Keep this Constitution in the repository and version it with the project.
2. Update relevant documentation whenever behavior, public contracts, operational procedures, or approved architectural decisions change.
3. Update `CHANGELOG.md` for completed user-visible, operational, or compatibility-relevant changes according to the repository convention.
4. Update the Decision Log when architecture, frozen design, business rules, or other recorded project decisions change.
5. Business rules must be documented in the project’s authoritative business-rule document when applicable.
6. Do not leave unresolved TODO or FIXME markers in completed production work.
7. Do not add commented-out code, debug output, temporary code, or dead code.

## Chapter 7 — Testing and Validation

1. Add or update tests for every changed behavior and approved acceptance criterion.
2. Run focused tests for the changed implementation, affected integration tests, and existing regression tests.
3. Validate boundary cases, invalid input, authorization, persistence behavior, and backward compatibility where applicable.
4. For database-backed code, verify query behavior, eager-loaded relations, write counts, transaction boundaries, and absence of N+1 behavior when relevant.
5. For UI changes, validate accessibility, responsive behavior, RTL behavior, and required browser or visual coverage.
6. For property-based tests, encode the approved correctness property and retain useful counterexamples.
7. A test that is expected to fail while proving an unfixed bug is exploration evidence, not a completed regression test. The permanent regression test must pass after the fix.
8. Do not mark a task complete with failing, skipped, unavailable, or unreviewed affected tests unless the blocker is explicitly reported and the task remains incomplete.
9. Never use destructive database commands such as `migrate:fresh` or `db:wipe` without explicit approval.

## Chapter 8 — Performance

1. Optimize only within the approved specification and only where there is a measurable or likely benefit.
2. Do not use performance work to broaden scope, introduce unrelated architecture, or change approved behavior silently.
3. Prefer the simplest algorithm with the lowest practical time and space complexity that remains clear and correct.
4. Minimize database queries, prevent N+1 queries, eager-load appropriate relations, and reuse existing query/domain logic.
5. Add indexes only when justified by actual query patterns, measured evidence, or an approved schema design.
6. Keep transactions short; do not hold locks while performing unrelated work.
7. Measure or justify performance-sensitive changes. If an optimization changes behavior, complexity, or maintainability, explain the trade-off before implementation.
8. For Bugfix specifications, performance changes must remain local to the affected implementation.
9. Report unrelated performance opportunities separately instead of implementing them.

## Chapter 9 — Review, Merge, and Change Safety

1. Keep pull requests and task changes focused, reviewable, and limited to approved files and scope.
2. Never merge work that has not passed the Definition of Done.
3. Preserve backward compatibility unless the approved specification explicitly authorizes a breaking change and documents its migration path.
4. Do not delete or overwrite another agent’s work.
5. Do not commit changes unless the project workflow or user explicitly authorizes the commit.
6. Do not push directly to `main` or `master`; use the repository’s approved branch and review process.
7. Use conventional, concise commit messages when commits are authorized.
8. Do not bypass repository hooks or verification steps without explicit approval.
9. If a blocker, architectural limitation, ownership conflict, or traceability gap is discovered, stop the affected work and report it.

## Chapter 10 — Definition of Done

A task is complete only when every applicable condition is satisfied:

- The approved Requirement is implemented.
- The approved Design is fully respected.
- The implementation maps to the approved Task.
- Related tests were added or updated.
- Focused, affected, and regression tests pass.
- Backward compatibility is preserved, or an approved migration path exists.
- Relevant documentation is updated.
- `CHANGELOG.md` is updated when the change is user-visible, operational, or compatibility-relevant.
- Business rules are documented when applicable.
- A readability and maintainability review is complete.
- No duplicated logic was introduced.
- No unresolved TODO or FIXME remains in the completed production change.
- No known blocker remains undisclosed.
- Modified files remain within assigned ownership boundaries.
- No unrelated feature, refactor, architecture change, deletion, or performance work was included.

If any condition is not satisfied, keep the task incomplete and report the missing condition.

## Final Review Checklist

Before merge, confirm:

1. Every change is traceable to an approved Requirement, Design section, and Task.
2. No scope expansion occurred.
3. The approved architecture and existing domain boundaries remain intact.
4. Existing implementations were searched and reused where appropriate.
5. Code communicates intent and contains only purposeful documentation.
6. Tests, regression coverage, documentation, and required project records are current.
7. Database access is bounded, eager-loaded where necessary, and free of known N+1 behavior.
8. Security, authorization, accessibility, RTL, and responsive requirements are preserved where applicable.
9. Deletions, compatibility impacts, and performance trade-offs are explicitly justified.
10. The task satisfies the complete Definition of Done.

## Amendment Rule

This Constitution is stable project policy. Amendments require explicit approval, a documented reason, and a focused review of affected chapters. During normal implementation, agents may interpret and apply these rules but must not amend them automatically.
