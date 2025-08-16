// Simple Profile JavaScript
let selectedAvatar = 'fas fa-user';

document.addEventListener('DOMContentLoaded', function () {
    loadUserProfile();
    loadUserPreferences();
    loadUserGoals();
    loadUserAvatar();
});

// Load user profile data
async function loadUserProfile() {
    try {
        const response = await fetch('../../includes/get_user_data.php');
        const data = await response.json();

        if (data.success) {
            const user = data.user;

            document.getElementById('profileName').textContent = user.name || 'User';
            document.getElementById('profileEmail').textContent = user.email || '';
            document.getElementById('joinDate').textContent = 'Member since ' + formatDate(user.created_at);

            document.getElementById('userName').value = user.name || '';
            document.getElementById('userEmail').value = user.email || '';

            // Load active days separately
            loadActiveDays();
            document.getElementById('totalHabits').textContent = user.total_habits || 0;
            document.getElementById('longestStreak').textContent = user.longest_streak || 0;

        } else {
            document.getElementById('profileName').textContent = 'User';
            document.getElementById('profileEmail').textContent = 'user@example.com';
            document.getElementById('joinDate').textContent = 'Member since Today';
            // Load active days separately
            loadActiveDays();
        }
    } catch (error) {
        console.error('Error loading user profile:', error);
        document.getElementById('profileName').textContent = 'User';
        document.getElementById('profileEmail').textContent = 'user@example.com';
        document.getElementById('joinDate').textContent = 'Member since Today';
        // Load active days separately
        loadActiveDays();
    }
}

// Load user preferences
function loadUserPreferences() {
    const emailNotifications = localStorage.getItem('emailNotifications') === 'true';
    const dailyReminders = localStorage.getItem('dailyReminders') === 'true';
    const reminderTime = localStorage.getItem('reminderTime') || '09:00';

    document.getElementById('emailNotifications').checked = emailNotifications;
    document.getElementById('dailyReminders').checked = dailyReminders;
    document.getElementById('reminderTime').value = reminderTime;

    // Add event listeners
    document.getElementById('emailNotifications').addEventListener('change', updatePreferences);
    document.getElementById('dailyReminders').addEventListener('change', updatePreferences);
    document.getElementById('reminderTime').addEventListener('change', updatePreferences);
}

// Load user goals
function loadUserGoals() {
    const dailyTarget = localStorage.getItem('dailyTarget') || 5;
    const weeklyGoal = localStorage.getItem('weeklyGoal') || 80;
    const streakTarget = localStorage.getItem('streakTarget') || 30;

    document.getElementById('dailyTarget').value = dailyTarget;
    document.getElementById('weeklyGoal').value = weeklyGoal;
    document.getElementById('streakTarget').value = streakTarget;
}

// Load user avatar
function loadUserAvatar() {
    const savedAvatar = localStorage.getItem('userAvatar') || 'fas fa-user';
    selectedAvatar = savedAvatar;
    updateAvatarDisplay(savedAvatar);
}

// Load active days (actual login days)
async function loadActiveDays() {
    try {
        const response = await fetch('../../includes/get_login_days.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('memberSince').textContent = data.active_days || 0;
        } else {
            document.getElementById('memberSince').textContent = 0;
        }
    } catch (error) {
        console.error('Error loading active days:', error);
        document.getElementById('memberSince').textContent = 0;
    }
}

// Update avatar display
function updateAvatarDisplay(iconClass) {
    const avatarElement = document.getElementById('profileAvatar');
    avatarElement.innerHTML = `<i class="${iconClass}"></i>`;
}

// Tab switching
function switchTab(tabName) {
    // Remove active class from all tabs and buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    // Add active class to selected tab and button
    document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
    document.getElementById(`${tabName}Tab`).classList.add('active');
}

// Avatar functions
function openAvatarModal() {
    const modal = new bootstrap.Modal(document.getElementById('avatarModal'));
    modal.show();

    // Highlight currently selected avatar
    document.querySelectorAll('.avatar-option').forEach(option => {
        option.classList.remove('selected');
        if (option.dataset.icon === selectedAvatar) {
            option.classList.add('selected');
        }
    });
}

