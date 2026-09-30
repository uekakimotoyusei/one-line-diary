/**
 * フォームごとに独立した画像プレビューを準備する。
 * @param {Element} field プレビュー対象の画像入力欄。
 * @returns {void} ファイル選択イベントを登録する。
 */
function initializeImagePreview(field) {
    const input = field.querySelector('input[type="file"]');
    const previewImage = field.querySelector('[data-preview-image]');
    const placeholder = field.querySelector('[data-image-placeholder]');
    const status = field.querySelector('[data-preview-status]');
    const originalSrc = previewImage.getAttribute('src');
    let objectUrl = null;

    /**
     * 保存済みの画像に戻し、前のプレビュー用URLを解放する。
     * @returns {void} プレビュー表示のみを更新する。
     */
    function resetPreview() {
        if (originalSrc) {
            previewImage.src = originalSrc;
            status.textContent = '現在の画像';
        } else {
            previewImage.removeAttribute('src');
            status.textContent = '画像未選択';
        }
        previewImage.hidden = !originalSrc;
        previewImage.alt = '現在の画像';
        placeholder.hidden = Boolean(originalSrc);
        // 選び直しを繰り返しても画像のメモリを保持し続けないよう解放する。
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    }

    /**
     * 選択画像をブラウザ内で読み込み、保存前の見た目を表示する。
     * @returns {void} 選択画像の読み込みを開始する。
     */
    function updatePreview() {
        resetPreview();
        const file = input.files[0];
        if (!file) {
            return;
        }

        const selectedUrl = URL.createObjectURL(file);
        objectUrl = selectedUrl;
        const image = new Image();
        /**
         * 読み込み済みの画像だけを表示し、途中のちらつきを防ぐ。
         * @returns {void} プレビュー画像を差し替える。
         */
        image.onload = () => {
            // 選び直した後に届いた古い読み込み結果で表示を上書きしない。
            if (objectUrl !== selectedUrl) {
                return;
            }
            previewImage.src = selectedUrl;
            previewImage.hidden = false;
            previewImage.alt = '選択中の画像のプレビュー';
            placeholder.hidden = true;
            status.textContent = '選択中の画像（未保存）';
        };
        /**
         * 読み込めない画像の場合に元の表示へ戻す。
         * @returns {void} 選び直しの案内を表示する。
         */
        image.onerror = () => {
            // 選び直した後に届いた古い読み込み結果で表示を上書きしない。
            if (objectUrl !== selectedUrl) {
                return;
            }
            resetPreview();
            status.textContent = '画像をプレビューできません。JPEG形式の画像を選び直してください。';
        };
        image.src = selectedUrl;
    }

    input.addEventListener('change', updatePreview);
}

document.querySelectorAll('[data-image-field]').forEach(initializeImagePreview);
