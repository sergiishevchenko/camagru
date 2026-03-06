# AJAX vs Classic Form Submit

## Quick Definition

### Classic form submit

The browser submits the form and performs a page navigation.

- Request is handled by the browser navigation stack
- Server typically returns HTML (or redirect to HTML)
- Browser loads a new document

### AJAX submit

JavaScript submits data in the background, then updates UI without full page reload.

- Request is created by JS (`fetch` or `XMLHttpRequest`)
- Server often returns JSON
- JS updates part of the DOM directly

## What Changes for the User

### Classic form submit

- The whole page refreshes
- Scroll position usually resets
- CSS/JS/assets can be re-requested
- Flash messages are usually rendered by server templates

### AJAX submit

- Page stays in place
- Only specific components update (errors, success, counters, comments)
- Interaction feels faster and smoother
- Client code must handle success/error states explicitly

## What Changes in Network Tab

## Classic form submit (typical pattern)

- `POST /login` (or `/register`)
- `302` redirect to another URL (often `/`)
- `GET /` with `Type = document`
- Follow-up requests for CSS, JS, images, fonts

Strong indicator: after submit, you see a new `document` request.

## AJAX submit (typical pattern)

- `POST /login` with `Type = fetch` or `xhr`
- Response body is JSON (for example `{"success": true, "redirect": "/"}`)
- No automatic `document` navigation unless JS explicitly triggers one

Strong indicator: only `fetch/xhr` appears for the action, and no new `document` request is created by the submit itself.

## Why "fetch exists" Is Not Always Enough

A flow can be mixed:

1. Form is sent with `fetch` (looks like AJAX)
2. JS then calls `window.location.href = "/"`
3. Browser loads a new document

Result:

- Technically a `fetch` happened
- But UX still includes full page navigation
- For strict "no full page reload" checks, this does not qualify

## How to Test Correctly in DevTools

Use this sequence every time:

1. Open DevTools -> Network
2. Enable `Preserve log` (optional but useful)
3. Click `Clear` to remove old requests
4. Submit one action (login/register/like/comment/delete)
5. Inspect only requests created after the click

Interpretation:

- New `document` request after click -> full navigation happened
- Only `fetch/xhr` requests for that action -> AJAX behavior

Tip: keep `All` selected after submit, not only `Fetch/XHR`, so you do not miss a `document` entry.

## Camagru Evaluation Context

Relevant checklist items:

- Login submits via `fetch()` and no full reload
- Register submits via AJAX and no full reload
- Like/comment/delete actions happen via XHR and update UI directly

To pass these checks:

- Action request must be `fetch/xhr`
- UI must update without page-level navigation caused by submit flow

## Typical Implementation Patterns

## Classic submit

Server-side flow:

- Validate input
- Set session/flash data
- `redirect("/")`
- Render full page on next request

Client-side flow:

- Plain `<form method="POST" action="/login">`
- No JS submit interception required

## AJAX submit

Client-side flow:

- Intercept submit with `event.preventDefault()`
- Send payload via `fetch(form.action, {...})`
- Parse JSON
- Update DOM (errors, success state, counters)

Server-side flow:

- Detect AJAX request (for example `X-Requested-With: XMLHttpRequest` or JSON content type)
- Return JSON response shape
- Keep non-AJAX fallback for progressive enhancement
