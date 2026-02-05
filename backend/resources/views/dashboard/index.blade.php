<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OPF-CD Dashboard - Operations, Projects & Finance Command Center</title>
    <meta name="description" content="OPF-CD Dashboard for managing operations, projects, and finance">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            color: #fff;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated grid background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s linear infinite;
            pointer-events: none;
            z-index: 0;
        }
        
        @keyframes gridMove {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(50px, 50px);
            }
        }
        
        /* Gradient overlays */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(168, 85, 247, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        /* Noise texture overlay */
        .noise-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
            opacity: 0.03;
            pointer-events: none;
            z-index: 1;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 40px 40px;
            position: relative;
            z-index: 2;
        }
        
        /* Status indicator */
        .status-bar {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 20px;
            font-size: 0.875rem;
            color: #10b981;
            z-index: 100;
            animation: fadeInDown 0.6s ease-out;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }
        
        header {
            margin-bottom: 60px;
            animation: fadeInDown 0.6s ease-out;
        }
        
        /* Quick stats section */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 60px;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .stat-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #6366f1 0%, #a855f7 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card:hover::before {
            transform: scaleX(1);
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #94a3b8;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #f1f5f9;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }
        
        .stat-value.loading {
            color: #64748b;
        }
        
        .stat-subtitle {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 400;
        }
        
        .stat-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-badge.green {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .stat-badge.amber {
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }
        
        .stat-badge.red {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header-content {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 16px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            box-shadow: 
                0 10px 40px rgba(99, 102, 241, 0.4),
                0 0 0 1px rgba(99, 102, 241, 0.1) inset;
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }
        
        h1 {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.02em;
        }
        
        .subtitle {
            font-size: 1.125rem;
            color: #94a3b8;
            font-weight: 400;
            margin-left: 80px;
        }
        
        .dashboard-section {
            margin-bottom: 60px;
            animation: fadeInUp 0.6s ease-out backwards;
        }
        
        .dashboard-section:nth-child(2) { animation-delay: 0.1s; }
        .dashboard-section:nth-child(3) { animation-delay: 0.2s; }
        .dashboard-section:nth-child(4) { animation-delay: 0.3s; }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        
        .section-icon {
            font-size: 1.5rem;
            animation: iconPulse 3s ease-in-out infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: -0.01em;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 24px;
            list-style: none;
        }
        
        .dashboard-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1 0%, #a855f7 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Shimmer effect */
        .dashboard-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.03),
                transparent
            );
            transition: left 0.5s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-8px) scale(1.01);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(99, 102, 241, 0.2) inset,
                0 0 40px rgba(99, 102, 241, 0.1);
        }
        
        .dashboard-card:hover::before {
            transform: scaleX(1);
        }
        
        .dashboard-card:hover::after {
            left: 100%;
        }
        
        .dashboard-card a {
            display: block;
            padding: 32px;
            text-decoration: none;
            color: inherit;
        }
        
        .dashboard-card a:focus {
            outline: 2px solid #6366f1;
            outline-offset: 4px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .card-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(168, 85, 247, 0.2) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(168, 85, 247, 0.3) 100%);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.2);
        }
        
        .card-badge {
            padding: 4px 12px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #10b981;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-badge.new {
            background: rgba(236, 72, 153, 0.1);
            border-color: rgba(236, 72, 153, 0.2);
            color: #ec4899;
        }
        
        .card-title {
            font-size: 1.375rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #f1f5f9;
            letter-spacing: -0.01em;
            transition: color 0.3s ease;
        }
        
        .dashboard-card:hover .card-title {
            color: #a78bfa;
        }
        
        .card-description {
            color: #94a3b8;
            font-size: 0.9375rem;
            line-height: 1.6;
            font-weight: 400;
        }
        
        .card-arrow {
            margin-top: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6366f1;
            font-size: 0.875rem;
            font-weight: 600;
            opacity: 0;
            transform: translateX(-10px);
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover .card-arrow {
            opacity: 1;
            transform: translateX(0);
        }
        
        /* Footer */
        footer {
            margin-top: 80px;
            padding: 40px 0;
            border-top: 1px solid rgba(148, 163, 184, 0.1);
            animation: fadeInUp 0.6s ease-out 0.4s backwards;
        }
        
        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .footer-info {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .footer-info strong {
            color: #94a3b8;
            font-weight: 600;
        }
        
        .footer-links {
            display: flex;
            gap: 24px;
            list-style: none;
        }
        
        .footer-links a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: #a78bfa;
        }
        
        .footer-links a:focus {
            outline: 2px solid #6366f1;
            outline-offset: 4px;
            border-radius: 4px;
        }
        
        /* Responsive breakpoints */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 40px 20px;
            }
            
            h1 {
                font-size: 2.5rem;
            }
            
            .subtitle {
                margin-left: 0;
                margin-top: 12px;
                font-size: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .status-bar {
                top: 10px;
                right: 10px;
                font-size: 0.8rem;
                padding: 6px 12px;
            }
            
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            h1 {
                font-size: 2rem;
            }
            
            .card-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
            }
        }
        
        /* Accessibility improvements */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* High contrast mode */
        @media (prefers-contrast: high) {
            .dashboard-card {
                border: 2px solid #6366f1;
            }
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 12px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1e293b;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 6px;
            border: 2px solid #1e293b;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
        
        /* Focus visible for keyboard navigation */
        *:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: 2px;
        }
    </style>
