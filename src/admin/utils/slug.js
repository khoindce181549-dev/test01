/**
 * Turn a free-form string into a URL-safe slug.
 *
 * - Lowercase, trimmed
 * - Quotes stripped (so "what's" → "whats")
 * - Any run of non-alphanumeric characters → single dash
 * - No leading or trailing dashes
 * - No double dashes
 * - Capped at 80 chars to match the DB column
 *
 * Used by NewQuizModal's Step 3 slug auto-generator and anywhere else we
 * need to derive a slug from a title.
 */
export function slugify(input) {
  return String(input)
    .toLowerCase()
    .trim()
    .replace(/['"]+/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-')
    .slice(0, 80);
}
