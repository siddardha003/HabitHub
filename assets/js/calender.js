// Calendar state
let currentDate = new Date();
let selectedDate = null;
let currentView = 'month'; // 'month' or 'week'
let calendarData = {
    habits: [],
    completions: {},
    notes: {},
    stats: {}
};
let visibleHabits = {};
let currentNoteHabit = null;

// Category colors mapping
const categoryColors = {
    health: '#22c55e',
    physical: '#f59e0b',
    learning: '#3b82f6',
    mindfulness: '#8b5cf6',
    creativity: '#ec4899',
    productivity: '#10b981',
    social: '#f97316',
    lifestyle: '#eab308'
};

// Initialize calendar
async function initCalendar() {
    setupEventListeners();
    await loadCalendarData();
    updateView();
}

// Load calendar data from server
async function loadCalendarData() {
    showLoading(true);

    try {
        const month = currentDate.getMonth() + 1;
        const year = currentDate.getFullYear();

        const response = await fetch(`../../includes/get_calendar_data.php?month=${month}&year=${year}`);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Calendar API response:', text); // Debug log

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }

        if (data.success) {
            calendarData = data;

            // Initialize visible habits
            visibleHabits = {};
            data.habits.forEach(habit => {
                visibleHabits[habit.id] = true;
            });

            updateHabitLegend();
            updateMonthlyStats();
        } else {
            console.error('Calendar API error:', data);
            throw new Error(data.message || 'Failed to load calendar data');
        }
    } catch (error) {
        console.error('Error loading calendar data:', error);
        showError('Failed to load calendar data. Please refresh the page.');
    } finally {
        showLoading(false);
    }
}

// Show/hide loading state
function showLoading(show) {
    const loadingState = document.getElementById('loadingState');
    const calendarLayout = document.getElementById('calendarLayout');

    if (show) {
        loadingState.style.display = 'flex';
        calendarLayout.style.display = 'none';
    } else {
        loadingState.style.display = 'none';
        calendarLayout.style.display = 'grid';
    }
}

// Show error message
function showError(message) {
    // Create a simple error notification
    const errorDiv = document.createElement('div');
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ef4444;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        z-index: 1002;
        animation: slideIn 0.3s ease;
    `;
    errorDiv.textContent = message;
    document.body.appendChild(errorDiv);

    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Update month/year display
function updateMonthYear() {
    const monthNames = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    if (currentView === 'month') {
        document.getElementById('monthYear').textContent =
            `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
    } else {
        // Week view - show week range
        const weekStart = getWeekStart(currentDate);
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekEnd.getDate() + 6);

        const startMonth = monthNames[weekStart.getMonth()];
        const endMonth = monthNames[weekEnd.getMonth()];
        const startDay = weekStart.getDate();
        const endDay = weekEnd.getDate();

        if (weekStart.getMonth() === weekEnd.getMonth()) {
            document.getElementById('monthYear').textContent =
                `${startMonth} ${startDay}-${endDay}, ${weekStart.getFullYear()}`;
        } else {
            document.getElementById('monthYear').textContent =
                `${startMonth} ${startDay} - ${endMonth} ${endDay}, ${weekStart.getFullYear()}`;
        }
    }
}

// Get start of week (Sunday)
function getWeekStart(date) {
    const start = new Date(date);
    start.setDate(start.getDate() - start.getDay());
    return start;
}

// Update current view
function updateView() {
    updateMonthYear();

    if (currentView === 'month') {
        generateMonthView();
        document.getElementById('calendarGrid').style.display = 'grid';
        document.getElementById('weekView').style.display = 'none';
    } else {
        generateWeekView();
        document.getElementById('calendarGrid').style.display = 'none';
        document.getElementById('weekView').style.display = 'block';
    }
}

