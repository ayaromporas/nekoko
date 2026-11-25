/* Image edits stay local until the existing, authenticated form is submitted. */
document.addEventListener('DOMContentLoaded', () => {
    if (!window.DataTransfer || !window.HTMLDialogElement) return;
    document.querySelectorAll('input[name="listing_images[]"], input[name="nekoko_profile_images[]"]').forEach(input => {
        const gallery = document.createElement('div');
        gallery.className = 'nekoko-crop-previews';
        input.closest('.nekoko-file-input').after(gallery);
        const notice = document.createElement('p');
        notice.className = 'nekoko-form__help';
        notice.setAttribute('role', 'status');
        gallery.after(notice);
        let originals = [], files = [], urls = [];
        let internalChange = false;
        function sync() {
            const transfer = new DataTransfer();
            files.forEach(file => transfer.items.add(file));
            input.files = transfer.files;
            internalChange = true;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            internalChange = false;
            render();
        }
        function render() {
            urls.forEach(url => URL.revokeObjectURL(url));
            urls = [];
            gallery.replaceChildren();
            files.forEach((file, index) => {
                const item = document.createElement('figure');
                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                urls.push(image.src);
                image.alt = file.name;
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = 'Iseci';
                button.setAttribute('aria-label', 'Iseci fotografiju ' + (index + 1));
                button.onclick = () => edit(index, button);
                item.append(image, button);
                gallery.append(item);
            });
        }
        input.addEventListener('change', () => {
            if (internalChange) return;
            originals = Array.from(input.files || []);
            files = originals.slice();
            notice.textContent = files.length ? 'Kliknite „Iseci“ ispod fotografije da podesite kadar pre čuvanja.' : '';
            render();
        });
        async function edit(index, trigger) {
            const dialog = document.createElement('dialog');
            dialog.className = 'nekoko-crop';
            dialog.setAttribute('aria-labelledby', 'nekoko-crop-title');
            dialog.innerHTML = `<h2 id="nekoko-crop-title">Isecite fotografiju</h2>
                <p>Pomerite sliku prstom ili mišem. Zum i položaj možete podesiti i klizačima.</p>
                <label>Format <select data-ratio><option value="1">Kvadrat (1:1)</option><option value="1.5">Vodoravno (3:2)</option><option value="0.75">Uspravno (3:4)</option><option value="original">Izvorni format</option></select></label>
                <canvas aria-label="Pregled kadra fotografije"></canvas>
                <label>Zum <input data-zoom type="range" min="1" max="4" step="0.01" value="1"></label>
                <label>Levo / desno <input data-x type="range" min="0" max="1" step="0.001" value="0.5"></label>
                <label>Gore / dole <input data-y type="range" min="0" max="1" step="0.001" value="0.5"></label>
                <p data-error role="alert"></p><div class="nekoko-crop-actions"><button type="button" data-reset>Vrati početni kadar</button><button type="button" data-cancel>Otkaži</button><button type="button" data-save>Primeni isečak</button></div>`;
            document.body.append(dialog);
            const canvas = dialog.querySelector('canvas'), context = canvas.getContext('2d');
            const ratio = dialog.querySelector('[data-ratio]'), zoom = dialog.querySelector('[data-zoom]');
            const x = dialog.querySelector('[data-x]'), y = dialog.querySelector('[data-y]');
            const save = dialog.querySelector('[data-save]');
            const image = new Image(), source = URL.createObjectURL(originals[index]);
            const profile = input.name === 'nekoko_profile_images[]';
            ratio.value = profile ? '1' : '1.5';
            ratio.disabled = profile;
            let rect, drag;
            function draw() {
                const aspect = ratio.value === 'original' ? image.naturalWidth / image.naturalHeight : Number(ratio.value);
                let width = Math.min(image.naturalWidth, image.naturalHeight * aspect) / Number(zoom.value);
                let height = width / aspect;
                rect = { x: (image.naturalWidth - width) * Number(x.value), y: (image.naturalHeight - height) * Number(y.value), width, height };
                canvas.width = Math.round(400 * Math.min(1, aspect));
                canvas.height = Math.round(canvas.width / aspect);
                context.clearRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, rect.x, rect.y, width, height, 0, 0, canvas.width, canvas.height);
            }
            dialog.addEventListener('close', () => {
                URL.revokeObjectURL(source);
                dialog.remove();
                if (trigger.isConnected) trigger.focus();
                else gallery.querySelectorAll('button')[index]?.focus();
            }, { once: true });
            dialog.querySelector('[data-cancel]').onclick = () => dialog.close();
            dialog.querySelector('[data-reset]').onclick = () => { zoom.value = 1; x.value = y.value = 0.5; draw(); };
            [ratio, zoom, x, y].forEach(control => control.addEventListener('input', draw));
            canvas.addEventListener('pointerdown', event => {
                drag = { x: event.clientX, y: event.clientY, px: Number(x.value), py: Number(y.value) };
                canvas.setPointerCapture(event.pointerId);
            });
            canvas.addEventListener('pointermove', event => {
                if (!drag) return;
                const bounds = canvas.getBoundingClientRect();
                const dx = image.naturalWidth - rect.width, dy = image.naturalHeight - rect.height;
                x.value = dx > 0 ? Math.max(0, Math.min(1, drag.px - (event.clientX - drag.x) * rect.width / bounds.width / dx)) : 0.5;
                y.value = dy > 0 ? Math.max(0, Math.min(1, drag.py - (event.clientY - drag.y) * rect.height / bounds.height / dy)) : 0.5;
                draw();
            });
            ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(type => canvas.addEventListener(type, () => { drag = null; }));
            save.onclick = async () => {
                save.disabled = true;
                try {
                    const output = document.createElement('canvas');
                    const scale = Math.min(1, 1600 / Math.max(rect.width, rect.height));
                    output.width = Math.max(1, Math.round(rect.width * scale));
                    output.height = Math.max(1, Math.round(rect.height * scale));
                    output.getContext('2d').drawImage(image, rect.x, rect.y, rect.width, rect.height, 0, 0, output.width, output.height);
                    const type = originals[index].type === 'image/png' ? 'image/png' : 'image/jpeg';
                    const blob = await new Promise(resolve => output.toBlob(resolve, type, 0.92));
                    if (!blob) throw new Error('export');
                    files[index] = new File([blob], originals[index].name.replace(/\.[^.]+$/, '') + '-iseceno.' + (type === 'image/png' ? 'png' : 'jpg'), { type });
                    sync();
                    notice.textContent = 'Isečak je primenjen. Sačuvajte formu da biste objavili izmene.';
                    dialog.close();
                } catch (error) {
                    dialog.querySelector('[data-error]').textContent = 'Slika nije mogla da se iseče. Pokušajte ponovo ili izaberite manju JPG/PNG sliku.';
                    save.disabled = false;
                }
            };
            try {
                image.src = source;
                await image.decode();
                draw();
                dialog.showModal();
            } catch (error) {
                URL.revokeObjectURL(source);
                dialog.remove();
                notice.textContent = 'Nije moguće otvoriti ovu fotografiju. Izaberite ispravnu JPG, PNG ili WebP sliku.';
            }
        }
    });
});
