// Notification System
class NotificationManager {
    constructor() {
        this.notifications = [];
        this.container = null;
        this.init();
    }

    init() {
        this.createContainer();
        this.loadNotifications();
        this.startPolling();
    }

    createContainer() {
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
            `;
            document.body.appendChild(container);
            this.container = container;
        } else {
            this.container = document.getElementById('notification-container');
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('get_notifications.php');
            const data = await response.json();
            this.notifications = data.notifications || [];
            this.updateBadge();
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    updateBadge() {
        const badge = document.getElementById('notification-badge');
        const unread = this.notifications.filter(n => !n.read).length;

        if (badge) {
            if (unread > 0) {
                badge.textContent = unread > 99 ? '99+' : unread;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };

        notification.style.cssText = `
            background: white;
            border-left: 4px solid ${colors[type]};
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        `;

        notification.innerHTML = `
            <i class="fas ${icons[type]}" style="color: ${colors[type]}; font-size: 1.5rem;"></i>
            <div style="flex: 1;">
                <p style="margin: 0; color: #1e293b; font-weight: 600;">${message}</p>
            </div>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 1.2rem;">
                <i class="fas fa-times"></i>
            </button>
        `;

        this.container.appendChild(notification);

        if (duration > 0) {
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    }

    startPolling() {
        // Check for new notifications every 30 seconds
        setInterval(() => this.loadNotifications(), 30000);
    }

    async markAsRead(id) {
        try {
            await fetch('mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            this.loadNotifications();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
});
