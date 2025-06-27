document.addEventListener('DOMContentLoaded', () => {
    console.log('index.js loaded successfully. Welcome, Attar!');
    const loginForm = document.querySelector('.login-container form');
    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            const usernameOrEmailInput = loginForm.querySelector('#username_or_email');
            const passwordInput = loginForm.querySelector('#password');

            if (!usernameOrEmailInput.value.trim() || !passwordInput.value.trim()) {
                alert('Username/Email dan password tidak boleh kosong!');
                event.preventDefault(); 
            }
        });
    }
});