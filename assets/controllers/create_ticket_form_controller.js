import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["createFormButton", "form"];

    open() {
        this.formTarget.style.display = "block"
        this.createFormButtonTarget.style.display = "none"
    }
    close() {
        this.formTarget.style.display = "none"
        this.createFormButtonTarget.style.display = "block"
    }
}
