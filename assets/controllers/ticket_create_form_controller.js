import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["form"];

    toggle() {
        this.formTarget.classList.toggle('max-h-0')
        this.formTarget.classList.toggle('max-h-100')
    }

    async submit(event) {
        event.preventDefault();

        const response = await fetch(this.element.action, {
            method: 'POST',
            body: new FormData(this.element)
        });

        if (response.ok) {
            this.element.reset();
        }
    }
}
