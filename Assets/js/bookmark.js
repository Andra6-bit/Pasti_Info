async function toggleBookmark(competitionId, button) {
    if (!competitionId || !button) {
        return;
    }

    button.disabled = true;
    const formData = new FormData();
    formData.append('competition_id', competitionId);

    try {
        const response = await fetch('../pages/toggle_bookmark.php', {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            if (data.message) {
                alert(data.message);
            }

            if (response.status === 401) {
                window.location.href = '../pages/auth.php';
            }
            return;
        }

        const saved = data.action === 'saved';
        button.textContent = saved ? '♥' : '♡';
        button.style.color = saved ? '#e53e3e' : '#aaa';
    } catch (error) {
        console.error('Bookmark error:', error);
        alert('Gagal menyimpan bookmark. Silakan coba lagi.');
    } finally {
        button.disabled = false;
    }
}
