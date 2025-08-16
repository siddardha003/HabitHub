// Achievements page functionality with fallback data
document.addEventListener('DOMContentLoaded', function () {
    initializeAchievements();
    setupEventListeners();
});

// Comprehensive achievement data with all categories
const achievementsData = {
    streaks: [
        {
            id: 'fire_starter',
            name: 'Fire Starter',
            description: 'Complete habits for 3 consecutive days',
            icon: 'fas fa-fire',
            rarity: 'common',
            points: 50,
            progress: 3,
            maxProgress: 3,
            earned: true,
            earnedDate: '2025-01-10'
        },
        {
            id: 'habit_warrior',
            name: 'Habit Warrior',
            description: 'Maintain a 7-day streak',
            icon: 'fas fa-sword',
            rarity: 'rare',
            points: 150,
            progress: 7,
            maxProgress: 7,
            earned: true,
            earnedDate: '2025-01-14'
        },
        {
            id: 'consistency_king',
            name: 'Consistency King',
            description: 'Achieve a 14-day streak',
            icon: 'fas fa-crown',
            rarity: 'epic',
            points: 300,
            progress: 12,
            maxProgress: 14,
            earned: false
        },
        {
            id: 'unstoppable_force',
            name: 'Unstoppable Force',
            description: 'Reach a 30-day streak',
            icon: 'fas fa-rocket',
            rarity: 'legendary',
            points: 500,
            progress: 12,
            maxProgress: 30,
            earned: false
        },
        {
            id: 'century_club',
            name: 'Century Club',
            description: 'Achieve a 100-day streak',
            icon: 'fas fa-trophy',
            rarity: 'legendary',
            points: 1000,
            progress: 12,
            maxProgress: 100,
            earned: false
        }
    ],
    progress: [
        {
            id: 'first_steps',
            name: 'First Steps',
            description: 'Complete your first habit',
            icon: 'fas fa-baby',
            rarity: 'common',
            points: 25,
            progress: 1,
            maxProgress: 1,
            earned: true,
            earnedDate: '2025-01-08'
        },
        {
            id: 'habit_collector',
            name: 'Habit Collector',
            description: 'Create 5 different habits',
            icon: 'fas fa-list',
            rarity: 'common',
            points: 75,
            progress: 5,
            maxProgress: 5,
            earned: true,
            earnedDate: '2025-01-12'
        },
        {
            id: 'century_mark',
            name: 'Century Mark',
            description: 'Complete 100 total habit instances',
            icon: 'fas fa-check-circle',
            rarity: 'rare',
            points: 200,
            progress: 87,
            maxProgress: 100,
            earned: false
        },
        {
            id: 'habit_master',
            name: 'Habit Master',
            description: 'Complete 500 total habit instances',
            icon: 'fas fa-star',
            rarity: 'epic',
            points: 400,
            progress: 87,
            maxProgress: 500,
            earned: false
        }
    ],
    perfection: [
        {
            id: 'perfect_day',
            name: 'Perfect Day',
            description: 'Complete all habits in a single day',
            icon: 'fas fa-sun',
            rarity: 'rare',
            points: 100,
            progress: 1,
            maxProgress: 1,
            earned: true,
            earnedDate: '2025-01-11'
        },
        {
            id: 'perfect_week',
            name: 'Perfect Week',
            description: 'Maintain 100% completion for 7 consecutive days',
            icon: 'fas fa-calendar-week',
            rarity: 'epic',
            points: 350,
            progress: 3,
            maxProgress: 7,
            earned: false
        },
        {
            id: 'perfect_month',
            name: 'Perfect Month',
            description: 'Achieve 100% completion for an entire month',
            icon: 'fas fa-medal',
            rarity: 'legendary',
            points: 1000,
            progress: 0,
            maxProgress: 30,
            earned: false
        }
    ],
    consistency: [
        {
            id: 'weekly_warrior',
            name: 'Weekly Warrior',
            description: 'Complete habits for 7 consecutive days',
            icon: 'fas fa-shield-alt',
            rarity: 'rare',
            points: 150,
            progress: 7,
            maxProgress: 7,
            earned: true,
            earnedDate: '2025-01-14'
        },
        {
            id: 'monthly_master',
            name: 'Monthly Master',
            description: 'Stay active for 30 days in a month',
            icon: 'fas fa-calendar-alt',
            rarity: 'epic',
            points: 300,
            progress: 16,
            maxProgress: 30,
            earned: false
        },
        {
            id: 'never_miss',
            name: 'Never Miss',
            description: 'Complete habits without missing a day for 60 days',
            icon: 'fas fa-bullseye',
            rarity: 'legendary',
            points: 750,
            progress: 16,
            maxProgress: 60,
            earned: false
        }
    ],
    categories: [
        {
            id: 'health_hero',
            name: 'Health Hero',
            description: 'Complete 50 health-related habits',
            icon: 'fas fa-heart',
            rarity: 'rare',
            points: 200,
            progress: 32,
            maxProgress: 50,
            earned: false
        },
        {
            id: 'learning_legend',
            name: 'Learning Legend',
            description: 'Complete 30 learning habits',
            icon: 'fas fa-book',
            rarity: 'epic',
            points: 250,
            progress: 18,
            maxProgress: 30,
            earned: false
        },
        {
            id: 'fitness_fanatic',
            name: 'Fitness Fanatic',
            description: 'Complete 40 fitness habits',
            icon: 'fas fa-dumbbell',
            rarity: 'rare',
            points: 180,
            progress: 25,
            maxProgress: 40,
            earned: false
        },
        {
            id: 'mindful_master',
            name: 'Mindful Master',
            description: 'Complete 25 mindfulness habits',
            icon: 'fas fa-leaf',
            rarity: 'epic',
            points: 220,
            progress: 15,
            maxProgress: 25,
            earned: false
        },
        {
            id: 'productivity_pro',
            name: 'Productivity Pro',
            description: 'Complete 35 productivity habits',
            icon: 'fas fa-chart-line',
            rarity: 'rare',
            points: 190,
            progress: 22,
            maxProgress: 35,
            earned: false
        }
    ]
};

