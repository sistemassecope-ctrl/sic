/**
 * PAO v2 - JavaScript Principal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    initSidebarToggle();
    
    // Alerts auto-dismiss
    initAlerts();
    
    // Form validations
    initFormValidations();
    
    // Tooltips
    initTooltips();
});

/**
 * Sidebar Toggle Functionality
 */
function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const appContainer = document.querySelector('.app-container');
    
    if (toggleBtn && appContainer) {
        toggleBtn.addEventListener('click', function() {
            appContainer.classList.toggle('sidebar-collapsed');
            
            // Save preference
            localStorage.setItem('sidebarCollapsed', 
                appContainer.classList.contains('sidebar-collapsed')
            );
        });
        
        // Restore preference
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            appContainer.classList.add('sidebar-collapsed');
        }
    }
}

/**
 * Auto-dismiss alerts
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    
    alerts.forEach(alert => {
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            fadeOut(alert);
        }, 5000);
        
        // Close button
        const closeBtn = alert.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => fadeOut(alert));
        }
    });
}

/**
 * Fade out element and remove
 */
function fadeOut(element) {
    element.style.transition = 'opacity 300ms ease, transform 300ms ease';
    element.style.opacity = '0';
    element.style.transform = 'translateY(-10px)';
    
    setTimeout(() => {
        element.remove();
    }, 300);
}

/**
 * Form Validations
 */
function initFormValidations() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Por favor complete todos los campos requeridos', 'error');
            }
        });
    });
}

/**
 * Notification Toast
 */
function showNotification(message, type = 'info') {
    const container = document.querySelector('.notification-container') || createNotificationContainer();
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto remove
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

function createNotificationContainer() {
    const container = document.createElement('div');
    container.className = 'notification-container';
    container.style.cssText = `
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    `;
    document.body.appendChild(container);
    return container;
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || icons.info;
}

/**
 * Tooltips
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', function() {
            const text = this.dataset.tooltip;
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-popup';
            tooltip.textContent = text;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.cssText = `
                position: fixed;
                top: ${rect.top - 35}px;
                left: ${rect.left + rect.width / 2}px;
                transform: translateX(-50%);
                background: var(--bg-tertiary);
                color: var(--text-primary);
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
                border-radius: 4px;
                box-shadow: var(--shadow-md);
                z-index: 9999;
                white-space: nowrap;
            `;
            
            this._tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

/**
 * AJAX Request Helper
 */
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    if (config.body && typeof config.body === 'object') {
        config.body = JSON.stringify(config.body);
    }
    
    try {
        const response = await fetch(url, config);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Error en la solicitud');
        }
        
        return data;
    } catch (error) {
        showNotification(error.message, 'error');
        throw error;
    }
}

/**
 * Confirm Dialog
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Table Action - Delete
 */
function deleteRecord(url, id, tableName = 'registro') {
    confirmAction(`¿Está seguro de eliminar este ${tableName}?`, async () => {
        try {
            await apiRequest(url, {
                method: 'DELETE',
                body: { id }
            });
            
            showNotification(`${tableName} eliminado correctamente`, 'success');
            
            // Remove row from table
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                fadeOut(row);
            } else {
                location.reload();
            }
        } catch (error) {
            console.error('Error deleting:', error);
        }
    });
}
