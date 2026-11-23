(() => {
    const scrollKey = `cybermart-scroll:${window.location.pathname}`;

    const readSavedPosition = () => {
        try {
            const rawPosition = window.sessionStorage.getItem(scrollKey);
            window.sessionStorage.removeItem(scrollKey);

            if (rawPosition === null) {
                return null;
            }

            const position = Number.parseInt(rawPosition, 10);
            return Number.isFinite(position) && position >= 0 ? position : null;
        } catch (error) {
            return null;
        }
    };

    const savedPosition = readSavedPosition();
    if (savedPosition !== null) {
        const restoreScroll = () => window.scrollTo(0, savedPosition);
        requestAnimationFrame(() => requestAnimationFrame(restoreScroll));
        window.addEventListener('load', restoreScroll, { once: true });
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.preserveScroll === 'false') {
            return;
        }

        try {
            window.sessionStorage.setItem(scrollKey, String(window.scrollY));
        } catch (error) {
            // Forms still work if the browser has disabled session storage.
        }
    }, true);

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.copy-payload');
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        const payload = button.dataset.copy ?? '';
        try {
            await navigator.clipboard.writeText(payload);
            const original = button.textContent;
            button.textContent = 'Copied';
            window.setTimeout(() => { button.textContent = original; }, 1200);
        } catch (error) {
            button.textContent = 'Select code';
        }
    });
})();
