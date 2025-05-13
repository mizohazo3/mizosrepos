// Modal Component
class Modal {
    constructor() {
        this.modalContainer = document.getElementById('modal-container');
        this.modalOverlay = null;
        this.modalContent = null;
        this.focusableElements = null;
        this.firstFocusableElement = null;
        this.lastFocusableElement = null;
        this.previousActiveElement = null;
    }

    create(options = {}) {
        const {
            title = '',
            message = '',
            type = 'confirm',
            inputType = '',
            inputValue = '',
            inputPlaceholder = '',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            okText = 'OK'
        } = options;

        this.previousActiveElement = document.activeElement;

        // Create modal structure with ARIA attributes
        this.modalOverlay = document.createElement('div');
        this.modalOverlay.className = 'modal-overlay';
        this.modalOverlay.setAttribute('role', 'dialog');
        this.modalOverlay.setAttribute('aria-modal', 'true');
        this.modalOverlay.setAttribute('aria-labelledby', 'modal-title');
        this.modalOverlay.setAttribute('aria-describedby', 'modal-message');

        this.modalContent = document.createElement('div');
        this.modalContent.className = 'modal-content';

        // Create header
        const header = document.createElement('div');
        header.className = 'modal-header';
        const titleElement = document.createElement('h2');
        titleElement.className = 'modal-title';
        titleElement.id = 'modal-title';
        titleElement.textContent = title;
        header.appendChild(titleElement);

        // Create body
        const body = document.createElement('div');
        body.className = 'modal-body';
        const messageElement = document.createElement('p');
        messageElement.id = 'modal-message';
        messageElement.textContent = message;
        body.appendChild(messageElement);

        // Add input if needed
        let input = null;
        if (inputType) {
            input = document.createElement('input');
            input.type = inputType;
            input.className = 'modal-input';
            input.value = inputValue;
            input.placeholder = inputPlaceholder;
            input.setAttribute('aria-label', inputPlaceholder);
            body.appendChild(input);
        }

        // Create footer with buttons
        const footer = document.createElement('div');
        footer.className = 'modal-footer';

        let confirmButton, cancelButton;

        if (type === 'confirm' || type === 'warning') {
            cancelButton = document.createElement('button');
            cancelButton.className = 'modal-button cancel-button';
            cancelButton.textContent = cancelText;
            cancelButton.setAttribute('aria-label', cancelText);

            confirmButton = document.createElement('button');
            confirmButton.className = `modal-button confirm-button ${type === 'warning' ? 'warning' : ''}`;
            confirmButton.textContent = confirmText;
            confirmButton.setAttribute('aria-label', confirmText);

            footer.appendChild(cancelButton);
            footer.appendChild(confirmButton);
        } else {
            const okButton = document.createElement('button');
            okButton.className = 'modal-button ok-button';
            okButton.textContent = okText;
            okButton.setAttribute('aria-label', okText);
            footer.appendChild(okButton);
        }

        // Assemble modal
        this.modalContent.appendChild(header);
        this.modalContent.appendChild(body);
        this.modalContent.appendChild(footer);
        this.modalOverlay.appendChild(this.modalContent);
        this.modalContainer.appendChild(this.modalOverlay);

        // Show modal
        this.modalContainer.style.display = 'block';

        // Set up focus trap
        this.setupFocusTrap();

        // Return promise for async/await usage
        return new Promise((resolve) => {
            const handleConfirm = () => {
                const result = input ? input.value : true;
                this.close();
                resolve(result);
            };

            const handleCancel = () => {
                this.close();
                resolve(false);
            };

            // Event listeners
            if (type === 'confirm' || type === 'warning') {
                confirmButton.addEventListener('click', handleConfirm);
                cancelButton.addEventListener('click', handleCancel);
                this.modalOverlay.addEventListener('click', (e) => {
                    if (e.target === this.modalOverlay) handleCancel();
                });
            } else {
                const okButton = footer.querySelector('.ok-button');
                okButton.addEventListener('click', handleConfirm);
                this.modalOverlay.addEventListener('click', (e) => {
                    if (e.target === this.modalOverlay) handleConfirm();
                });
            }

            // Keyboard event listeners
            this.modalContent.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    handleCancel();
                } else if (e.key === 'Enter' && !input) {
                    handleConfirm();
                }
                this.handleFocusTrap(e);
            });

            // Focus first interactive element
            if (input) {
                input.focus();
            } else if (type === 'confirm' || type === 'warning') {
                cancelButton.focus();
            } else {
                footer.querySelector('.ok-button').focus();
            }
        });
    }

    setupFocusTrap() {
        // Get all focusable elements
        this.focusableElements = this.modalContent.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        this.firstFocusableElement = this.focusableElements[0];
        this.lastFocusableElement = this.focusableElements[this.focusableElements.length - 1];
    }

    handleFocusTrap(e) {
        const isTabPressed = e.key === 'Tab';
        
        if (!isTabPressed) return;

        if (e.shiftKey) { // Shift + Tab
            if (document.activeElement === this.firstFocusableElement) {
                e.preventDefault();
                this.lastFocusableElement.focus();
            }
        } else { // Tab
            if (document.activeElement === this.lastFocusableElement) {
                e.preventDefault();
                this.firstFocusableElement.focus();
            }
        }
    }

    close() {
        if (this.modalContainer) {
            this.modalContainer.style.display = 'none';
            if (this.modalOverlay) {
                this.modalOverlay.remove();
            }
            // Restore focus to the previously focused element
            if (this.previousActiveElement) {
                this.previousActiveElement.focus();
            }
        }
    }

    showError(input) {
        input.classList.add('error');
        setTimeout(() => input.classList.remove('error'), 500);
    }
}

// Create a global modal instance
const modal = new Modal(); 