// Special achievements that don't fit in main categories
const specialAchievements = [
    {
        id: 'early_bird',
        name: 'Early Bird',
        description: 'Complete 10 habits before 8 AM',
        icon: 'fas fa-sun',
        rarity: 'rare',
        points: 120,
        progress: 6,
        maxProgress: 10,
        earned: false,
        category: 'special'
    },
    {
        id: 'night_owl',
        name: 'Night Owl',
        description: 'Complete 10 habits after 10 PM',
        icon: 'fas fa-moon',
        rarity: 'rare',
        points: 120,
        progress: 3,
        maxProgress: 10,
        earned: false,
        category: 'special'
    },
    {
        id: 'weekend_warrior',
        name: 'Weekend Warrior',
        description: 'Maintain habits on weekends for 4 consecutive weeks',
        icon: 'fas fa-calendar-weekend',
        rarity: 'epic',
        points: 280,
        progress: 2,
        maxProgress: 4,
        earned: false,
        category: 'special'
    }
];

let currentFilter = 'all';
let currentCategory = 'all';
let searchTerm = '';

function initializeAchievements() {
    calculateAndUpdateStats();
    renderAchievements();
    updateRecentAchievements();
}

function calculateAndUpdateStats() {
    const allAchievements = getAllAchievements();
    const earnedAchievements = allAchievements.filter(a => a.earned);
    const totalXP = earnedAchievements.reduce((sum, a) => sum + a.points, 0);
    const totalAchievements = earnedAchievements.length;

    // Calculate level based on XP (every 500 XP = 1 level)
    const level = Math.floor(totalXP / 500) + 1;
    const currentLevelXP = totalXP % 500;
    const nextLevelXP = 500;
    const progressPercent = (currentLevelXP / nextLevelXP) * 100;

    // Find rarest achievement
    const rarityOrder = { 'common': 1, 'rare': 2, 'epic': 3, 'legendary': 4 };
    const rarestAchievement = earnedAchievements.reduce((rarest, current) => {
        return rarityOrder[current.rarity] > rarityOrder[rarest.rarity] ? current : rarest;
    }, earnedAchievements[0] || { rarity: 'common' });

    // Get current streak (mock data)
    const currentStreak = 12;

    // Update stats in UI
    document.querySelector('.stats-grid .stat-card:nth-child(1) h3').textContent = totalAchievements;
    document.querySelector('.stats-grid .stat-card:nth-child(2) h3').textContent = totalXP.toLocaleString();
    document.querySelector('.stats-grid .stat-card:nth-child(3) h3').textContent = `🔥 ${currentStreak}`;
    document.querySelector('.stats-grid .stat-card:nth-child(4) h3').textContent = capitalizeFirst(rarestAchievement.rarity);

    // Update level display
    document.querySelector('.level-number').textContent = level;
    document.querySelector('.level-title').textContent = getLevelTitle(level);
    document.querySelector('.level-info h2').textContent = `Level ${level} - ${getLevelTitle(level)}`;
    document.querySelector('.progress-fill').style.width = `${progressPercent}%`;
    document.querySelector('.xp-info').textContent = `${currentLevelXP.toLocaleString()} / ${nextLevelXP.toLocaleString()} XP to next level`;
}

