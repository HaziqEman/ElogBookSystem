<form
    id="cm-form"
    class="cm-composer"
    autocomplete="off"
>
    <button
        id="cm-attach"
        class="cm-ic"
        type="button"
        aria-label="Attach"
    ><i class="fa-solid fa-paperclip"></i></button>
    <div class="cm-field">
        <textarea
            id="cm-input"
            rows="1"
            placeholder="Type your message..."
            maxlength="1000"
            enterkeyhint="send"
        ></textarea>
        <button
            id="cm-emoji-btn"
            class="cm-emo"
            type="button"
            aria-label="Emoji"
        ><i class="fa-regular fa-face-smile"></i></button>
    </div>
    <button
        id="cm-send"
        class="cm-go"
        type="submit"
        aria-label="Send"
        hidden
    ><i class="fa-solid fa-paper-plane"></i></button>
    <button
        id="cm-mic"
        class="cm-go"
        type="button"
        aria-label="Record voice message"
    ><i class="fa-solid fa-microphone"></i></button>

    <div
        id="cm-menu"
        class="cm-menu"
        hidden
    >
        <button
            id="cm-pick-doc"
            type="button"
        ><i
                class="fa-solid fa-file-lines"
                style="background:#6366f1"
            ></i> Document</button>
        <button
            id="cm-pick-photo"
            type="button"
        ><i
                class="fa-solid fa-image"
                style="background:#0ea5e9"
            ></i> Photos</button>
        <button
            id="cm-pick-cam"
            type="button"
        ><i
                class="fa-solid fa-camera"
                style="background:#ec4899"
            ></i> Camera</button>
    </div>
    <div
        id="cm-emoji"
        class="cm-emoji"
        hidden
    ></div>

    <input
        id="cm-in-doc"
        type="file"
        hidden
        multiple
        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,application/pdf"
    >
    <input
        id="cm-in-photo"
        type="file"
        hidden
        multiple
        accept="image/*"
    >
    <input
        id="cm-in-cam"
        type="file"
        hidden
        accept="image/*"
        capture="environment"
    >
</form>

<div
    id="cm-rec"
    class="cm-rec"
    hidden
>
    <button
        id="cm-rec-cancel"
        class="cm-ic"
        type="button"
        aria-label="Cancel recording"
    ><i class="fa-solid fa-trash"></i></button>
    <div class="cm-rt"><span class="cm-dot"></span><span
            id="cm-rec-time">0:00</span><span
            style="color:var(--cm-mute);font-weight:400"
        >Recording…</span></div>
    <button
        id="cm-rec-send"
        class="cm-go"
        type="button"
        aria-label="Send voice message"
    ><i class="fa-solid fa-paper-plane"></i></button>
</div>
