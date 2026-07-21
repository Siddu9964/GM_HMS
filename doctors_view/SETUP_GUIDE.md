# Quick Fix Guide - Database Setup

## Problem
The dashboard is showing errors because required database tables don't exist yet.

## Solution - Run This SQL

### Step 1: Open phpMyAdmin
Navigate to: `http://localhost/phpmyadmin`

### Step 2: Select Database
Click on database: **hmsci**

### Step 3: Execute SQL
1. Click on the **SQL** tab
2. Copy the entire contents of `database_setup.sql`
3. Paste into the SQL query box
4. Click **Go** button

### Step 4: Verify
You should see success messages for:
- ✅ consultations table created
- ✅ notifications table created
- ✅ prescriptions table created
- ✅ lab_orders table created
- ✅ lab_results table created
- ✅ Sample doctor inserted
- ✅ Sample notifications inserted

### Step 5: Refresh Dashboard
Go back to: `http://localhost/GM_HMS/doctors_view/dasbord.php`

All errors should be gone!

---

## What This Creates

### 1. Tables (5 new tables)
- `consultations` - SOAP notes and clinical documentation
- `notifications` - Doctor alerts and notifications
- `prescriptions` - Prescription records
- `lab_orders` - Lab test orders
- `lab_results` - Lab test results

### 2. Sample Data
- **Doctor**: Dr. Rajesh Kumar (ID: DOC-20251226-001)
  - Specialization: General Medicine
  - Status: Active
  - Email: rajesh.kumar@gmhms.com

- **Notifications**: 3 sample notifications
  - New appointment
  - Lab report available
  - Follow-up reminder

---

## Alternative: Command Line

If you prefer command line:

```bash
mysql -u root -p hmsci < database_setup.sql
```

---

## Troubleshooting

**Error: Table already exists**
- This is OK! The script uses `CREATE TABLE IF NOT EXISTS`
- It won't overwrite existing data

**Error: Foreign key constraint fails**
- Make sure `patient` and `doctors` tables exist first
- Check that patient_id and doctor_id columns match

**Still seeing errors after setup?**
- Clear browser cache (Ctrl+Shift+Delete)
- Hard refresh (Ctrl+F5)
- Check browser console for new errors
