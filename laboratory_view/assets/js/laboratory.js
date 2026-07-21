/**
 * Core Javascript for Laboratory Information System
 */

/**
 * Standard API caller for laboratory module
 */
async function lisApi(method, endpoint, body = null) {
    const url = '/GM_HMS' + (endpoint.startsWith('/') ? endpoint : '/' + endpoint);
    const options = {
        method: method.toUpperCase(),
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Hospital-Branch': window.HOSPITAL_BRANCH || ''
        }
    };
    
    if (body instanceof FormData) {
        // Drop Content-Type header so browser can set it automatically with boundaries
        delete options.headers['Content-Type'];
        options.body = body;
    } else if (body && (options.method === 'POST' || options.method === 'PUT' || options.method === 'PATCH')) {
        options.body = JSON.stringify(body);
    }
    
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

/**
 * Animate counting up to a target number
 */
function lisCountUp(element, target, duration = 1000) {
    if (!element) return;
    
    const targetNum = parseInt(target) || 0;
    const startNum = parseInt(element.innerText.replace(/,/g, '')) || 0;
    
    if (startNum === targetNum) {
        element.innerText = targetNum.toLocaleString();
        return;
    }
    
    const range = targetNum - startNum;
    const startTime = performance.now();
    
    function updateCount(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing out function
        const easeOut = 1 - Math.pow(1 - progress, 3);
        
        const currentVal = Math.round(startNum + (range * easeOut));
        element.innerText = currentVal.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(updateCount);
        } else {
            element.innerText = targetNum.toLocaleString();
        }
    }
    
    requestAnimationFrame(updateCount);
}

/**
 * Display a toast notification using SweetAlert2
 */
function lisToast(message, type = 'success') {
    if (typeof Swal !== 'undefined') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        
        Toast.fire({
            icon: type,
            title: message
        });
    } else {
        alert(message);
    }
}
