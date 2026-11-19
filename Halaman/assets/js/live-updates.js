(function() {
    const intervalMilliseconds = 5000;
    let updateInProgress = false;

    async function updateLiveContent() {
        const currentContent = document.querySelector('[data-live-updates]');
        const activeElement = document.activeElement;
        if (
            updateInProgress
            || document.visibilityState !== 'visible'
            || !currentContent
            || (activeElement && activeElement.closest('form'))
            || document.querySelector('details[open]')
        ) {
            return;
        }

        updateInProgress = true;
        try {
            const response = await fetch(window.location.href, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) {
                throw new Error('Pembaruan data gagal dengan status HTTP ' + response.status + '.');
            }

            const responseUrl = new URL(response.url);
            if (responseUrl.pathname !== window.location.pathname) {
                throw new Error('Sesi berakhir atau halaman dialihkan; silakan masuk kembali.');
            }

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const updatedContent = page.querySelector('[data-live-updates]');
            if (!updatedContent) {
                throw new Error('Konten pembaruan tidak ditemukan pada halaman.');
            }

            if (currentContent.innerHTML !== updatedContent.innerHTML) {
                currentContent.innerHTML = updatedContent.innerHTML;
            }
        } catch (error) {
            console.error('Pembaruan otomatis Servitory gagal:', error);
        } finally {
            updateInProgress = false;
        }
    }

    window.setInterval(updateLiveContent, intervalMilliseconds);
})();
