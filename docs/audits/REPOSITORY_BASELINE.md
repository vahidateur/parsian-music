# Repository Baseline Stabilization Report

**Date:** 2026-07-23  
**Branch:** `cleanup-before-public`  
**Mode:** Read-only audit; no production code, deletion, staging, or commit performed.

## Verdict

The repository is **not clean or deterministic yet**. The index contains staged documentation and generated artifacts; the worktree contains production Teacher Hero changes, screenshot deletions, and untracked reference/specification trees. Documentation-only updates cannot make the working tree clean without an explicit commit, restore/discard, or isolation decision.

## Change classification

| Change group | Current state | Classification | Required next decision |
|---|---|---|---|
| `.kiro/steering/00–19` | staged additions | Commit candidate | Commit separately as project governance docs |
| Teacher Hero skeleton/visual specs | staged additions | Commit candidate | Commit separately from application code |
| `AI_SCENES/*.md` | staged additions | Commit candidate after index gap check | Resolve missing Scene 05 reference first |
| Teacher Hero verification report/scripts | staged additions | Preserve and reconcile | Update contract/portability before relying on them |
| `resources/css/**`, mock, Blade Hero/layout files | unstaged modifications | Move to Phase 2.4C/2.5 | Do not mix with Phase 1/0.5 baseline |
| Existing screenshot corpus | staged additions/deletions and untracked captures | Generated-output candidate | Curate or remove only with explicit approval |
| `.kiro/specs/admin-panel-completion/` | untracked | Preserve as separate planning change | Do not mix with Teacher Hero work |
| `.kiro/specs/teacher-profile-page/` | untracked | Preserve for its independent feature phase | Do not resume or merge into Phase 0.5 |
| `admin-v2/` | untracked reference prototype | Preserve as reference only | Never use as production source or commit wholesale |
| `bit.cloud/` | untracked reference workspace | Preserve as read-only source | Copy only approved assets later; exclude caches/node_modules/config |

## Specific risks

- Current Hero mock asset URLs do not all resolve from the inspected storage tree.
- Verification scripts describe the older empty-slot contract but the worktree renders image assets.
- Verification scripts contain machine-specific hostname and Chrome-path assumptions.
- Current working-tree Hero CSS/Blade changes include work beyond the staged Phase 1 and CSS-only Phase 2 contracts.
- Registry paths are stale and omit many existing components.

## Baseline policy

No Phase 0.5 implementation may begin until the classified groups above are explicitly disposed of. This report does not authorize commit, deletion, restore, staging, or migration of any group. The next baseline action must be an explicit Git disposition, followed by `git status --short` verification.
