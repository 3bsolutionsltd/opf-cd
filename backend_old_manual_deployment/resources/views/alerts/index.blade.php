<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Alerts - OPF-CD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            padding: 24px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #202020;
        }

        .stats {
            display: flex;
            gap: 20px;
        }

        .stat {
            text-align: center;
            padding: 8px 16px;
            border-radius: 6px;
            background: #f8f8f8;
        }

        .stat-label {
            font-size: 11px;
            color: #707070;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 600;
        }

        .stat.critical .stat-value {
            color: #d32f2f;
        }

        .stat.warning .stat-value {
            color: #f57c00;
        }

        .stat.info .stat-value {
            color: #0288d1;
        }

        .filters {
            padding: 16px 24px;
            background: #fafafa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .filter-label {
            font-size: 14px;
            color: #505050;
            font-weight: 500;
        }

        .filter-btn {
            padding: 6px 14px;
            border: 1px solid #d0d0d0;
            background: white;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: #f0f0f0;
        }

        .filter-btn.active {
            background: #1976d2;
            color: white;
            border-color: #1976d2;
        }

        .alerts-list {
            padding: 24px;
        }

        .alert-item {
            padding: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 12px;
            display: flex;
            gap: 16px;
            transition: all 0.2s;
        }

        .alert-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .alert-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .alert-item.critical .alert-icon {
            background: #ffebee;
            color: #d32f2f;
        }

        .alert-item.warning .alert-icon {
            background: #fff3e0;
            color: #f57c00;
        }

        .alert-item.info .alert-icon {
            background: #e1f5fe;
            color: #0288d1;
        }

        .alert-content {
            flex: 1;
        }

        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .alert-type {
            font-size: 11px;
            color: #707070;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .alert-message {
            font-size: 14px;
            color: #303030;
            line-height: 1.5;
        }

        .alert-meta {
            display: flex;
            gap: 16px;
            margin-top: 8px;
            font-size: 12px;
            color: #808080;
        }

        .alert-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .action-btn {
            padding: 6px 12px;
            border: 1px solid #d0d0d0;
            background: white;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: #f0f0f0;
        }

        .action-btn.dismiss {
            color: #0288d1;
            border-color: #0288d1;
        }

        .action-btn.dismiss:hover {
            background: #e1f5fe;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #808080;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .empty-state-text {
            font-size: 16px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #808080;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container" x-data="alertsManager()">
        <div class="header">
            <h1>System Alerts</h1>
            <div class="stats">
                <div class="stat critical">
                    <div class="stat-label">Critical</div>
                    <div class="stat-value" x-text="counts.critical">0</div>
                </div>
                <div class="stat warning">
                    <div class="stat-label">Warning</div>
                    <div class="stat-value" x-text="counts.warning">0</div>
                </div>
                <div class="stat info">
                    <div class="stat-label">Info</div>
                    <div class="stat-value" x-text="counts.info">0</div>
                </div>
            </div>
        </div>

        <div class="filters">
            <span class="filter-label">Filter:</span>
            <button class="filter-btn" :class="{ active: filter === 'all' }" @click="filter = 'all'">
                All Alerts
            </button>
            <button class="filter-btn" :class="{ active: filter === 'critical' }" @click="filter = 'critical'">
                Critical
            </button>
            <button class="filter-btn" :class="{ active: filter === 'warning' }" @click="filter = 'warning'">
                Warning
            </button>
            <button class="filter-btn" :class="{ active: filter === 'info' }" @click="filter = 'info'">
                Info
            </button>
        </div>

        <div class="alerts-list">
            <div class="loading" x-show="loading">
                Loading alerts...
            </div>

            <div class="empty-state" x-show="!loading && filteredAlerts.length === 0">
                <div class="empty-state-icon">✓</div>
                <div class="empty-state-text">No active alerts</div>
            </div>

            <template x-for="alert in filteredAlerts" :key="alert.id">
                <div class="alert-item" :class="alert.severity">
                    <div class="alert-icon" x-text="getAlertIcon(alert.severity)"></div>
                    <div class="alert-content">
                        <div class="alert-header">
                            <div>
                                <div class="alert-type" x-text="formatAlertType(alert.type)"></div>
                                <div class="alert-message" x-text="alert.message"></div>
                            </div>
                        </div>
                        <div class="alert-meta">
                            <span x-text="formatEntity(alert.entity_type, alert.entity_id)"></span>
                            <span>•</span>
                            <span x-text="formatDate(alert.created_at)"></span>
                        </div>
                    </div>
                    <div class="alert-actions">
                        <button class="action-btn dismiss" @click="dismissAlert(alert.id)">
                            Dismiss
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function alertsManager() {
            return {
                alerts: [],
                counts: { total: 0, critical: 0, warning: 0, info: 0 },
                filter: 'all',
                loading: true,

                init() {
                    this.loadAlerts();
                    this.loadCounts();
                },

                async loadAlerts() {
                    this.loading = true;
                    try {
                        const response = await fetch('/api/alerts');
                        if (response.ok) {
                            const data = await response.json();
                            this.alerts = data.alerts || [];
                        }
                    } catch (error) {
                        console.error('Failed to load alerts:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async loadCounts() {
                    try {
                        const response = await fetch('/api/alerts/count');
                        if (response.ok) {
                            this.counts = await response.json();
                        }
                    } catch (error) {
                        console.error('Failed to load counts:', error);
                    }
                },

                async dismissAlert(alertId) {
                    try {
                        const response = await fetch(`/api/alerts/${alertId}/dismiss`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                        });

                        if (response.ok) {
                            // Remove from local list
                            this.alerts = this.alerts.filter(a => a.id !== alertId);
                            // Reload counts
                            this.loadCounts();
                        }
                    } catch (error) {
                        console.error('Failed to dismiss alert:', error);
                    }
                },

                get filteredAlerts() {
                    if (this.filter === 'all') {
                        return this.alerts;
                    }
                    return this.alerts.filter(a => a.severity === this.filter);
                },

                getAlertIcon(severity) {
                    const icons = {
                        critical: '⚠',
                        warning: '⚡',
                        info: 'ℹ'
                    };
                    return icons[severity] || 'ℹ';
                },

                formatAlertType(type) {
                    return type.replace(/_/g, ' ');
                },

                formatEntity(entityType, entityId) {
                    return `${entityType} #${entityId}`;
                },

                formatDate(timestamp) {
                    const date = new Date(timestamp);
                    const now = new Date();
                    const diffMs = now - date;
                    const diffMins = Math.floor(diffMs / 60000);
                    const diffHours = Math.floor(diffMs / 3600000);
                    const diffDays = Math.floor(diffMs / 86400000);

                    if (diffMins < 1) return 'Just now';
                    if (diffMins < 60) return `${diffMins}m ago`;
                    if (diffHours < 24) return `${diffHours}h ago`;
                    if (diffDays < 7) return `${diffDays}d ago`;
                    
                    return date.toLocaleDateString();
                }
            };
        }
    </script>
</body>
</html>
