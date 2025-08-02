// Update greeting with current time and user name
function updateGreeting() {
    const userName = localStorage.getItem('userName') || 'User';
    document.getElementById('user-name').textContent = userName;

    const now = new Date();
    const hour = now.getHours();
    let greeting = '';

    if (hour < 12) greeting = 'Good morning';
    else if (hour < 17) greeting = 'Good afternoon';
    else greeting = 'Good evening';

    document.getElementById('greeting-message').textContent = `${greeting}! Let's manage your habits`;
    
    // Update date and time
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
    document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US');
}

// Handle habit form submission
document.getElementById('add-habit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const habitName = document.getElementById('habit-name').value;
    const frequency = document.getElementById('habit-frequency').value;
    
    addHabit(habitName, frequency);
    this.reset();
});

// Add new habit
function addHabit(name, frequency) {
    const habits = getHabits();
    const newHabit = {
        id: Date.now(),
        name: name,
        frequency: frequency,
        createdAt: new Date().toISOString(),
        streak: 0,
        lastCompleted: null
    };
    
    habits.push(newHabit);
    localStorage.setItem('habits', JSON.stringify(habits));
    displayHabits();
}

// Get habits from localStorage
function getHabits() {
    return JSON.parse(localStorage.getItem('habits')) || [];
}

// Delete habit
function deleteHabit(id) {
    const habits = getHabits();
    const updatedHabits = habits.filter(habit => habit.id !== id);
    localStorage.setItem('habits', JSON.stringify(updatedHabits));
    displayHabits();
}

// Display habits
function displayHabits(filter = 'all') {
    const habitsGrid = document.getElementById('habits-grid');
    const habits = getHabits();
    
    const filteredHabits = filter === 'all' 
        ? habits 
        : habits.filter(habit => habit.frequency === filter);
    
    habitsGrid.innerHTML = filteredHabits.map(habit => `
        <div class="habit-card">
            <div class="habit-meta">
                <h3 class="habit-title">${habit.name}</h3>
                <span class="habit-frequency">${habit.frequency}</span>
            </div>
            <p class="habit-streak">
                <i class="fas fa-fire"></i> ${habit.streak} day streak
            </p>
            <div class="habit-actions">
                <button class="habit-button complete-button" onclick="markHabitComplete(${habit.id})">
                    <i class="fas fa-check"></i> Complete
                </button>
                <button class="habit-button delete-button" onclick="deleteHabit(${habit.id})">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    `).join('');
}

// Get badge color based on frequency
function getFrequencyColor(frequency) {
    switch(frequency) {
        case 'daily': return 'primary';
        case 'weekly': return 'success';
        case 'monthly': return 'warning';
        default: return 'secondary';
    }
}

// Mark habit as complete
function markHabitComplete(id) {
    const habits = getHabits();
    const habit = habits.find(h => h.id === id);
    
    if (habit) {
        const today = new Date().toDateString();
        if (habit.lastCompleted !== today) {
            habit.streak++;
            habit.lastCompleted = today;
            localStorage.setItem('habits', JSON.stringify(habits));
            displayHabits();
        }
    }
}

// Handle habit filters
document.querySelectorAll('.habit-filters button').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('.habit-filters button').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        displayHabits(this.dataset.filter);
    });
});

// Initialize
// Update stats
function updateStats() {
    const habits = getHabits();
    const today = new Date().toDateString();
    
    // Total habits
    document.getElementById('total-habits').textContent = habits.length;
    
    // Completed today
    const completedToday = habits.filter(h => h.lastCompleted === today).length;
    document.getElementById('total-completed').textContent = completedToday;
    
    // Longest streak
    const longestStreak = habits.reduce((max, h) => Math.max(max, h.streak), 0);
    document.getElementById('longest-streak').textContent = longestStreak;
    
    // Success rate
    const successRate = habits.length ? Math.round((completedToday / habits.length) * 100) : 0;
    document.getElementById('success-rate').textContent = successRate + '%';
}

document.addEventListener('DOMContentLoaded', function() {
    updateGreeting();
    displayHabits();
    updateStats();
    
    // Update time and stats every second
    setInterval(() => {
        updateGreeting();
        updateStats();
    }, 1000);
});
