import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["items"];
    static values = {
        prototype: String,
        index: Number
    };

    add() {
        const html = this.prototypeValue.replace(
            /__name__/g,
            this.indexValue
        );
        this.itemsTarget.insertAdjacentHTML(
            'beforeend',
            `<div class="card-item">
${html}
<button type="button" data-action="collection#remove">Delete</button>
</div>`);
        this.indexValue++;
    }

    remove(event) {
        event.target.closest('.card-item').remove();
    }
}
