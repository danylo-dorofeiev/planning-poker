import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'copyButton', 'checkButton'];

    copy() {
        navigator.clipboard.writeText(this.inputTarget.value);

        this.copyButtonTarget.classList.add('hidden!');
        this.checkButtonTarget.classList.remove('hidden!');

        setTimeout(() => {
            this.copyButtonTarget.classList.remove('hidden!');
            this.checkButtonTarget.classList.add('hidden!');
        }, 2000);
    }
}
