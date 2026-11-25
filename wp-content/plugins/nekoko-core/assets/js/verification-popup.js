document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('nekoko-verification-popup');
    if (!dialog) return;
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
    const address = new URL(window.location.href);
    address.searchParams.delete('nekoko_verify_notice');
    window.history.replaceState(window.history.state, '', address.href);
    dialog.querySelector('[data-verification-dismiss]').addEventListener('click', () => {
        if (typeof dialog.close === 'function') dialog.close();
        else dialog.removeAttribute('open');
    });
});