// Generate month view
function generateMonthView() {
    const calendarGrid = document.getElementById('calendarGrid');
    calendarGrid.innerHTML = '';

    // Day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'day-header';
        dayHeader.textContent = day;
        calendarGrid.appendChild(dayHeader);
    });

    // Get first day of month and number of days
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    // Previous month's trailing days
    const prevMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 0);
    for (let i = startingDayOfWeek - 1; i >= 0; i--) {
        const day = prevMonth.getDate() - i;
        const dayElement = createMonthDayElement(day, true, currentDate.getMonth() - 1);
        calendarGrid.appendChild(dayElement);
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = createMonthDayElement(day, false, currentDate.getMonth());
        calendarGrid.appendChild(dayElement);
    }

    // Next month's leading days
    const totalCells = calendarGrid.children.length - 7; // Subtract header cells
    const remainingCells = 42 - totalCells; // 6 rows × 7 columns = 42 cells
    for (let day = 1; day <= remainingCells; day++) {
        const dayElement = createMonthDayElement(day, true, currentDate.getMonth() + 1);
        calendarGrid.appendChild(dayElement);
    }
}

// Create month day element
function createMonthDayElement(day, isOtherMonth, month) {
    const dayElement = document.createElement('div');
    dayElement.className = 'calendar-day';

    if (isOtherMonth) {
        dayElement.classList.add('other-month');
    }

    // Check if it's today
    const today = new Date();
    const dayDate = new Date(currentDate.getFullYear(), month, day);
    if (!isOtherMonth && dayDate.toDateString() === today.toDateString()) {
        dayElement.classList.add('today');
    }

    const dayNumber = document.createElement('div');
    dayNumber.className = 'day-number';
    dayNumber.textContent = day;
    dayElement.appendChild(dayNumber);

    // Add habit indicators and completion bar
    if (!isOtherMonth) {
        const dateKey = formatDateKey(currentDate.getFullYear(), month, day);
        const dayCompletions = calendarData.completions[dateKey] || {};

        const { indicators, completionRate } = createHabitIndicators(dayCompletions, dateKey);
        dayElement.appendChild(indicators);

        const completionBar = createCompletionBar(completionRate);
        dayElement.appendChild(completionBar);

        // Check if it's a perfect day
        if (completionRate === 100 && calendarData.habits.length > 0) {
            dayElement.classList.add('perfect-day');
        }

        // Add daily note preview if exists
        const dailyNote = calendarData.notes[dateKey];
        if (dailyNote) {
            const notePreview = document.createElement('div');
            notePreview.className = 'day-note-preview';
            notePreview.textContent = dailyNote.length > 20 ? dailyNote.substring(0, 20) + '...' : dailyNote;
            notePreview.title = dailyNote;
            dayElement.appendChild(notePreview);
        }
    }

    // Add click event
    dayElement.addEventListener('click', () => {
        if (!isOtherMonth) {
            showDayDetail(currentDate.getFullYear(), month, day);
        }
    });

    return dayElement;
}

// Generate week view - Beautiful cards
function generateWeekView() {
    const weekContent = document.getElementById('weekContent');
    weekContent.innerHTML = '';
    
    const weekStart = getWeekStart(currentDate);
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(date.getDate() + i);
        
        const dateKey = formatDateKey(date.getFullYear(), date.getMonth(), date.getDate());
        const dayCompletions = calendarData.completions[dateKey] || {};
        const dailyNote = calendarData.notes[dateKey] || '';
        
        // Calculate completion rate
        const { indicators, completionRate } = createHabitIndicators(dayCompletions, dateKey);
        const isPerfectDay = completionRate === 100 && calendarData.habits.length > 0;
        
        // Create day card
        const dayCard = document.createElement('div');
        dayCard.className = 'week-day';
        
        const today = new Date();
        if (date.toDateString() === today.toDateString()) {
            dayCard.classList.add('today');
        }
        
        if (isPerfectDay) {
            dayCard.classList.add('perfect-day');
        }
        
        // Create header class
        let headerClass = 'week-day-header';
        if (date.toDateString() === today.toDateString()) {
            headerClass += ' today';
        }
        if (isPerfectDay) {
            headerClass += ' perfect-day';
        }
        
        dayCard.innerHTML = `
            <div class="${headerClass}">
                <div class="week-day-name">${dayNames[i]}</div>
                <div class="week-day-number">${date.getDate()}</div>
                <div class="week-day-date">${monthNames[date.getMonth()]} ${date.getFullYear()}</div>
            </div>
            
            <div class="week-day-content">
                <div class="week-habit-indicators">
                    ${generateWeekHabitDots(dayCompletions, dateKey)}
                </div>
                
                <div class="week-completion-bar">
                    <div class="week-completion-fill" style="width: ${completionRate}%"></div>
                </div>
                
                ${dailyNote ? `
                    <div class="week-note-preview">${dailyNote.length > 50 ? dailyNote.substring(0, 50) + '...' : dailyNote}</div>
                ` : `
                    <div class="week-note-placeholder">No reflection added</div>
                `}
            </div>
        `;
        
        // Add click event to open day detail
        dayCard.addEventListener('click', () => {
            showDayDetail(date.getFullYear(), date.getMonth(), date.getDate());
        });
        
        weekContent.appendChild(dayCard);
    }
}

