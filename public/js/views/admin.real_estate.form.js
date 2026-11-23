function showFacebookCopyFeedback() {
    const feedback = document.getElementById("copy-feedback");
    feedback.classList.add("show");
    setTimeout(() => feedback.classList.remove("show"), 6500);
}
function copyFacebookPostImmediately() {
    const field = document.getElementById("facebook-post-text"),
        start = field.selectionStart,
        end = field.selectionEnd;
    field.focus();
    field.select();
    field.setSelectionRange(0, field.value.length);
    const copied = document.execCommand("copy");
    field.setSelectionRange(start, end);
    if (copied) showFacebookCopyFeedback();
    return copied;
}
async function copyFacebookPost() {
    if (copyFacebookPostImmediately()) return;
    try {
        await navigator.clipboard.writeText(document.getElementById("facebook-post-text").value);
        showFacebookCopyFeedback();
    } catch (error) {
        alert("تعذر النسخ تلقائيًا. حدد النص الموجود في المربع وانسخه بواسطة Ctrl + C.");
    }
}
document.getElementById("copy-facebook-post")?.addEventListener("click", copyFacebookPost);
document.getElementById("open-facebook-share")?.addEventListener("click", (event) => {
    const shareUrl = event.currentTarget.dataset.shareUrl;
    if (!copyFacebookPostImmediately()) {
        alert("تعذر النسخ تلقائيًا. اضغط زر نسخ النص فقط أولًا، ثم افتح Facebook.");
        return;
    }
    const popup = window.open(shareUrl, "_blank");
    if (!popup) window.location.href = shareUrl;
});
document.getElementById("facebook-kit")?.addEventListener("click", (event) => {
    if (event.target === event.currentTarget) event.currentTarget.close();
});
