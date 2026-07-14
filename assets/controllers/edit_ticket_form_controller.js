import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["form"];

    async open(event) {
        const url = event.currentTarget.dataset.url;

        const response = await fetch(url);

        const html = await response.text();

        this.formTarget.innerHTML = html;
        this.formTarget.style.display = "block";
    }

    close() {
        this.formTarget.innerHTML = '';
        this.formTarget.style.display = "none";
    }
}
