// Habits data
let habits = [];

// Category icons and colors mapping
const categoryIcons = {
    health: 'fa-heart',
    physical: 'fa-dumbbell',
    learning: 'fa-book',
    mindfulness: 'fa-leaf',
    creativity: 'fa-paint-brush',
    productivity: 'fa-tasks',
    social: 'fa-users',
    lifestyle: 'fa-home'
};

// Dynamic greeting based on time
async function updateGreeting() {
    try {
        const response = await fetch('../../includes/get_user_data.php');
        const text = await response.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Server response:', text);
            throw new Error('Invalid server response');
        }

        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch user data');
        }

        const now = new Date();
        const hour = now.getHours();
        const userName = data.user.name;

        let greeting;
        if (hour < 12) {
            greeting = `Good Morning, ${userName.charAt(0).toUpperCase() + userName.slice(1)}!`;
        } else if (hour < 18) {
            greeting = `Good Afternoon, ${userName.charAt(0).toUpperCase() + userName.slice(1)}!`;
        } else {
            greeting = `Good Evening, ${userName.charAt(0).toUpperCase() + userName.slice(1)}!`;
        }

        document.getElementById('greeting').textContent = greeting;

        const profileIcon = document.querySelector('.profile-icon');
        if (profileIcon) {
            profileIcon.textContent = userName.charAt(0).toUpperCase();
        }
    } catch (error) {
        console.error('Error updating greeting:', error);
        const hour = new Date().getHours();
        let greeting = 'Good ';
        if (hour < 12) greeting += 'Morning';
        else if (hour < 18) greeting += 'Afternoon';
        else greeting += 'Evening';

        document.getElementById('greeting').textContent = `${greeting}, Guest!`;

        const profileIcon = document.querySelector('.profile-icon');
        if (profileIcon) {
            profileIcon.textContent = 'G';
        }
    }
}

// Update current date
function updateDate() {
    const now = new Date();
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', options);
}

// Toggle habit completion
async function toggleHabit(element) {
    const checkIcon = element.querySelector('.fas.fa-check');
    const habitCard = element.closest('.habit-card');
    const habitId = habitCard.dataset.habitId;
    const habit = habits.find(h => h.id === parseInt(habitId));
    const today = new Date().getDay();
    const completed = !element.classList.contains('completed');

    console.log('Toggle habit debug:', {
        habitId: habitId,
        habitFound: !!habit,
        habitsCount: habits.length,
        habitIds: habits.map(h => h.id)
    });

    if (!habit) {
        console.error('Habit not found in habits array. Refreshing habits...');
        await fetchHabits(); // Refresh habits from server
        return;
    }

    try {
        const response = await fetch('../../includes/toggle_habit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                habitId: habitId,
                completed: completed,
                date: new Date().toISOString().split('T')[0]
            })
        });

        const responseText = await response.text();
        console.log('Raw toggle response:', responseText); // Debug log

        let data;
        try {
            data = JSON.parse(responseText);
            console.log('Parsed toggle response:', data); // Debug log
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', responseText);
            throw new Error('Invalid JSON response from server');
        }

        if (data.success) {
            if (completed) {
                element.classList.add('completed');
                checkIcon.style.display = 'block';
                habit.weekProgress[today] = true;
                habit.completedDays++;
                habit.currentStreak = data.currentStreak;

                element.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                }, 200);

                // Check if all habits are completed (from server response)
                if (data.allHabitsCompleted) {
                    console.log('All habits completed! Global streak:', data.globalStreak); // Debug log
                    showStreakAnimation(data.globalStreak);
                }

                playSound('complete');
            } else {
                element.classList.remove('completed');
                checkIcon.style.display = 'none';
                habit.weekProgress[today] = false;
                habit.completedDays--;
                habit.currentStreak = data.currentStreak;

                playSound('incomplete');
            }

            updateHabitProgress(habitCard, habit);
            updateStats();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error toggling habit:', error);
        alert('Failed to update habit. Please try again.');
        // Revert visual changes if the server update failed
        element.classList.toggle('completed');
        checkIcon.style.display = element.classList.contains('completed') ? 'block' : 'none';
    }
}

// Play sound effects
function playSound(type) {
    if (type === 'complete') {
        console.log('Playing completion sound');
    } else {
        console.log('Playing incomplete sound');
    }
}

