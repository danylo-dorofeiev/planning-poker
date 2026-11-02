import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container'];

    show(event) {
        const { html, duration = 5000 } = event.detail;

        if (!html) return;

        const template = document.createElement('template');
        template.innerHTML = html.trim();

        const notification = document.createElement('div');
        notification.classList.add('notification');

        notification.append(template.content.cloneNode(true));

        this.containerTarget.appendChild(notification);

        if (duration > 0) {
            setTimeout(() => {
                setTimeout(() => {
                    notification.classList.add('notification-exit');

                    notification.addEventListener('animationend', () => {
                        notification.remove();
                    }, { once: true });
                }, duration / 2);
            }, duration / 2);
        }
    }

    close(event) {
        event.currentTarget.closest('.notification')?.remove();
    }

    clear() {
        this.containerTarget.innerHTML = '';
    }
}
