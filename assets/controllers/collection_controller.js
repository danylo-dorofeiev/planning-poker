import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['items', 'prototype', 'addButton'];

    static values = {
        index: Number,
        max: {
            type: Number,
            default: 11,
        },
    };

    connect() {
        this.updateAddButton();
    }

    add() {
        if (this.itemsTarget.children.length >= this.maxValue) {
            return;
        }

        const html = this.prototypeTarget.innerHTML.replace(
            /__name__/g,
            this.indexValue
        );

        this.itemsTarget.insertAdjacentHTML('beforeend', html);

        this.indexValue++;

        this.updateAddButton();
    }

    remove(event) {
        event.currentTarget
            .closest('[data-collection-target="item"]')
            .remove();

        this.updateAddButton();
    }

    updateAddButton() {
        this.addButtonTarget.hidden =
            this.itemsTarget.children.length >= this.maxValue;
    }
}