// Generate habit dots for week view
function generateWeekHabitDots(dayCompletions, dateKey) {
    let dotsHTML = '';
    
    // Convert dateKey to Date object for comparison
    const displayDate = new Date(dateKey);
    
    calendarData.habits.forEach(habit => {
        if (visibleHabits[habit.id]) {
            // Check if habit was created on or before this date
            const habitCreatedDate = new Date(habit.created_at);
            
            // Compare only the date parts (ignore time)
            const habitDateOnly = new Date(habitCreatedDate.getFullYear(), habitCreatedDate.getMonth(), habitCreatedDate.getDate());
            const displayDateOnly = new Date(displayDate.getFullYear(), displayDate.getMonth(), displayDate.getDate());
            
            // Only show habit if it existed on this date
            if (habitDateOnly <= displayDateOnly) {
                const isCompleted = dayCompletions[habit.id] || false;
                const dotClass = isCompleted ? '' : 'incomplete';
                
                dotsHTML += `
                    <div class="week-habit-dot ${habit.category} ${dotClass}" 
                         style="background: ${isCompleted ? (categoryColors[habit.category] || '#666') : 'transparent'}"
                         title="${habit.name}">
                    </div>
                `;
            }
        }
    });
    
    return dotsHTML;
}

// Generate habits list for week view
function generateWeekHabits(dayCompletions) {
    let habitsHTML = '';

    calendarData.habits.forEach(habit => {
        if (visibleHabits[habit.id]) {
            const isCompleted = dayCompletions[habit.id] || false;

            habitsHTML += `
                <div class="week-habit-item ${isCompleted ? 'completed' : ''}">
                    <div class="week-habit-icon" style="background: ${categoryColors[habit.category] || '#666'}">
                        <i class="fas ${habit.icon}"></i>
                    </div>
                    <div class="week-habit-details">
                        <div class="week-habit-name">${habit.name}</div>
                        <div class="week-habit-category">${habit.category}</div>
                    </div>
                    <div class="week-habit-status ${isCompleted ? 'completed' : 'incomplete'}">
                        ${isCompleted ? '✓' : '○'}
                    </div>
                </div>
            `;
        }
    });

    return habitsHTML;
}

// Create habit indicators for month view
function createHabitIndicators(completions, dateKey) {
    const indicators = document.createElement('div');
    indicators.className = 'habit-indicators';

    let completed = 0;
    let total = 0;

    // Convert dateKey to Date object for comparison
    const displayDate = new Date(dateKey);

    // Debug logging for specific dates
    if (dateKey === '2025-08-17' || dateKey === '2025-08-24') {
        console.log(`Debug for ${dateKey}:`, {
            displayDate: displayDate,
            totalHabits: calendarData.habits.length,
            habits: calendarData.habits.map(h => ({
                id: h.id,
                name: h.name,
                created_at: h.created_at,
                createdDate: new Date(h.created_at),
                willShow: new Date(h.created_at) <= displayDate
            }))
        });
    }

    calendarData.habits.forEach(habit => {
        if (visibleHabits[habit.id]) {
            // Check if habit was created on or before this date
            const habitCreatedDate = new Date(habit.created_at);
            
            // Compare only the date parts (ignore time)
            const habitDateOnly = new Date(habitCreatedDate.getFullYear(), habitCreatedDate.getMonth(), habitCreatedDate.getDate());
            const displayDateOnly = new Date(displayDate.getFullYear(), displayDate.getMonth(), displayDate.getDate());
            
            // Only show habit if it existed on this date
            if (habitDateOnly <= displayDateOnly) {
                const dot = document.createElement('div');
                dot.className = `habit-dot ${habit.category}`;
                dot.style.background = categoryColors[habit.category] || '#666';

                if (completions[habit.id]) {
                    completed++;
                } else {
                    dot.classList.add('incomplete');
                    dot.style.background = 'transparent';
                    dot.style.border = `1px solid ${categoryColors[habit.category] || '#ccc'}`;
                }
                total++;

                indicators.appendChild(dot);
            }
        }
    });

    const completionRate = total > 0 ? (completed / total) * 100 : 0;
    return { indicators, completionRate };
}

