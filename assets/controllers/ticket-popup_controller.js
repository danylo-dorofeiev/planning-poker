import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static targets = ['modal', 'content'];
    static values = {
        showUrl: String,
    };
    async open() {
        this.modalTarget.style.display = 'block';
        this.contentTarget.textContent = 'Loading...';
        try {
            const response = await fetch(this.showUrlValue, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }
            this.contentTarget.innerHTML = await response.text();
        } catch (error) {
            console.error(error);
            this.contentTarget.textContent = 'Failed to load ticket.';
        }
    }
    close() {
        this.modalTarget.style.display = 'none';
        this.contentTarget.innerHTML = '';
    }
    closeOnOverlay(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
    }
}
