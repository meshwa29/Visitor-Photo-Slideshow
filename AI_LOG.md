# AI Log

> **Honesty note.** Section A records real events from the AI-assisted build session that produced this code base.
> Section B is for **you** to fill in with your own genuine prompts and observations. Replace every `PLACEHOLDER`
> before submission and do not add interactions that did not happen.

## A. Build session (recorded)

| # | Prompt / task (summary) | What the AI produced | Notes |
|---|--------------------------|----------------------|-------|
| 1 | Full project brief (PHP, MySQL, XAMPP, admin approval, slideshow, documentation) | Architecture plan, folder structure, database schema | Stack taken strictly from the brief. |
| 2 | Generate SQL and default admin | `sql/database.sql` with a bcrypt hash for `Admin@123` | Hash was verified by a round-trip check in the build environment; re-test your login in XAMPP. |
| 3 | Visitor upload with validation | `upload.php`, helper functions, `upload.js` | Server repeats every client-side check. |
| 4 | Slideshow with auto-refresh | `slideshow.php`, `slideshow.js`, `slideshow_data.php` | ES5 style for TV browsers. |
| 5 | Admin module | login, dashboard, visitors, photos, approve/reject/delete | All state changes are POST + CSRF. |
| 6 | Documentation and test plan | README, ASSUMPTIONS, ARCHITECTURE, test plan | Test results intentionally left empty. |

### AI mistakes found during the build session

1. **Folder creation bug.** The AI ran `mkdir -p {admin,assets/css,...}` in a shell that does not expand braces. It created one folder literally named `{admin,assets` and later file writes failed with "Directory nonexistent". *Found:* the error output of the first file-creation command. *Fixed:* removed the odd folder and created each directory explicitly.
2. **Overlapping image loads (design flaw caught in review).** The first slideshow draft let a slow image load finish after the user had already clicked Next, which could show the wrong photo. *Fixed:* a `loadToken` counter so only the newest request may update the screen.
3. **`hidden` attribute ignored (caught in review).** The empty-state box used the `hidden` attribute but CSS `display:flex` overrides it, so it would always show. *Fixed:* added `[hidden]{display:none !important}` to `style.css`.
4. **Not executed.** PHP was **not available** in the build environment, so the PHP files were reviewed by reading and static checks only, never run. You must run the checks in `tests/MANUAL_TEST_PLAN.md` yourself.

## B. Your own AI interactions (PLACEHOLDER - replace)

| # | Date | Prompt you used | What the AI answered | What you changed or learned |
|---|------|-----------------|----------------------|------------------------------|
| 1 | PLACEHOLDER | PLACEHOLDER | PLACEHOLDER | PLACEHOLDER |
| 2 | PLACEHOLDER | PLACEHOLDER | PLACEHOLDER | PLACEHOLDER |
| 3 | PLACEHOLDER | PLACEHOLDER | PLACEHOLDER | PLACEHOLDER |

### Mistakes you found yourself (PLACEHOLDER - replace with real observations)

1. PLACEHOLDER - what was wrong, how you noticed it (test case ID, error message), how you fixed it.
2. PLACEHOLDER - same format.
