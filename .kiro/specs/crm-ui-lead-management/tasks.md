# Implementation Plan

## Overview

Implement complete CRM UI for Lead Management with 11 tasks focusing on Persian translations, form enhancements, kanban polish, and correctness properties via property-based testing.

## Tasks

- [x] 1. Add Persian Translation Keys for Lead Management (50+ keys for all lead views: navigation, kanban, show page, forms, flash messages, filters, timeline, student conversion). Files: lang/fa/admin.php. Acceptance: All keys added with Persian values, no duplicates, no raw key strings in views.
- [x] 2. Add @php CSS Variables to Create and Edit Forms (Define $btnPrimary, $btnSecondary, $inputClass at top of both create.blade.php and edit.blade.php @section('content'). Match CSS classes from index.blade.php). Files: resources/views/admin/leads/create.blade.php, edit.blade.php. Acceptance: Both forms render identical styling, buttons use variables, forms submit successfully.
- [x] 3. Add start_date Field to Convert Lead Form (Add optional date input to Convert_Card on show.blade.php before submit button. Label: "{{ __('admin.start_date') }} ({{ __('admin.optional') }})".). Files: resources/views/admin/leads/show.blade.php. Acceptance: Field renders, form submits with/without value, enrollment created with correct start date.
- [x] 4. Remove Draggable from Kanban Cards and Add Static Note (Remove draggable="true" from card anchors. Add static note below each column header: "تغییر وضعیت را از صفحه جزئیات سرنخ انجام دهید"). Files: resources/views/admin/leads/kanban.blade.php. Acceptance: No draggable attributes, static note visible, cards remain clickable, overdue labels render.
- [x] 5. Verify Overdue Styling Across All Views (Review-only. Verify: Index rows have bg-rose-500/[0.03], Kanban cards show «سررسید گذشته» with text-rose-400, Show page date has text-rose-400 with suffix. Only visible when isOverdue() true and not terminal.). Files: none (review only). Acceptance: All three views styled correctly for overdue, no styling for terminal statuses.
- [x] 6. Dashboard Integration & Navigation (Verify: Sidebar links to /admin/leads and /admin/leads/kanban, index/kanban have view-toggle buttons, create buttons on both, all back/cancel buttons link correctly.). Files: none (already configured). Acceptance: All navigation links work, no broken links, no 404 errors.
- [x] 7. Property-Based Testing - Translation Completeness (Create tests/Feature/LeadTranslationsTest.php. Scan all lead view files for __('admin.X') calls. Verify each key exists in lang/fa/admin.php with non-empty Persian value.). Files: tests/Feature/LeadTranslationsTest.php. Acceptance: Test passes for all 50+ lead-specific keys, no missing or empty keys.
- [x] 8. Property-Based Testing - Form Field Consistency (Create tests/Feature/LeadFormConsistencyTest.php. Browser tests verify create and edit forms render identical fields in identical order via reusable partial, old() fallback works, validation messages appear.). Files: tests/Feature/LeadFormConsistencyTest.php. Acceptance: Test passes, forms are identical.
- [x] 9. Property-Based Testing - Overdue Indicator Accuracy (Create tests/Feature/LeadOverdueIndicatorTest.php. Verify overdue indicator appears only when isOverdue() true and not terminal. Test with past/future follow-up dates and terminal statuses.). Files: tests/Feature/LeadOverdueIndicatorTest.php. Acceptance: Test passes for all views, overdue styling only appears when expected.
- [x] 10. Property-Based Testing - No N+1 Queries (Create tests/Feature/LeadQueryOptimizationTest.php. Profile query counts: show page = 1 query, index = 2 queries, kanban = 2 queries. No per-field queries for relations.). Files: tests/Feature/LeadQueryOptimizationTest.php. Acceptance: All query counts correct, no N+1 detected.
- [x] 11. UI Polish & Final Review (Manual testing: Render all lead views, submit all forms with valid/invalid data, navigate all links, verify translations load, check responsive design, accessibility). Files: none (review only). Acceptance: All views render, all forms work, all links valid, no console errors, responsive on mobile/tablet/desktop.

## Notes

All tasks must complete in sequence per Task Dependency Graph. Each task modifies maximum 5 files. No backend changes allowed.

---

## Task Dependency Graph

```json
{
  "waves": [
    {
      "wave": 1,
      "tasks": [1]
    },
    {
      "wave": 2,
      "tasks": [2]
    },
    {
      "wave": 3,
      "tasks": [3]
    },
    {
      "wave": 4,
      "tasks": [4]
    },
    {
      "wave": 5,
      "tasks": [5]
    },
    {
      "wave": 6,
      "tasks": [6]
    },
    {
      "wave": 7,
      "tasks": [7, 8, 9, 10]
    },
    {
      "wave": 8,
      "tasks": [11]
    }
  ]
}
```

## Summary

**Total Files Modified:** 5  
**Total Tasks:** 11  
**Estimated Effort:** 4-6 hours
