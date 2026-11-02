import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['openIcon', 'closeIcon'];

    connect() {
        if (localStorage.getItem('sidebar-closed') === 'true') {
            this.close();
        }
    }

    close() {
        const sidebar = document.getElementById('sidebar');

        sidebar.classList.replace('max-w-85', 'max-w-0');
        sidebar.classList.replace(
            'border-secondary-gray-700',
            'border-transparent'
        );

        this.openIconTarget.classList.remove('hidden');
        this.closeIconTarget.classList.add('hidden');

        localStorage.setItem('sidebar-closed', 'true');
    }

    open() {
        const sidebar = document.getElementById('sidebar');

        sidebar.classList.replace('max-w-0', 'max-w-85');
        sidebar.classList.replace(
            'border-transparent',
            'border-secondary-gray-700'
        );

        this.openIconTarget.classList.add('hidden');
        this.closeIconTarget.classList.remove('hidden');

        localStorage.setItem('sidebar-closed', 'false');
    }
}
