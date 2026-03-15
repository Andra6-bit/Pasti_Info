function showResult() {
    const email = document.getElementById('emailInput').value;
    const password = document.getElementById('passInput').value;

    if (email === '' || password === '') {
        document.getElementById('popup').style.display = 'flex';
        document.getElementById('popupMessage').textContent = 'Email dan Password harus diisi!';
        return;
    }

    document.getElementById('popup').style.display = 'flex';
    document.getElementById('popupMessage').innerHTML = 
    ` Berhasil Login!<br>Email: ${email}<br>Password: ${'•'.repeat(password.length)}`;
}

function closePopup() {
    document.getElementById('popup').style.display = 'none';
}