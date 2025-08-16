// Auth related JavaScript functions

// Function to handle signup
async function handleSignup(event) {
    event.preventDefault();

    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    const baseUrl = window.location.protocol + '//' + window.location.host + '/HabitHub';
    const signupUrl = baseUrl + '/includes/signup.php';

    try {
        const response = await fetch(signupUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username: username,
                email: email,
                password: password
            })
        });

        // Get response text first to check what we received
        const responseText = await response.text();
        console.log('Response text:', responseText);

        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            console.error('Response was:', responseText);
            alert('Server returned invalid response. Check console for details.');
            return;
        }

        if (response.ok) {
            alert('Signup successful!');
            window.location.href = 'signin.html';
        } else {
            alert(data.message || 'Signup failed!');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred during signup: ' + error.message);
    }
}

// Function to handle signin
async function handleSignin(event) {
    event.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    const baseUrl = window.location.protocol + '//' + window.location.host + '/HabitHub';
    const signinUrl = baseUrl + '/includes/signin.php';

    try {
        const response = await fetch(signinUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email,
                password: password
            })
        });

        // Get response text first to check what we received
        const responseText = await response.text();
        console.log('Response text:', responseText);

        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('JSON parse error:', jsonError);
            console.error('Response was:', responseText);
            alert('Server returned invalid response. Check console for details.');
            return;
        }

        if (response.ok) {
            alert('Login successful!');
            window.location.href = '../dashboard/dashboard.html';
        } else {
            alert(data.message || 'Login failed!');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred during login: ' + error.message);
    }
}

// Add event listeners to forms
document.addEventListener('DOMContentLoaded', function () {
    const signupForm = document.getElementById('signup-form');
    const signinForm = document.getElementById('signin-form');

    if (signupForm) {
        signupForm.addEventListener('submit', handleSignup);
    }

    if (signinForm) {
        signinForm.addEventListener('submit', handleSignin);
    }
});
