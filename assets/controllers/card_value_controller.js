import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    validate() {
        const value = this.element.value;

        const segments = [
            ...new Intl.Segmenter(undefined, {
                granularity: 'grapheme'
            }).segment(value)
        ].map(item => item.segment);

        const containsEmoji = /\p{Extended_Pictographic}/u.test(value);

        // 1 emoji
        if (containsEmoji) {
            this.element.value = segments.length === 1
                ? value
                : '';

            return;
        }

        // 4 symbols
        if (segments.length > 4) {
            this.element.value = segments.slice(0, 4).join('');
        }
    }
}
