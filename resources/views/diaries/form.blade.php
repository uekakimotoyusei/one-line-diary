@if ($errors->any())
    <p class="error" role="alert">入力内容を確認してください。画像を選択していた場合は、選び直してください。</p>
@endif
<div class="field">
    <label for="title">タイトル（必須・50文字以内・改行不可）</label>
    <input id="title" name="title" type="text" value="{{ old('title', $diary->title) }}" required aria-describedby="title-error">
    @error('title')
        <p class="error" id="title-error">{{ $message }}</p>
    @enderror
</div>
<div class="field">
    <label for="body">本文（必須・140文字以内・改行不可）</label>
    <input id="body" name="body" type="text" value="{{ old('body', $diary->body) }}" required aria-describedby="body-error">
    @error('body')
        <p class="error" id="body-error">{{ $message }}</p>
    @enderror
</div>
<div class="field" data-image-field>
    <label for="image">画像（任意・JPEG形式・5MB以下）</label>
    <p class="image-status" data-preview-status role="status" aria-live="polite">
        @if ($diary->image_path)
            現在の画像
        @else
            画像未選択
        @endif
    </p>
    {{-- 選択前後に同じ表示枠を使い、画像の位置が移動しないようにする。 --}}
    <div class="image-frame">
        <img data-preview-image @if ($diary->image_path) src="{{ Storage::disk('public')->url($diary->image_path) }}" @else hidden @endif alt="現在の画像">
        <span data-image-placeholder @if ($diary->image_path) hidden @endif>選択した画像がここに表示されます。</span>
    </div>
    @if ($diary->image_path)
        <p>新しい画像を選択して「更新する」を押すと差し替わります。画像を選ばなければ現在の画像を保持します。</p>
    @else
        <p>画像を選択するとプレビューを表示します。「投稿する」を押すまで保存されません。</p>
    @endif
    <input id="image" name="image" type="file" accept=".jpg,.jpeg,image/jpeg" aria-describedby="image-error">
    @error('image')
        <p class="error" id="image-error">{{ $message }}</p>
    @enderror
</div>
@once
    <script src="{{ asset('js/image-preview.js') }}" defer></script>
@endonce
