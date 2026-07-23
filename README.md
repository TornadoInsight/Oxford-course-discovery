# Course Discovery — Oxford International

A WordPress-based course discovery system: an extensible, hook-driven filter
pipeline over a small domain model, a REST API, an accessible frontend, and
a WordPress admin for managing Courses/Instructors/Providers.

Built for the Oxford International Senior Full Stack PHP Developer take-home
task. The brief asked for architecture, domain modelling and filter
extensibility to be the focus, not UI polish — this README (and the code
comments) lean into explaining *why*, not just *what*.

## Contents

- [Environment requirements](#environment-requirements)
- [Setup instructions](#setup-instructions)
- [Database setup](#database-setup)
- [Development commands](#development-commands)
- [Testing](#testing)
- [Architecture & key decisions](#architecture--key-decisions)
- [Extending the system: adding a new filter](#extending-the-system-adding-a-new-filter)
- [Assumptions](#assumptions)
- [Performance & scalability](#performance--scalability)

## Environment requirements

- PHP 8.1+ (developed against 8.4)
- MySQL/MariaDB 10.6+
- Composer 2.x
- Node 18+ (asset build only — the plugin ships built assets, Node isn't needed at runtime)
- WP-CLI (installed via Composer, see below — no separate install needed)

No Docker is required. The task brief lists Docker as optional ("Submission
can also use Docker..."); this project uses a native PHP/MySQL setup instead
(see [Assumptions](#assumptions) for why) — `composer install` + WP-CLI gets
you the entire stack.

## Setup instructions

```bash
# 1. Clone and install root dependencies (WordPress core is fetched separately, see below)
git clone <this-repo> course-discovery && cd course-discovery
curl -sS https://getcomposer.org/installer | php -- --install-dir=bin --filename=composer
./bin/composer install

# 2. Fetch WordPress core (kept out of git; composer only manages plugins/wp-cli)
./vendor/bin/wp core download --path=wordpress --version=6.9.5 --locale=en_US

# 3. Configure environment
cp .env.example .env
# edit .env with your DB credentials, WP_HOME/WP_SITEURL, and fresh secret keys
# (generate keys at https://api.wordpress.org/secret-key/1.1/salt/)

# 4. Create the database (see Database setup below), then install WordPress
./vendor/bin/wp core install --path=wordpress \
  --url="$WP_HOME" --title="Course Discovery" \
  --admin_user=admin --admin_password=<choose-one> --admin_email=you@example.com

# 5. Activate plugins (runs the DB migration automatically on activation)
./vendor/bin/wp plugin activate advanced-custom-fields --path=wordpress
./vendor/bin/wp plugin activate course-discovery --path=wordpress

# 6. Install the plugin's own PHP dependencies (PSR-4 autoload, test tooling)
cd wordpress/wp-content/plugins/course-discovery && ../../../../bin/composer install && cd -

# 7. Build frontend assets
cd wordpress/wp-content/plugins/course-discovery && npm install && npm run build && cd -

# 8. Seed demo content and serve
./vendor/bin/wp course-discovery seed --path=wordpress
./bin/serve.sh   # http://127.0.0.1:8080
```

Then create a page containing the `[course_discovery]` shortcode (or set an
existing one as the homepage) — the seed command above populates demo
Courses/Instructors/Providers so there's something to filter immediately.

```bash
./vendor/bin/wp post create --post_type=page --post_title="Course Finder" \
  --post_status=publish --post_content="[course_discovery]" --path=wordpress
```

### Why WP core isn't Composer-managed

`johnpbloch/wordpress` (the common Composer-based approach for pulling WP
core) depends on a lightly-maintained custom installer plugin that, in
practice on this setup, didn't reliably relocate core files to the
configured install directory. Rather than fight a fragile dependency,
WP-CLI's own `wp core download` — WordPress's own supported tool for exactly
this job — is used instead. Plugins (ACF) and WP-CLI itself remain
Composer-managed via `wpackagist`.

## Database setup

Two databases: one for the app, one for automated tests (the WordPress test
suite drops/recreates tables between runs, so it must never point at your
working database).

```sql
CREATE DATABASE course_discovery      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE course_discovery_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'course_discovery'@'127.0.0.1' IDENTIFIED BY '<password>';
GRANT ALL PRIVILEGES ON course_discovery.*      TO 'course_discovery'@'127.0.0.1';
GRANT ALL PRIVILEGES ON course_discovery_test.* TO 'course_discovery'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Put the app DB credentials in `.env`; the test DB credentials live in
`wordpress/wp-content/plugins/course-discovery/tests/wp-tests-config.php`
(overridable via `WP_TESTS_DB_*` env vars).

### Migrations

Beyond WordPress's own schema, this project adds one table:
`wp_course_discovery_start_dates` (see
[Architecture](#architecture--key-decisions) for why). Migrations run
automatically on plugin activation, and are idempotent/re-runnable:

```bash
./vendor/bin/wp course-discovery migrate --path=wordpress
```

New migrations: add a class implementing
`CourseDiscovery\Migrations\Migration` (a `version()` string + an `up()`
method using `dbDelta`), then list it in `Plugin::migrationRunner()`.

### Exporting the database

```bash
mysqldump -h 127.0.0.1 -u course_discovery -p course_discovery > course-discovery.sql
```

Restoring: `mysql -u course_discovery -p course_discovery < course-discovery.sql`.

## Development commands

| Command | Purpose |
|---|---|
| `./bin/serve.sh [port]` | Runs the site with PHP's built-in server (defaults to 8080) |
| `./vendor/bin/wp <command> --path=wordpress` | Any WP-CLI command |
| `./vendor/bin/wp course-discovery migrate --path=wordpress` | Run pending DB migrations |
| `./vendor/bin/wp course-discovery seed --path=wordpress` | Populate demo content (idempotent) |
| `npm run build` (in the plugin dir) | Production asset build (minified) |
| `npm run dev` (in the plugin dir) | Watch mode for JS/CSS |
| `./bin/composer <command>` | Root Composer (WP core deps, ACF, WP-CLI) |
| plugin dir: `../../../../bin/composer <command>` | Plugin's own Composer (PSR-4 autoload, test tooling) |

## Testing

Three PHP test tiers, plus browser E2E, reflecting a deliberate trade-off
between speed and realism:

| Tier | Config | What it needs | What it covers |
|---|---|---|---|
| Unit | `phpunit.xml` | Nothing (no WP, no DB) | Domain value objects (`Price`, `StartDate`, `FilterCriteria`) and `CourseQueryBuilder`'s arg-building logic in isolation |
| Integration | `phpunit-wp.xml`, suite `integration` | `course_discovery_test` DB | Filters against real WordPress data (post types, taxonomies, ACF, the start-dates table) |
| Feature | `phpunit-wp.xml`, suite `feature` | `course_discovery_test` DB | REST endpoints end-to-end via `rest_do_request()` |
| E2E | `e2e/` (Playwright) | A running site (`bin/serve.sh`) with seeded data | Real browser behaviour: keyboard-only filtering, chronological start-date ordering, automated a11y scan |

```bash
# Unit — fast, run constantly during development
cd wordpress/wp-content/plugins/course-discovery
./vendor/bin/phpunit -c phpunit.xml

# Integration + feature — needs course_discovery_test
./vendor/bin/phpunit -c phpunit-wp.xml

# E2E — needs the site running (bin/serve.sh) and seeded
cd e2e
npm install && npx playwright install chromium
COURSE_DISCOVERY_BASE_URL=http://127.0.0.1:8080 npx playwright test
```

### What's tested and why

- **Filter grouping (AND across filters, OR within a filter) is the
  highest-risk area** — it's easy to break while changing one filter in
  isolation and not notice, since each filter's own tests still pass.
  `tests/Integration/Query/CourseRepositorySearchTest.php` reproduces the
  brief's worked example verbatim: `(provider OR) AND (location OR) AND
  (category)`, with explicit courses that should and shouldn't match, so a
  regression here fails loudly and specifically.
- **Start date sync/formatting** is the second highest-risk area: three
  representations (ACF day-level date picker, the `{month}-{year}` key used
  in URLs/UI, and the DATE column in the lookup table) have to agree, or
  filtering silently returns wrong results. `StartDateTest` covers the value
  object's parsing/formatting/sorting directly; `StartDateFilterTest` covers
  chronological ordering end-to-end against the real table.
- **Location derivation** (mirrored from Provider to Course) is covered by
  the repository test's provider/location combinations — a stale mirror
  would show up as a course matching the wrong location.

### Regression prevention strategy

- Unit tests run on every change (no infrastructure needed) — cheap enough
  to be a pre-commit habit.
- The two highest-risk behaviours above have integration tests that assert
  on *outcomes* (which course IDs come back) rather than *implementation*
  (which WP_Query args were built), so refactoring the query builder
  internals doesn't require rewriting these tests — only breaking the
  actual behaviour does.
- CI (not configured in this repo, but this is how it should run): unit
  suite on every push; integration/feature/E2E suites on every push to a
  DB-backed runner, blocking merge on failure.

### How to test a new filter consistently

Every `CourseFilter` implementation should have a test class extending
`CourseDiscovery\Tests\Support\FilterContractTestCase`. It asserts the
baseline contract for free (non-empty key/label, `options()` returns
`FilterOption[]`, `apply()` with nothing selected is a no-op) — see
`CategoryFilterTest`/`StartDateFilterTest` for the pattern. Add filter-specific
behavioural tests (like the ones in those two classes) alongside it.

## Architecture & key decisions

**Composition root**: `CourseDiscovery\Plugin` wires everything together in
one place and is the only class that knows about WordPress hook timing —
every other class is constructed with plain PHP dependencies and is testable
without WordPress loaded (see `tests/Unit`).

### Domain model choices

| Data point | Storage | Why |
|---|---|---|
| Name | `post_title` | Native WP field — free indexing, free core search |
| Short description | `post_excerpt` | Native WP field, not reinvented as ACF/postmeta |
| Long description | `post_content` | Native WP field, works with core search & `the_content` filters |
| Price | ACF number field | Simple scalar today; deliberately narrow — see below |
| Instructors / Providers | ACF relationship fields | Multi-value, native post-to-post relationships |
| Categories | Hierarchical taxonomy `course_category` | Exactly what taxonomies are for |
| **Locations** | Taxonomy `location`, assigned on `provider`, **mirrored onto `course`** | "Derived from provider" is a real requirement. Mirroring the terms via `wp_set_object_terms()` (see `Sync\LocationSync`) whenever a course's providers or a provider's locations change means filtering by location is a plain, indexed `tax_query` — no runtime join from course → provider → location on every request. |
| **Start dates** | Dedicated table `wp_course_discovery_start_dates(course_id, start_date DATE)`, synced from an ACF repeater (`Sync\StartDateSync`) | The one data point core WP schema genuinely can't serve well — see below. |

**Why a real table for start dates, specifically:** the brief requires a
chronologically sorted, **distinct** list of `{month}-{year}` values across
every course for the dropdown, plus efficient filtering by selected dates.
An ACF repeater stores each row as a separately-keyed postmeta row
(`start_dates_0_date`, `start_dates_1_date`, ...) with no shared index —
getting a distinct sorted list means loading and de-duplicating every
course's meta in PHP, which doesn't scale. A real `DATE` column with proper
indexes makes both operations a single indexed SQL query
(`SELECT DISTINCT start_date ... ORDER BY start_date`, and a join/`IN`
lookup for filtering). This is the migration the brief explicitly asks for.

**Price is intentionally a single scalar**, matching the brief's note that
it "can be extended to support range or multiple price points" — `Price` is
a value object with one job (format/compare an amount+currency); a future
`PriceRange` can sit alongside it without any calling code needing to change,
as long as it exposes the same `format()` contract.

### The filter pipeline — Strategy + Registry + hook pipeline

Every filter (Search, Providers, Locations, Start Dates, Categories)
implements one interface:

```php
interface CourseFilter {
    public function key(): string;
    public function label(): string;
    /** @return list<FilterOption> */
    public function options(FilterContext $context): array;
    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void;
}
```

That's the entire contract for adding a new filter — see
[Extending the system](#extending-the-system-adding-a-new-filter). Five
WordPress hooks make every stage of the pipeline overridable by third-party
code without touching this plugin's source (see `Support\Hooks` for the
canonical list):

| Hook | Fires | Lets third-party code... |
|---|---|---|
| `course_discovery/filters/register` (action) | Once, at boot | **Register additional filters** |
| `course_discovery/filters/{key}/options` (filter) | Per filter, per request | **Alter filter options** |
| `course_discovery/criteria/transform` (filter) | After parsing the request | **Transform search criteria** |
| `course_discovery/query/args` (filter) | Immediately before the query runs | **Modify filter queries** |
| `course_discovery/query/order` (filter) | While resolving sort order | **Customise result ordering** |

**AND/OR grouping** (top-level AND across filters, OR within one filter's
selected values — exactly the brief's worked example) is enforced in exactly
one place: `CourseQueryBuilder`. Individual filters never build `tax_query`/
`meta_query` fragments by hand; they call intention-revealing methods
(`addTaxTerms`, `addMetaIn`, `restrictToPostIds`) and the builder is
responsible for correct grouping. This means the grouping rule can't
accidentally be violated by a new filter's author, and it's covered by a
dedicated unit test (`CourseQueryBuilderTest`).

**`CourseQueryBuilder`/`CourseRepository`** are the abstraction over
`WP_Query` the brief asks for: filters describe *what* they want narrowed,
never touch `WP_Query` directly, and `CourseRepository` is the only place a
`WP_Post` is converted into the `Course` domain entity — REST responses and
templates depend on `Course`/`Price`/`StartDate`/`PostRef`, never on raw
`WP_Post` or bare primitives.

### Frontend

`[course_discovery]` shortcode renders a fully working, server-rendered
result list and a plain `<form method="get">` filter panel — it works with
JavaScript disabled (a real GET request re-renders the same PHP template).
`assets/src/js/app.js` progressively enhances that same form: intercepts
submit/change events, fetches the REST API, and re-renders in place with
`history.pushState` for shareable URLs. Locations and Start Dates use a
hand-rolled accessible multi-select combobox (`assets/src/js/combobox.js`,
button + `role="listbox"` popup with roving tabindex, typeahead, and full
keyboard support) since the brief requires a dropdown combobox specifically
for those two; Providers/Categories use plain checkbox `<fieldset>`s.

## Extending the system: adding a new filter

Example — filtering by whether a course still has open availability,
without touching any existing file except the one line that registers it:

```php
final class AvailabilityFilter implements CourseFilter
{
    public function key(): string { return 'availability'; }
    public function label(): string { return __('Availability', 'my-plugin'); }

    public function options(FilterContext $context): array
    {
        return [new FilterOption('open', __('Open', 'my-plugin'))];
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        if (in_array('open', $criteria->valuesFor($this->key()), true)) {
            $builder->addMetaIn('availability_status', ['open']);
        }
    }
}

add_action('course_discovery/filters/register', function (FilterRegistry $registry) {
    $registry->register(new AvailabilityFilter());
});
```

It immediately shows up in `GET /course-discovery/v1/filters`, participates
in AND/OR grouping automatically, and gets baseline test coverage for free
by extending `FilterContractTestCase`.

## Assumptions

- **No Docker**: the brief lists it as optional; the development machine
  used for this submission didn't have it installed, so a native PHP/MySQL
  setup was used instead, fully documented above for reproducibility.
- **Start date input**: ACF ships no dedicated month/year-only picker, so a
  standard date picker is used with its display format restricted to
  `F Y` ("July 2026"); the day component is captured but discarded by the
  `StartDate` value object everywhere it matters (filtering, display,
  sorting).
- **"Locations... derived from provider"** is interpreted as: a provider
  can itself have more than one location (e.g. a provider with campuses in
  two countries), and a course's locations are the union of its selected
  providers' locations — mirrored via taxonomy terms rather than computed
  per-request (see Architecture above).
- **Currency**: `Price` defaults to GBP (no currency field in the brief);
  the value object is currency-aware so this is a one-line change if needed.
- **Auth**: the REST endpoints are read-only and public (`__return_true`),
  matching a public course-search page; no rate limiting/auth was added as
  it's out of scope for the brief.
- **Local dev server has no pretty permalinks** (PHP's built-in server does
  no URL rewriting), so the REST client code explicitly handles both plain
  (`?rest_route=`) and pretty permalink structures — this also means it'll
  work unmodified on a production host with real rewrite rules.

## Performance & scalability

Not implemented (per the brief — "implementation of large-scale
optimisations is not required"), but documented as requested:

**Meta query limitations.** Providers/Instructors are ACF relationship
fields, filtered via `meta_query` with `LIKE` against a serialized array
(`addMetaIn()` in `CourseQueryBuilder`). This has two real costs at scale:
(1) no index can serve a `LIKE '%"5"%'`-style query, so it becomes a full
table scan of `wp_postmeta` as course count grows; (2) quoting the search
value (`'"5"'` not `'5'`) avoids the classic false-positive where filtering
for provider `1` also matches provider `12`, but it's still a string scan,
not an index lookup. This is precisely why Locations/Categories use
taxonomies instead (real indexed join tables via
`wp_term_relationships`) and Start Dates uses a dedicated table — both sidestep
this limitation entirely. Providers/Instructors were left as relationship
fields because they're genuinely many-to-many *and* the brief doesn't
require filtering by them to return a distinct sorted list the way Start
Dates does, so the tradeoff didn't justify a third custom table for this
submission — it's the first thing to change if provider-filtering perf
became a real bottleneck (see "lookup tables" below).

**Indexing.** The one custom table has indexes on `course_id`, `start_date`,
and a unique compound `(course_id, start_date)`. `wp_postmeta` is indexed on
`meta_key` but not on `meta_value` for arbitrary comparisons, which is the
root cause of most WordPress meta_query slowness at scale.

**Query performance / N+1 risk.** `CourseRepository::mapPost()` calls
`get_field()`/`get_the_terms()` per course per request. WordPress's object
cache (when a persistent cache like Redis/Memcached is configured) caches
these lookups per-post, so this is cheap after warmup on typical hosting —
but on a cache-less host, this is an N+1 pattern worth flattening into a
single query (e.g. `update_postmeta_cache()`/`update_object_term_cache()`
primed in bulk before the loop) once course counts grow large.

**Caching opportunities.** Filter *options* (the dropdown contents) change
rarely relative to how often they're read — a natural candidate for a short
transient (`get_transient`/`set_transient`, invalidated on course/provider
save) rather than querying on every page load. Course *search results*
are harder to cache generically (high cardinality of filter combinations)
but a persistent object cache turns repeated identical `WP_Query` calls
into cache hits automatically.

**Pagination.** Currently offset-based (`paged`/`posts_per_page`), fine at
current scale; offset pagination degrades on very large result sets
(`OFFSET` still has to skip N rows). Cursor-based pagination (keyset,
ordering by an indexed tiebreaker like `ID`) would replace it if deep
pagination over large result sets became common.

**Search optimisation.** `WP_Query`'s `s` parameter does a `LIKE` scan
across `post_title`/`post_excerpt`/`post_content` — no relevance ranking,
no stemming, and it gets slow as content volume grows because it can't use
a useful index for leading-wildcard `LIKE '%term%'` matches. MySQL
`FULLTEXT` indexes on those columns would be the first upgrade; beyond that,
a dedicated search engine (see below).

**Path to hundreds of thousands / millions of courses:**

1. **Denormalised read table first.** A single flattened
   `course_search_index` table (course_id + already-resolved
   provider/location/category IDs + price + a search-optimised text blob),
   rebuilt on course save, turns every filter combination into indexed
   `WHERE`/`JOIN` queries against one table instead of `WP_Query`
   assembling `tax_query`/`meta_query` across several. This is the natural
   next step of the same pattern the start-dates table already
   demonstrates — extended to cover every filter, not just one.
2. **External search engine** (Meilisearch, Typesense, or Elasticsearch)
   once free-text relevance ranking, typo tolerance, or facet counts
   (showing *how many* results each filter option would produce) become
   real product requirements — things `WP_Query` fundamentally can't do
   well. The `CourseFilter`/`CourseQueryBuilder` abstraction is exactly
   what makes this swappable later: a `SearchEngineCourseQueryBuilder`
   implementing the same intention-revealing methods
   (`addTaxTerms`/`addMetaIn`/`search`) could replace the `WP_Query`-backed
   one without any `CourseFilter` implementation changing, because filters
   depend on the builder's methods, not on `WP_Query` directly.
3. **Read replicas / connection offload** once a single MySQL primary is
   the bottleneck — standard WordPress horizontal scaling, orthogonal to
   this plugin's design.
