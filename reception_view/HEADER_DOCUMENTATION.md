# Reception Dashboard Header - 50-50 Split Layout

## Overview
Created a responsive header UI with a 50-50 split layout for the Reception Dashboard. The left side displays a personalized greeting, and the right side shows available doctors with their specializations fetched from the database.

## Files Created/Modified

### 1. **API Endpoint** - `reception_view/api/get_available_doctors.php`
- Fetches active doctors from the `doctors` table in the `hmsci` database
- Returns JSON response with doctor details (doctor_id, full_name, specialization)
- Includes authentication check for receptionist role
- Proper error handling

### 2. **HTML Structure** - `reception_view/index.php`
- Replaced the old welcome banner with a new 50-50 grid layout
- **Left Section**: Greeting with dynamic time-based message and current date
- **Right Section**: Doctors panel with header and scrollable list
- Fully responsive design

### 3. **CSS Styling** - `reception_view/assets/css/reception_dashboard.css`
- Added comprehensive styles for the header split layout
- **Features**:
  - Gradient background for greeting section with animated pulse effect
  - Clean white panel for doctors list
  - Custom scrollbar styling
  - Doctor item cards with hover effects
  - Avatar circles with initials
  - Smooth animations and transitions
  - Fully responsive breakpoints for all screen sizes

### 4. **JavaScript Logic** - `reception_view/assets/js/dashboard.js`
- Added `loadAvailableDoctors()` function to fetch and display doctors
- Integrated with existing dashboard initialization
- **Features**:
  - Fetches data from API endpoint
  - Generates initials from doctor names for avatars
  - Staggered animation for doctor items
  - Error handling with user-friendly messages
  - Empty state handling
  - HTML escaping for security

## Design Features

### Left Section (Greeting)
- **Background**: Teal gradient (#0FA4AF to #056674)
- **Content**: 
  - Dynamic greeting (Good Morning/Afternoon/Evening)
  - User's full name from session
  - Current date in long format
- **Effects**: 
  - Animated pulse background
  - Hover lift effect
  - Smooth transitions

### Right Section (Doctors Panel)
- **Header**: Light gradient background with doctor icon
- **List**: 
  - Scrollable container (max-height: 180px)
  - Custom styled scrollbar
  - Doctor items with:
    - Avatar circle with initials
    - Doctor name
    - Specialization with stethoscope icon
  - Hover effects on each item
  - Staggered fade-in animations

## Responsive Breakpoints

- **Desktop (1200px+)**: Full 50-50 split, larger text
- **Tablet (992px - 1199px)**: Stacked layout (100% width each)
- **Mobile (768px - 991px)**: Reduced padding and font sizes
- **Small Mobile (< 768px)**: Compact layout with smaller avatars

## Database Schema

**Table**: `doctors`
**Database**: `hmsci`

**Columns Used**:
- `doctor_id` - Unique identifier
- `full_name` - Doctor's full name
- `specialization` - Medical specialization
- `status` - Filter for 'Active' doctors only

## API Response Format

```json
{
  "success": true,
  "data": [
    {
      "doctor_id": "1",
      "full_name": "Dr. John Smith",
      "specialization": "Cardiology"
    },
    ...
  ],
  "count": 5
}
```

## Security Features

1. **Session Authentication**: Checks if user is logged in as Receptionist
2. **SQL Injection Prevention**: Uses prepared statements with PDO
3. **XSS Prevention**: HTML escaping in JavaScript rendering
4. **Error Handling**: Graceful error messages without exposing system details

## Usage

The header automatically loads when the reception dashboard page loads:

1. User logs in as Receptionist
2. Dashboard initializes
3. Greeting updates based on current time
4. API fetches available doctors
5. Doctors list renders with smooth animations

## Customization Options

### Change Colors
Edit CSS variables in `reception_dashboard.css`:
```css
--primary-color: #0FA4AF;
--primary-hover: #0C8A94;
--secondary-teal: #056674;
```

### Adjust Doctor List Height
Modify in CSS:
```css
.doctors-list {
    max-height: 180px; /* Change this value */
}
```

### Change Animation Speed
Modify in JavaScript:
```javascript
const animationDelay = index * 0.1; // Change multiplier
```

## Browser Compatibility

- ✅ Chrome/Edge (Latest)
- ✅ Firefox (Latest)
- ✅ Safari (Latest)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Performance

- Lightweight API calls
- Efficient DOM rendering
- CSS animations (GPU accelerated)
- No external dependencies (except Font Awesome for icons)

## Future Enhancements

1. Real-time updates using WebSocket or polling
2. Click on doctor to view schedule
3. Filter doctors by specialization
4. Show doctor availability status (Available/Busy/Off-duty)
5. Add doctor photos instead of initials
6. Show number of patients waiting for each doctor

---

**Created**: January 26, 2026
**Version**: 1.0
**Author**: GM HMS Development Team
