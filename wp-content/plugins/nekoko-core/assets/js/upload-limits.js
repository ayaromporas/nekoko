document.addEventListener('DOMContentLoaded', () => {
    const config = window.nekokoUploadLimits;
    if (!config) return;
    document.querySelectorAll('input[name="listing_images[]"],input[name="nekoko_profile_images[]"]').forEach(input => {
        const help = document.createElement('p');
        help.className = 'nekoko-form__help';
        help.textContent = 'Najveća dozvoljena veličina je 4 MB po slici. Dozvoljeni formati: JPG, PNG i WebP.';
        const error = document.createElement('p');
        error.setAttribute('role', 'alert');
        error.style.color = '#a93633';
        input.closest('.nekoko-file-input').after(help, error);
        function validate() {
            const files = Array.from(input.files || []);
            if (files.some(file => file.size > config.maxBytes)) {
                return 'Slika je veća od dozvoljene veličine fajla (4 MB). Pokušajte ponovo sa manjom slikom.';
            }
            if (config.maxFiles && files.length > config.maxFiles) return 'Izabrali ste previše slika. Pokušajte ponovo sa manje slika.';
            // Reserve space for text fields and multipart headers as well as the image data.
            if (config.postBytes && files.reduce((sum, file) => sum + file.size, 0) + 262144 > config.postBytes) {
                return 'Ukupna veličina izabranih slika je prevelika za jedno slanje. Pokušajte ponovo sa manjim slikama ili ih dodajte u više navrata.';
            }
            return '';
        }
        input.addEventListener('change', () => {
            error.textContent = validate();
            if (error.textContent) input.value = '';
        }, true);
        input.form.addEventListener('submit', event => {
            const message = validate();
            if (message) {
                event.preventDefault();
                error.textContent = message;
                error.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        });
    });
});
