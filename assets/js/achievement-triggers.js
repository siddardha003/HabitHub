// Achievement Trigger - Client-side helper for real-time achievement checking
// Add this script to any page that needs achievement checking

/**
 * Trigger achievement check for user actions
 * @param {string} actionType - Type of action (login, habit_created, habit_completed)
 * @param {object} data - Additional data for the action
 * @param {function} callback - Optional callback for handling results
 */
function triggerAchievementCheck(actionType, data = {}, callback = null) {
    // Determine the correct path based on current location
    let apiPath = '../../includes/check_achievements.php';
    
    // If we're in the root directory or a different structure
    if (window.location.pathname.includes('/pages/dashboard/')) {
        apiPath = '../../includes/check_achievements.php';
    } else if (window.location.pathname.includes('/pages/')) {
        apiPath = '../includes/check_achievements.php';
    } else {
        apiPath = 'includes/check_achievements.php';
    }
    
    fetch(apiPath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action_type: actionType,
            data: JSON.stringify(data)
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        if (result.success && result.awarded_achievements && result.awarded_achievements.length > 0) {
            console.log('🏆 New achievements unlocked:', result.awarded_achievements);
            
            // Show achievement notification (optional)
            if (typeof showAchievementNotification === 'function') {
                result.awarded_achievements.forEach(achievement => {
                    showAchievementNotification(achievement);
                });
            }
        }
        
        if (callback) {
            callback(result);
        }
    })
    .catch(error => {
        console.error('Achievement check failed:', error);
        if (callback) {
            callback({ success: false, error: error.message });
        }
    });
}

/**
 * Simple achievement notification (can be customized)
 */
function showAchievementNotification(achievementKey) {
    // Create a simple notification
    const notification = document.createElement('div');
    notification.className = 'achievement-notification';
    notification.innerHTML = `
        <div class="achievement-popup">
            <div class="achievement-icon">🏆</div>
            <div class="achievement-text">
                <strong>Achievement Unlocked!</strong><br>
                <span>${achievementKey.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
            </div>
        </div>
    `;
    
    // Add styles if not already present
    if (!document.querySelector('#achievement-styles')) {
        const styles = document.createElement('style');
        styles.id = 'achievement-styles';
        styles.textContent = `
            .achievement-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                animation: slideInRight 0.5s ease-out;
            }
            
            .achievement-popup {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px 20px;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 250px;
                max-width: 350px;
            }
            
            .achievement-icon {
                font-size: 24px;
                animation: bounce 1s infinite;
            }
            
            .achievement-text strong {
                font-size: 14px;
                display: block;
                margin-bottom: 4px;
            }
            
            .achievement-text span {
                font-size: 12px;
                opacity: 0.9;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-5px);
                }
                60% {
                    transform: translateY(-3px);
                }
            }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        notification.style.animation = 'slideInRight 0.5s ease-out reverse';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 500);
    }, 4000);
}

/**
 * Quick trigger functions for common actions
 */
const AchievementTriggers = {
    login: () => triggerAchievementCheck('login'),
    
    habitCreated: (habitData = {}) => triggerAchievementCheck('habit_created', habitData),
    
    habitCompleted: (habitId, category = null, completionTime = null) => {
        const data = { habit_id: habitId };
        if (category) data.category = category;
        if (completionTime) data.completion_time = completionTime;
        triggerAchievementCheck('habit_completed', data);
    }
};

// Auto-trigger login achievement check when page loads (if user is logged in)
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure session is established
    setTimeout(() => {
        // Only trigger on dashboard pages, not achievements page to avoid unnecessary calls
        if (window.location.pathname.includes('/dashboard/') && 
            !window.location.pathname.includes('/achievements.html')) {
            AchievementTriggers.login();
        }
    }, 1000);
});

console.log('🏆 Achievement system loaded - ready for real-time checking');