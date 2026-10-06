import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['player'];

    connect() {
        this.positionPlayers();

        this.resizeObserver = new ResizeObserver(() => {
            this.positionPlayers();
        });

        this.resizeObserver.observe(this.element);
    }

    disconnect() {
        this.resizeObserver?.disconnect();
    }

    positionPlayers() {
        const width = this.element.clientWidth;
        const height = this.element.clientHeight;
        const players = this.playerTargets;

        if (!players.length) {
            return;
        }

        const radius = height / 2;
        const straight = width - radius * 2;

        const top = straight;
        const bottom = straight;
        const side = Math.PI * radius;
        const perimeter = top + bottom + side * 2;

        players.forEach((player, index) => {
            const distance = (index / players.length) * perimeter;

            let x;
            let y;

            const startOffset = top / 2;
            const position = (distance + startOffset) % perimeter;

            if (position < top) {
                // TOP
                x = radius + position;
                y = 0;
            } else if (position < top + side) {
                // RIGHT
                const d = position - top;
                const angle = -Math.PI / 2 + d / radius;

                x = width - radius + Math.cos(angle) * radius;
                y = radius + Math.sin(angle) * radius;
            } else if (position < top + side + bottom) {
                // BOTTOM
                const d = position - top - side;

                x = width - radius - d;
                y = height;
            } else {
                // LEFT
                const d = position - top - side - bottom;
                const angle = Math.PI / 2 + d / radius;

                x = radius + Math.cos(angle) * radius;
                y = radius + Math.sin(angle) * radius;
            }

            player.style.left = `${x}px`;
            player.style.top = `${y}px`;
            player.style.transform = 'translate(-50%, -50%)';
        });
    }
}
