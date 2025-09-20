// Achievements page functionality - now loads from database
document.addEventListener('DOMContentLoaded', function () {
    initializeAchievements();
    setupEventListeners();
});

// Achievement data will be loaded from backend
let achievementsData = null;
let achievementsStats = null;

let currentFilter = 'all';
let currentCategory = 'all';
let searchTerm = '';

// Initialize achievements from backend
async function initializeAchievements() {
    await loadAchievements();
    calculateAndUpdateStats();
    renderAchievements();
    updateRecentAchievements();
}

// Load achievements from backend
async function loadAchievements() {
    try {
        const response = await fetch('../../includes/get_achievements.php');
        const data = await response.json();
        
        if (data.success) {
            achievementsData = data.achievements;
            achievementsStats = data.stats; // Store the stats from backend
        } else {
            console.error('Failed to load achievements:', data.message);
            achievementsData = {}; // Fallback to empty object
            achievementsStats = {};
        }
    } catch (error) {
        console.error('Error loading achievements:', error);
        achievementsData = {}; // Fallback to empty object
        achievementsStats = {};
    }
}

function calculateAndUpdateStats() {
    if (!achievementsData || !achievementsStats) {
        return; // Don't update if data not loaded yet
    }
    
    // Use stats from backend API
    const totalAchievements = achievementsStats.total_earned || 0;
    const totalXP = achievementsStats.total_xp || 0;
    const currentStreak = achievementsStats.current_streak || 0;
    const rarestRarity = achievementsStats.rarest_rarity || 'common';

    // Calculate level based on XP (every 500 XP = 1 level)
    const level = Math.floor(totalXP / 500) + 1;
    const currentLevelXP = totalXP % 500;
    const nextLevelXP = 500;
    const progressPercent = (currentLevelXP / nextLevelXP) * 100;

    // Create rarest achievement object for compatibility
    const rarestAchievement = { rarity: rarestRarity };

    // Update stats in UI
    const statCards = document.querySelectorAll('.stats-grid .stat-card');
    if (statCards.length >= 4) {
        statCards[0].querySelector('h3').textContent = totalAchievements;
        statCards[1].querySelector('h3').textContent = totalXP.toLocaleString();
        statCards[2].querySelector('h3').textContent = getLevelTitle(level);
        statCards[3].querySelector('h3').textContent = capitalizeFirst(rarestAchievement.rarity);
    }

    // Update level display elements if they exist
    const levelNumber = document.querySelector('.level-number');
    if (levelNumber) levelNumber.textContent = level;
    
    const levelTitle = document.querySelector('.level-title');
    if (levelTitle) levelTitle.textContent = getLevelTitle(level);
    
    const levelInfo = document.querySelector('.level-info h2');
    if (levelInfo) levelInfo.textContent = `Level ${level} - ${getLevelTitle(level)}`;
    
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) progressFill.style.width = `${progressPercent}%`;
    
    const xpInfo = document.querySelector('.xp-info');
    if (xpInfo) xpInfo.textContent = `${currentLevelXP.toLocaleString()} / ${nextLevelXP.toLocaleString()} XP to next level`;
}

function getLevelTitle(level) {
    if (level >= 20) return 'Habit Legend';
    if (level >= 15) return 'Habit Hero';
    if (level >= 10) return 'Habit Master';
    if (level >= 5) return 'Habit Warrior';
    return 'Habit Novice';
}

function getAllAchievements() {
    if (!achievementsData) {
        return [];
    }
    
    const all = [];
    Object.values(achievementsData).forEach(category => {
        all.push(...category);
    });
    return all;
}

function getFilteredAchievements() {
    let achievements = getAllAchievements();

    if (!achievementsData) {
        return [];
    }

    // Filter by category
    if (currentCategory !== 'all') {
        if (achievementsData[currentCategory]) {
            achievements = achievementsData[currentCategory];
        } else {
            achievements = [];
        }
    }

    // Filter by status
    if (currentFilter !== 'all') {
        achievements = achievements.filter(achievement => {
            switch (currentFilter) {
                case 'earned':
                    return achievement.earned;
                case 'in-progress':
                    return !achievement.earned && achievement.progress > 0 && (achievement.isActive !== false);
                case 'locked':
                    return achievement.isLocked === true;
                default:
                    return true;
            }
        });
    }

    // Filter by search term
    if (searchTerm) {
        achievements = achievements.filter(achievement =>
            achievement.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            achievement.description.toLowerCase().includes(searchTerm.toLowerCase())
        );
    }

    return achievements;
}

