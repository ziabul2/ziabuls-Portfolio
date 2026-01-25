/**
 * Portfolio Update Workflow Manager
 * Handles client-side validation, submission, and feedback
 * 
 * Usage:
 *   const updater = new PortfolioUpdater('hero');
 *   updater.init();
 *   
 * The form with id="edit-form" will be automatically enhanced
 * with validation, submission handling, and feedback
 */

class PortfolioUpdater {
    constructor(section) {
        this.section = section;
        this.form = document.getElementById('edit-form');
        this.submitBtn = document.querySelector('[type="submit"]');
        this.statusMsg = document.getElementById('status-message');
        this.isSubmitting = false;
        this.validationRules = this.getValidationRules();
    }
    
    /**
     * Initialize the updater
     */
    init() {
        if (!this.form) {
            console.error('No form with id="edit-form" found');
            return;
        }
        
        // Attach form submission handler
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Optional: Real-time validation on field blur
        this.form.querySelectorAll('[data-validate]').forEach(field => {
            field.addEventListener('blur', (e) => this.validateField(e.target));
        });
        
        console.log(`PortfolioUpdater initialized for section: ${this.section}`);
    }
    
    /**
     * Handle form submission
     */
    async handleSubmit(e) {
        e.preventDefault();
        
        if (this.isSubmitting) {
            console.log('Already submitting...');
            return;
        }
        
        // Phase 1: Client-side validation
        const clientErrors = this.validateForm();
        if (Object.keys(clientErrors).length > 0) {
            this.showErrors(clientErrors);
            return;
        }
        
        // Phase 2: Prepare data
        const formData = new FormData(this.form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            // Skip the section field
            if (key === 'section') continue;
            
            // Handle arrays (e.g., socials[])
            if (key.includes('[')) {
                // Complex field handling
                data[key] = value;
            } else {
                data[key] = value;
            }
        }
        
        // Phase 3: Server-side validation
        try {
            this.lockForm('Validating...');
            
            const validateResult = await this.validateServer(data);
            if (!validateResult.valid) {
                this.showErrors(validateResult.errors);
                this.unlockForm();
                return;
            }
        } catch (err) {
            this.showError('Validation request failed: ' + err.message);
            this.unlockForm();
            return;
        }
        
        // Phase 4: Save data
        try {
            this.lockForm('Saving changes...');
            
            const saveResult = await this.saveData(data);
            
            if (saveResult.success) {
                // Phase 5: Show success and redirect
                this.showSuccess(saveResult.message, saveResult.backup_file);
                
                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = 'index.php?backup=' + 
                        encodeURIComponent(saveResult.backup_file);
                }, 2000);
            } else {
                this.showError(saveResult.error);
                this.unlockForm();
            }
        } catch (err) {
            this.showError('Save request failed: ' + err.message);
            this.unlockForm();
        }
    }
    
    /**
     * Validate form on client side
     */
    validateForm() {
        const errors = {};
        
        this.form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                errors[field.name] = field.previousElementSibling?.textContent || 
                    'This field is required';
            }
        });
        
        // Custom validation rules
        Object.entries(this.validationRules).forEach(([fieldName, rules]) => {
            const field = this.form.elements[fieldName];
            if (!field) return;
            
            for (const rule of rules) {
                const err = this.checkRule(field.value, rule);
                if (err) {
                    errors[fieldName] = err;
                    break;
                }
            }
        });
        
        return errors;
    }
    
    /**
     * Validate a single field
     */
    validateField(field) {
        const rules = this.validationRules[field.name];
        if (!rules) return true;
        
        for (const rule of rules) {
            const err = this.checkRule(field.value, rule);
            if (err) {
                this.showFieldError(field.name, err);
                return false;
            }
        }
        
        this.clearFieldError(field.name);
        return true;
    }
    
    /**
     * Check individual validation rule
     */
    checkRule(value, rule) {
        if (rule === 'required' && !value.trim()) {
            return 'This field is required';
        }
        
        if (rule === 'email' && value && !this.isValidEmail(value)) {
            return 'Invalid email address';
        }
        
        if (rule === 'url' && value && !this.isValidUrl(value)) {
            return 'Invalid URL';
        }
        
        if (rule.startsWith('max:')) {
            const max = parseInt(rule.split(':')[1]);
            if (value.length > max) {
                return `Maximum ${max} characters allowed`;
            }
        }
        
        if (rule.startsWith('min:')) {
            const min = parseInt(rule.split(':')[1]);
            if (value.length < min) {
                return `Minimum ${min} characters required`;
            }
        }
        
        if (rule.startsWith('pattern:')) {
            const pattern = rule.split(':')[1];
            if (!new RegExp(pattern).test(value)) {
                return 'Invalid format';
            }
        }
        
        return null;
    }
    
    /**
     * Server-side validation
     */
    async validateServer(data) {
        const formData = new FormData();
        formData.append('section', this.section);
        formData.append('data', JSON.stringify(data));
        
        const response = await fetch('/admin/api/validate.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return await response.json();
    }
    
    /**
     * Save data to server
     */
    async saveData(data) {
        const formData = new FormData();
        formData.append('section', this.section);
        
        // Flatten and send data
        Object.entries(data).forEach(([key, value]) => {
            if (typeof value === 'object') {
                formData.append(key, JSON.stringify(value));
            } else {
                formData.append(key, value);
            }
        });
        
        const response = await fetch('/admin/api/save.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return await response.json();
    }
    
    /**
     * Lock form during submission
     */
    lockForm(text = 'Processing...') {
        this.isSubmitting = true;
        
        if (this.submitBtn) {
            this.submitBtn.disabled = true;
            this.submitBtn.textContent = text;
        }
        
        this.form.querySelectorAll('input, textarea, select').forEach(field => {
            field.disabled = true;
        });
    }
    
    /**
     * Unlock form after submission
     */
    unlockForm() {
        this.isSubmitting = false;
        
        if (this.submitBtn) {
            this.submitBtn.disabled = false;
            this.submitBtn.textContent = 'Save Changes';
        }
        
        this.form.querySelectorAll('input, textarea, select').forEach(field => {
            field.disabled = false;
        });
    }
    
    /**
     * Show validation errors
     */
    showErrors(errors) {
        this.clearMessages();
        
        Object.entries(errors).forEach(([field, message]) => {
            this.showFieldError(field, message);
        });
        
        // Also show in status message
        const errorCount = Object.keys(errors).length;
        this.statusMsg.className = 'error-msg';
        this.statusMsg.textContent = `${errorCount} error(s) found. Please check the form.`;
        this.statusMsg.classList.remove('hidden');
    }
    
    /**
     * Show field-specific error
     */
    showFieldError(fieldName, message) {
        const field = this.form.elements[fieldName];
        if (!field) return;
        
        field.classList.add('has-error');
        
        // Look for error message element
        let errorEl = field.nextElementSibling;
        if (!errorEl || !errorEl.classList.contains('error-msg')) {
            errorEl = document.createElement('span');
            errorEl.className = 'error-msg';
            field.parentNode.insertBefore(errorEl, field.nextSibling);
        }
        
        errorEl.textContent = message;
    }
    
    /**
     * Clear field error
     */
    clearFieldError(fieldName) {
        const field = this.form.elements[fieldName];
        if (!field) return;
        
        field.classList.remove('has-error');
        
        const errorEl = field.nextElementSibling;
        if (errorEl && errorEl.classList.contains('error-msg')) {
            errorEl.textContent = '';
        }
    }
    
    /**
     * Show success message
     */
    showSuccess(message, backupFile) {
        this.clearMessages();
        
        this.statusMsg.className = 'success-msg';
        this.statusMsg.innerHTML = `
            <strong>✓ ${message}</strong><br>
            <small>Backup: ${backupFile}</small><br>
            <small>Redirecting to dashboard...</small>
        `;
        this.statusMsg.classList.remove('hidden');
    }
    
    /**
     * Show error message
     */
    showError(message) {
        this.clearMessages();
        
        this.statusMsg.className = 'error-msg';
        this.statusMsg.textContent = '✗ ' + message;
        this.statusMsg.classList.remove('hidden');
    }
    
    /**
     * Clear all messages
     */
    clearMessages() {
        this.form.querySelectorAll('.error-msg').forEach(el => {
            if (el.id !== 'status-message') {
                el.textContent = '';
            }
        });
    }
    
    /**
     * Get validation rules for this section
     */
    getValidationRules() {
        return {
            'hero_name': ['required', 'max:100'],
            'hero_image': ['required'],
            'hero_desc': ['required', 'max:500'],
            'hero_quote': ['max:200'],
            'about_image': ['required'],
            'about_intro': ['required'],
            'email': ['email'],
            'website': ['url'],
            'social_url': ['url']
        };
    }
    
    /**
     * Utility: Validate email
     */
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    /**
     * Utility: Validate URL
     */
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
}

// Auto-initialize when DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Get section from form or data attribute
    const form = document.getElementById('edit-form');
    if (form) {
        const section = form.dataset.section || form.elements['section']?.value;
        if (section) {
            const updater = new PortfolioUpdater(section);
            updater.init();
        }
    }
});
