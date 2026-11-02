import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];

    connect() {
        this.closeOtherElements = (event) => {
            if (event.detail !== this.element) {
                this.close();
            }
        };

        this.handleOutsideClick = (event) => {
            if (!this.element.contains(event.target)) {
                this.close();
            }
        };

        window.addEventListener('ui:open', this.closeOtherElements);
        document.addEventListener('click', this.handleOutsideClick);
    }

    disconnect() {
        window.removeEventListener('ui:open', this.closeOtherElements);
        document.removeEventListener('click', this.handleOutsideClick);
    }

    toggle(event) {
        event.stopPropagation();

        const isClosed = this.menuTarget.classList.contains('opacity-0');

        if (isClosed) {
            window.dispatchEvent(
                new CustomEvent('ui:open', {
                    detail: this.element,
                })
            );

            this.menuTarget.classList.remove(
                'opacity-0',
                '-translate-y-2',
                'pointer-events-none'
            );
        } else {
            this.close();
        }
    }

    close() {
        this.menuTarget.classList.add(
            'opacity-0',
            '-translate-y-2',
            'pointer-events-none'
        );
    }
}
