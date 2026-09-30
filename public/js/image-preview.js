document.querySelectorAll('[data-image-field]').forEach((field) => {
    const input = field.querySelector('input[type="file"]');
    const previewImage = field.querySelector('[data-preview-image]');
    const placeholder = field.querySelector('[data-image-placeholder]');
    const status = field.querySelector('[data-preview-status]');
    const originalSrc = previewImage.getAttribute('src');
    let objectUrl = null;

    /** 保存済みの画像に戻し、前のプレビュー用URLを解放する。 */
    function resetPreview() {
        if (originalSrc) {
            previewImage.src = originalSrc;
        } else {
            previewImage.removeAttribute('src');
        }
        previewImage.hidden = !originalSrc;
        previewImage.alt = '現在の画像';
        placeholder.hidden = Boolean(originalSrc);
        status.textContent = originalSrc ? '現在の画像' : '画像未選択';
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    }

    /** 選択画像をブラウザ内で読み込み、保存前の見た目を表示する。 */
    function updatePreview() {
        resetPreview();
        const file = input.files[0];
        if (!file) {
            return;
        }

        const selectedUrl = URL.createObjectURL(file);
        objectUrl = selectedUrl;
        const image = new Image();
        image.onload = () => {
            if (objectUrl !== selectedUrl) {
                return;
            }
            previewImage.src = selectedUrl;
            previewImage.hidden = false;
            previewImage.alt = '選択中の画像のプレビュー';
            placeholder.hidden = true;
            status.textContent = '選択中の画像（未保存）';
        };
        image.onerror = () => {
            if (objectUrl !== selectedUrl) {
                return;
            }
            resetPreview();
            status.textContent = '画像をプレビューできません。JPEG形式の画像を選び直してください。';
        };
        image.src = selectedUrl;
    }

    input.addEventListener('change', updatePreview);
});
