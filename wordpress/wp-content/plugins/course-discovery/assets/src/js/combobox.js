/**
 * Progressively enhances a `<select multiple>` into a button-triggered,
 * multi-selectable listbox popup — the standard accessible pattern for a
 * "multi-select dropdown" (the ARIA APG combobox pattern only defines a
 * single-select text-input variant, so this follows the widely used
 * button+listbox convention instead: trigger button with
 * aria-haspopup="listbox", popup with role="listbox"
 * aria-multiselectable="true").
 *
 * The original <select> is kept in the DOM (visually hidden, not disabled)
 * as the source of truth and as a no-JS-compatible fallback if this script
 * fails to load or run.
 */
export class MultiCombobox {
  constructor(selectEl) {
    this.select = selectEl;
    this.select.classList.add('cd-combobox__native');

    this.options = Array.from(selectEl.options).map((option) => ({
      value: option.value,
      label: option.textContent,
      selected: option.selected,
    }));

    this.activeIndex = 0;
    this.isOpen = false;

    this.buildMarkup();
    this.bindEvents();
    this.updateTriggerLabel();
  }

  buildMarkup() {
    const label = this.select.getAttribute('aria-label') || this.select.name;
    const id = this.select.id || `cd-combobox-${Math.random().toString(36).slice(2)}`;

    this.wrapper = document.createElement('div');
    this.wrapper.className = 'cd-combobox';

    this.trigger = document.createElement('button');
    this.trigger.type = 'button';
    this.trigger.className = 'cd-combobox__trigger';
    this.trigger.setAttribute('aria-haspopup', 'listbox');
    this.trigger.setAttribute('aria-expanded', 'false');
    this.trigger.id = `${id}-trigger`;

    this.popup = document.createElement('div');
    this.popup.className = 'cd-combobox__popup';
    this.popup.setAttribute('role', 'listbox');
    this.popup.setAttribute('aria-multiselectable', 'true');
    this.popup.setAttribute('aria-label', label);
    this.popup.hidden = true;

    this.optionEls = this.options.map((option, index) => {
      const el = document.createElement('div');
      el.className = 'cd-combobox__option';
      el.setAttribute('role', 'option');
      el.dataset.index = String(index);
      el.tabIndex = -1;
      el.textContent = option.label;
      el.setAttribute('aria-selected', option.selected ? 'true' : 'false');
      if (option.selected) {
        el.classList.add('is-selected');
      }
      this.popup.appendChild(el);
      return el;
    });

    this.select.parentElement.insertBefore(this.wrapper, this.select);
    this.wrapper.appendChild(this.trigger);
    this.wrapper.appendChild(this.popup);
    this.wrapper.appendChild(this.select);
  }

  bindEvents() {
    this.trigger.addEventListener('click', () => this.toggle());
    this.trigger.addEventListener('keydown', (event) => this.onTriggerKeydown(event));
    this.popup.addEventListener('keydown', (event) => this.onPopupKeydown(event));

    this.optionEls.forEach((el, index) => {
      el.addEventListener('click', () => {
        this.toggleSelection(index);
        this.focusOption(index);
      });
    });

    document.addEventListener('click', (event) => {
      if (this.isOpen && !this.wrapper.contains(event.target)) {
        this.close();
      }
    });
  }

  onTriggerKeydown(event) {
    if (['ArrowDown', 'Enter', ' '].includes(event.key)) {
      event.preventDefault();
      this.open();
      this.focusOption(this.firstSelectedIndex());
    }
  }

  onPopupKeydown(event) {
    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        this.focusOption(Math.min(this.activeIndex + 1, this.optionEls.length - 1));
        break;
      case 'ArrowUp':
        event.preventDefault();
        this.focusOption(Math.max(this.activeIndex - 1, 0));
        break;
      case 'Home':
        event.preventDefault();
        this.focusOption(0);
        break;
      case 'End':
        event.preventDefault();
        this.focusOption(this.optionEls.length - 1);
        break;
      case ' ':
      case 'Enter':
        event.preventDefault();
        this.toggleSelection(this.activeIndex);
        break;
      case 'Escape':
        event.preventDefault();
        this.close();
        this.trigger.focus();
        break;
      case 'Tab':
        this.close();
        break;
      default:
        if (event.key.length === 1) {
          this.typeahead(event.key);
        }
    }
  }

  typeahead(char) {
    const start = (this.activeIndex + 1) % this.optionEls.length;
    const ordered = [...this.optionEls.slice(start), ...this.optionEls.slice(0, start)];
    const match = ordered.find((el) => el.textContent.toLowerCase().startsWith(char.toLowerCase()));
    if (match) {
      this.focusOption(Number(match.dataset.index));
    }
  }

  firstSelectedIndex() {
    const index = this.options.findIndex((option) => option.selected);
    return index === -1 ? 0 : index;
  }

  focusOption(index) {
    this.activeIndex = index;
    this.optionEls.forEach((el, i) => {
      el.tabIndex = i === index ? 0 : -1;
      el.classList.toggle('is-active', i === index);
    });
    this.optionEls[index].focus();
    this.popup.setAttribute('aria-activedescendant', this.optionEls[index].id || '');
  }

  toggleSelection(index) {
    const option = this.options[index];
    option.selected = !option.selected;
    this.select.options[index].selected = option.selected;
    this.optionEls[index].setAttribute('aria-selected', option.selected ? 'true' : 'false');
    this.optionEls[index].classList.toggle('is-selected', option.selected);
    this.updateTriggerLabel();
    this.select.dispatchEvent(new Event('change', { bubbles: true }));
  }

  updateTriggerLabel() {
    const label = this.select.getAttribute('aria-label') || this.select.name;
    const count = this.options.filter((option) => option.selected).length;
    this.trigger.textContent = count > 0 ? `${label} (${count})` : label;
  }

  open() {
    this.isOpen = true;
    this.popup.hidden = false;
    this.trigger.setAttribute('aria-expanded', 'true');
  }

  close() {
    this.isOpen = false;
    this.popup.hidden = true;
    this.trigger.setAttribute('aria-expanded', 'false');
  }

  toggle() {
    if (this.isOpen) {
      this.close();
    } else {
      this.open();
      this.focusOption(this.firstSelectedIndex());
    }
  }
}

export function enhanceCombobox(selectEl) {
  return new MultiCombobox(selectEl);
}
