import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["form", "target", "error"];

    open() {
        this.formTarget.style.display = "block"
        this.targetTarget.style.display = "none"
    }
    close() {
        this.formTarget.style.display = "none"
        this.targetTarget.style.display = "block"
    }
    async fetch(event) {
        const url = event.currentTarget.dataset.url;
        const response = await fetch(url);
        const html = await response.text();

        this.formTarget.innerHTML = html;
        this.formTarget.style.display = "block";
    }
    validate(event) {
        const cards = this.element.querySelectorAll('.card-item');
        if (cards.length < 2) {
            event.preventDefault();
            alert('A deck must contain at least 2 cards.')
        }
    }
}