function getLevelTitle(level) {
    if (level >= 20) return 'Habit Legend';
    if (level >= 15) return 'Habit Hero';
    if (level >= 10) return 'Habit Master';
    if (level >= 5) return 'Habit Warrior';
    return 'Habit Novice';
}

function getAllAchievements() {
    const all = [];
    Object.values(achievementsData).forEach(category => {
        all.push(...category);
    });
    all.push(...specialAchievements);
    return all;
}

function getFilteredAchievements() {
    let achievements = getAllAchievements();

    // Filter by category
    if (currentCategory !== 'all') {
        if (achievementsData[currentCategory]) {
            achievements = achievementsData[currentCategory];
        } else if (currentCategory === 'special') {
            achievements = specialAchievements;
        }
    }

    // Filter by status
    if (currentFilter !== 'all') {
        achievements = achievements.filter(achievement => {
            switch (currentFilter) {
                case 'earned':
                    return achievement.earned;
                case 'in-progress':
                    return !achievement.earned && achievement.progress > 0;
                case 'locked':
                    return !achievement.earned && achievement.progress === 0;
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

    grid.innerHTML = achievements.map(achievement => createAchievementCard(achievement)).join('');
}

function createAchievementCard(achievement) {
    const progressPercent = (achievement.progress / achievement.maxProgress) * 100;
    const isEarned = achievement.earned;
    const isInProgress = !isEarned && achievement.progress > 0;
    const isLocked = !isEarned && achievement.progress === 0;

    let statusClass = '';
    if (isEarned) statusClass = 'earned';
    else if (isLocked) statusClass = 'locked';

    return `
        <div class="achievement-card ${statusClass}" data-achievement-id="${achievement.id}">
            <div class="achievement-header">
                <div class="achievement-icon ${achievement.rarity}">
                    <i class="${achievement.icon}"></i>
                </div>
                <div class="achievement-info">
                    <h4>${achievement.name}</h4>
                    <div class="achievement-rarity ${achievement.rarity}">
                        ${capitalizeFirst(achievement.rarity)}
                    </div>
                    <div class="achievement-points">
                        <i class="fas fa-star"></i>
                        ${achievement.points} XP
                    </div>
                </div>
            </div>
            
            <div class="achievement-description">
                ${achievement.description}
            </div>
            
            ${!isEarned ? `
                <div class="achievement-progress">
                    <div class="progress-header">
                        <span class="progress-text">Progress</span>
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
    const allAchievements = getAllAchievements();
    const recentEarned = allAchievements
        .filter(a => a.earned)
        .sort((a, b) => new Date(b.earnedDate) - new Date(a.earnedDate))
        .slice(0, 3);

    const timeline = document.querySelector('.timeline');
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

function shareAchievement(achievementId) {
    const achievement = getAllAchievements().find(a => a.id === achievementId);
    if (!achievement) return;

    if (navigator.share) {
        navigator.share({
            title: `I earned the "${achievement.name}" achievement!`,
            text: `Just unlocked "${achievement.name}" in HabitHub! ${achievement.description}`,
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        const text = `I earned the "${achievement.name}" achievement in HabitHub! ${achievement.description}`;
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Achievement details copied to clipboard!');
        });
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