function renderAchievements() {
    const achievements = getFilteredAchievements();
    const grid = document.getElementById('achievementsGrid');

    if (achievements.length === 0) {
        grid.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">
                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>No achievements found matching your criteria.</p>
            </div>
        `;
        return;
    }

    // Sort achievements to show active ones first
    const sortedAchievements = achievements.sort((a, b) => {
        // Priority order: Active > Earned > Locked
        const getPriority = (achievement) => {
            if (!achievement.earned && (achievement.isActive !== false) && (achievement.isLocked !== true)) {
                return 1; // Active achievements (highest priority)
            } else if (achievement.isLocked) {
                return 2; // Locked achievements (middle priority)
            } else {
                return 3; // Earned achievements (lowest priority)
            }
        };
        
        const priorityA = getPriority(a);
        const priorityB = getPriority(b);
        
        if (priorityA !== priorityB) {
            return priorityA - priorityB; // Sort by priority
        }
        
        // If same priority, maintain original order (requirement_value order from backend)
        return 0;
    });

    grid.innerHTML = sortedAchievements.map(achievement => createAchievementCard(achievement)).join('');
}

function createAchievementCard(achievement) {
    const progressPercent = (achievement.progress / achievement.maxProgress) * 100;
    const isEarned = achievement.earned;
    const isActive = achievement.isActive !== undefined ? achievement.isActive : true; // Default to active if not specified
    const isLocked = achievement.isLocked !== undefined ? achievement.isLocked : false; // Default to not locked if not specified
    const isInProgress = !isEarned && achievement.progress > 0 && isActive;

    let statusClass = '';
    if (isEarned) statusClass = 'earned';
    else if (isLocked) statusClass = 'locked';
    else if (isActive) statusClass = 'active';

    return `
        <div class="achievement-card ${statusClass}" data-achievement-id="${achievement.id}">
            <div class="achievement-header">
                <div class="achievement-icon ${achievement.category || 'default'}">
                    <i class="${achievement.icon}"></i>
                </div>
                <div class="achievement-content">
                    <div class="achievement-info">
                        <h4>${achievement.name}</h4>
                        <div class="achievement-rarity ${achievement.rarity}">
                            ${capitalizeFirst(achievement.rarity)}
                        </div>
                    </div>
                    <div class="achievement-description">
                        ${achievement.description}
                    </div>
                </div>
            </div>
            <div class="achievement-meta">
                <div class="achievement-points">
                    <i class="fas fa-star"></i>
                    ${achievement.points} XP
                </div>
                ${!isEarned ? `
                    <div class="achievement-progress">
                        <div class="progress-header">
                            <span class="progress-text">Progress: ${achievement.progress}/${achievement.maxProgress}</span>
                            <span class="progress-percentage">${Math.round(progressPercent)}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-bar-fill ${achievement.rarity}" style="width: ${progressPercent}%"></div>
                        </div>
                    </div>
                ` : `
                    <div class="achievement-date">
                        <i class="fas fa-calendar"></i>
                        Earned on ${formatDate(achievement.earnedDate)}
                    </div>
                `}
            </div>
            
            ${isEarned ? `
                <button class="share-btn" onclick="shareAchievement('${achievement.id}')">
                    <i class="fas fa-share"></i> Share Achievement
                </button>
            ` : ''}
        </div>
    `;
}

function setupEventListeners() {
    // Category tabs
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.dataset.category;
            renderAchievements();
        });
    });

    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            renderAchievements();
        });
    });

    // Search box
    const searchBox = document.getElementById('searchAchievements');
    searchBox.addEventListener('input', function () {
        searchTerm = this.value;
        renderAchievements();
    });

    // Achievement card clicks
    document.addEventListener('click', function (e) {
        const card = e.target.closest('.achievement-card');
        if (card && !e.target.closest('.share-btn')) {
            const achievementId = card.dataset.achievementId;
            showAchievementDetails(achievementId);
        }
    });
}

function updateRecentAchievements() {
    const timeline = document.querySelector('.timeline');
    
    // Safety check - timeline element might not exist on all pages
    if (!timeline) {
        console.log('Timeline element not found - skipping recent achievements update');
        return;
    }
    
    const allAchievements = getAllAchievements();
    const recentEarned = allAchievements
        .filter(a => a.earned)
        .sort((a, b) => new Date(b.earnedDate) - new Date(a.earnedDate))
        .slice(0, 3);

    timeline.innerHTML = recentEarned.map(achievement => `
        <div class="timeline-item">
            <div class="timeline-icon ${achievement.rarity}">
                <i class="${achievement.icon}"></i>
            </div>
            <div class="timeline-content">
                <h5>${achievement.name}</h5>
                <p>Earned ${getRelativeTime(achievement.earnedDate)} - ${achievement.points} XP</p>
            </div>
        </div>
    `).join('');
}

function showAchievementDetails(achievementId) {
    const achievement = getAllAchievements().find(a => a.id === achievementId);
    if (!achievement) return;

    // Create modal or detailed view
    const modal = document.createElement('div');
    modal.className = 'achievement-modal';
    modal.innerHTML = `
        <div class="modal-backdrop" onclick="closeAchievementModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <div class="achievement-icon ${achievement.rarity}" style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="${achievement.icon}"></i>
                </div>
                <h2>${achievement.name}</h2>
                <div class="achievement-rarity ${achievement.rarity}">
                    ${capitalizeFirst(achievement.rarity)} Achievement
                </div>
            </div>
            <div class="modal-body">
                <p>${achievement.description}</p>
                <div class="achievement-stats">
                    <div class="stat">
                        <strong>Reward:</strong> ${achievement.points} XP
                    </div>
                    <div class="stat">
                        <strong>Progress:</strong> ${achievement.progress}/${achievement.maxProgress}
                    </div>
                    ${achievement.earned ? `
                        <div class="stat">
                            <strong>Earned:</strong> ${formatDate(achievement.earnedDate)}
                        </div>
                    ` : ''}
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeAchievementModal()">Close</button>
                ${achievement.earned ? `
                    <button onclick="shareAchievement('${achievement.id}')">Share</button>
                ` : ''}
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Add modal styles if not already added
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        styles.textContent = `
            .achievement-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .modal-backdrop {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(5px);
            }
            .modal-content {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                border-radius: 20px;
                padding: 2rem;
                max-width: 500px;
                width: 90%;
                position: relative;
                text-align: center;
            }
            .modal-header h2 {
                margin: 1rem 0 0.5rem;
                color: #333;
            }
            .modal-body {
                margin: 1.5rem 0;
            }
            .achievement-stats {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                margin-top: 1rem;
                text-align: left;
            }
            .modal-footer {
                display: flex;
                gap: 1rem;
                justify-content: center;
            }
            .modal-footer button {
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 500;
            }
            .modal-footer button:first-child {
                background: #6c757d;
                color: white;
            }
            .modal-footer button:last-child {
                background: var(--mindfulness-color);
                color: white;
            }
        `;
        document.head.appendChild(styles);
    }
}

