import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        voteUrl: String
    };
    async submit(event) {
        const value = event.currentTarget.dataset.value;
        await fetch(this.voteUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams({ value }),
        });
    }
}
