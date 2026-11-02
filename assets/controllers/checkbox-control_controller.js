import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    toggleAll(event) {
        const checkboxes = this.element.querySelectorAll(
            'input[type="checkbox"]:not([data-action*="toggleAll"])'
        );

        checkboxes.forEach(checkbox => {
            checkbox.checked = event.currentTarget.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
}
