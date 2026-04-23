document.getElementById("username").addEventListener("input", () => {
    const username = document.getElementById("username").value;
    
    fetch(`check_username.php?username=${encodeURIComponent(username)}`)
        .then(res => res.json())
        .then(data => {
            const msg = document.getElementById("un-error");
            msg.innerHTML = data.exists ? "Username already exists." : "&nbsp;";
        });
});

document.getElementById("email").addEventListener("input", () => {
    const email = document.getElementById("email").value;

    fetch (`check_email.php?email=${encodeURIComponent(email)}`)
        .then(res => res.json())
        .then(data => {
            const msg = document.getElementById("email-error");
            msg.innerHTML = data.exists ? "Email already exists." : "&nbsp;";
        });
});

function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('toggle-btn');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.classList.remove('fa-eye');
        toggleButton.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleButton.classList.remove('fa-eye-slash');
        toggleButton.classList.add('fa-eye');
    }
}

function toggleConfirmPasswordVisibility() {
    const cPasswordInput = document.getElementById('c-password');
    const toggleCButton = document.getElementById('toggle-c-btn');

    if (cPasswordInput.type === 'password') {
        cPasswordInput.type = 'text';
        toggleCButton.classList.remove('fa-eye');
        toggleCButton.classList.add('fa-eye-slash');
    } else {
        cPasswordInput.type = 'password';
        toggleCButton.classList.remove('fa-eye-slash');
        toggleCButton.classList.add('fa-eye');
    }
}

function validateFName() {
    const fnameInput = document.getElementById('fname').value;
    const fnError = document.getElementById('fn-error');

    if (fnameInput.length > 50) {
        fnError.innerText = "First name should not exceed 50 characters.";
    } else if (fnameInput.length < 1) {
        fnError.innerHTML = "First name is required.";
    } else {
        fnError.innerHTML = "&nbsp;";
    }
}

function validateLName() {
    const lnError = document.getElementById('ln-error');
    const lnameInput = document.getElementById('lname').value;

    if (lnameInput.length > 50) {
        lnError.innerText = "Last name should not exceed 50 characters.";
    } else if (lnameInput.length < 1) {
        lnError.innerHTML = "Last name is required.";
    } else {
        lnError.innerHTML = "&nbsp;";
    }
}

function validateUsername() {
    const unameInput = document.getElementById('username').value;
    const unError = document.getElementById('un-error');

    if (unameInput.length > 30) {
        unError.innerText = "Username should not exceed 30 characters.";
    } else if (unameInput.length < 5) {
        unError.innerText = "Username should be at least 5 characters long.";
    } else {
        unError.innerHTML = "&nbsp;";
    }
}

function validateEmail() {
    const emailInput = document.getElementById('email').value;
    const emailError = document.getElementById('email-error');
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (emailInput.length > 50) {
        fnError.innerText = "Email should not exceed 50 characters.";
    } else if (!emailPattern.test(emailInput)) {
        emailError.innerText = "Invalid email format. (e.g. abc@example.com)";
    } else if (emailInput.length < 5) {
        emailError.innerText = "Email should be at least 5 characters long.";
    } else if (emailPattern.test(emailInput)) {
        emailError.innerHTML = "&nbsp;";
    } else if (emailInput.length < 1) {
        emailError.innerHTML = "Email is required.";
    } else {
        emailError.innerHTML = "&nbsp;";
    }
}

function validatePwd() {
    const pwdInput = document.getElementById('password').value;
    const pwdError = document.getElementById('p-error');

    if (pwdInput.length < 8) {
        pwdError.innerText = "Password should be at least 8 characters long.";
    } else if (pwdInput.length > 30) {
        pwdError.innerText = "Password should not exceed 30 characters.";
    } else {
        pwdError.innerHTML = "&nbsp;";
    }
}

function validateCPwd() {
    const pwdInput = document.getElementById('password').value;
    const cPwdInput = document.getElementById('c-password').value;
    const cPwdError = document.getElementById('cp-error');

    if (pwdInput !== cPwdInput) {
        cPwdError.innerText = "Passwords do not match.";
    } else {
        cPwdError.innerHTML = "&nbsp;";
    }
}