// Habits page specific functionality
let habitsData = [];
let habitToDelete = null;

// Category icons mapping (reusing from dashboard.js)
const categoryIconsHabits = {
  health: 'fa-heart',
  physical: 'fa-dumbbell',
  learning: 'fa-book',
  mindfulness: 'fa-leaf',
  creativity: 'fa-paint-brush',
  productivity: 'fa-tasks',
  social: 'fa-users',
  lifestyle: 'fa-home'
};

// Fetch and render habits in table format
async function fetchAndRenderHabitsTable() {
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
      habitsData = data.habits;
      renderHabitsTable();
    } else {
      if (data.debug) {
        console.log('Debug info:', data.debug);
      }
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('Error fetching habits:', error);
    alert('Failed to load habits. Please try refreshing the page.');
  }
}

// Render habits in table format
function renderHabitsTable() {
  const tableBody = document.getElementById('habitsTableBody');
  const mobileCards = document.getElementById('habitsMobileCards');
  const emptyState = document.getElementById('emptyState');
  const tableContainer = document.querySelector('.habits-table-container .table-responsive');

  if (!habitsData || habitsData.length === 0) {
    tableContainer.style.display = 'none';
    mobileCards.style.display = 'none';
    emptyState.style.display = 'block';
    return;
  }

  emptyState.style.display = 'none';
  tableBody.innerHTML = '';
  mobileCards.innerHTML = '';

  habitsData.forEach(habit => {
    const weeklyProgress = Math.round((habit.completedDays / 7) * 100);
    const createdDate = new Date(habit.created_at).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });

    // Desktop table row
    const row = `
      <tr data-habit-id="${habit.id}">
        <td>
          <div class="habit-info-cell">
            <div class="habit-icon-table ${habit.category}">
              <i class="fas ${habit.icon}"></i>
            </div>
            <div class="habit-details">
              <h5>${habit.name.charAt(0).toUpperCase() + habit.name.slice(1)}</h5>
              <span>${habit.category}</span>
            </div>
          </div>
        </td>
        <td>
          <span class="category-badge ${habit.category}">
            ${habit.category}
          </span>
        </td>
        <td>
          <div class="streak-badge-table">
            <i class="fas fa-fire"></i>
            ${habit.currentStreak || 0} days
          </div>
        </td>
        <td>
          <div class="progress-bar-container">
            <div class="progress-bar-custom">
              <div class="progress-bar-fill" style="width: ${weeklyProgress}%"></div>
            </div>
            <div class="progress-text">${habit.completedDays || 0}/7 completed</div>
          </div>
        </td>
        <td>
          <span class="date-display">${createdDate}</span>
        </td>
        <td>
          <div class="action-buttons">
            <button class="btn-action btn-edit" onclick="editHabit(${habit.id})" title="Edit Habit">
              <i class="fas fa-edit"></i>
              Edit
            </button>
            <button class="btn-action btn-delete" onclick="openDeleteModal(${habit.id})" title="Delete Habit">
              <i class="fas fa-trash"></i>
              Delete
            </button>
          </div>
        </td>
      </tr>
    `;

    // Mobile card
    const card = `
      <div class="habit-mobile-card" data-habit-id="${habit.id}">
        <div class="habit-mobile-header">
          <div class="habit-icon-table ${habit.category}">
            <i class="fas ${habit.icon}"></i>
          </div>
          <div class="habit-mobile-info">
            <h5>${habit.name.charAt(0).toUpperCase() + habit.name.slice(1)}</h5>
            <span class="category-badge ${habit.category}">${habit.category}</span>
          </div>
        </div>
        
        <div class="habit-mobile-stats">
          <div class="habit-mobile-stat">
            <div class="habit-mobile-stat-label">Current Streak</div>
            <div class="habit-mobile-stat-value">
              <i class="fas fa-fire" style="color: #ff6b6b; margin-right: 0.25rem;"></i>
              ${habit.currentStreak || 0} days
            </div>
          </div>
          <div class="habit-mobile-stat">
            <div class="habit-mobile-stat-label">Weekly Progress</div>
            <div class="habit-mobile-stat-value">${habit.completedDays || 0}/7 (${weeklyProgress}%)</div>
          </div>
        </div>
        
        <div class="habit-mobile-stat" style="margin-bottom: 1rem;">
          <div class="habit-mobile-stat-label">Created</div>
          <div class="habit-mobile-stat-value">${createdDate}</div>
        </div>
        
        <div class="habit-mobile-actions">
          <button class="btn-action btn-edit" onclick="editHabit(${habit.id})">
            <i class="fas fa-edit"></i>
            Edit
          </button>
          <button class="btn-action btn-delete" onclick="openDeleteModal(${habit.id})">
            <i class="fas fa-trash"></i>
            Delete
          </button>
        </div>
      </div>
    `;

    tableBody.innerHTML += row;
    mobileCards.innerHTML += card;
  });
}

// Open delete confirmation modal
function openDeleteModal(habitId) {
  const habit = habitsData.find(h => h.id === parseInt(habitId));
  if (!habit) {
    console.error('Habit not found');
    return;
  }

  habitToDelete = habit;
  
  // Update modal content
  document.getElementById('deleteHabitName').textContent = habit.name.charAt(0).toUpperCase() + habit.name.slice(1);
  document.getElementById('deleteHabitCategory').textContent = `Category: ${habit.category.charAt(0).toUpperCase() + habit.category.slice(1)}`;

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('deleteHabitModal'));
  modal.show();
}