function closeAchievementModal() {
    const modal = document.querySelector('.achievement-modal');
    if (modal) {
        modal.remove();
    }
}


function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--mindfulness-color);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        z-index: 1001;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Utility functions
function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function getRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 1) return 'yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
    return `${Math.floor(diffDays / 30)} months ago`;
}

// Add notification animations
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(notificationStyles);

// Logout functionality
function handleLogout(event) {
    event.preventDefault();

    if (confirm('Are you sure you want to logout?')) {
        fetch('../../includes/logout.php', {
            method: 'POST'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '../../index.html';
                } else {
                    alert('Logout failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                // Force redirect even if request fails
                window.location.href = '../../index.html';
            });
    }
}

// Add some celebration effects for earned achievements
function addCelebrationEffect() {
    // Create confetti effect
    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.backgroundColor = getRandomColor();
            confetti.style.animationDelay = Math.random() * 3 + 's';
            document.body.appendChild(confetti);

            setTimeout(() => confetti.remove(), 3000);
        }, i * 50);
    }
}

function getRandomColor() {
    const colors = ['#ffd700', '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeaa7'];
    return colors[Math.floor(Math.random() * colors.length)];
}

// Add keyboard shortcuts
document.addEventListener('keydown', function (e) {
    // Press 'f' to focus search
    if (e.key === 'f' && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
        document.getElementById('searchAchievements').focus();
    }

    // Press 'Escape' to close modal
    if (e.key === 'Escape') {
        closeAchievementModal();
    }

    // Press numbers 1-4 to switch filters
    if (e.key >= '1' && e.key <= '4') {
        const filters = ['all', 'earned', 'in-progress', 'locked'];
        const filterIndex = parseInt(e.key) - 1;
        if (filters[filterIndex]) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            document.querySelector(`[data-filter="${filters[filterIndex]}"]`).classList.add('active');
            currentFilter = filters[filterIndex];
            renderAchievements();
        }
    }
});

// Add smooth scrolling for better UX
document.documentElement.style.scrollBehavior = 'smooth';

// Additional functions for achievement unlocking and celebrations

