# QUICK REFERENCE: Livewire Migration Summary

## The Situation
Your codebase is **100% Livewire-dependent** for all interactive features. To migrate to pure Laravel requires systematic refactoring of:
- 17 Livewire components (~5,500 LOC)
- 19 Blade files (150+ wire: directives)
- 30+ API endpoints to create
- Event system (20+ events)
- Streaming system (SSE/WebSockets)

## The Plan (6 Phases)

```
PHASE 1: Routing        (4-6 hrs)  ← Start here
         ↓
PHASE 2: Blade          (8-12 hrs)
         ↓
PHASE 3: State Mgmt     (6-8 hrs)
         ↓
PHASE 4: API Endpoints  (10-14 hrs)
         ↓
PHASE 5: Streaming      (6-10 hrs)
         ↓
PHASE 6: Cleanup        (2-4 hrs)  ← Remove Livewire
```

**Total: 40-60 hours (2-3 weeks with testing)**

## What Gets Replaced

| Current | Becomes | Method |
|---------|---------|--------|
| `@livewire('component')` | Blade `@include()` | Direct include |
| `wire:click="method"` | `@click="postTo('/api/...')"` | Alpine.js + AJAX |
| `wire:model` | Alpine `x-model` or form submission | Manual binding |
| `wire:loading` | JavaScript class toggling | Manual state |
| `wire:stream` | EventSource SSE | Direct JavaScript |
| `dispatch('event')` | AJAX POST request | REST API |
| Livewire events | JavaScript callbacks/promises | Promise chains |

## Critical Components (Priority Order)

1. **🔴 ClaudeCodeApp** (827 lines)
   - File uploads
   - Session management
   - Message streaming
   - Repo integration

2. **🔴 ChatInterface** (1,106 lines)
   - LLM response streaming
   - Message handling
   - Real-time updates

3. **🔴 ArtifactPanel** (416 lines)
   - Document management
   - Real-time updates
   - Export functionality

4. **🔴 SettingsModal** (558 lines)
   - Settings persistence
   - Form validation
   - API key management

## Blade Directives to Replace (by frequency)

```
wire:click                  45+ instances   → @click with AJAX
wire:model                  30+ instances   → x-model or form submission
wire:model.live.debounce    8+ instances    → Alpine @input with debounce
wire:loading                15+ instances   → Manual JS class toggle
wire:navigate.hover         3+ instances    → Regular <a href>
wire:stream                 1 instance      → JavaScript EventSource
```

## Dependencies Between Phases

```
✅ Phase 1 = ZERO dependencies (standalone routing)
✅ Phase 2 = Requires Phase 1 routes to exist
✅ Phase 3 = Requires Phase 2 blade structure
✅ Phase 4 = Requires Phase 3 state management pattern
✅ Phase 5 = Requires Phase 4 API endpoints
✅ Phase 6 = Requires ALL previous phases complete
```

**Cannot skip phases** - Each builds on previous.

## Recommended Approach

### Option A: Full Migration (Recommended)
- Do all 6 phases sequentially
- Takes 3-4 weeks
- Result: Zero Livewire dependency
- Best for: Long-term maintainability

### Option B: Gradual Migration (Risk: Complexity)
- Run Livewire + Pure Laravel in parallel
- Use feature flags to toggle
- Takes 4-6 weeks
- Risk: Two competing systems

### Option C: Partial Migration (Not Recommended)
- Keep some Livewire, convert some to pure Laravel
- Creates technical debt
- Avoid this approach

## Key Risks

| Risk | Severity | Mitigation |
|------|----------|-----------|
| Streaming failures with SSE | HIGH | Test SSE + have WebSocket fallback |
| API response format mismatch | HIGH | Define schema first, contract tests |
| Lost session state | MEDIUM | Implement session persistence early |
| File upload handling | MEDIUM | Test thoroughly in Phase 4 |
| Race conditions in debounce | LOW | Implement client-side debounce |

## Success Metrics

After migration, verify:
- ✅ All routes return 200 status
- ✅ Forms submit without JavaScript errors
- ✅ Search/debounce works smoothly
- ✅ Message streaming completes in <5s
- ✅ File uploads work correctly
- ✅ No browser console errors
- ✅ 95%+ E2E test pass rate
- ✅ Zero Livewire references in code

## Files to Read Next

1. **MIGRATION_AUDIT_REPORT.md** - Full detailed analysis (12KB)
2. **Phase 1 Implementation** - Routing conversion guide
3. **Phase 2 Implementation** - Blade conversion templates

## Quick Start (Phase 1)

```bash
# 1. Create feature branch
git checkout -b livewire-migration-phase1

# 2. Create new controllers
touch app/Http/Controllers/ClaudeCodeController.php
touch app/Http/Controllers/DesignController.php
touch app/Http/Controllers/ChatApiController.php

# 3. Update routes/web.php
# - Replace Livewire routes with controller routes
# - Add API routes for chat, artifacts, etc.

# 4. Create tests
touch tests/Feature/ChatApiTest.php
touch tests/Feature/ArtifactApiTest.php

# 5. Test and commit
php artisan test
git commit -m "Phase 1: Routing migration"
```

## Time Estimation by Role

| Role | Phases | Hours |
|------|--------|-------|
| Backend Developer | 1,3,4,5,6 | 35-45 hrs |
| Frontend Developer | 2,3 | 8-15 hrs |
| QA/Tester | All | 10-15 hrs |

## Estimated Timeline

```
Day 1-2: Phase 1 (Routes)              ✅ Can be done in parallel
Day 3-4: Phase 2 (Blade)               ✅ Team can work on different files
Day 5:   Phase 3 (State)               ✅ Quick phase
Day 6-8: Phase 4 (API)                 ❌ Critical, needs careful testing
Day 9-10: Phase 5 (Streaming)          ❌ High complexity
Day 11:   Phase 6 (Cleanup)            ✅ Quick phase
Day 12-15: Testing & Bugfixes          ✅ Parallel with each phase
```

## Go/No-Go Decision Points

### After Phase 1: Should we continue?
- ✅ GO if: All routes return 200, no routing errors
- ❌ NO-GO if: 404s persist, middleware issues

### After Phase 2: Should we continue?
- ✅ GO if: All Blade templates render, no syntax errors
- ❌ NO-GO if: Missing directives, broken layouts

### After Phase 3: Should we continue?
- ✅ GO if: State persists across navigations
- ❌ NO-GO if: Lost data, session issues

### After Phase 4: Should we continue?
- ✅ GO if: All API endpoints work, 95%+ tests pass
- ❌ NO-GO if: Network errors, validation failures

### After Phase 5: Should we continue?
- ✅ GO if: Streaming works, <5s response time
- ❌ NO-GO if: Connection drops, slow responses

### After Phase 6: Ship it!
- ✅ SHIP if: All E2E tests pass, no Livewire references
- ❌ HOLD if: Console errors, missing features

---

**No code has been changed.** This is an audit and plan only.

To proceed: Review MIGRATION_AUDIT_REPORT.md and confirm you want to start Phase 1.