function selectAvatar(iconClass) {
    document.querySelectorAll('.avatar-option').forEach(option => {
        option.classList.remove('selected');
    });

    document.querySelector(`[data-icon="${iconClass}"]`).classList.add('selected');
    selectedAvatar = iconClass;
}

function saveAvatar() {
    localStorage.setItem('userAvatar', selectedAvatar);
    updateAvatarDisplay(selectedAvatar);

    const modal = bootstrap.Modal.getInstance(document.getElementById('avatarModal'));
    modal.hide();

    showNotification('Avatar updated!', 'success');
}

// Save personal information
async function savePersonalInfo() {
    const name = document.getElementById('userName').value.trim();
    const email = document.getElementById('userEmail').value.trim();

    if (!name || !email) {
        showNotification('Please fill in all fields', 'error');
        return;
    }

    if (!isValidEmail(email)) {
        showNotification('Please enter a valid email', 'error');
        return;
    }

    try {
        const response = await fetch('../../includes/update_profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: name,
                email: email
            })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('profileName').textContent = name;
            document.getElementById('profileEmail').textContent = email;
            showNotification('Profile updated successfully!', 'success');
        } else {
            showNotification('Failed to update profile: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showNotification('Error updating profile', 'error');
    }
}

// Save goals
function saveGoals() {
    const dailyTarget = document.getElementById('dailyTarget').value;
    const weeklyGoal = document.getElementById('weeklyGoal').value;
    const streakTarget = document.getElementById('streakTarget').value;

    if (dailyTarget < 1 || dailyTarget > 20) {
        showNotification('Daily target must be between 1 and 20', 'error');
        return;
    }

    if (weeklyGoal < 10 || weeklyGoal > 100) {
        showNotification('Weekly goal must be between 10% and 100%', 'error');
        return;
    }

    if (streakTarget < 1 || streakTarget > 365) {
        showNotification('Streak target must be between 1 and 365 days', 'error');
        return;
    }

    localStorage.setItem('dailyTarget', dailyTarget);
    localStorage.setItem('weeklyGoal', weeklyGoal);
    localStorage.setItem('streakTarget', streakTarget);

    showNotification('Goals saved successfully!', 'success');
}

// Update preferences
function updatePreferences() {
    const emailNotifications = document.getElementById('emailNotifications').checked;
    const dailyReminders = document.getElementById('dailyReminders').checked;
    const reminderTime = document.getElementById('reminderTime').value;

    localStorage.setItem('emailNotifications', emailNotifications);
    localStorage.setItem('dailyReminders', dailyReminders);
    localStorage.setItem('reminderTime', reminderTime);

    showNotification('Preferences updated!', 'success');
}

// Change password
function changePassword() {
    const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

// Submit password change
async function submitPasswordChange() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (!currentPassword || !newPassword || !confirmPassword) {
        showNotification('Please fill in all fields', 'error');
        return;
    }

    if (newPassword !== confirmPassword) {
        showNotification('Passwords do not match', 'error');
        return;
    }

    if (newPassword.length < 6) {
        showNotification('Password must be at least 6 characters', 'error');
        return;
    }

    try {
        const response = await fetch('../../includes/change_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                currentPassword: currentPassword,
                newPassword: newPassword
            })
        });

        const data = await response.json();

        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
            modal.hide();
            document.getElementById('changePasswordForm').reset();
            showNotification('Password changed successfully!', 'success');
        } else {
            showNotification('Failed to change password: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error changing password:', error);
        showNotification('Error changing password', 'error');
    }
}

// Export data
function exportData() {
    showNotification('Export feature coming soon!', 'info');
}

// Delete account
function deleteAccount() {
    if (confirm('Are you sure you want to delete your account? This cannot be undone.')) {
        showNotification('Account deletion requires additional verification', 'info');
    }
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'Today';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Removed calculateDaysActive - now using actual login tracking

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showNotification(message, type = 'info') {
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;

    const colors = {
        success: '#28a745',
        error: '#dc3545',
        info: '#17a2b8',
        warning: '#ffc107'
    };
    notification.style.backgroundColor = colors[type] || colors.info;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Add CSS for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Logout function
function handleLogout(event) {
    event.preventDefault();
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../../includes/logout.php';
    }
}