// Simulate achievement unlock (for demo purposes)
function unlockAchievement(achievementId) {
    const achievement = getAllAchievements().find(a => a.id === achievementId);
    if (!achievement || achievement.earned) return;

    // Mark as earned
    achievement.earned = true;
    achievement.progress = achievement.maxProgress;
    achievement.earnedDate = new Date().toISOString().split('T')[0];

    // Create celebration effect
    createCelebration();

    // Show achievement unlock notification
    showAchievementUnlock(achievement);

    // Re-render achievements
    setTimeout(() => {
        renderAchievements();
        calculateAndUpdateStats();
        updateRecentAchievements();
    }, 2000);
}

// Create celebration effect
function createCelebration() {
    // Create confetti
    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            createConfetti();
        }, i * 50);
    }

    // Add celebration class to level badge
    const levelBadge = document.querySelector('.level-badge');
    if (levelBadge) {
        levelBadge.classList.add('celebration');
        setTimeout(() => {
            levelBadge.classList.remove('celebration');
        }, 600);
    }
}

// Create confetti piece
function createConfetti() {
    const confetti = document.createElement('div');
    confetti.className = 'confetti';
    confetti.style.cssText = `
        position: fixed;
        width: 10px;
        height: 10px;
        background: ${getRandomColor()};
        animation: confetti-fall 3s ease-in-out forwards;
        pointer-events: none;
        z-index: 9999;
    `;
    confetti.style.left = Math.random() * window.innerWidth + 'px';
    confetti.style.top = '-10px';

    document.body.appendChild(confetti);

    setTimeout(() => {
        if (document.body.contains(confetti)) {
            document.body.removeChild(confetti);
        }
    }, 3000);
}

// Show achievement unlock notification
function showAchievementUnlock(achievement) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        animation: fadeIn 0.3s ease;
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            max-width: 400px;
            animation: slideUp 0.3s ease;
        ">
            <div style="
                width: 100px;
                height: 100px;
                background: linear-gradient(135deg, #ffd700, #ffed4e);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 36px;
                margin: 0 auto 2rem;
                box-shadow: 0 0 30px rgba(255, 215, 0, 0.4);
                animation: levelGlow 2s ease-in-out infinite alternate;
                color: #fff;
            ">
                <i class="${achievement.icon}"></i>
            </div>
            <h2 style="color: #333; margin-bottom: 1rem; font-size: 24px;">🎉 Achievement Unlocked!</h2>
            <h3 style="color: var(--mindfulness-color); margin-bottom: 0.5rem;">${achievement.name}</h3>
            <p style="color: #666; margin-bottom: 1.5rem;">${achievement.description}</p>
            <p style="color: #ffd700; font-weight: 600; font-size: 18px;">+${achievement.points} XP</p>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: linear-gradient(135deg, #8b5cf6, #3b82f6);
                color: white;
                border: none;
                padding: 0.75rem 2rem;
                border-radius: 8px;
                margin-top: 1rem;
                cursor: pointer;
                font-weight: 500;
            ">Awesome!</button>
        </div>
    `;

    document.body.appendChild(modal);

    // Auto-close after 5 seconds
    setTimeout(() => {
        if (document.body.contains(modal)) {
            document.body.removeChild(modal);
        }
    }, 5000);
}

// Demo function to unlock achievements (for testing)
function demoUnlockAchievement() {
    const unlockedAchievements = getAllAchievements().filter(a => !a.earned);
    if (unlockedAchievements.length > 0) {
        const randomAchievement = unlockedAchievements[Math.floor(Math.random() * unlockedAchievements.length)];
        unlockAchievement(randomAchievement.id);
    }
}

// Add additional CSS animations
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes confetti-fall {
        0% {
            transform: translateY(-10px) rotateZ(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) rotateZ(720deg);
            opacity: 0;
        }
    }
    
    @keyframes levelGlow {
        0% {
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
        }
        100% {
            box-shadow: 0 0 40px rgba(255, 215, 0, 0.8);
        }
    }
    
    .celebration {
        animation: bounce 0.6s ease;
    }
    
    @keyframes bounce {
        0%, 20%, 60%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        80% {
            transform: translateY(-5px);
        }
    }
`;
document.head.appendChild(additionalStyles);

// Update the DOMContentLoaded event listener to include demo functionality
document.addEventListener('DOMContentLoaded', function () {
    initializeAchievements();
    setupEventListeners();
    
    // Demo: Add click handler to level badge for testing achievement unlock
    const levelBadge = document.querySelector('.level-badge');
    if (levelBadge) {
        levelBadge.addEventListener('click', () => {
            demoUnlockAchievement();
        });
    }
});