// Create completion bar
function createCompletionBar(completionRate) {
    const bar = document.createElement('div');
    bar.className = 'completion-bar';

    const fill = document.createElement('div');
    fill.className = 'completion-fill';
    fill.style.width = `${completionRate}%`;

    bar.appendChild(fill);
    return bar;
}

// Format date key for data lookup
function formatDateKey(year, month, day) {
    return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

// Show day detail modal
function showDayDetail(year, month, day) {
    selectedDate = new Date(year, month, day);
    const dateKey = formatDateKey(year, month, day);
    const completions = calendarData.completions[dateKey] || {};
    const dailyNote = calendarData.notes[dateKey] || '';

    // Update modal date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('modalDate').textContent = selectedDate.toLocaleDateString('en-US', options);

    // Populate modal (removed progress section)
    const modalHabits = document.getElementById('modalHabits');
    modalHabits.innerHTML = '';

    // Add habits (only show habits that existed on this date)
    calendarData.habits.forEach(habit => {
        // Check if habit was created on or before this date
        const habitCreatedDate = new Date(habit.created_at);
        
        // Compare only the date parts (ignore time)
        const habitDateOnly = new Date(habitCreatedDate.getFullYear(), habitCreatedDate.getMonth(), habitCreatedDate.getDate());
        const selectedDateOnly = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
        
        // Only show habit if it existed on this date
        if (habitDateOnly <= selectedDateOnly) {
            const habitItem = document.createElement('div');
            habitItem.className = 'modal-habit-item';

            const isCompleted = completions[habit.id] || false;

            habitItem.innerHTML = `
                <div class="habit-icon-modal" style="background: ${categoryColors[habit.category] || '#666'}">
                    <i class="fas ${habit.icon}"></i>
                </div>
                <div class="modal-habit-info">
                    <h4>${habit.name}</h4>
                    <span>${habit.category}</span>
                </div>
                <div class="habit-status">
                    <div class="status-icon ${isCompleted ? 'completed' : 'incomplete'}">
                        <i class="fas fa-${isCompleted ? 'check' : 'times'}"></i>
                    </div>
                </div>
            `;

            modalHabits.appendChild(habitItem);
        }
    });

    // Add daily note section
    const noteSection = document.createElement('div');
    noteSection.className = 'daily-note-section';
    noteSection.innerHTML = `
        <div class="note-section-header">
            <h4><i class="fas fa-sticky-note"></i> Share Your Day!!</h4>
            <button class="note-edit-btn" onclick="openDailyNoteModal('${dateKey}')">
                <i class="fas fa-${dailyNote ? 'edit' : 'plus'}"></i>
                ${dailyNote ? 'Edit Note' : 'Add Note'}
            </button>
        </div>
        ${dailyNote ? `<div class="note-content">${dailyNote}</div>` : '<div class="note-placeholder">Share your thoughts on this day</div>'}
    `;

    modalHabits.appendChild(noteSection);

    // Show modal
    document.getElementById('dayDetailModal').classList.add('active');
}

// Open note modal
function openNoteModal(habit, dateKey) {
    currentNoteHabit = { habit, dateKey };

    const existingNote = calendarData.notes[dateKey] && calendarData.notes[dateKey][habit.id] || '';

    document.getElementById('noteModalTitle').textContent = `Note for ${habit.name}`;
    document.getElementById('habitNoteText').value = existingNote;

    document.getElementById('habitNoteModal').classList.add('active');
    document.getElementById('habitNoteText').focus();
}

// Close note modal
function closeNoteModal() {
    document.getElementById('habitNoteModal').classList.remove('active');
    currentNoteHabit = null;
}

// Save habit note
async function saveHabitNote() {
    if (!currentNoteHabit) return;

    const noteContent = document.getElementById('habitNoteText').value.trim();
    const { habit, dateKey } = currentNoteHabit;

    try {
        const response = await fetch('../../includes/save_habit_note.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                habitId: habit.id,
                date: dateKey,
                noteContent: noteContent
            })
        });

        const data = await response.json();

        if (data.success) {
            // Update local data
            if (!calendarData.notes[dateKey]) {
                calendarData.notes[dateKey] = {};
            }

            if (noteContent) {
                calendarData.notes[dateKey][habit.id] = noteContent;
            } else {
                delete calendarData.notes[dateKey][habit.id];
            }

            // Refresh view
            updateView();

            // Close modals
            closeNoteModal();
            document.getElementById('dayDetailModal').classList.remove('active');

            // Show success message
            showSuccessMessage('Note saved successfully!');
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error saving note:', error);
        showError('Failed to save note. Please try again.');
    }
}

