import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        voteUrl: String
    };

    async submit(event) {
        const clickedButton = event.currentTarget;
        const value = clickedButton.dataset.value;

        if (clickedButton.classList.contains('active')) {
            return;
        }

        this.element.querySelectorAll('.active').forEach(button => {
            button.classList.remove('active');
        });

        clickedButton.classList.add('active');

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
