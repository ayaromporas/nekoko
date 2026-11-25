(function () {
    'use strict';
    var button = document.getElementById('nekoko-convert-subscribers');
    var status = document.getElementById('nekoko-role-conversion-status');
    var config = window.nekokoMemberRoleTools;
    if (!button || !status || !config) return;
    button.addEventListener('click', async function () {
        if (button.disabled) return;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        var cursor = 0, checked = 0, changed = 0;
        status.textContent = 'Provera korisnika je u toku…';
        try {
            var done = false;
            while (!done) {
                var response = await fetch(config.url, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body: new URLSearchParams({action: 'nekoko_convert_subscribers', nonce: config.nonce, cursor: cursor}).toString()});
                if (!response.ok) throw new Error('Zahtev nije uspeo. Osvežite stranicu i pokušajte ponovo.');
                var result = await response.json();
                if (!result.success) throw new Error(result.data && result.data.message || 'Promena uloga nije uspela.');
                if (!result.data.done && result.data.cursor <= cursor) throw new Error('Provera je zaustavljena. Pokušajte ponovo.');
                cursor = result.data.cursor;
                checked += result.data.checked;
                changed += result.data.changed;
                done = result.data.done;
                status.textContent = (done ? 'Provera završena. ' : 'Provera je u toku… ') + 'Provereno: ' + checked + '. Promenjeno iz Pretplatnik u Oglašivač: ' + changed + '.';
            }
        } catch (error) {
            status.textContent = error.message + ' Do sada provereno: ' + checked + ', promenjeno: ' + changed + '. Ponovno pokretanje je bezbedno.';
        } finally {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    });
}());
