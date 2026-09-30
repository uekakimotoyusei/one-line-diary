document.querySelectorAll('[data-image-field]').forEach((field) => {
    const input = field.querySelector('input[type="file"]');
    const currentImage = field.querySelector('[data-current-image]');
    const preview = field.querySelector('[data-image-preview]');
    const previewImage = field.querySelector('[data-preview-image]');
    const status = field.querySelector('[data-preview-status]');
    let objectUrl = null;

    /** 保存済みの画像に戻し、前のプレビュー用URLを解放する。 */
    function resetPreview() {
        preview.hidden = true;
        previewImage.removeAttribute('src');
        if (currentImage) {
            currentImage.hidden = false;
        }
        status.textContent = '';
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
            preview.hidden = false;
            if (currentImage) {
                currentImage.hidden = true;
            }
            status.textContent = 'プレビューを表示しています。保存するには投稿または更新してください。';
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
