import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        this.closeOtherDropdowns = (event) => {
            if (event.detail !== this.element) {
                this.close();
            }
        };

        window.addEventListener('dropdown:open', this.closeOtherDropdowns);
    }

    disconnect() {
        window.removeEventListener('dropdown:open', this.closeOtherDropdowns);
    }

    toggle(event) {
        event.stopPropagation();

        const isClosed = this.menuTarget.classList.contains('opacity-0');

        if (isClosed) {
            window.dispatchEvent(
                new CustomEvent('dropdown:open', {
                    detail: this.element
                })
            );
        }

        this.menuTarget.classList.toggle('opacity-0');
        this.menuTarget.classList.toggle('-translate-y-2');
        this.menuTarget.classList.toggle('pointer-events-none');
    }

    close() {
        this.menuTarget.classList.add(
            'opacity-0',
            '-translate-y-2',
            'pointer-events-none'
        );
    }

    clickOutside(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }
}
