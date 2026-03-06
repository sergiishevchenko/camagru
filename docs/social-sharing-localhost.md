# Social Sharing on Localhost: Why Preview Is Missing

## Short Answer

A share button can be correct even when preview is empty.

Why:

- Share dialogs can open with a URL parameter (`u=` or `url=`) without validating full metadata rendering
- Social crawlers must fetch the target page from the public internet
- `http://localhost:8080/...` is not publicly reachable
- Therefore crawlers cannot read Open Graph/Twitter meta tags from your local machine

## How Sharing Works (Two Separate Steps)

## Step 1: Link generation in your app

Your app creates a share URL such as:

- Facebook: `https://www.facebook.com/sharer/sharer.php?u=<encoded-page-url>`
- Twitter/X: `https://twitter.com/intent/tweet?url=<encoded-page-url>&text=<...>`

If this URL contains `/image/{id}`, your share-link generation is correct.

## Step 2: Metadata scraping by social platform

After the dialog opens, the platform crawler attempts to fetch:

- The shared page URL (`/image/{id}`)
- Its metadata (`og:title`, `og:image`, `og:url`, `twitter:card`, etc.)

If crawler cannot access the page, preview card is missing or partial.

## What Is Expected on Localhost

On local-only setup (`localhost`):

- Share button opens dialog: expected
- Dialog URL includes your image page link: expected
- Rich card preview is absent: often expected

This does not necessarily indicate a bug in your app.

## Evaluation-Focused Validation Checklist

For local defense, validate these items:

1. Share buttons are present on image cards or image page
2. Facebook button opens `facebook.com/sharer/sharer.php`
3. Twitter button opens `twitter.com/intent/tweet`
4. URL query (`u=` or `url=`) contains `/image/{id}`
5. Page source of `/image/{id}` contains:
   - `<meta property="og:title" ...>`
   - `<meta property="og:image" ...>`
   - `<meta property="og:url" ...>`
   - `<meta name="twitter:card" ...>`

If all checks pass, sharing integration is technically correct for local environment.

## How to Prove It Quickly in Browser

## A) Verify generated share link

1. Open gallery or image page
2. Right click share button -> copy link address
3. Decode URL mentally or with DevTools:
   - Confirm encoded target includes `/image/{id}`

Example:

`https://www.facebook.com/sharer/sharer.php?u=http%3A%2F%2Flocalhost%3A8080%2Fimage%2F6`

This confirms correct routing target.

## B) Verify metadata exists

1. Open `/image/{id}` directly
2. View page source
3. Search for OG/Twitter tags listed above

If tags exist, your page is prepared for social preview once publicly reachable.

## Why Facebook/Twitter Sometimes Show "No Link Preview"

Typical causes in local development:

- Target URL is `localhost`
- Target URL is private LAN IP not reachable from internet
- Temporary tunnel blocks bots or rate-limits requests
- Cached old scrape results on platform side

The first cause is the most common.

## Optional: Full Preview Test with Public URL

If evaluator insists on visible card preview, use a public tunnel/domain:

1. Expose local app via tunnel (`https://...`)
2. Ensure generated share links use that public base URL
3. Re-test share buttons
4. Use platform debuggers to force re-scrape

Useful tools:

- Facebook Sharing Debugger: [https://developers.facebook.com/tools/debug/](https://developers.facebook.com/tools/debug/)
- Twitter Card Validator (availability may vary by account/region)

## Suggested Verbal Explanation During Defense

Use this short statement:

"Share links are generated correctly and point to `/image/{id}`.  
Open Graph and Twitter meta tags are present on the image page.  
Card preview is limited on localhost because social crawlers cannot access local URLs."

## Practical Conclusion

For local evaluation, separate:

- **Integration correctness** (button URLs + meta tags)  
from
- **External crawler rendering** (requires public reachability)

If integration checks pass, your implementation is valid even without full preview on localhost.
