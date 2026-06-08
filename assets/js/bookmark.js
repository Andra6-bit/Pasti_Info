async function toggleBookmark(competitionId, button) {
    // 1-line reason: Check if button is already disabled to prevent multiple simultaneous toggle requests.
    if (!competitionId || !button || button.disabled) {
        return;
    }

    button.disabled = true;
    const formData = new FormData();
    formData.append('competition_id', competitionId);

    try {
        const response = await fetch('../controllers/toggle-bookmark.php', {
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
        if (saved) {
            button.classList.add('saved');
        } else {
            button.classList.remove('saved');
        }
    } catch (error) {
        console.error('Bookmark error:', error);
        alert('Failed to save bookmark. Please try again.');
    } finally {
        button.disabled = false;
    }
}
