// Modal Component
class Modal {
    constructor() {
        this.modalContainer = null;
        this.modalContent = null;
        this.createModalContainer();
    }

    createModalContainer() {
        // Create modal container if it doesn't exist
        if (!document.getElementById('modal-container')) {
            const container = document.createElement('div');
            container.id = 'modal-container';
            container.style.display = 'none';
            document.body.appendChild(container);
            this.modalContainer = container;
        } else {
            this.modalContainer = document.getElementById('modal-container');
        }
    }

    show({ title, message, confirmText = 'Confirm', cancelText = 'Cancel', type = 'confirm', onConfirm, onCancel, customContent = null, content = null, onOpen = null }) {
        // Create modal elements
        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'modal-overlay';
        
        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        this.modalContent = modalContent;
        
        const modalHeader = document.createElement('div');
        modalHeader.className = 'modal-header';
        
        const modalTitle = document.createElement('h3');
        modalTitle.className = 'modal-title';
        modalTitle.textContent = title;
        
        const modalBody = document.createElement('div');
        modalBody.className = 'modal-body';
        
        // Add message if provided
        if (message) {
            const messageP = document.createElement('p');
            messageP.innerHTML = message;
            modalBody.appendChild(messageP);
        }
        
        let input = null;
        
        // Add DOM element content if provided
        if (content) {
            modalBody.appendChild(content);
        }
        // Add HTML string content if provided
        else if (customContent) {
            const customContentContainer = document.createElement('div');
            customContentContainer.innerHTML = customContent;
            modalBody.appendChild(customContentContainer);
            
            input = customContentContainer.querySelector('input');
            if (input) {
                input.addEventListener('keyup', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const confirmButton = this.modalContent.querySelector('.confirm-button');
                        if (confirmButton && !confirmButton.disabled) {
                            confirmButton.click();
                        }
                    }
                });
                setTimeout(() => input.focus(), 100);
            }
        }
        
        const modalFooter = document.createElement('div');
        modalFooter.className = 'modal-footer';
        
        // Add buttons based on type (skip if using custom content with its own buttons)
        if ((type === 'confirm' || type === 'warning') && !content) {
            const confirmButton = document.createElement('button');
            confirmButton.className = `modal-button confirm-button ${type}`;
            confirmButton.textContent = confirmText;
            
            const handleConfirm = async () => {
                let inputError = false;

                if (input) {
                    const value = input.value.trim();
                    if (!value) {
                        input.classList.add('error');
                        input.focus();
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'modal-error';
                        errorMsg.textContent = 'This field cannot be empty';
                        errorMsg.style.color = 'var(--accent-error)';
                        errorMsg.style.fontSize = '0.85em';
                        errorMsg.style.marginTop = '4px';
                        
                        // Remove any existing error message
                        const existingError = input.parentNode.querySelector('.modal-error');
                        if (existingError) {
                            existingError.remove();
                        }
                        
                        input.parentNode.appendChild(errorMsg);
                        inputError = true;
                        return;
                    } else {
                        // Clear any previous error state
                        input.classList.remove('error');
                        const existingError = input.parentNode.querySelector('.modal-error');
                        if (existingError) {
                            existingError.remove();
                        }
                    }
                }

                if (!inputError) {
                    this.hide();
                    if (onConfirm) await onConfirm(input, input ? input.value.trim() : null);
                }
            };
            
            confirmButton.onclick = handleConfirm;
            
            const cancelButton = document.createElement('button');
            cancelButton.className = 'modal-button cancel-button';
            cancelButton.textContent = cancelText;
            cancelButton.onclick = () => {
                this.hide();
                if (onCancel) onCancel();
            };
            
            modalFooter.appendChild(confirmButton);
            modalFooter.appendChild(cancelButton);
        } else if (!content) {
            const okButton = document.createElement('button');
            okButton.className = 'modal-button ok-button';
            okButton.textContent = 'OK';
            okButton.onclick = () => {
                this.hide();
                if (onConfirm) onConfirm();
            };
            modalFooter.appendChild(okButton);
        }
        
        // Assemble modal
        modalHeader.appendChild(modalTitle);
        modalContent.appendChild(modalHeader);
        modalContent.appendChild(modalBody);
        
        // Add footer only if we're not using a custom content that has its own buttons
        if (!content) {
            modalContent.appendChild(modalFooter);
        }
        
        modalOverlay.appendChild(modalContent);
        
        // Show modal
        this.modalContainer.innerHTML = '';
        this.modalContainer.appendChild(modalOverlay);
        this.modalContainer.style.display = 'block';
        
        // Call onOpen callback if provided
        if (onOpen && typeof onOpen === 'function') {
            onOpen();
        }
        
        // Focus the confirm button (unless we have an input)
        if (!input && !content) {
            const confirmButton = modalContent.querySelector('.confirm-button, .ok-button');
            if (confirmButton) confirmButton.focus();
        }

        // Add click outside to close
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) {
                this.hide();
                if (onCancel) onCancel();
            }
        });

        // Add escape key to close
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                this.hide();
                if (onCancel) onCancel();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }
    
    hide() {
        if (this.modalContainer) {
            this.modalContainer.style.display = 'none';
            this.modalContainer.innerHTML = '';
            this.modalContent = null;
        }
    }

    // Helper method to get input value
    getInput() {
        if (!this.modalContent) return null;
        const input = this.modalContent.querySelector('.modal-input');
        return input ? input.value : null;
    }
    
    close() {
        this.hide();
    }
}

// Create a singleton instance
const modal = new Modal(); 