// Show streak animation when all habits are completed
function showStreakAnimation(currentStreak) {
    const streakCard = document.querySelectorAll('.stat-card')[1];

    // Create and show the streak celebration overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  `;

    const message = document.createElement('div');
    message.style.cssText = `
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    text-align: center;
    animation: bounceIn 0.5s;
    max-width: 90%;
    width: 400px;
  `;

    // Get current achievement level
    const currentAchievement = calculateAchievementLevel(currentStreak);
    const nextAchievement = getNextAchievement(currentStreak);

    const streakText = currentStreak > 1 ?
        `🔥 ${currentStreak} Day Win Streak! 🔥` :
        `🎉 Streak Started! 🎉`;

    let achievementProgress = '';
    if (nextAchievement) {
        const daysToNext = nextAchievement.days - currentStreak;
        achievementProgress = `
      <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
        <p style="color: #666;">Current Level: ${currentAchievement.name}</p>
        <p style="color: #666;">Next Achievement: ${nextAchievement.name}</p>
        <p style="color: #666;">${daysToNext} days to go!</p>
        <div style="background: #eee; height: 8px; border-radius: 4px; margin-top: 0.5rem;">
          <div style="background: ${currentAchievement.color}; width: ${(currentStreak / nextAchievement.days) * 100}%; height: 100%; border-radius: 4px; transition: width 0.3s ease;"></div>
        </div>
      </div>
    `;
    }

    message.innerHTML = `
    <h2>${streakText}</h2>
    <p>Amazing! You've completed all habits today!</p>
    ${currentStreak > 0 ? `<p>Keep going! You're on fire! 🔥</p>` : ''}
    ${achievementProgress}
  `;

    overlay.appendChild(message);
    document.body.appendChild(overlay);

    // Add bounce animation to streak number in stats
    streakCard.style.animation = 'bounce 0.5s';

    // Remove overlay after 5 seconds (increased from 3 to show achievement progress)
    setTimeout(() => {
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.5s';
        setTimeout(() => {
            document.body.removeChild(overlay);
            streakCard.style.animation = '';
        }, 500);
    }, 5000);
}

// Get next achievement milestone
function getNextAchievement(currentStreak) {
    const milestones = [
        { days: 3, name: "Star" },
        { days: 5, name: "Superstar" },
        { days: 7, name: "Champion" },
        { days: 50, name: "Hall of Fame" },
        { days: 100, name: "Invincible" },
        { days: 150, name: "Legend" },
        { days: 200, name: "Golden" },
        { days: 250, name: "Visionary" },
        { days: 300, name: "Supreme" },
        { days: 365, name: "Interstellar" },
        { days: 500, name: "Galactic" }
    ];

    for (const milestone of milestones) {
        if (currentStreak < milestone.days) {
            return milestone;
        }
    }
    return null; // No next achievement (reached maximum)
}


// Update streak
function updateStreak(habit) {
    const today = new Date().getDay();
    let streak = 0;

    for (let i = today; i >= 0; i--) {
        if (habit.weekProgress[i]) {
            streak++;
        } else {
            break;
        }
    }

    if (habit.weekProgress[today]) {
        for (let i = 6; i > today; i--) {
            if (habit.weekProgress[i]) {
                streak++;
            } else {
                break;
            }
        }
    }

    habit.currentStreak = streak;
}

// Update habit progress
function updateHabitProgress(habitCard, habit) {
    const percentage = Math.round((habit.completedDays / 7) * 100);
    const progressSection = habitCard.querySelector('.progress-section');
    const completionRate = progressSection.querySelector('.completion-rate');
    const weekProgress = progressSection.children[1];

    completionRate.textContent = `${habit.completedDays}/7 completed`;
    weekProgress.textContent = `${percentage}% this week`;

    // Update streak badge
    const streakBadge = habitCard.querySelector('.streak-badge');
    streakBadge.innerHTML = `<i class="fas fa-fire"></i> ${habit.currentStreak} days`;
}

// Get global streak from server
async function getGlobalStreak() {
    try {
        const response = await fetch('../../includes/get_global_streak.php');
        const data = await response.json();

        if (data.success) {
            return {
                currentStreak: data.current_streak,
                bestStreak: data.best_streak,
                lastCompletedDate: data.last_completion_date
            };
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error fetching global streak:', error);
        return {
            currentStreak: 0,
            bestStreak: 0,
            lastCompletedDate: null
        };
    }
}

const achievementLevels = [
    { days: 0, name: "Beginner", icon: "fa-seedling", color: "#9ca3af" },
    { days: 3, name: "Star", icon: "fa-star", color: "#f59e0b" },
    { days: 5, name: "Superstar", icon: "fa-stars", color: "#8b5cf6" },
    { days: 7, name: "Champion", icon: "fa-trophy", color: "#6366f1" },
    { days: 50, name: "Hall of Fame", icon: "fa-medal", color: "#f59e0b" },
    { days: 100, name: "Invincible", icon: "fa-shield-halved", color: "#06b6d4" },
    { days: 150, name: "Legend", icon: "fa-crown", color: "#84cc16" },
    { days: 200, name: "Golden", icon: "fa-award", color: "#eab308" },
    { days: 250, name: "Visionary", icon: "fa-eye", color: "#14b8a6" },
    { days: 300, name: "Supreme", icon: "fa-gem", color: "#ec4899" },
    { days: 365, name: "Interstellar", icon: "fa-planet-ringed", color: "#8b5cf6" },
    { days: 500, name: "Galactic", icon: "fa-galaxy", color: "#6366f1" }
];

// Calculate achievement level based on best streak
function calculateAchievementLevel(streak) {
    // Find the highest achievement level that the user qualifies for
    for (let i = achievementLevels.length - 1; i >= 0; i--) {
        if (streak >= achievementLevels[i].days) {
            return achievementLevels[i];
        }
    }
    return achievementLevels[0];
}

function getNextAchievement(streak) {
    // Find the next achievement level that the user hasn't reached yet
    for (let i = 0; i < achievementLevels.length; i++) {
        if (streak < achievementLevels[i].days) {
            return achievementLevels[i];
        }
    }
    return null; // Return null if user has reached the highest level
}


// Update stats dynamically
async function updateStats() {
    // Only run on dashboard page (check if stat cards exist)
    const statCards = document.querySelectorAll('.stat-card h3');
    if (statCards.length === 0) return;

    try {
        const globalStreak = await getGlobalStreak();
        console.log('Global streak data:', globalStreak); // Debug log

        const stats = {
            activeHabits: habits.length,
            currentStreak: globalStreak.currentStreak,
            weeklyProgress: Math.round(habits.reduce((acc, h) => acc + (h.completedDays / 7), 0) / habits.length * 100),
            achievementLevel: calculateAchievementLevel(globalStreak.bestStreak)
        };

        console.log('Calculated stats:', stats); // Debug log

        // Update UI
        statCards[0].textContent = stats.activeHabits;
        statCards[1].textContent = stats.currentStreak;
        statCards[2].textContent = stats.weeklyProgress + '%';

        // Update achievement level with Font Awesome icon
        const achievement = stats.achievementLevel;
        statCards[3].innerHTML = `
      <i class="fas ${achievement.icon}" style="color: ${achievement.color}; font-size: 28px;"></i>
    `;
        const achievementCard = statCards[3].closest('.stat-card');
        achievementCard.querySelector('p').textContent = achievement.name;

        // Add tooltip with achievement info
        const nextAchievement = getNextAchievement(globalStreak.currentStreak);
        if (nextAchievement) {
            const daysToNext = nextAchievement.days - globalStreak.currentStreak;
            achievementCard.title = `${achievement.name} - Next: ${nextAchievement.name} (${daysToNext} days to go)`;
        } else {
            achievementCard.title = `${achievement.name} - Maximum level achieved!`;
        }
    } catch (error) {
        console.error('Error updating stats:', error);
    }
}

// Render habits
function renderHabits() {
    const habitsGrid = document.getElementById('habitsGrid');
    if (!habitsGrid) return; // Exit if element doesn't exist (e.g., on habits page)
    habitsGrid.innerHTML = '';

    habits.forEach(habit => {
        const today = new Date().getDay();
        const weekDays = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

        let weekProgressHTML = '';
        habit.weekProgress.forEach((completed, index) => {
            const isToday = index === today;
            const completedClass = completed ? 'completed' : '';
            const todayClass = isToday ? 'today' : '';

            weekProgressHTML += `
        <div>
          <div class="day-label">${weekDays[index]}</div>
          <div class="day-check ${completedClass} ${todayClass}" ${isToday ? 'onclick="toggleHabit(this)"' : ''}>
            <i class="fas fa-check" style="display: ${completed ? 'block' : 'none'};"></i>
          </div>
        </div>
      `;
        });

        const percentage = Math.round((habit.completedDays / 7) * 100);

        const habitCard = `
      <div class="habit-card ${habit.category}" data-habit-id="${habit.id}">
        <div class="habit-header">
          <div class="habit-title">
            <div class="habit-icon">
              <i class="fas ${habit.icon}"></i>
            </div>
            <div>
              <h4>${habit.name.charAt(0).toUpperCase() + habit.name.slice(1)}</h4>
              <span>${habit.category.charAt(0).toUpperCase() + habit.category.slice(1)}</span>
            </div>
          </div>
          <div class="streak-badge">
            <i class="fas fa-fire"></i>
            ${habit.currentStreak} days
          </div>
        </div>
        <div class="week-progress">
          <div class="week-days">
            ${weekProgressHTML}
          </div>
        </div>
        <div class="progress-section">
          <span class="completion-rate">${habit.completedDays}/7 completed</span>
          <span>${percentage}% this week</span>
        </div>
      </div>
    `;

        habitsGrid.innerHTML += habitCard;
    });
}

// Modal functions
function openAddHabitModal() {
    const modal = new bootstrap.Modal(document.getElementById('addHabitModal'));
    modal.show();
}

async function addNewHabit() {
    const nameInput = document.getElementById('habitName');
    const categorySelect = document.getElementById('habitCategory');

    if (!nameInput.value.trim() || !categorySelect.value) {
        alert('Please fill in all fields');
        return;
    }

    try {
        const response = await fetch('../../includes/add_habit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: nameInput.value.trim(),
                category: categorySelect.value,
                icon: categoryIcons[categorySelect.value]
            })
        });

        const data = await response.json();
        if (data.success) {
            // Ensure the habit ID is an integer
            data.habit.id = parseInt(data.habit.id);
            habits.push(data.habit);

            console.log('Added new habit:', data.habit); // Debug log

            nameInput.value = '';
            categorySelect.selectedIndex = 0;

            const modal = bootstrap.Modal.getInstance(document.getElementById('addHabitModal'));
            modal.hide();

            renderHabits();
            updateStats();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error adding habit:', error);
        alert('Failed to add habit. Please try again.');
    }
}

// Daily notification prompt
function showDailyPrompt() {
    const lastPrompt = localStorage.getItem('lastDailyPrompt');
    const today = new Date().toDateString();

    if (lastPrompt !== today) {
        setTimeout(() => {
            if (confirm('🌟 Ready to crush your habits today? Check in now!')) {
                const firstIncomplete = document.querySelector('.day-check.today:not(.completed)');
                if (firstIncomplete) {
                    firstIncomplete.scrollIntoView({ behavior: 'smooth' });
                    firstIncomplete.style.animation = 'pulse 2s infinite';
                }
            }
            localStorage.setItem('lastDailyPrompt', today);
        }, 2000);
    }
}

// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('open');
}

// Logout functionality
async function handleLogout(event) {
    event.preventDefault();

    try {
        const response = await fetch('../../includes/logout.php');
        const data = await response.json();

        if (data.success) {
            alert('Logged out successfully');
            window.location.href = '../auth/signin.html';
        } else {
            throw new Error(data.message || 'Logout failed');
        }
    } catch (error) {
        console.error('Logout error:', error);
        alert('Error during logout. Please try again.');
    }
}

// Fetch habits from server
async function fetchHabits() {
    // Only run on dashboard page (check if habitsGrid exists)
    if (!document.getElementById('habitsGrid')) return;

    try {
        const response = await fetch('../../includes/get_habits.php');
        const text = await response.text();
        console.log('Raw response:', text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON:', text);
            throw new Error('Invalid JSON response from server');
        }

        console.log('Parsed response:', data);

        if (data.success) {
            habits = data.habits;
            renderHabits();
            updateStats();
        } else {
            if (data.debug) {
                console.log('Debug info:', data.debug);
            }
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error fetching habits:', error);
        alert('Failed to load habits. Please try refreshing the page. Check console for details.');
    }
}

// Initialize app
document.addEventListener('DOMContentLoaded', function () {
    updateGreeting();
    updateDate();
    fetchHabits(); // This now has its own check for dashboard page

    // Only run dashboard-specific code if we're on the dashboard page
    if (document.getElementById('habitsGrid')) {
        showDailyPrompt();

        const todayHabits = document.querySelectorAll('.day-check.today:not(.completed)');
        todayHabits.forEach(habit => {
            habit.style.borderColor = '#8b5cf6';
            habit.style.borderWidth = '3px';
        });
    }
});