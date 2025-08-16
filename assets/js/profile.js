// Profile Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    loadUserProfile();
    loadUserPreferences();
    loadUserGoals();
});

// Load user profile data
async function loadUserProfile() {
    try {
        const response = await fetch('../../includes/get_user_data.php');
        const data = await response.json();
        
        if (data.success) {
            const user = data.user;
            
            // Update profile header
            document.getElementById('profileName').textContent = user.name || 'User';
            document.getElementById('profileEmail').textContent = user.email || '';
            
            // Update profile avatar (first letter of name)
            const avatarElement = document.getElementById('profileAvatar');
            const firstLetter = (user.name || 'U').charAt(0).toUpperCase();
            avatarElement.innerHTML = firstLetter;
            
            // Update personal info section
            document.getElementById('displayName').textContent = user.name || 'Not set';
            document.getElementById('displayEmail').textContent = user.email || 'Not set';
            document.getElementById('joinDate').textContent = formatDate(user.created_at) || 'Unknown';
            
            // Update stats
            document.getElementById('memberSince').textContent = calculateDaysActive(user.created_at);
            document.getElementById('totalHabits').textContent = user.total_habits || 0;
            document.getElementById('longestStreak').textContent = user.longest_streak || 0;
            
        } else {
            console.error('Failed to load user data:', data.message);
        }
    } catch (error) {
        console.error('Error loading user profile:', error);
    }
}

// Load user preferences
function loadUserPreferences() {
    // Load from localStorage or default values
    const theme = localStorage.getItem('theme') || 'light';
    const emailNotifications = localStorage.getItem('emailNotifications') === 'true';
    const dailyReminders = localStorage.getItem('dailyReminders') === 'true';
    const reminderTime = localStorage.getItem('reminderTime') || '09:00';
    
    document.getElementById('themeSelect').value = theme;
    document.getElementById('emailNotifications').checked = emailNotifications;
    document.getElementById('dailyReminders').checked = dailyReminders;
    document.getElementById('reminderTime').value = reminderTime;
}

// Load user goals
function loadUserGoals() {
    // Load from localStorage or default values
    const dailyTarget = localStorage.getItem('dailyTarget') || 5;
    const weeklyGoal = localStorage.getItem('weeklyGoal') || 80;
    const streakTarget = localStorage.getItem('streakTarget') || 30;
    
    document.getElementById('displayDailyTarget').textContent = `${dailyTarget} habits`;
    document.getElementById('displayWeeklyGoal').textContent = `${weeklyGoal}%`;
    document.getElementById('displayStreakTarget').textContent = `${streakTarget} days`;
}

// Toggle edit mode for sections
function toggleEdit(section) {
    const isEditing = document.getElementById(`${section}Actions`).style.display !== 'none';
    
    if (isEditing) {
        cancelEdit(section);
    } else {
        if (section === 'personal') {
            // Show input fields, hide display spans
            document.getElementById('displayName').style.display = 'none';
            document.getElementById('editName').style.display = 'inline-block';
            document.getElementById('editName').value = document.getElementById('displayName').textContent;
            
            document.getElementById('displayEmail').style.display = 'none';
            document.getElementById('editEmail').style.display = 'inline-block';
            document.getElementById('editEmail').value = document.getElementById('displayEmail').textContent;
            
            document.getElementById('personalActions').style.display = 'flex';
        } else if (section === 'goals') {
            // Show input fields for goals
            document.getElementById('displayDailyTarget').style.display = 'none';
            document.getElementById('editDailyTarget').style.display = 'inline-block';
            document.getElementById('editDailyTarget').value = parseInt(document.getElementById('displayDailyTarget').textContent);
            
            document.getElementById('displayWeeklyGoal').style.display = 'none';
            document.getElementById('editWeeklyGoal').style.display = 'inline-block';
            document.getElementById('editWeeklyGoal').value = parseInt(document.getElementById('displayWeeklyGoal').textContent);
            
            document.getElementById('displayStreakTarget').style.display = 'none';
            document.getElementById('editStreakTarget').style.display = 'inline-block';
            document.getElementById('editStreakTarget').value = parseInt(document.getElementById('displayStreakTarget').textContent);
            
            document.getElementById('goalsActions').style.display = 'flex';
        }
    }
}

