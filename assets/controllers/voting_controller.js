import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        voteUrl: String
    };
    async vote(event) {
        const cardId = event.currentTarget.dataset.cardId;
        const response = await fetch(this.voteUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                card: cardId
            })
        });
        if (!response.ok) {
            console.error('Vote failed');
            return;
        }
        console.log('Vote saved:', cardId);
    }
}
