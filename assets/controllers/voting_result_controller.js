import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'toggleButtonOpen',
        'toggleButtonClose',
        'inputBlock',
        'input',
        'timeBasedSelect',
    ];

    static values = {
        url: String,
        timeBasedUrl: String,
        deleteUrl: String,
        votingResult: String,
    };

    connect() {
        this.closeOtherElements = (event) => {
            if (event.detail !== this.element) {
                this.close();
            }
        };

        window.addEventListener('ui:open', this.closeOtherElements);
    }

    disconnect() {
        window.removeEventListener('ui:open', this.closeOtherElements);
    }

    showNotification(html, duration = 5000) {
        if (!html) return;

        window.dispatchEvent(
            new CustomEvent('notification:show', {
                detail: {
                    html,
                    duration,
                },
            })
        );
    }

    inputBlockToggle() {
        const isClosed = this.inputBlockTarget.classList.contains('opacity-0');

        if (isClosed) {
            window.dispatchEvent(
                new CustomEvent('ui:open', {
                    detail: this.element,
                })
            );

            this.inputBlockTarget.classList.remove(
                'opacity-0',
                'translate-x-0',
                'pointer-events-none'
            );

            this.inputBlockTarget.classList.add('-translate-x-2');
        } else {
            this.close();
        }
    }

    close() {
        this.inputBlockTargets.forEach((block) => {
            block.classList.remove('-translate-x-2');

            block.classList.add(
                'opacity-0',
                'translate-x-0',
                'pointer-events-none'
            );
        });

        this.inputTarget.value = this.votingResultValue;
        this.inputTarget.setCustomValidity('');
    }

    validateInput() {
        const result = this.inputTarget.value.trim();

        if (
            this.timeBasedSelectTarget.checked &&
            !/^\d+$/.test(result)
        ) {
            this.inputTarget.setCustomValidity(
                'Bitte eine Zahl eingeben.'
            );
        } else {
            this.inputTarget.setCustomValidity('');
        }

        this.inputTarget.reportValidity();
    }

    async save(event) {
        event.preventDefault();

        this.validateInput();

        if (!this.inputTarget.checkValidity()) {
            return;
        }

        const result = this.inputTarget.value.trim();

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({
                    'voting-result': result,
                }),
            });

            const data = await response.json();

            if (data.notification) {
                this.showNotification(data.notification);
                return;
            }

            if (!response.ok) {
                throw new Error(
                    data.error ?? 'Fehler beim Speichern.'
                );
            }

            this.inputTarget.value = data.votingResult;

        } catch (error) {
            console.error(error);
        }
    }

    async updateTimeBased() {
        const checkbox = this.timeBasedSelectTarget;
        const previousValue = checkbox.dataset.previousValue === '1';
        const isTimeBased = checkbox.checked;

        if (this.votingResultValue.trim() !== '') {
            const confirmed = window.confirm(
                'Changing the estimation type will delete the current voting result. Do you want to continue?'
            );

            if (!confirmed) {
                checkbox.checked = previousValue;
                return;
            }
        }

        try {
            const response = await fetch(this.timeBasedUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({
                    isTimeBased: isTimeBased ? '1' : '0',
                }),
            });

            const data = await response.json();

            if (data.notification) {
                checkbox.checked = previousValue;
                this.showNotification(data.notification);
                return;
            }

            if (!response.ok) {
                throw new Error(
                    data.error ?? 'Fehler beim Speichern.'
                );
            }

            checkbox.dataset.previousValue = isTimeBased ? '1' : '0';

            if (this.votingResultValue.trim() !== '') {
                this.votingResultValue = '';
                this.inputTarget.value = '';
            }

        } catch (error) {
            checkbox.checked = previousValue;
            console.error(error);
        }
    }

    reset() {
        this.inputTarget.value = this.votingResultValue;
        this.inputTarget.setCustomValidity('');
    }

    async deleteVotingResult() {
        if (!this.votingResultValue.trim()) return;

        const confirmed = window.confirm(
            'Do you really want to delete the voting result?'
        );

        if (!confirmed) return;

        try {
            const response = await fetch(this.deleteUrlValue, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (data.notification) {
                this.showNotification(data.notification);
                return;
            }

            if (!response.ok) {
                throw new Error(data.error ?? 'Fehler beim Löschen.');
            }

            this.votingResultValue = '';
            this.inputTarget.value = '';

        } catch (error) {
            console.error(error);
        }
    }
}
