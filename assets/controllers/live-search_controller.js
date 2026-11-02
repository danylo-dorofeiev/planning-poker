import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'item', 'empty'];

    search() {
        const query = this.inputTarget.value.trim().toLowerCase();
        let visibleCount = 0;

        this.itemTargets.forEach((item) => {
            const name = item.dataset.itemName || '';
            const key = item.dataset.itemKey || '';

            const matches = name.includes(query) || key.includes(query);

            item.hidden = !matches;

            if (matches) {
                visibleCount++;
            }
        });

        this.emptyTarget.hidden = visibleCount > 0;
    }

    clear() {
        this.inputTarget.value = '';
        this.search();
        this.inputTarget.focus();
    }
}
