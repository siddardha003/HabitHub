# Requirements Document

## Introduction

This feature standardizes the week definition across all pages of the HabitHub application to use Sunday-Saturday as the consistent week structure. While the calendar and dashboard pages already use Sunday-Saturday weeks correctly, the analytics page currently has inconsistent week calculations that need to be aligned. This standardization will ensure all weekly calculations, displays, and user interactions follow the same Sunday-Saturday week pattern across analytics, dashboard, calendar, and any other pages.

## Requirements

### Requirement 1

**User Story:** As a user, I want the analytics page weekly progress to use the same Sunday-Saturday week definition as other pages, so that my habit tracking data is consistent across the entire application.

#### Acceptance Criteria

1. WHEN viewing analytics weekly progress chart THEN the system SHALL display weekly progress using Sunday-Saturday weeks
2. WHEN calculating weekly completion rates in analytics THEN the system SHALL use Sunday-Saturday week boundaries
3. WHEN displaying "Days of Week" analytics chart THEN the system SHALL order days starting with Sunday
4. WHEN showing weekly labels in analytics THEN the system SHALL use Sunday as the first day of the week

### Requirement 2

**User Story:** As a user, I want weekly calculations (success rates, streaks, progress) to be consistent between analytics and dashboard pages, so that I can trust the accuracy of my habit tracking data.

#### Acceptance Criteria

1. WHEN calculating weekly success rates in analytics THEN the system SHALL use Sunday-Saturday week boundaries matching dashboard calculations
2. WHEN determining weekly streaks in analytics THEN the system SHALL count consecutive Sunday-Saturday weeks
3. WHEN aggregating weekly habit completions THEN the system SHALL group data by Sunday-Saturday periods
4. WHEN displaying weekly progress charts THEN the system SHALL align data points to Sunday-Saturday weeks

### Requirement 3

**User Story:** As a user, I want the analytics page to show the same week structure as the calendar page, so that the visual representation matches across all features.

#### Acceptance Criteria

1. WHEN viewing analytics weekly charts THEN the system SHALL use the same Sunday-Saturday structure as calendar
2. WHEN calculating "this week" statistics THEN the system SHALL use Sunday-Saturday boundaries
3. WHEN displaying weekly trends THEN the system SHALL align with calendar week definitions
4. WHEN showing weekly comparisons THEN the system SHALL use consistent Sunday-Saturday periods

### Requirement 4

**User Story:** As a developer, I want a centralized week calculation utility, so that all pages use the same logic for week-related operations.

#### Acceptance Criteria

1. WHEN any page needs to calculate week boundaries THEN the system SHALL use a shared utility function
2. WHEN determining which week a date belongs to THEN the system SHALL use consistent Sunday-Saturday logic
3. WHEN formatting week labels or ranges THEN the system SHALL use a standardized format
4. WHEN the analytics page calculates weekly data THEN the system SHALL use the same utility functions as dashboard and calendar pages