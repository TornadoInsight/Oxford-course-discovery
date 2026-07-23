<?php

/**
 * @var \CourseDiscovery\Domain\FilterCriteria $criteria
 * @var \CourseDiscovery\Domain\FilterContext $context
 * @var \CourseDiscovery\Query\SearchResult $result
 * @var list<\CourseDiscovery\Filters\Contract\CourseFilter> $filters
 *
 * Renders a fully functional, keyboard-operable, no-JS-required search form
 * (a plain GET request re-renders this same template server-side) which
 * assets/src/js/app.js then progressively enhances with fetch-based
 * filtering and accessible comboboxes for Locations/Start Dates.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="course-discovery" data-course-discovery-root data-rest-url="<?php echo esc_url(rest_url('course-discovery/v1')); ?>">
    <form class="course-discovery__filters" method="get" action="" aria-label="<?php esc_attr_e('Course filters', 'course-discovery'); ?>">
        <div class="course-discovery__field">
            <label for="cd-search"><?php esc_html_e('Search courses', 'course-discovery'); ?></label>
            <input
                type="search"
                id="cd-search"
                name="search"
                value="<?php echo esc_attr($criteria->search() ?? ''); ?>"
                placeholder="<?php esc_attr_e('Search by name or description…', 'course-discovery'); ?>"
            />
        </div>

        <?php foreach ($filters as $filter): ?>
            <?php
            if ($filter->key() === 'search') {
                continue;
            }
            $options = $filter->options($context);
            $selected = $criteria->valuesFor($filter->key());
            $fieldId = 'cd-filter-' . $filter->key();
            $isCombobox = in_array($filter->key(), ['locations', 'start_dates'], true);
            ?>
            <fieldset class="course-discovery__field course-discovery__field--<?php echo esc_attr($filter->key()); ?>"
                <?php echo $isCombobox ? 'data-combobox' : ''; ?>
                data-filter-key="<?php echo esc_attr($filter->key()); ?>"
            >
                <legend><?php echo esc_html($filter->label()); ?></legend>

                <?php if ($isCombobox): ?>
                    <select
                        id="<?php echo esc_attr($fieldId); ?>"
                        name="filters[<?php echo esc_attr($filter->key()); ?>][]"
                        multiple
                        size="6"
                        aria-label="<?php echo esc_attr($filter->label()); ?>"
                    >
                        <?php foreach ($options as $option): ?>
                            <option value="<?php echo esc_attr($option->value); ?>" <?php selected(in_array($option->value, $selected, true)); ?>>
                                <?php echo esc_html($option->label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="course-discovery__checkboxes">
                        <?php foreach ($options as $option): ?>
                            <label class="course-discovery__checkbox">
                                <input
                                    type="checkbox"
                                    name="filters[<?php echo esc_attr($filter->key()); ?>][]"
                                    value="<?php echo esc_attr($option->value); ?>"
                                    <?php checked(in_array($option->value, $selected, true)); ?>
                                />
                                <?php echo esc_html($option->label); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </fieldset>
        <?php endforeach; ?>

        <div class="course-discovery__field course-discovery__field--actions">
            <button type="submit"><?php esc_html_e('Apply filters', 'course-discovery'); ?></button>
            <a href="<?php echo esc_url(remove_query_arg(['search', 'filters', 'paged'])); ?>">
                <?php esc_html_e('Clear all', 'course-discovery'); ?>
            </a>
        </div>
    </form>

    <p class="course-discovery__meta" data-course-discovery-count aria-live="polite">
        <?php
        printf(
            /* translators: %d: number of courses found */
            esc_html(_n('%d course found', '%d courses found', $result->total, 'course-discovery')),
            (int) $result->total
        );
        ?>
    </p>

    <ul class="course-discovery__results" data-course-discovery-results>
        <?php foreach ($result->courses as $course): ?>
            <li class="course-discovery__card">
                <article aria-labelledby="cd-course-<?php echo esc_attr((string) $course->id); ?>">
                    <h3 id="cd-course-<?php echo esc_attr((string) $course->id); ?>">
                        <a href="<?php echo esc_url($course->permalink); ?>"><?php echo esc_html($course->name); ?></a>
                    </h3>
                    <p class="course-discovery__excerpt"><?php echo esc_html($course->shortDescription); ?></p>
                    <dl class="course-discovery__facts">
                        <?php if ($course->price !== null): ?>
                            <div><dt><?php esc_html_e('Price', 'course-discovery'); ?></dt><dd><?php echo esc_html($course->price->format()); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($course->providers !== []): ?>
                            <div><dt><?php esc_html_e('Provider', 'course-discovery'); ?></dt><dd><?php echo esc_html(implode(', ', array_map(static fn ($p) => $p->name, $course->providers))); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($course->locations !== []): ?>
                            <div><dt><?php esc_html_e('Location', 'course-discovery'); ?></dt><dd><?php echo esc_html(implode(', ', array_map(static fn ($l) => $l->name, $course->locations))); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($course->upcomingStartDates() !== []): ?>
                            <div><dt><?php esc_html_e('Start dates', 'course-discovery'); ?></dt><dd><?php echo esc_html(implode(', ', array_map(static fn ($d) => $d->label(), $course->upcomingStartDates()))); ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </article>
            </li>
        <?php endforeach; ?>
        <?php if ($result->courses === []): ?>
            <li class="course-discovery__empty"><?php esc_html_e('No courses match your filters.', 'course-discovery'); ?></li>
        <?php endif; ?>
    </ul>

    <?php if ($result->totalPages() > 1): ?>
        <nav class="course-discovery__pagination" aria-label="<?php esc_attr_e('Course results pages', 'course-discovery'); ?>">
            <?php for ($page = 1; $page <= $result->totalPages(); $page++): ?>
                <a
                    href="<?php echo esc_url(add_query_arg('paged', $page)); ?>"
                    aria-current="<?php echo $page === $result->page ? 'page' : 'false'; ?>"
                ><?php echo esc_html((string) $page); ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
</div>
