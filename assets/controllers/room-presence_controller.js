import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['members'];
    static values = {
        heartbeatUrl: String,
        membersUrl: String,
        interval: {
            type: Number,
            default: 10000,
        },
    };

    connect() {
        this.update();
        this.timer = setInterval(() => {
            this.update();
        }, this.intervalValue);
    }

    disconnect() {
        clearInterval(this.timer);
    }

    async update() {
        try {
            await fetch(this.heartbeatUrlValue, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const response = await fetch(this.membersUrlValue, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-store',
            });
            if (!response.ok) {
                throw new Error(`Members request failed: ${response.status}`);
            }
            this.membersTarget.innerHTML = await response.text();
        } catch (error) {
            console.error('Room presence:', error);
        }
    }
}
