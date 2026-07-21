/**
 * Voice Input Module for Consultation
 * Handles real-time speech-to-text using Groq API
 * Supports multilingual input (Kannada, Hindi, English, Telugu, Tamil, etc.)
 */

class VoiceInput {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.isRecording = false;
        this.currentField = null;
        this.stream = null;
    }

    /**
     * Start voice input for a specific field
     * @param {string} fieldId - The ID of the textarea to populate
     */
    async startRecording(fieldId) {
        try {
            // Check browser support
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.showError('Your browser does not support audio recording. Please use Chrome, Firefox, or Edge.');
                return;
            }

            this.currentField = fieldId;
            this.audioChunks = [];

            // Request microphone permission
            this.stream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    sampleRate: 16000 // Groq API downsamples to 16kHz anyway
                }
            });

            // Create MediaRecorder with WebM format (best supported)
            const mimeType = this.getSupportedMimeType();
            this.mediaRecorder = new MediaRecorder(this.stream, { mimeType });

            // Collect audio chunks
            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                }
            };

            // Handle recording stop
            this.mediaRecorder.onstop = async () => {
                await this.processRecording();
            };

            // Start recording
            this.mediaRecorder.start();
            this.isRecording = true;

            // Update UI
            this.showRecordingUI();

        } catch (error) {
            console.error('Microphone access error:', error);
            if (error.name === 'NotAllowedError') {
                this.showError('Microphone permission denied. Please allow microphone access and try again.');
            } else {
                this.showError('Failed to access microphone: ' + error.message);
            }
        }
    }

    /**
     * Stop recording and process audio
     */
    stopRecording() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;

            // Stop all tracks
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }

            // Update UI
            this.hideRecordingUI();
        }
    }

    /**
     * Process recorded audio and send to API
     */
    async processRecording() {
        try {
            console.log('🔄 Processing recording...');
            console.log('📊 Total audio chunks:', this.audioChunks.length);

            // Show processing indicator
            this.showProcessing();

            // Create audio blob
            const audioBlob = new Blob(this.audioChunks, { type: this.mediaRecorder.mimeType });
            console.log('📦 Audio blob created:', audioBlob.size, 'bytes, type:', audioBlob.type);

            // Check file size (25MB limit)
            if (audioBlob.size > 25 * 1024 * 1024) {
                console.error('❌ File too large:', audioBlob.size);
                this.showError('Recording too long. Please record shorter segments (max 25MB).');
                this.hideProcessing();
                return;
            }

            // Send to backend API
            const formData = new FormData();
            formData.append('audio', audioBlob, 'recording.webm');
            formData.append('field', this.currentField);

            console.log('📤 Sending to API: /GM_HMS/api/consultations/translate-audio');
            console.log('📝 Field:', this.currentField);

            const response = await fetch('/GM_HMS/api/consultations/translate-audio', {
                method: 'POST',
                body: formData
            });

            console.log('📥 API Response status:', response.status);

            const result = await response.json();
            console.log('📄 API Response:', result);

            if (result.success && result.data.text) {
                console.log('✅ Translation successful:', result.data.text);
                // Update textarea with translated text
                this.updateTextField(result.data.text);
                this.showSuccess('Speech translated successfully!');
            } else {
                console.error('❌ Translation failed:', result.message);
                this.showError(result.message || 'Translation failed. Please try again.');
            }

        } catch (error) {
            console.error('❌ Translation error:', error);
            this.showError('Failed to translate audio. Please check your connection and try again.');
        } finally {
            this.hideProcessing();
        }
    }

    /**
     * Update the target text field with translated text
     * @param {string} text - Translated text
     */
    updateTextField(text) {
        const textarea = document.getElementById(`soap-${this.currentField}`);
        if (textarea) {
            // Append to existing text or replace
            const currentText = textarea.value.trim();
            if (currentText) {
                textarea.value = currentText + '\n\n' + text;
            } else {
                textarea.value = text;
            }

            // Trigger any change events
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    /**
     * Get supported MIME type for recording
     */
    getSupportedMimeType() {
        const types = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/ogg;codecs=opus',
            'audio/mp4'
        ];

        for (const type of types) {
            if (MediaRecorder.isTypeSupported(type)) {
                return type;
            }
        }

        return 'audio/webm'; // Fallback
    }

    /**
     * Show recording UI indicators
     */
    showRecordingUI() {
        const button = document.querySelector(`button[onclick*="startVoiceInput('${this.currentField}')"]`);
        if (button) {
            button.innerHTML = '<i class="fas fa-stop-circle"></i> Stop Recording';
            button.classList.add('recording-active');
            button.onclick = () => this.stopRecording();
        }

        // Add recording indicator
        const indicator = document.createElement('div');
        indicator.id = 'recording-indicator';
        indicator.className = 'recording-indicator';
        indicator.innerHTML = `
            <div class="recording-pulse"></div>
            <span>Recording... Speak now</span>
        `;
        document.body.appendChild(indicator);
    }

    /**
     * Hide recording UI indicators
     */
    hideRecordingUI() {
        const button = document.querySelector('.recording-active');
        if (button) {
            button.innerHTML = '<i class="fas fa-microphone"></i> Voice Dictation';
            button.classList.remove('recording-active');
            button.onclick = () => startVoiceInput(this.currentField);
        }

        const indicator = document.getElementById('recording-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Show processing indicator
     */
    showProcessing() {
        const indicator = document.createElement('div');
        indicator.id = 'processing-indicator';
        indicator.className = 'processing-indicator';
        indicator.innerHTML = `
            <div class="spinner"></div>
            <span>Translating speech to text...</span>
        `;
        document.body.appendChild(indicator);
    }

    /**
     * Hide processing indicator
     */
    hideProcessing() {
        const indicator = document.getElementById('processing-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Voice Input Error',
                text: message,
                confirmButtonColor: '#1f6b4a'
            });
        } else {
            alert(message);
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: message,
                showConfirmButton: false,
                timer: 3000
            });
        }
    }
}

// Create global instance
const voiceInput = new VoiceInput();

/**
 * Global function to start voice input
 * Called from HTML onclick handlers
 */
function startVoiceInput(fieldId) {
    voiceInput.startRecording(fieldId);
}