</head>
<body x-data="{
    summary: null,
    loading: true,
    get averageProjectHealth() {
        return this.summary?.average_project_health || 'green';
    },
    async fetchSummary() {
        try {
            const response = await fetch('/api/dashboard/summary');
            this.summary = await response.json();
        } catch (error) {
            console.error('Failed to fetch dashboard summary:', error);
        } finally {
            this.loading = false;
        }
    }
}" x-init="fetchSummary()">
    <div class="noise-overlay"></div>
    
    <div class="status-bar" role="status" aria-live="polite">
        <div class="status-dot"></div>
        <span>System Online</span>
    </div>
    
    <div class="container">
        <header>
            <div class="header-content">
                <div class="logo" aria-label="OPF-CD Logo">⚡</div>
                <h1>OPF-CD Dashboard</h1>
            </div>
            <p class="subtitle">Operations, Projects & Finance Command Dashboard</p>
        </header>
        
        <!-- Quick Stats -->
        <section class="quick-stats" aria-label="Dashboard summary statistics">
            <div class="stat-card">
                <div class="stat-label">Active Projects</div>
                <div class="stat-value" x-text="loading ? '...' : summary?.active_projects || '0'"></div>
                <div class="stat-subtitle" x-show="!loading" x-text="'of ' + (summary?.total_projects || 0) + ' total'"></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Cash at Hand</div>
                <div class="stat-value" x-text="loading ? '...' : (summary?.currency || '') + ' ' + ((summary?.cash_at_hand || 0).toLocaleString())"></div>
                <div class="stat-subtitle">Available liquidity</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Pipeline Value</div>
                <div class="stat-value" x-text="loading ? '...' : (summary?.currency || '') + ' ' + ((summary?.total_pipeline_value || 0).toLocaleString())"></div>
                <div class="stat-subtitle">Total opportunities</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Project Health</div>
                <div class="stat-value">
                    <span 
                        class="stat-badge" 
                        x-show="!loading && summary"
                        :class="{
                            'green': averageProjectHealth === 'green',
                            'amber': averageProjectHealth === 'amber',
                            'red': averageProjectHealth === 'red'
                        }"
                        x-text="averageProjectHealth.toUpperCase()"
                    ></span>
                    <span x-show="loading">...</span>
                </div>
                <div class="stat-subtitle" x-show="!loading && (summary?.projects_at_risk || 0) > 0" x-text="(summary?.projects_at_risk || 0) + ' at risk'"></div>
                <div class="stat-subtitle" x-show="!loading && (summary?.projects_at_risk || 0) === 0">All projects healthy</div>
            </div>
        </section>
        
        <main role="main">
            <!-- Management Section -->
            <section class="dashboard-section" aria-labelledby="management-heading">
                <div class="section-header">
                    <span class="section-icon" aria-hidden="true">⚙️</span>
                    <h2 id="management-heading">Data Management</h2>
                </div>
                <nav aria-label="Data management">
                    <ul class="dashboard-grid">
                        <li class="dashboard-card">
                            <a href="/projects" aria-label="Manage projects">
                                <div class="card-header">
                                    <div class="card-icon" aria-hidden="true">📁</div>
                                    <span class="card-badge new">New</span>
                                </div>
                                <h3 class="card-title">Projects Management</h3>
                                <p class="card-description">Create, edit, and manage all projects</p>
                                <div class="card-arrow" aria-hidden="true">
                                    <span>Manage Projects</span>
                                    <span>→</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </nav>
            </section>

            <section class="dashboard-section" aria-labelledby="projects-heading">
                <div class="section-header">
                    <span class="section-icon" aria-hidden="true">📊</span>
                    <h2 id="projects-heading">Project Dashboards</h2>
                </div>
                <nav aria-label="Project dashboards">
                    <ul class="dashboard-grid">
                        <li class="dashboard-card">
                            <a href="/dashboard/project-progress/1" aria-label="View project progress dashboard">
                                <div class="card-header">
                                    <div class="card-icon" aria-hidden="true">📈</div>
                                    <span class="card-badge new">New</span>
                                </div>
                                <h3 class="card-title">Project Progress</h3>
                                <p class="card-description" id="progress-desc">Track project completion status and monitor task progress in real-time</p>
                                <div class="card-arrow" aria-hidden="true">
                                    <span>View Dashboard</span>
                                    <span>→</span>
                                </div>
                            </a>
                        </li>
                        <li class="dashboard-card">
                            <a href="/dashboard/payment-gap/1" aria-label="View payment gap analysis dashboard">
                                <div class="card-header">
                                    <div class="card-icon" aria-hidden="true">💰</div>
                                </div>
                                <h3 class="card-title">Payment Gap</h3>
                                <p class="card-description" id="gap-desc">Monitor payment vs progress gap and identify financial risks</p>
                                <div class="card-arrow" aria-hidden="true">
                                    <span>View Dashboard</span>
                                    <span>→</span>
                                </div>
                            </a>
                        </li>
                        <li class="dashboard-card">
                            <a href="/dashboard/project-health/1" aria-label="View project health indicators dashboard">
                                <div class="card-header">
                                    <div class="card-icon" aria-hidden="true">🏥</div>
                                    <span class="card-badge">Updated</span>
                                </div>
                                <h3 class="card-title">Project Health</h3>
                                <p class="card-description" id="health-desc">View project health indicators and performance metrics</p>
                                <div class="card-arrow" aria-hidden="true">
                                    <span>View Dashboard</span>
                                    <span>→</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </nav>
            </section>
        
            <section class="dashboard-section" aria-labelledby="finance-heading">
                <div class="section-header">
                    <span class="section-icon" aria-hidden="true">💼</span>
                    <h2 id="finance-heading">Finance Dashboards</h2>
                </div>
                <nav aria-label="Finance dashboards">
                    <ul class="dashboard-grid">
                        <li class="dashboard-card">
                            <a href="/dashboard/cash-flow" aria-label="View cash flow analysis dashboard">
                                <div class="card-header">
                                    <div class="card-icon" aria-hidden="true">💵</div>
                                </div>
                                <h3 class="card-title">Cash Flow</h3>
                                <p class="card-description" id="cashflow-desc">Monitor cash position, runway, and burn rate analysis</p>
                                <div class="card-arrow" aria-hidden="true">
                                    <span>View Dashboard</span>
                                    <span>→</span>
                                </div>
                            </a>
                        </li>
                        <li class="dashboard-card">
                            <a href="/dashboard/upcoming-expenses" aria-label="View upcoming expenses dashboard">
                                <div class="card-header">
                                    <div class="card-icon" aria-hidden="true">📅</div>
                                </div>
                                <h3 class="card-title">Upcoming Expenses</h3>
                                <p class="card-description" id="expenses-desc">View scheduled expenses and manage financial obligations</p>
                                <div class="card-arrow" aria-hidden="true">
                                    <span>View Dashboard</span>
                                    <span>→</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </nav>
            </section>
        
            <section class="dashboard-section" aria-labelledby="sales-heading">
                <div class="section-header">
                    <span class="section-icon" aria-hidden="true">🎯</span>
                    <h2 id="sales-heading">Sales Dashboards</h2>
                </div>
                <nav aria-label="Sales dashboards">
                    <ul class="dashboard-grid">
                        <li class="dashboard-card">
                            <a href="/dashboard/sales-pipeline" aria-label="View sales pipeline forecast dashboard">
                                <div class="card-header">
                                    <div class="card-icon" aria-hidden="true">🚀</div>
                                </div>
                                <h3 class="card-title">Sales Pipeline</h3>
                                <p class="card-description" id="pipeline-desc">Track opportunities, forecasts, and weighted pipeline value</p>
                                <div class="card-arrow" aria-hidden="true">
                                    <span>View Dashboard</span>
                                    <span>→</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </nav>
            </section>
        </main>
        
        <footer role="contentinfo">
            <div class="footer-content">
                <div class="footer-info">
                    <strong>OPF-CD Dashboard</strong> v1.0.0 | Built with Laravel & Alpine.js
                </div>
                <ul class="footer-links">
                    <li><a href="#" aria-label="View documentation">Documentation</a></li>
                    <li><a href="#" aria-label="View API reference">API Reference</a></li>
                    <li><a href="#" aria-label="Access support">Support</a></li>
                </ul>
            </div>
        </footer>
    </div>
</body>
</html>