// Cancel edit mode
function cancelEdit(section) {
    if (section === 'personal') {
        document.getElementById('displayName').style.display = 'inline-block';
        document.getElementById('editName').style.display = 'none';
        document.getElementById('displayEmail').style.display = 'inline-block';
        document.getElementById('editEmail').style.display = 'none';
        document.getElementById('personalActions').style.display = 'none';
    } else if (section === 'goals') {
        document.getElementById('displayDailyTarget').style.display = 'inline-block';
        document.getElementById('editDailyTarget').style.display = 'none';
        document.getElementById('displayWeeklyGoal').style.display = 'inline-block';
        document.getElementById('editWeeklyGoal').style.display = 'none';
        document.getElementById('displayStreakTarget').style.display = 'inline-block';
        document.getElementById('editStreakTarget').style.display = 'none';
        document.getElementById('goalsActions').style.display = 'none';
    }
}

// Save personal information
async function savePersonalInfo() {
    const name = document.getElementById('editName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    
    if (!name || !email) {
        alert('Please fill in all fields');
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
            // Update display values
            document.getElementById('displayName').textContent = name;
            document.getElementById('displayEmail').textContent = email;
            document.getElementById('profileName').textContent = name;
            document.getElementById('profileEmail').textContent = email;
            
            // Update avatar
            const avatarElement = document.getElementById('profileAvatar');
            avatarElement.innerHTML = name.charAt(0).toUpperCase();
            
            cancelEdit('personal');
            showNotification('Profile updated successfully!', 'success');
        } else {
            showNotification('Failed to update profile: ' + data.message, 'error');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showNotification('An error occurred while updating profile', 'error');
    }
}

// Save goals
function saveGoals() {
    const dailyTarget = document.getElementById('editDailyTarget').value;
    const weeklyGoal = document.getElementById('editWeeklyGoal').value;
    const streakTarget = document.getElementById('editStreakTarget').value;
    
    // Save to localStorage
    localStorage.setItem('dailyTarget', dailyTarget);
    localStorage.setItem('weeklyGoal', weeklyGoal);
    localStorage.setItem('streakTarget', streakTarget);
    
    // Update display values
    document.getElementById('displayDailyTarget').textContent = `${dailyTarget} habits`;
    document.getElementById('displayWeeklyGoal').textContent = `${weeklyGoal}%`;
    document.getElementById('displayStreakTarget').textContent = `${streakTarget} days`;
    
    cancelEdit('goals');
    showNotification('Goals updated successfully!', 'success');
}

// Change theme
function changeTheme() {
    const theme = document.getElementById('themeSelect').value;
    localStorage.setItem('theme', theme);
    
    // Apply theme (you can expand this based on your theme system)
    document.body.setAttribute('data-theme', theme);
    showNotification('Theme updated!', 'success');
}

// Update notification settings
function updateNotificationSettings() {
    const emailNotifications = document.getElementById('emailNotifications').checked;
    const dailyReminders = document.getElementById('dailyReminders').checked;
    
    localStorage.setItem('emailNotifications', emailNotifications);
    localStorage.setItem('dailyReminders', dailyReminders);
    
    showNotification('Notification settings updated!', 'success');
}

// Update reminder time
function updateReminderTime() {
    const reminderTime = document.getElementById('reminderTime').value;
    localStorage.setItem('reminderTime', reminderTime);
    showNotification('Reminder time updated!', 'success');
}

// Change avatar (placeholder function)
function changeAvatar() {
    // This would typically open a file picker or avatar selection modal
    showNotification('Avatar change feature coming soon!', 'info');
}

// Export data
function exportData() {
    // This would generate and download user data
    showNotification('Data export feature coming soon!', 'info');
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
        showNotification('Please fill in all password fields', 'error');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showNotification('New passwords do not match', 'error');
        return;
    }
    
    if (newPassword.length < 6) {
        showNotification('New password must be at least 6 characters long', 'error');
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
        showNotification('An error occurred while changing password', 'error');
    }
}

// Delete account
function deleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        if (confirm('This will permanently delete all your data. Are you absolutely sure?')) {
            // This would call the delete account endpoint
            showNotification('Account deletion feature requires additional security verification', 'info');
        }
    }
}

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'Unknown';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function calculateDaysActive(joinDate) {
    if (!joinDate) return 0;
    const join = new Date(joinDate);
    const now = new Date();
    const diffTime = Math.abs(now - join);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    // Set background color based on type
    const colors = {
        success: '#4CAF50',
        error: '#f44336',
        info: '#2196F3',
        warning: '#ff9800'
    };
    notification.style.backgroundColor = colors[type] || colors.info;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
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

// Logout function (shared with other pages)
function handleLogout(event) {
    event.preventDefault();
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../../includes/logout.php';
    }
}