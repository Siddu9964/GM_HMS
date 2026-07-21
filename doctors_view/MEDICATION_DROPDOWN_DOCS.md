# Medication Dropdown Feature - Documentation

## Overview
The enhanced medication dropdown system allows doctors to select medications by **generic name** (e.g., Paracetamol) and then choose from a list of **brand names** (e.g., Dolo 650, Crocin, Panadol, Calpol).

## Features

### 1. **Smart Search**
- Type the generic name (e.g., "Para" for Paracetamol)
- Dropdown shows matching medications with their categories
- Minimum 2 characters required to trigger search

### 2. **Brand Selection**
- Each generic medication displays all available brands
- Brands are shown with:
  - Brand name (e.g., "Dolo 650")
  - Common dosages (e.g., "500mg, 650mg, 1000mg")
  - Category badge (e.g., "Analgesic/Antipyretic")

### 3. **Auto-Fill**
- Selecting a brand automatically fills:
  - Medication name field with "Brand (Generic)" format
  - Dosage field with the most common dosage
- Focus automatically moves to the next field

### 4. **Visual Design**
- Medical-themed teal color scheme
- Smooth animations and hover effects
- Professional dropdown with clear hierarchy
- Mobile responsive

## How It Works

### User Flow:
1. Click on "Tablet Name" field in the medication table
2. Start typing generic name (e.g., "Paracetamol")
3. Dropdown appears showing:
   ```
   📦 Paracetamol [Analgesic/Antipyretic]
      💊 Dolo 650 (500mg, 650mg, 1000mg)
      💊 Crocin (500mg, 650mg, 1000mg)
      💊 Panadol (500mg, 650mg)
      💊 Calpol (500mg, 650mg)
   ```
4. Click on desired brand (e.g., "Dolo 650")
5. Field fills with "Dolo 650 (Paracetamol)"
6. Dosage auto-fills with "650mg"
7. Continue with frequency, duration, etc.

## Files Created

### 1. **API Endpoint**
- **File**: `doctors_view/api/get_medications.php`
- **Purpose**: Provides comprehensive medication database
- **Returns**: JSON with generic names, brands, categories, and dosages

### 2. **JavaScript Logic**
- **File**: `doctors_view/assets/js/medication-dropdown.js`
- **Functions**:
  - `loadMedicationsDatabase()` - Loads medications from API
  - `handleMedicationSearch()` - Filters and displays matches
  - `selectMedication()` - Handles brand selection
  - `addMedicationRowEnhanced()` - Creates enhanced table rows

### 3. **CSS Styling**
- **File**: `doctors_view/assets/css/medication-dropdown.css`
- **Features**:
  - Dropdown animations
  - Hover effects
  - Medical color scheme
  - Responsive design

## Medication Database

Currently includes 24 common medications:
- **Analgesics**: Paracetamol, Ibuprofen, Tramadol, Diclofenac
- **Antibiotics**: Amoxicillin, Azithromycin, Ciprofloxacin, Levofloxacin
- **Gastrointestinal**: Omeprazole, Pantoprazole, Ranitidine, Domperidone
- **Cardiovascular**: Atorvastatin, Amlodipine, Metformin
- **Respiratory**: Cetirizine, Montelukast, Salbutamol
- **Others**: Prednisolone, Levothyroxine, Vitamin D3, Multivitamins

### Adding More Medications
Edit `doctors_view/api/get_medications.php` and add to the `$medications` array:

```php
[
    'generic_name' => 'Generic Name',
    'brands' => ['Brand 1', 'Brand 2', 'Brand 3'],
    'category' => 'Category Name',
    'common_dosages' => ['10mg', '20mg', '40mg']
]
```

## Integration Points

### 1. **Consultation Page**
- Added CSS link in `<head>`
- Added JS script before `consultation.js`
- Updated "Add Another Medicine" button

### 2. **AI Auto-Complete**
- Automatically uses enhanced dropdown
- Fallback to basic version if not loaded

### 3. **Backward Compatibility**
- Old `addMedicationRow()` function still works
- System checks for enhanced version first
- Graceful fallback if medication API fails

## Browser Compatibility
- ✅ Chrome/Edge (Recommended)
- ✅ Firefox
- ✅ Safari
- ✅ Mobile browsers

## Future Enhancements
1. **Database Integration**: Replace hardcoded array with actual database
2. **Search by Brand**: Allow searching by brand name too
3. **Favorites**: Save frequently prescribed medications
4. **Drug Interactions**: Show warnings for drug combinations
5. **Dosage Calculator**: Auto-calculate based on patient weight/age
6. **Voice Input**: Integrate with existing voice dictation

## Troubleshooting

### Dropdown Not Appearing
- Check browser console for errors
- Verify `medication-dropdown.js` is loaded
- Ensure API endpoint is accessible

### Medications Not Loading
- Check `api/get_medications.php` returns valid JSON
- Verify session authentication
- Check browser network tab for 200 status

### Styling Issues
- Ensure `medication-dropdown.css` is loaded
- Check for CSS conflicts with other stylesheets
- Clear browser cache

## Support
For issues or enhancements, contact the development team.

---
**Version**: 1.0  
**Last Updated**: January 2026  
**Author**: GM HMS Development Team