// Show success message
function showSuccessMessage(message) {
    const successDiv = document.createElement('div');
    successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        z-index: 1002;
        animation: slideIn 0.3s ease;
    `;
    successDiv.textContent = message;
    document.body.appendChild(successDiv);

    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}

// Update habit legend
function updateHabitLegend() {
    const legendList = document.getElementById('habitLegendList');
    legendList.innerHTML = '';

    if (calendarData.habits.length === 0) {
        legendList.innerHTML = '<p style="color: #666; font-style: italic;">No habits found. Add some habits to get started!</p>';
        return;
    }

    calendarData.habits.forEach(habit => {
        const habitItem = document.createElement('div');
        habitItem.className = 'habit-item';

        habitItem.innerHTML = `
            <div class="habit-info">
                <div class="habit-color" style="background: ${categoryColors[habit.category] || '#666'};"></div>
                <span class="habit-name">${habit.name.charAt(0).toUpperCase() + habit.name.slice(1)}</span>
            </div>
            <div class="habit-toggle ${visibleHabits[habit.id] ? 'active' : ''}" 
                 data-habit-id="${habit.id}"></div>
        `;

        // Add toggle event
        const toggle = habitItem.querySelector('.habit-toggle');
        toggle.addEventListener('click', () => {
            visibleHabits[habit.id] = !visibleHabits[habit.id];
            toggle.classList.toggle('active');
            updateView();
        });

        legendList.appendChild(habitItem);
    });
}

// Update monthly stats
function updateMonthlyStats() {
    const stats = calendarData.stats;

    document.getElementById('overallProgress').textContent = `${stats.overallProgress}%`;
    document.getElementById('perfectDays').textContent = stats.perfectDays;
    document.getElementById('currentStreak').textContent = `🔥 ${stats.currentStreak} days`;
    document.getElementById('bestHabit').textContent = stats.bestHabit;
    document.getElementById('daysActive').textContent = `${stats.activeDays}/${stats.daysInMonth}`;

    // Update title based on view
    const title = currentView === 'month' ? 'This Month' : 'This Week';
    document.getElementById('statsTitle').textContent = title;
}

// Navigation functions
async function previousPeriod() {
    if (currentView === 'month') {
        currentDate.setMonth(currentDate.getMonth() - 1);
    } else {
        currentDate.setDate(currentDate.getDate() - 7);
    }
    await loadCalendarData();
    updateView();
}

async function nextPeriod() {
    if (currentView === 'month') {
        currentDate.setMonth(currentDate.getMonth() + 1);
    } else {
        currentDate.setDate(currentDate.getDate() + 7);
    }
    await loadCalendarData();
    updateView();
}

async function goToToday() {
    const today = new Date();
    currentDate = new Date(today);
    await loadCalendarData();
    updateView();

    // Open today's modal
    setTimeout(() => {
        showDayDetail(today.getFullYear(), today.getMonth(), today.getDate());
    }, 300);
}

// Switch view
function switchView(view) {
    currentView = view;

    // Update button states
    document.getElementById('monthViewBtn').classList.toggle('active', view === 'month');
    document.getElementById('weekViewBtn').classList.toggle('active', view === 'week');

    updateView();
}

// Setup event listeners
function setupEventListeners() {
    // Navigation buttons
    document.getElementById('prevMonth').addEventListener('click', previousPeriod);
    document.getElementById('nextMonth').addEventListener('click', nextPeriod);
    document.getElementById('todayBtn').addEventListener('click', goToToday);

    // View toggle buttons
    document.getElementById('monthViewBtn').addEventListener('click', () => switchView('month'));
    document.getElementById('weekViewBtn').addEventListener('click', () => switchView('week'));

    // Close modals
    document.getElementById('closeModal').addEventListener('click', () => {
        document.getElementById('dayDetailModal').classList.remove('active');
    });

    document.getElementById('closeDailyNoteModal').addEventListener('click', closeDailyNoteModal);

    // Close modals on background click
    document.getElementById('dayDetailModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('dayDetailModal')) {
            document.getElementById('dayDetailModal').classList.remove('active');
        }
    });

    document.getElementById('dailyNoteModal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('dailyNoteModal')) {
            closeDailyNoteModal();
        }
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (document.getElementById('dayDetailModal').classList.contains('active') ||
            document.getElementById('dailyNoteModal').classList.contains('active')) {
            if (e.key === 'Escape') {
                document.getElementById('dayDetailModal').classList.remove('active');
                closeDailyNoteModal();
            }
        } else {
            switch (e.key) {
                case 'ArrowLeft':
                    previousPeriod();
                    break;
                case 'ArrowRight':
                    nextPeriod();
                    break;
                case 'Home':
                    goToToday();
                    break;
                case 'm':
                case 'M':
                    switchView('month');
                    break;
                case 'w':
                case 'W':
                    switchView('week');
                    break;
            }
        }
    });

    // Touch/swipe support for mobile
    let touchStartX = null;
    let touchStartY = null;

    const calendarContainer = document.querySelector('.calendar-main');

    calendarContainer.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    });

    calendarContainer.addEventListener('touchend', (e) => {
        if (touchStartX === null || touchStartY === null) return;

        const touchEndX = e.changedTouches[0].clientX;
        const touchEndY = e.changedTouches[0].clientY;
        const diffX = touchStartX - touchEndX;
        const diffY = touchStartY - touchEndY;

        // Horizontal swipe detection
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
            if (diffX > 0) {
                nextPeriod(); // Swipe left = next period
            } else {
                previousPeriod(); // Swipe right = previous period
            }
        }

        touchStartX = null;
        touchStartY = null;
    });
}

// Daily note functions
let currentNoteDate = null;

function openDailyNoteModal(dateKey) {
    currentNoteDate = dateKey;

    const existingNote = calendarData.notes[dateKey] || '';
    const date = new Date(dateKey);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };

    document.getElementById('noteModalTitle').textContent = `Share Your Day!! - ${date.toLocaleDateString('en-US', options)}`;
    document.getElementById('dailyNoteText').value = existingNote;

    document.getElementById('dailyNoteModal').classList.add('active');
    document.getElementById('dailyNoteText').focus();
}

function closeDailyNoteModal() {
    document.getElementById('dailyNoteModal').classList.remove('active');
    currentNoteDate = null;
}

async function saveDailyNote() {
    if (!currentNoteDate) return;

    const noteContent = document.getElementById('dailyNoteText').value.trim();
    
    console.log('Saving daily note:', { date: currentNoteDate, noteContent });

    try {
        const response = await fetch('../../includes/save_daily_note.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                date: currentNoteDate,
                noteContent: noteContent
            })
        });

        console.log('Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }

        if (data.success) {
            // Update local data
            if (noteContent) {
                calendarData.notes[currentNoteDate] = noteContent;
            } else {
                delete calendarData.notes[currentNoteDate];
            }

            // Refresh view
            updateView();

            // Close modals
            closeDailyNoteModal();
            document.getElementById('dayDetailModal').classList.remove('active');

            // Show success message
            showSuccessMessage('Daily notes saved successfully!');
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error saving daily note:', error);
        showError('Failed to save reflection. Please try again.');
    }
}

// Initialize calendar when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initCalendar();
});

// Add CSS animations
const styleSheet = document.createElement('style');
styleSheet.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .calendar-grid, .week-view {
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    
    .calendar-day, .week-habit-item {
        transition: all 0.2s ease;
    }
    
    .habit-dot {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .completion-fill {
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
`;
document.head.appendChild(styleSheet);