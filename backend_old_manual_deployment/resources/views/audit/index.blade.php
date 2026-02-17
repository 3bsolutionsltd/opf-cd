<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - OPF-CD</title>
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
            max-width: 1400px;
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

        .header .flex {
            display: flex;
            align-items: center;
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
            color: #303030;
        }

        .filters {
            padding: 16px 24px;
            background: #fafafa;
            border-bottom: 1px solid #e0e0e0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-label {
            font-size: 12px;
            color: #505050;
            font-weight: 500;
        }

        .filter-input,
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #d0d0d0;
            background: white;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
        }

        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #1976d2;
        }

        .filter-actions {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #1976d2;
            background: #1976d2;
            color: white;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .filter-btn:hover {
            background: #1565c0;
        }

        .filter-btn.secondary {
            background: white;
            color: #505050;
            border-color: #d0d0d0;
        }

        .filter-btn.secondary:hover {
            background: #f0f0f0;
        }

        .logs-list {
            padding: 24px;
        }

        .log-item {
            padding: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: 80px 1fr 100px;
            gap: 16px;
            transition: all 0.2s;
        }

        .log-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .log-action {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 50px;
        }

        .action-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-badge.create {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .action-badge.update {
            background: #e3f2fd;
            color: #1565c0;
        }

        .action-badge.delete {
            background: #ffebee;
            color: #c62828;
        }

        .log-content {
            flex: 1;
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .log-entity {
            font-size: 14px;
            font-weight: 600;
            color: #303030;
        }

        .log-entity-id {
            color: #707070;
            font-weight: 400;
        }

        .log-user {
            font-size: 13px;
            color: #505050;
            margin-bottom: 4px;
        }

        .log-meta {
            display: flex;
            gap: 16px;
            font-size: 12px;
            color: #808080;
        }

        .log-actions {
            display: flex;
            align-items: center;
        }

        .view-btn {
            padding: 6px 12px;
            border: 1px solid #d0d0d0;
            background: white;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .view-btn:hover {
            background: #f0f0f0;
        }

        .changes-detail {
            margin-top: 12px;
            padding: 12px;
            background: #f8f8f8;
            border-radius: 4px;
            font-size: 12px;
            font-family: 'Courier New', monospace;
            max-height: 200px;
            overflow-y: auto;
        }

        .change-field {
            margin-bottom: 8px;
        }

        .change-field-name {
            font-weight: 600;
            color: #303030;
        }

        .change-before {
            color: #c62828;
            text-decoration: line-through;
        }

        .change-after {
            color: #2e7d32;
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

        .pagination {
            padding: 16px 24px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination-info {
            font-size: 13px;
            color: #505050;
        }

        .pagination-controls {
            display: flex;
            gap: 8px;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container" x-data="auditManager()">
        <div class="header">
            <h1>Audit Logs</h1>
            <div class="flex items-center gap-4">
                <a href="/api/reports/export/audit-logs?limit=500" 
                   download
                   class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 transition-colors">
                    📊 Export CSV
                </a>
                <div class="stats">
                    <div class="stat">
                        <div class="stat-label">Total</div>
                        <div class="stat-value" x-text="stats.total">0</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Creates</div>
                        <div class="stat-value" x-text="stats.creates">0</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Updates</div>
                        <div class="stat-value" x-text="stats.updates">0</div>
                    </div>
                    <div class="stat">
                        <div class="stat-label">Deletes</div>
                        <div class="stat-value" x-text="stats.deletes">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label class="filter-label">Entity Type</label>
                <select class="filter-select" x-model="filters.entity_type">
                    <option value="">All Types</option>
                    <option value="projects">Projects</option>
                    <option value="tasks">Tasks</option>
                    <option value="payment_milestones">Payment Milestones</option>
                    <option value="expenses">Expenses</option>
                    <option value="opportunities">Opportunities</option>
                    <option value="accounts">Accounts</option>
                    <option value="cash_transactions">Cash Transactions</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Action</label>
                <select class="filter-select" x-model="filters.action">
                    <option value="">All Actions</option>
                    <option value="create">Create</option>
                    <option value="update">Update</option>
                    <option value="delete">Delete</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">From Date</label>
                <input type="date" class="filter-input" x-model="filters.from_date">
            </div>

            <div class="filter-group">
                <label class="filter-label">To Date</label>
                <input type="date" class="filter-input" x-model="filters.to_date">
            </div>

            <div class="filter-actions">
                <button class="filter-btn" @click="applyFilters()">Apply</button>
                <button class="filter-btn secondary" @click="clearFilters()">Clear</button>
            </div>
        </div>

        <div class="logs-list">
            <div class="loading" x-show="loading">
                Loading audit logs...
            </div>

            <div class="empty-state" x-show="!loading && logs.length === 0">
                <div class="empty-state-icon">📋</div>
                <div class="empty-state-text">No audit logs found</div>
            </div>

            <template x-for="log in logs" :key="log.id">
                <div class="log-item">
                    <div class="log-action">
                        <span class="action-badge" :class="log.action" x-text="log.action"></span>
                    </div>
                    <div class="log-content">
                        <div class="log-header">
                            <div>
                                <div class="log-entity">
                                    <span x-text="formatEntityType(log.entity_type)"></span>
                                    <span class="log-entity-id">#<span x-text="log.entity_id"></span></span>
                                </div>
                                <div class="log-user" x-text="`by ${log.user_name || 'Unknown User'}`"></div>
                            </div>
                        </div>
                        <div class="log-meta">
                            <span x-text="formatDate(log.created_at)"></span>
                            <span x-show="log.ip_address">•</span>
                            <span x-show="log.ip_address" x-text="log.ip_address"></span>
                        </div>
                        <div x-show="expandedLog === log.id" class="changes-detail">
                            <template x-if="log.action === 'update' && log.changes.changed_fields">
                                <div>
                                    <div style="margin-bottom: 8px; font-weight: 600;">Changed Fields:</div>
                                    <template x-for="field in log.changes.changed_fields" :key="field">
                                        <div class="change-field">
                                            <span class="change-field-name" x-text="field + ':'"></span>
                                            <span class="change-before" x-text="JSON.stringify(log.changes.before[field])"></span>
                                            →
                                            <span class="change-after" x-text="JSON.stringify(log.changes.after[field])"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="log.action === 'create' && log.changes.after">
                                <div>
                                    <div style="margin-bottom: 8px; font-weight: 600;">Created Record:</div>
                                    <pre style="white-space: pre-wrap;" x-text="JSON.stringify(log.changes.after, null, 2)"></pre>
                                </div>
                            </template>
                            <template x-if="log.action === 'delete' && log.changes.before">
                                <div>
                                    <div style="margin-bottom: 8px; font-weight: 600;">Deleted Record:</div>
                                    <pre style="white-space: pre-wrap;" x-text="JSON.stringify(log.changes.before, null, 2)"></pre>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="log-actions">
                        <button class="view-btn" @click="toggleExpand(log.id)">
                            <span x-text="expandedLog === log.id ? 'Hide' : 'View'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="pagination" x-show="!loading && logs.length > 0">
            <div class="pagination-info">
                Showing <span x-text="logs.length"></span> records
            </div>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function auditManager() {
            return {
                logs: [],
                stats: { total: 0, creates: 0, updates: 0, deletes: 0 },
                filters: {
                    entity_type: '',
                    action: '',
                    from_date: '',
                    to_date: '',
                },
                loading: true,
                expandedLog: null,

                init() {
                    this.loadLogs();
                    this.loadStats();
                },

                async loadLogs() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams();
                        Object.keys(this.filters).forEach(key => {
                            if (this.filters[key]) {
                                params.append(key, this.filters[key]);
                            }
                        });
                        params.append('limit', '100');

                        const response = await fetch(`/api/audit-logs?${params}`);
                        if (response.ok) {
                            const data = await response.json();
                            this.logs = data.logs || [];
                        }
                    } catch (error) {
                        console.error('Failed to load logs:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async loadStats() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filters.entity_type) params.append('entity_type', this.filters.entity_type);
                        if (this.filters.from_date) params.append('from_date', this.filters.from_date);
                        if (this.filters.to_date) params.append('to_date', this.filters.to_date);

                        const response = await fetch(`/api/audit-logs/stats?${params}`);
                        if (response.ok) {
                            this.stats = await response.json();
                        }
                    } catch (error) {
                        console.error('Failed to load stats:', error);
                    }
                },

                applyFilters() {
                    this.loadLogs();
                    this.loadStats();
                },

                clearFilters() {
                    this.filters = {
                        entity_type: '',
                        action: '',
                        from_date: '',
                        to_date: '',
                    };
                    this.loadLogs();
                    this.loadStats();
                },

                toggleExpand(logId) {
                    this.expandedLog = this.expandedLog === logId ? null : logId;
                },

                formatEntityType(type) {
                    return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                },

                formatDate(timestamp) {
                    const date = new Date(timestamp);
                    return date.toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            };
        }
    </script>
</body>
</html>
