import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        interval: { type: Number, default: 10000 },
    };

    connect() {
        console.log('Heartbeat controller connected');

        this.isSending = false;
        this.sendHeartbeat();

        this.intervalId = setInterval(() => {
            this.sendHeartbeat();
        }, this.intervalValue);
    }

    disconnect() {
        clearInterval(this.intervalId);
    }

    async sendHeartbeat() {
        if (this.isSending) return;

        this.isSending = true;

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                console.error('Heartbeat failed:', response.status);
            }
        } catch (error) {
            console.error('Heartbeat failed:', error);
        } finally {
            this.isSending = false;
        }
    }
}