// Confirm and delete habit
async function confirmDeleteHabit() {
  if (!habitToDelete) {
    console.error('No habit selected for deletion');
    return;
  }

  try {
    const response = await fetch('../../includes/delete_habit.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        habitId: habitToDelete.id
      })
    });

    const data = await response.json();
    
    if (data.success) {
      // Remove habit from local array
      habitsData = habitsData.filter(h => h.id !== habitToDelete.id);
      
      // Re-render table
      renderHabitsTable();
      
      // Hide modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('deleteHabitModal'));
      modal.hide();
      
      // Reset habitToDelete
      habitToDelete = null;
      
      // Show success message
      showNotification('Habit deleted successfully!', 'success');
    } else {
      throw new Error(data.message || 'Failed to delete habit');
    }
  } catch (error) {
    console.error('Error deleting habit:', error);
    showNotification('Failed to delete habit. Please try again.', 'error');
  }
}

// Edit habit functionality
let habitToEdit = null;

function editHabit(habitId) {
  const habit = habitsData.find(h => h.id === parseInt(habitId));
  if (!habit) {
    console.error('Habit not found');
    return;
  }

  habitToEdit = habit;
  
  // Populate the edit form
  document.getElementById('editHabitName').value = habit.name;
  document.getElementById('editHabitCategory').value = habit.category;

  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('editHabitModal'));
  modal.show();
}

// Confirm and update habit
async function confirmEditHabit() {
  if (!habitToEdit) {
    console.error('No habit selected for editing');
    return;
  }

  const nameInput = document.getElementById('editHabitName');
  const categorySelect = document.getElementById('editHabitCategory');

  if (!nameInput.value.trim() || !categorySelect.value) {
    showNotification('Please fill in all fields', 'error');
    return;
  }

  try {
    const response = await fetch('../../includes/edit_habit.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        habitId: habitToEdit.id,
        name: nameInput.value.trim(),
        category: categorySelect.value,
        icon: categoryIconsHabits[categorySelect.value]
      })
    });

    const data = await response.json();
    
    if (data.success) {
      // Update habit in local array
      const habitIndex = habitsData.findIndex(h => h.id === habitToEdit.id);
      if (habitIndex !== -1) {
        habitsData[habitIndex] = {
          ...habitsData[habitIndex],
          name: nameInput.value.trim(),
          category: categorySelect.value,
          icon: categoryIconsHabits[categorySelect.value]
        };
      }
      
      // Re-render table
      renderHabitsTable();
      
      // Hide modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('editHabitModal'));
      modal.hide();
      
      // Reset habitToEdit
      habitToEdit = null;
      
      // Show success message
      showNotification('Habit updated successfully!', 'success');
    } else {
      throw new Error(data.message || 'Failed to update habit');
    }
  } catch (error) {
    console.error('Error updating habit:', error);
    showNotification('Failed to update habit. Please try again.', 'error');
  }
}

// Add new habit (reusing from dashboard.js but adapted for table view)
async function addNewHabitTable() {
  const nameInput = document.getElementById('habitName');
  const categorySelect = document.getElementById('habitCategory');

  if (!nameInput.value.trim() || !categorySelect.value) {
    showNotification('Please fill in all fields', 'error');
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
        icon: categoryIconsHabits[categorySelect.value]
      })
    });

    const data = await response.json();
    if (data.success) {
      // Ensure the habit ID is an integer
      data.habit.id = parseInt(data.habit.id);
      habitsData.push(data.habit);

      console.log('Added new habit:', data.habit);
      
      // Trigger achievement check for habit creation
      if (typeof AchievementTriggers !== 'undefined') {
        AchievementTriggers.habitCreated({
          habit_id: data.habit.id,
          category: data.habit.category,
          name: data.habit.name
        });
      }

      // Clear form
      nameInput.value = '';
      categorySelect.selectedIndex = 0;

      // Hide modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('addHabitModal'));
      modal.hide();

      // Re-render table
      renderHabitsTable();
      
      showNotification('Habit added successfully!', 'success');
    } else {
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('Error adding habit:', error);
    showNotification('Failed to add habit. Please try again.', 'error');
  }
}

// Show notification
function showNotification(message, type = 'info') {
  // Create notification element
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    color: white;
    font-weight: 500;
    z-index: 1050;
    animation: slideIn 0.3s ease;
  `;

  // Set background color based on type
  switch (type) {
    case 'success':
      notification.style.background = '#10b981';
      break;
    case 'error':
      notification.style.background = '#ef4444';
      break;
    default:
      notification.style.background = '#3b82f6';
  }

  notification.textContent = message;
  document.body.appendChild(notification);

  // Remove notification after 3 seconds
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => {
      document.body.removeChild(notification);
    }, 300);
  }, 3000);
}

// Override the addNewHabit function for habits page
function addNewHabit() {
  addNewHabitTable();
}

// Initialize habits page
document.addEventListener('DOMContentLoaded', function() {
  // Only run habits-specific code if we're on the habits page
  if (document.getElementById('habitsTable')) {
    fetchAndRenderHabitsTable();
  }
});

// Add CSS for notifications
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
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
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(100%);
      opacity: 0;
    }
  }
`;
document.head.appendChild(notificationStyles);