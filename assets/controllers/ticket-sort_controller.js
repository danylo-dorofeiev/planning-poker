import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.observer = new MutationObserver(() => this.sort());

        this.observer.observe(this.element, {
            childList: true
        });

        this.sort();
    }

    disconnect() {
        this.observer.disconnect();
    }

    sort() {
        const tickets = [...this.element.children]
            .filter(ticket => ticket.hasAttribute('data-updated-at'));

        const sortedTickets = [...tickets].sort((a, b) => {
            return Number(b.dataset.updatedAt) - Number(a.dataset.updatedAt);
        });

        sortedTickets.forEach((ticket, index) => {
            if (tickets[index] !== ticket) {
                this.element.appendChild(ticket);
            }
        });
    }
}
