import { Controller } from '@hotwired/stimulus';
import { renderStreamMessage } from '@hotwired/turbo';

export default class extends Controller {
    static targets = ['stream'];

    static values = {
        url: String,
    };

    connect() {
        this.eventSource = new EventSource(this.urlValue);

        this.eventSource.onmessage = async (event) => {
            let data;

            try {
                data = JSON.parse(event.data);
            } catch (error) {
                console.error('Invalid Mercure event:', error);
                return;
            }

            const target = this.streamTargets.find(
                target => target.id === data.target
            );

            if (!target) {
                return;
            }

            if (!data.url) {
                console.error(
                    `No URL provided for target "${data.target}"`
                );
                return;
            }

            await this.update(target, data.url);
        };

        this.eventSource.onerror = (error) => {
            console.error('Mercure connection error:', error);
        };
    }

    async update(target, url) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'text/vnd.turbo-stream.html',
                },
            });

            if (!response.ok) {
                console.error(
                    `Failed to update "${target.id}":`,
                    response.status
                );

                return;
            }

            const html = await response.text();

            renderStreamMessage(html);
        } catch (error) {
            console.error(
                `Failed to fetch update for "${target.id}":`,
                error
            );
        }
    }

    disconnect() {
        this.eventSource?.close();
    }
}
