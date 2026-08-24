<?php
// index.php - Doctor Onboarding Form
// Database: hmsc_basaveshwranagara | Table: doctors_form
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Onboarding Form | HMSC Basaveshwaranagar</title>
    <meta name="description" content="Enter details to register a doctor in the HMSC system.">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <header>
        <div class="brand-badge">HMSC Basaveshwaranagar</div>
        <h1>Doctor Onboarding</h1>
        <p class="subtitle">Enter the details to register a doctor in the hospital system</p>
    </header>

    <div id="messageBox"></div>

    <form id="doctorForm" enctype="multipart/form-data">
        
        <!-- SECTION 1: Personal Information -->
        <div class="form-section">
            <h2>Personal Information</h2>
            <div class="grid">
                <div class="input-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" maxlength="100" placeholder="e.g. Dr. Rajesh Sharma" required>
                </div>
                
                <div class="input-group">
                    <label for="gender">Gender <span class="required">*</span></label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                </div>
                
                <div class="input-group">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" readonly required placeholder="Auto-calculated">
                </div>
                
                <div class="input-group">
                    <label for="blood_group">Blood Group</label>
                    <select id="blood_group" name="blood_group" required>
                        <option value="">Select Blood Group</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label for="marital_status">Marital Status</label>
                    <select id="marital_status" name="marital_status" required>
                        <option value="">Select Status</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Contact Information -->
        <div class="form-section">
            <h2>Contact Information</h2>
            <div class="grid">
                <div class="input-group">
                    <label for="mobile_number">Primary Mobile Number <span class="required">*</span></label>
                    <input type="tel" id="mobile_number" name="mobile_number" maxlength="10" pattern="[0-9]{10}" placeholder="Enter 10-digit mobile number" required>
                </div>
                
                <div class="input-group">
                    <label for="alternate_mobile">Alternate Mobile Number</label>
                    <input type="tel" id="alternate_mobile" name="alternate_mobile" maxlength="10" pattern="[0-9]{10}" placeholder="Enter 10-digit alternate mobile">
                </div>
                
                <div class="input-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" maxlength="255" placeholder="e.g. doctor@example.com" required>
                </div>

                <div class="input-group full-width">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Enter residential / clinic address"></textarea>
                </div>

                <div class="input-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" maxlength="50" placeholder="e.g. Bengaluru" required>
                </div>

                <div class="input-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state" maxlength="50" placeholder="e.g. Karnataka" required>
                </div>

                <div class="input-group">
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country" maxlength="50" placeholder="e.g. India" required>
                </div>

                <div class="input-group">
                    <label for="pincode">Pincode</label>
                    <input type="text" id="pincode" name="pincode" maxlength="10" placeholder="e.g. 560079" required>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Professional & Medical Registration Details -->
        <div class="form-section">
            <h2>Professional & Medical Details</h2>
            <div class="grid">
                <div class="input-group">
                    <label for="qualification">Medical Qualification <span class="required">*</span></label>
                    <input type="text" id="qualification" name="qualification" maxlength="100" placeholder="e.g. MBBS, MD, MS, DM" required>
                </div>

                <div class="input-group">
                    <label for="specialization">Specialization <span class="required">*</span></label>
                    <input type="text" id="specialization" name="specialization" maxlength="100" placeholder="e.g. Cardiology, Pediatrics" required>
                </div>

                <div class="input-group">
                    <label for="sub_specialization">Sub-Specialization</label>
                    <input type="text" id="sub_specialization" name="sub_specialization" maxlength="100" placeholder="e.g. Interventional Cardiology">
                </div>

                <div class="input-group">
                    <label for="medical_council">Medical Council</label>
                    <input type="text" id="medical_council" name="medical_council" maxlength="100" placeholder="e.g. Karnataka Medical Council" required>
                </div>

                <div class="input-group">
                    <label for="registration_number">Registration Number <span class="required">*</span></label>
                    <input type="text" id="registration_number" name="registration_number" maxlength="50" placeholder="e.g. KMC-45892" required>
                </div>

                <div class="input-group">
                    <label for="registration_year">Registration Year</label>
                    <input type="text" id="registration_year" name="registration_year" maxlength="4" placeholder="e.g. 2012">
                </div>

                <div class="input-group">
                    <label for="experience_years">Experience (Years) <span class="required">*</span></label>
                    <input type="number" id="experience_years" name="experience_years" min="0" max="60" placeholder="e.g. 10" required>
                </div>
            </div>
        </div>

        <!-- SECTION 4: Availability, Photo, Certificates & Signature -->
        <div class="form-section" style="border-bottom: none; padding-bottom: 0;">
            <h2>Availability, Photo, Certificates & Signature</h2>
            <div class="grid">
                <div class="input-group">
                    <label for="emergency_available">Emergency Available</label>
                    <select id="emergency_available" name="emergency_available" required>
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="photo">Doctor Profile Photo <span class="label-hint">(Image)</span></label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                </div>

                <div class="input-group">
                    <label for="license_document">License Document <span class="label-hint">(PDF / Image)</span></label>
                    <input type="file" id="license_document" name="license_document" accept=".pdf,image/*,.doc,.docx">
                </div>

                <div class="input-group">
                    <label for="KMC_Certificate">KMC Certificate <span class="label-hint">(PDF / Image)</span></label>
                    <input type="file" id="KMC_Certificate" name="KMC_Certificate" accept=".pdf,image/*,.doc,.docx">
                </div>

                <div class="input-group">
                    <label for="MBBS_Certificate">MBBS Certificate <span class="label-hint">(PDF / Image)</span></label>
                    <input type="file" id="MBBS_Certificate" name="MBBS_Certificate" accept=".pdf,image/*,.doc,.docx">
                </div>

                <div class="input-group">
                    <label for="MS_MD_Certificate">MS / MD Certificate <span class="label-hint">(PDF / Image)</span></label>
                    <input type="file" id="MS_MD_Certificate" name="MS_MD_Certificate" accept=".pdf,image/*,.doc,.docx">
                </div>

                <div class="input-group">
                    <label for="signature">Digital Signature <span class="label-hint">(Image / Scan)</span></label>
                    <input type="file" id="signature" name="signature" accept="image/*">
                </div>
            </div>
        </div>

        <!-- Confirmation Checkbox -->
        <div class="form-section confirmation-section" style="border-bottom: none; padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
            <div class="confirmation-box">
                <input type="checkbox" id="confirmation_check" name="confirmation_check" required>
                <label for="confirmation_check">Please check all data once. Once saved, it cannot be changed.</label>
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <span class="btn-text">Submit Doctor Details</span>
            <div class="loader"></div>
        </button>

    </form>
</div>

<script src="script.js"></script>
</body>
</html>
