// Auth related JavaScript functions

// Function to handle signup
async function handleSignup(event) {
    event.preventDefault();
    
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    const baseUrl = window.location.protocol + '//' + window.location.host + '/HabitHub';

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

        const data = await response.json();
        
        if (response.ok) {
            alert('Signup successful!');
            window.location.href = 'signin.html';
        } else {
            alert(data.message || 'Signup failed!');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred during signup.');
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

        const data = await response.json();
        
        if (response.ok) {
            alert('Login successful!');
            window.location.href = '../dashboard/dashboard.html';
        } else {
            alert(data.message || 'Login failed!');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred during login.');
    }
}

// Add event listeners to forms
document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signup-form');
    const signinForm = document.getElementById('signin-form');
    
    if (signupForm) {
        signupForm.addEventListener('submit', handleSignup);
    }
    
    if (signinForm) {
        signinForm.addEventListener('submit', handleSignin);
    }
});
