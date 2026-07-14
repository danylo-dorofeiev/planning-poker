import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["form", "target"];

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
    delete() {
        this.formTarget.innerHTML = '';
        this.formTarget.style.display = "none"
    }
}
