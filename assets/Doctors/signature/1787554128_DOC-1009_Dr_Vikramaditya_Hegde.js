// script.js - Doctor Registration Client Logic
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('doctorForm');
    const dobInput = document.getElementById('date_of_birth');
    const ageInput = document.getElementById('age');
    const mobileInput = document.getElementById('mobile_number');
    const altMobileInput = document.getElementById('alternate_mobile');
    const msgBox = document.getElementById('messageBox');
    const submitBtn = document.querySelector('.btn-submit');
    const btnText = submitBtn.querySelector('.btn-text');
    const loader = submitBtn.querySelector('.loader');

    // Restrict mobile inputs to only digits and max 10 numbers
    const mobileFields = [mobileInput, altMobileInput];
    mobileFields.forEach(input => {
        if (input) {
            // Block non-digit input on keypress
            input.addEventListener('keypress', (e) => {
                if (!/[0-9]/.test(e.key) || input.value.length >= 10) {
                    e.preventDefault();
                }
            });

            // Strip non-digits and slice to 10 on paste/input
            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
            });
        }
    });

    // Automatically calculate age from DOB
    dobInput.addEventListener('change', () => {
        if (dobInput.value) {
            const dob = new Date(dobInput.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            ageInput.value = age > 0 ? age : 0;
        } else {
            ageInput.value = '';
        }
    });

    // Form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Validate Primary Mobile Number (must be exactly 10 digits)
        const primaryMobile = mobileInput.value.trim();
        if (primaryMobile.length !== 10) {
            msgBox.textContent = 'Primary Mobile Number must be exactly 10 digits.';
            msgBox.className = 'error-msg';
            msgBox.style.display = 'block';
            mobileInput.focus();
            return;
        }

        // Validate Alternate Mobile Number if entered (must be exactly 10 digits)
        const altMobile = altMobileInput.value.trim();
        if (altMobile !== '' && altMobile.length !== 10) {
            msgBox.textContent = 'Alternate Mobile Number must be exactly 10 digits.';
            msgBox.className = 'error-msg';
            msgBox.style.display = 'block';
            altMobileInput.focus();
            return;
        }
        
        // Validate Confirmation Checkbox
        const confirmCheck = document.getElementById('confirmation_check');
        if (confirmCheck && !confirmCheck.checked) {
            msgBox.textContent = 'Please confirm by checking the box before submitting.';
            msgBox.className = 'error-msg';
            msgBox.style.display = 'block';
            msgBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        // UI Loading state
        btnText.style.display = 'none';
        loader.style.display = 'block';
        submitBtn.disabled = true;
        msgBox.style.display = 'none';
        msgBox.className = '';

        const formData = new FormData(form);

        try {
            const response = await fetch('process.php', {
                method: 'POST',
                body: formData
            });

            const rawText = await response.text();
            let result;
            try {
                result = JSON.parse(rawText);
            } catch (jsonErr) {
                console.error('Server returned non-JSON response:', rawText);
                msgBox.textContent = 'Server Error: ' + rawText.replace(/<[^>]*>?/gm, ' ').substring(0, 150);
                msgBox.className = 'error-msg';
                msgBox.style.display = 'block';
                return;
            }

            if (result.status === 'success') {
                msgBox.textContent = result.message;
                msgBox.className = 'success-msg';
                msgBox.style.display = 'block';
                form.reset(); // clear form
            } else {
                msgBox.textContent = result.message || 'An error occurred while saving.';
                msgBox.className = 'error-msg';
                msgBox.style.display = 'block';
            }
        } catch (error) {
            msgBox.textContent = 'Network error or invalid server response.';
            msgBox.className = 'error-msg';
            msgBox.style.display = 'block';
            console.error('Error:', error);
        } finally {
            // Restore UI
            btnText.style.display = 'block';
            loader.style.display = 'none';
            submitBtn.disabled = false;
            
            // Auto hide popup after 6 seconds
            setTimeout(() => {
                msgBox.style.display = 'none';
            }, 6000);
            
            // Allow clicking popup to dismiss early
            msgBox.onclick = () => {
                msgBox.style.display = 'none';
            };
        }
    });
});
