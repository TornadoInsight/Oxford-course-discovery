import { enhanceCombobox } from './combobox.js';

/**
 * Progressive enhancement layer: the page already works via plain GET form
 * submission (see templates/course-discovery.php, rendered server-side by
 * CourseDiscoveryShortcode). This script intercepts that same form and
 * fetches CourseDiscoveryConfig.restUrl instead, re-rendering results in
 * place and pushing shareable URL state — no behaviour is *only* available
 * via JS.
 */

function debounce(fn, delayMs) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delayMs);
  };
}

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value ?? '';
  return div.innerHTML;
}

function courseCardHtml(course) {
  const facts = [];
  if (course.price) {
    facts.push(`<div><dt>Price</dt><dd>${escapeHtml(course.price.formatted)}</dd></div>`);
  }
  if (course.providers.length) {
    facts.push(`<div><dt>Provider</dt><dd>${escapeHtml(course.providers.map((p) => p.name).join(', '))}</dd></div>`);
  }
  if (course.locations.length) {
    facts.push(`<div><dt>Location</dt><dd>${escapeHtml(course.locations.map((l) => l.name).join(', '))}</dd></div>`);
  }
  if (course.startDates.length) {
    facts.push(`<div><dt>Start dates</dt><dd>${escapeHtml(course.startDates.map((d) => d.label).join(', '))}</dd></div>`);
  }

  return `
    <li class="course-discovery__card">
      <article aria-labelledby="cd-course-${course.id}">
        <h3 id="cd-course-${course.id}"><a href="${escapeHtml(course.permalink)}">${escapeHtml(course.name)}</a></h3>
        <p class="course-discovery__excerpt">${escapeHtml(course.shortDescription)}</p>
        <dl class="course-discovery__facts">${facts.join('')}</dl>
      </article>
    </li>
  `;
}

class CourseDiscoveryApp {
  constructor(root) {
    this.root = root;
    this.restUrl = window.CourseDiscoveryConfig?.restUrl ?? root.dataset.restUrl;
    this.form = root.querySelector('.course-discovery__filters');
    this.resultsEl = root.querySelector('[data-course-discovery-results]');
    this.countEl = root.querySelector('[data-course-discovery-count]');
    this.paginationEl = root.querySelector('.course-discovery__pagination');
    this.page = 1;

    this.enhanceCombobox();
    this.bindForm();
    this.bindPagination();
  }

  enhanceCombobox() {
    this.root.querySelectorAll('fieldset[data-combobox] select[multiple]').forEach((select) => {
      enhanceCombobox(select);
    });
  }

  bindForm() {
    if (!this.form) {
      return;
    }

    this.form.addEventListener('submit', (event) => {
      event.preventDefault();
      this.page = 1;
      this.search();
    });

    const searchInput = this.form.querySelector('input[type="search"]');
    if (searchInput) {
      searchInput.addEventListener(
        'input',
        debounce(() => {
          this.page = 1;
          this.search();
        }, 350)
      );
    }

    this.form.querySelectorAll('input[type="checkbox"], select').forEach((el) => {
      el.addEventListener('change', () => {
        this.page = 1;
        this.search();
      });
    });
  }

  bindPagination() {
    this.root.addEventListener('click', (event) => {
      const link = event.target.closest('.course-discovery__pagination a');
      if (!link) {
        return;
      }
      event.preventDefault();
      const url = new URL(link.href);
      this.page = Number(url.searchParams.get('paged') || 1);
      this.search();
    });
  }

  buildParams() {
    const params = new URLSearchParams();
    const formData = new FormData(this.form);

    const search = formData.get('search');
    if (search) {
      params.set('search', String(search));
    }

    for (const [key, value] of formData.entries()) {
      const match = key.match(/^filters\[(.+)\]\[\]$/);
      if (match) {
        params.append(`filters[${match[1]}][]`, String(value));
      }
    }

    params.set('page', String(this.page));

    return params;
  }

  /**
   * Appends a sub-path to the REST base URL correctly whether permalinks
   * are pretty (restUrl is a clean path, e.g. /wp-json/course-discovery/v1)
   * or plain (restUrl is index.php?rest_route=/course-discovery/v1, so the
   * sub-path has to extend the rest_route param instead of the URL path).
   */
  buildEndpoint(path, params) {
    const url = new URL(this.restUrl, window.location.origin);

    if (url.searchParams.has('rest_route')) {
      url.searchParams.set('rest_route', url.searchParams.get('rest_route') + path);
    } else {
      url.pathname = url.pathname.replace(/\/?$/, '') + path;
    }

    for (const [key, value] of params.entries()) {
      url.searchParams.append(key, value);
    }

    return url.toString();
  }

  async search() {
    if (!this.restUrl) {
      return;
    }

    const params = this.buildParams();
    this.root.setAttribute('aria-busy', 'true');

    try {
      const response = await fetch(this.buildEndpoint('/courses', params), {
        headers: { Accept: 'application/json' },
      });
      const data = await response.json();
      this.render(data);
      this.updateUrl(params);
    } catch (error) {
      // Network/JS failure: the form's native GET submission (already
      // prevented above) is the fallback; let the user retry via submit.
      console.error('Course Discovery: search failed', error); // eslint-disable-line no-console
    } finally {
      this.root.removeAttribute('aria-busy');
    }
  }

  render(data) {
    if (this.countEl) {
      const noun = data.total === 1 ? 'course' : 'courses';
      this.countEl.textContent = `${data.total} ${noun} found`;
    }

    if (this.resultsEl) {
      this.resultsEl.innerHTML = data.courses.length
        ? data.courses.map(courseCardHtml).join('')
        : '<li class="course-discovery__empty">No courses match your filters.</li>';
    }

    if (this.paginationEl) {
      this.paginationEl.innerHTML = Array.from({ length: data.totalPages }, (_, i) => i + 1)
        .map(
          (page) =>
            `<a href="?paged=${page}" aria-current="${page === data.page ? 'page' : 'false'}">${page}</a>`
        )
        .join('');
    }
  }

  updateUrl(params) {
    const url = new URL(window.location.href);
    url.search = params.toString();
    window.history.pushState({}, '', url);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-course-discovery-root]').forEach((root) => new CourseDiscoveryApp(root));
});
