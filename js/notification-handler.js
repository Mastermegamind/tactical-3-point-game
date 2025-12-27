// Global Notification Handler
class NotificationHandler {
    constructor() {
        this.pollInterval = null;
        this.lastNotificationCheck = null;
        this.toastQueue = [];
    }

    // Initialize notification polling
    start() {
        this.poll();
        this.pollInterval = setInterval(() => this.poll(), 5000); // Check every 5 seconds
    }

    // Stop polling
    stop() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    // Poll for new notifications
    async poll() {
        try {
            const response = await fetch('api/get-notifications.php');
            const data = await response.json();

            if (data.success && data.notifications.length > 0) {
                data.notifications.forEach(notification => this.handleNotification(notification));
            }
        } catch (error) {
            console.error('Notification poll error:', error);
        }
    }

    // Handle incoming notification
    async handleNotification(notification) {
        // Mark as read
        await this.markAsRead(notification.id);

        switch (notification.type) {
            case 'challenge':
                this.handleChallengeNotification(notification);
                break;
            case 'game_start':
                this.handleGameStartNotification(notification);
                break;
            case 'game_end':
                this.handleGameEndNotification(notification);
                break;
            case 'system':
                this.handleSystemNotification(notification);
                break;
            default:
                this.showToast(notification.title, notification.message, 'info');
        }
    }

    // Handle challenge notification
    async handleChallengeNotification(notification) {
        const data = notification.data;

        if (data.challenge_id) {
            // Incoming challenge
            const result = await Swal.fire({
                title: notification.title,
                text: notification.message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Accept',
                cancelButtonText: 'Decline',
                confirmButtonColor: '#667eea',
                cancelButtonColor: '#fa709a',
                allowOutsideClick: false,
                timer: 30000, // Auto-decline after 30 seconds
                timerProgressBar: true
            });

            if (result.isConfirmed) {
                await this.respondToChallenge(data.challenge_id, 'accepted');
            } else if (result.isDismissed) {
                await this.respondToChallenge(data.challenge_id, 'rejected');
            }
        } else {
            // Challenge declined or other challenge notification
            this.showToast(notification.title, notification.message, 'info');
        }
    }

    // Handle game start notification
    handleGameStartNotification(notification) {
        const data = notification.data;

        Swal.fire({
            title: notification.title,
            text: notification.message,
            icon: 'success',
            confirmButtonText: 'Go to Game',
            confirmButtonColor: '#667eea',
            showCancelButton: true,
            cancelButtonText: 'Later'
        }).then((result) => {
            if (result.isConfirmed && data.session_id) {
                window.location.href = `play.php?session=${data.session_id}`;
            }
        });
    }

    // Handle game end notification
    handleGameEndNotification(notification) {
        this.showToast(notification.title, notification.message, 'success');
    }

    // Handle system notification
    handleSystemNotification(notification) {
        this.showToast(notification.title, notification.message, 'info');
    }

    // Respond to challenge
    async respondToChallenge(challengeId, response) {
        try {
            const res = await fetch('api/respond-challenge.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    challenge_id: challengeId,
                    response: response
                })
            });

            const data = await res.json();

            if (data.success) {
                if (response === 'accepted' && data.session_id) {
                    // Redirect to game
                    window.location.href = `play.php?session=${data.session_id}`;
                } else {
                    this.showToast('Challenge Response', data.message, 'success');
                }
            } else {
                this.showToast('Error', data.message || 'Failed to respond to challenge', 'error');
            }
        } catch (error) {
            console.error('Challenge response error:', error);
            this.showToast('Error', 'An error occurred', 'error');
        }
    }

    // Mark notification as read
    async markAsRead(notificationId) {
        try {
            await fetch('api/mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            });
        } catch (error) {
            console.error('Mark notification read error:', error);
        }
    }

    // Show toast notification
    showToast(title, message, icon = 'info') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: icon,
            title: title,
            text: message
        });
    }
}

// Auto-initialize on pages that need it
if (typeof autoStartNotifications !== 'undefined' && autoStartNotifications) {
    const notificationHandler = new NotificationHandler();
    notificationHandler.start();

    // Stop polling when page is unloaded
    window.addEventListener('beforeunload', () => {
        notificationHandler.stop();
    });
}
