# Future Features Guide (#6, #7, #8)
(For later implementation)

This document lists future enhancements you asked to keep for later:
- #6 caching strategy + invalidation
- #7 SEO + OpenGraph
- #8 Add-to-calendar (Google Calendar + ICS) + standardized map buttons

---

## #6 Caching Strategy + Invalidation (Future)

### A) What to cache
Cache only computed payloads (DTO/fields), not Eloquent models:
- `dto`
- `fields`
- template settings (future #4)

### B) What triggers invalidation
Because Pattern A edits many tables, you should invalidate cache on:
- Invitation saved/deleted
- InvitationCouple saved
- InvitationPerson saved
- InvitationEvent saved
- InvitationGalleryItem saved
- InvitationGiftAccount saved
- InvitationMap saved
- InvitationMusic saved
- Asset saved (if any section references it)

### C) How to implement invalidation
Option 1: model observers per related model:
- in `saved` → call `TemplateRenderer::forgetCache($invitation)`

Option 2: use a short TTL (simple):
- cache for 2–5 minutes
- no complex invalidation needed

---

## #7 SEO + OpenGraph (Future)

### A) Basic meta tags
Set:
- `<title>`
- `<meta name="description">`

### B) OpenGraph
- `og:title`
- `og:description`
- `og:url`
- `og:image` (use couple image asset publicUrl)
- `twitter:card`

### C) Indexing policy
- published pages: index
- preview pages: `noindex,nofollow`

### D) Share link consistency
- canonical URL: `/inv/{slug}`

---

## #8 Calendar + Maps Enhancement (Future)

### A) Add to Google Calendar
Build a link from event data:
- title
- start datetime
- end datetime
- location
- description

### B) ICS file download
Route:
- `/inv/{slug}/event/{eventId}.ics`

Generate `.ics` with:
- DTSTART/DTEND
- SUMMARY
- LOCATION
- DESCRIPTION
- UID

### C) Map buttons standardization
For each event:
- if event has location_url → use it
- else fallback to eventSection default_location_url
- else fallback to map_location_url

---

## Suggested implementation order later
1) #7 SEO/OG (quick win)
2) #8 calendar + ics
3) #6 caching + invalidation (only when traffic grows)
