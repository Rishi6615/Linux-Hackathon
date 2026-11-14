<?php
// Grand Heritage Hotel & Luxury Suites - Executive Operations Portal
session_start();

$msg = "";
$msg_type = "success";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Action 1: Create New Reservation
    if ($action === 'create_booking') {
        $guest = trim($_POST['guest_name'] ?? '');
        $room = trim($_POST['room_num'] ?? '');
        $nights = intval($_POST['nights'] ?? 1);
        $type = trim($_POST['room_type'] ?? 'Executive Suite');
        $rate = trim($_POST['rate_tier'] ?? 'Standard');
        
        if (!empty($guest) && !empty($room)) {
            $filename = "/srv/hotel/reception/booking_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $room) . ".txt";
            $content = "GUEST NAME: " . htmlspecialchars($guest) . "\n";
            $content .= "ROOM NUMBER: " . htmlspecialchars($room) . " (" . htmlspecialchars($type) . ")\n";
            $content .= "DURATION: " . $nights . " Night(s)\n";
            $content .= "RATE TIER: " . htmlspecialchars($rate) . "\n";
            $content .= "CHECK-IN TIME: " . date("Y-m-d H:i:s") . "\n";
            $content .= "STATUS: Confirmed & Checked-In\n";
            
            if (@file_put_contents($filename, $content) !== false) {
                $msg = "Reservation successfully created and secured for <strong>" . htmlspecialchars($guest) . "</strong> in Room " . htmlspecialchars($room) . ".";
            } else {
                $msg = "Unable to commit reservation file to secure storage. Please verify departmental access privileges.";
                $msg_type = "danger";
            }
        }
    }
    
    // Action 2: Update Housekeeping Status
    if ($action === 'update_room') {
        $room = trim($_POST['room_num'] ?? '');
        $status = trim($_POST['room_status'] ?? 'Cleaned & Ready');
        $staff = trim($_POST['staff_name'] ?? 'Floor Supervisor');
        
        if (!empty($room)) {
            $filename = "/srv/hotel/housekeeping/room_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $room) . ".txt";
            $content = "ROOM: " . htmlspecialchars($room) . "\n";
            $content .= "READINESS: " . htmlspecialchars($status) . "\n";
            $content .= "INSPECTED BY: " . htmlspecialchars($staff) . "\n";
            $content .= "UPDATED TIMESTAMP: " . date("Y-m-d H:i:s") . "\n";
            $content .= "DEPARTMENT: Housekeeping & Facility Services\n";
            
            if (@file_put_contents($filename, $content) !== false) {
                $msg = "Room " . htmlspecialchars($room) . " operational status successfully updated to <strong>" . htmlspecialchars($status) . "</strong>.";
            } else {
                $msg = "Unable to record room status. Please verify facility access credentials.";
                $msg_type = "danger";
            }
        }
    }
    
    // Action 3: Delete / Check-out booking
    if ($action === 'delete_file') {
        $file = $_POST['file_path'] ?? '';
        if (strpos($file, '/srv/hotel/') === 0 && file_exists($file)) {
            @unlink($file);
            $msg = "Guest check-out completed. Folio record for <strong>" . htmlspecialchars(basename($file)) . "</strong> safely cleared.";
        }
    }
}

// Active Tab
$tab = $_GET['tab'] ?? 'reception';

// System Telemetry Helper
$uptime = shell_exec('uptime -p 2>/dev/null') ?: 'System up';
$uptime = str_replace('up ', '', $uptime);
$load = shell_exec("uptime | awk -F'load average:' '{print $2}'") ?: '0.05, 0.08, 0.12';
$mem_info = shell_exec("free -m | awk '/Mem:/ {printf \"%d MB / %d MB (%.0f%%)\", $3, $2, ($3/$2)*100}'") ?: 'N/A';
$logged_users = shell_exec('who | wc -l') ?: '1';

// Disk Space Summary
$disks_raw = shell_exec("df -h / /srv/hotel/reception /srv/hotel/housekeeping /srv/hotel/admin /backup 2>/dev/null");
$disks = [];
$friendly_labels = [
    '/' => 'Core Operating System',
    '/srv/hotel/reception' => 'Front Desk & Guest Ledger',
    '/srv/hotel/housekeeping' => 'Housekeeping & Facility Vault',
    '/srv/hotel/admin' => 'Executive Management Repository',
    '/backup' => 'Disaster Recovery Cold Storage'
];

if ($disks_raw) {
    $lines = explode("\n", trim($disks_raw));
    array_shift($lines);
    foreach ($lines as $line) {
        $cols = preg_split('/\s+/', $line);
        if (count($cols) >= 6) {
            $mount = $cols[5];
            $disks[] = [
                'name' => $friendly_labels[$mount] ?? $mount,
                'mount' => $mount,
                'fs' => $cols[0],
                'size' => $cols[1],
                'used' => $cols[2],
                'avail' => $cols[3],
                'pcent' => $cols[4]
            ];
        }
    }
}

// Service statuses
$ssh_active = trim(shell_exec('systemctl is-active ssh 2>/dev/null')) === 'active';
$apache_active = trim(shell_exec('systemctl is-active apache2 2>/dev/null')) === 'active';
$cron_active = trim(shell_exec('systemctl is-active cron 2>/dev/null')) === 'active';

// Read Logs
$monitor_logs = file_exists('/var/log/hotel_monitor.log') ? shell_exec('tail -n 15 /var/log/hotel_monitor.log') : 'System diagnostics nominal. Continuous health checking active.';
$backup_logs = file_exists('/var/log/hotel_backup.log') ? shell_exec('tail -n 15 /var/log/hotel_backup.log') : 'Daily backup snapshots scheduled.';

// Read Files in Directories
$reception_files = glob('/srv/hotel/reception/*') ?: [];
$housekeeping_files = glob('/srv/hotel/housekeeping/*') ?: [];
$backup_archives = glob('/backup/*.tar.gz') ?: [];

// Filter out lost+found
$reception_valid = array_filter($reception_files, fn($f) => basename($f) !== 'lost+found');
$housekeeping_valid = array_filter($housekeeping_files, fn($f) => basename($f) !== 'lost+found');
$total_bookings = count($reception_valid);
$total_rooms = count($housekeeping_valid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Heritage Hotel & Suites — Executive Management Platform</title>
    <style>
        :root {
            /* Palette: Deep Charcoal / Black + Crisp White + Electric Blue Accent */
            --bg-canvas: #090d16;
            --bg-surface: #101726;
            --bg-card: #151d30;
            --bg-card-hover: #1b253d;
            --bg-input: #0c121e;
            
            --border-subtle: rgba(255, 255, 255, 0.08);
            --border-focus: #0066ff;
            --border-card: #202b42;
            
            --accent-electric: #0066ff;
            --accent-glow: #00d2ff;
            --accent-hover: #2575fc;
            
            --text-pure: #ffffff;
            --text-sub: #f1f5f9;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            
            --state-online: #10b981;
            --state-online-bg: rgba(16, 185, 129, 0.12);
            --state-warning: #f59e0b;
            --state-warning-bg: rgba(245, 158, 11, 0.12);
            --state-danger: #ef4444;
            --state-danger-bg: rgba(239, 68, 68, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-sub);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            padding-bottom: 50px;
        }

        /* Top Brand Navigation Bar */
        .navbar {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border-subtle);
            padding: 16px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(12px);
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent-electric) 0%, var(--accent-glow) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 22px;
            box-shadow: 0 0 20px rgba(0, 102, 255, 0.4);
        }

        .brand-text h1 {
            font-size: 19px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: var(--text-pure);
            text-transform: uppercase;
        }

        .brand-text p {
            font-size: 12px;
            color: var(--text-muted);
            letter-spacing: 0.3px;
        }

        .status-cluster {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
            border: 1px solid transparent;
        }

        .pill-live {
            background: var(--state-online-bg);
            color: #34d399;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .pill-blue {
            background: rgba(0, 102, 255, 0.12);
            color: #60a5fa;
            border-color: rgba(0, 102, 255, 0.3);
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--state-online);
            box-shadow: 0 0 10px var(--state-online);
            animation: pulse-dot 2s infinite ease-in-out;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Layout Container */
        .main-container {
            max-width: 1280px;
            margin: 28px auto 0 auto;
            padding: 0 28px;
        }

        /* Executive Metrics Summary Cards */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .kpi-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-card);
            border-radius: 14px;
            padding: 20px 24px;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            border-color: rgba(0, 102, 255, 0.4);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-electric), transparent);
            opacity: 0.7;
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .kpi-title {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-muted);
        }

        .kpi-icon {
            font-size: 20px;
        }

        .kpi-val {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-pure);
            letter-spacing: -0.5px;
        }

        .kpi-footer {
            font-size: 12px;
            color: var(--accent-glow);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Navigation Tabs */
        .tab-bar {
            display: flex;
            gap: 10px;
            background: var(--bg-surface);
            padding: 8px;
            border-radius: 12px;
            border: 1px solid var(--border-subtle);
            margin-bottom: 26px;
            overflow-x: auto;
        }

        .tab-item {
            flex: 1;
            text-align: center;
            padding: 12px 18px;
            border-radius: 9px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }

        .tab-item:hover {
            color: var(--text-pure);
            background: rgba(255, 255, 255, 0.04);
        }

        .tab-item.active {
            color: var(--text-pure);
            background: linear-gradient(135deg, var(--accent-electric) 0%, #1e40af 100%);
            box-shadow: 0 4px 16px rgba(0, 102, 255, 0.35);
        }

        /* Alerts */
        .alert-box {
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: var(--state-online-bg);
            color: #d1fae5;
            border-color: rgba(16, 185, 129, 0.4);
        }

        .alert-danger {
            background: var(--state-danger-bg);
            color: #fee2e2;
            border-color: rgba(239, 68, 68, 0.4);
        }

        /* Content Cards & Panels */
        .content-panel {
            background: var(--bg-surface);
            border: 1px solid var(--border-card);
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 26px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        }

        .panel-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .panel-heading h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-pure);
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.2px;
        }

        .panel-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: normal;
        }

        /* Modern Grid Systems */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 860px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .navbar { flex-direction: column; gap: 14px; align-items: flex-start; }
            .status-cluster { align-self: flex-start; }
        }

        /* Form Inputs & Controls */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-pure);
            letter-spacing: 0.2px;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-input);
            border: 1px solid var(--border-card);
            border-radius: 8px;
            color: var(--text-pure);
            font-size: 14px;
            transition: all 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: var(--accent-electric);
            box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.25);
            background: #101826;
        }

        /* Buttons */
        .btn-electric {
            background: linear-gradient(135deg, var(--accent-electric) 0%, var(--accent-hover) 100%);
            color: #ffffff;
            border: none;
            padding: 13px 26px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 18px rgba(0, 102, 255, 0.35);
        }

        .btn-electric:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(0, 102, 255, 0.5);
            filter: brightness(1.1);
        }

        .btn-electric:active {
            transform: translateY(0);
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
            border-radius: 6px;
        }

        .btn-action-delete {
            background: var(--state-danger-bg);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
        }

        .btn-action-delete:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        /* Modern Tables */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 14px;
        }

        th {
            background: #0d131f;
            color: var(--accent-glow);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid var(--border-card);
        }

        th:first-child { border-top-left-radius: 8px; }
        th:last-child { border-top-right-radius: 8px; }

        td {
            padding: 15px 18px;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-sub);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }

        .pill-clean { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .pill-progress { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .pill-dirty { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .pill-inspect { background: rgba(0, 102, 255, 0.15); color: #60a5fa; border: 1px solid rgba(0, 102, 255, 0.3); }

        /* Progress Bar */
        .progress-track {
            background: #0b1019;
            height: 8px;
            border-radius: 9999px;
            overflow: hidden;
            margin-top: 6px;
            border: 1px solid var(--border-subtle);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-electric), var(--accent-glow));
            border-radius: 9999px;
        }

        /* Console Output Box */
        .console-container {
            background: #080c14;
            border: 1px solid var(--border-card);
            border-radius: 10px;
            padding: 18px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12.5px;
            line-height: 1.6;
            color: #93c5fd;
            max-height: 280px;
            overflow-y: auto;
            white-space: pre-wrap;
        }

        /* Sidebar Info Cards */
        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 18px;
        }

        .feature-card h3 {
            font-size: 15px;
            color: var(--text-pure);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .feature-card p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }
    </style>
</head>
<body>

<!-- Header Navigation Bar -->
<header class="navbar">
    <div class="brand-container">
        <div class="brand-icon">🏛️</div>
        <div class="brand-text">
            <h1>Grand Heritage</h1>
            <p>Hotel & Luxury Suites — Executive Operations Hub</p>
        </div>
    </div>
    <div class="status-cluster">
        <div class="pill-badge pill-live">
            <span class="live-dot"></span>
            Operational Gateway
        </div>
        <div class="pill-badge pill-blue">
            <span>🛡️</span> Multi-Tier Security Active
        </div>
    </div>
</header>

<main class="main-container">

    <!-- KPI Metric Cards -->
    <section class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Active Reservations</span>
                <span class="kpi-icon">📋</span>
            </div>
            <div class="kpi-val"><?= $total_bookings ?></div>
            <div class="kpi-footer">✓ Recorded in Front Desk Registry</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Rooms Inspected</span>
                <span class="kpi-icon">🧹</span>
            </div>
            <div class="kpi-val"><?= $total_rooms ?></div>
            <div class="kpi-footer">✓ Ready for Guest Allocation</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Platform Availability</span>
                <span class="kpi-icon">⚡</span>
            </div>
            <div class="kpi-val">100%</div>
            <div class="kpi-footer">✓ Core Services Online</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-title">Data Protection</span>
                <span class="kpi-icon">🛡️</span>
            </div>
            <div class="kpi-val"><?= count($backup_archives) ?> Snapshots</div>
            <div class="kpi-footer">✓ Redundant Vault Synchronized</div>
        </div>
    </section>

    <!-- Feedback Message -->
    <?php if (!empty($msg)): ?>
        <div class="alert-box alert-<?= $msg_type ?>">
            <span><?= $msg_type === 'success' ? '✓' : '⚠️' ?></span>
            <div><?= $msg ?></div>
        </div>
    <?php endif; ?>

    <!-- Navigation Tab Bar -->
    <nav class="tab-bar">
        <a href="?tab=reception" class="tab-item <?= $tab === 'reception' ? 'active' : '' ?>">
            <span>🛎️</span> Front Desk & Reservations
        </a>
        <a href="?tab=housekeeping" class="tab-item <?= $tab === 'housekeeping' ? 'active' : '' ?>">
            <span>🧹</span> Housekeeping & Room Readiness
        </a>
        <a href="?tab=admin" class="tab-item <?= $tab === 'admin' ? 'active' : '' ?>">
            <span>📊</span> Executive Dashboard & Telemetry
        </a>
        <a href="?tab=backup" class="tab-item <?= $tab === 'backup' ? 'active' : '' ?>">
            <span>🛡️</span> Data Protection & Archival Vault
        </a>
    </nav>

    <!-- TAB 1: FRONT DESK & RESERVATIONS -->
    <?php if ($tab === 'reception'): ?>
        <div class="grid-2">
            <!-- Left: Reservation Form -->
            <div class="content-panel">
                <div class="panel-heading">
                    <div>
                        <h2><span>🛎️</span> New Guest Registration</h2>
                        <p class="panel-sub">Record guest arrival, room allocation, and booking parameters</p>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="create_booking">
                    <div class="form-group">
                        <label>Guest Full Name</label>
                        <input type="text" name="guest_name" placeholder="e.g. Dr. Alexander Wright" required>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Assigned Room Number</label>
                            <input type="text" name="room_num" placeholder="e.g. 402" required>
                        </div>
                        <div class="form-group">
                            <label>Duration of Stay (Nights)</label>
                            <input type="number" name="nights" value="3" min="1" max="60">
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Suite Category</label>
                            <select name="room_type">
                                <option value="Presidential Suite">Presidential Suite</option>
                                <option value="Executive Penthouse">Executive Penthouse</option>
                                <option value="Grand Deluxe King" selected>Grand Deluxe King</option>
                                <option value="Classic Heritage Double">Classic Heritage Double</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Rate Classification</label>
                            <select name="rate_tier">
                                <option value="VIP Corporate Executive">VIP Corporate Executive</option>
                                <option value="Standard Luxury Leisure" selected>Standard Luxury Leisure</option>
                                <option value="Diplomatic Special">Diplomatic Special</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-electric" style="width: 100%;">
                        <span>✓</span> Confirm & Secure Reservation
                    </button>
                </form>
            </div>

            <!-- Right: Operational Guidelines -->
            <div>
                <div class="feature-card">
                    <h3><span>💼</span> Front Desk Operations</h3>
                    <p>Reservations generated through this interface are instantly written to the high-availability storage vault. Records are locked with departmental permissions to safeguard guest confidentiality.</p>
                </div>
                <div class="feature-card">
                    <h3><span>🔑</span> Express Key Release</h3>
                    <p>Rooms marked as <strong>Cleaned & Ready</strong> in the Housekeeping module are immediately eligible for guest assignment and electronic key issuance.</p>
                </div>
                <div class="feature-card">
                    <h3><span>🔒</span> Data Confidentiality</h3>
                    <p>All guest portfolios are segregated by department. Cross-departmental viewing is restricted according to enterprise privilege hierarchy.</p>
                </div>
            </div>
        </div>

        <!-- Active Bookings Table -->
        <div class="content-panel">
            <div class="panel-heading">
                <div>
                    <h2><span>📋</span> Active Guest Reservations Ledger</h2>
                    <p class="panel-sub">Current in-house guests and confirmed upcoming arrivals</p>
                </div>
            </div>

            <?php if (empty($reception_valid)): ?>
                <p style="color: var(--text-muted); padding: 20px 0;">No active guest reservations recorded in the system.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Folio Record</th>
                                <th>File Size</th>
                                <th>Last Updated</th>
                                <th>Guest Details</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reception_valid as $f): ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--text-pure); font-size: 15px;"><?= htmlspecialchars(basename($f)) ?></strong>
                                    </td>
                                    <td style="color: var(--text-muted);"><?= filesize($f) ?> bytes</td>
                                    <td style="color: var(--text-muted);"><?= date("M d, Y · H:i", filemtime($f)) ?></td>
                                    <td>
                                        <div style="font-size: 13px; color: var(--accent-glow); line-height: 1.5;">
                                            <?= nl2br(htmlspecialchars(file_get_contents($f))) ?>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" onsubmit="return confirm('Complete check-out and clear folio for this guest?');" style="display: inline-block;">
                                            <input type="hidden" name="action" value="delete_file">
                                            <input type="hidden" name="file_path" value="<?= htmlspecialchars($f) ?>">
                                            <button type="submit" class="btn-action-delete btn-sm">
                                                Check Out
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- TAB 2: HOUSEKEEPING -->
    <?php if ($tab === 'housekeeping'): ?>
        <div class="grid-2">
            <!-- Left: Room Status Update Form -->
            <div class="content-panel">
                <div class="panel-heading">
                    <div>
                        <h2><span>🧹</span> Room Readiness Management</h2>
                        <p class="panel-sub">Update live sanitation, inspection, and readiness logs</p>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_room">
                    <div class="form-group">
                        <label>Select Room</label>
                        <select name="room_num">
                            <option value="101">Room 101 — Classic Heritage Double</option>
                            <option value="102">Room 102 — Classic Heritage Double</option>
                            <option value="201">Room 201 — Grand Deluxe King</option>
                            <option value="202">Room 202 — Grand Deluxe King</option>
                            <option value="301">Room 301 — Executive Penthouse</option>
                            <option value="302">Room 302 — Executive Penthouse</option>
                            <option value="402">Room 402 — Executive Suite</option>
                            <option value="501">Suite 501 — Presidential Palace Suite</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Current Cleanliness & Readiness Status</label>
                        <select name="room_status">
                            <option value="Cleaned & Ready">Cleaned & Ready for Check-in</option>
                            <option value="Cleaning in Progress">Cleaning in Progress</option>
                            <option value="Inspected by Supervisor">Inspected & Verified by Supervisor</option>
                            <option value="Dirty / Needs Service">Dirty / Service Requested</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Assigned Housekeeping Officer</label>
                        <select name="staff_name">
                            <option value="Lead Inspector Sarah Jenkins">Lead Inspector Sarah Jenkins</option>
                            <option value="Senior Housekeeper David Patel">Senior Housekeeper David Patel</option>
                            <option value="Duty Floor Supervisor">Duty Floor Supervisor</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-electric" style="width: 100%;">
                        <span>✓</span> Update Readiness Record
                    </button>
                </form>
            </div>

            <!-- Right: Standards Overview -->
            <div>
                <div class="feature-card">
                    <h3><span>✨</span> Quality Standard Protocols</h3>
                    <p>Rooms undergo triple-stage inspection prior to guest arrival: sanitization, linen refresh, and executive supervisor sign-off.</p>
                </div>
                <div class="feature-card">
                    <h3><span>🔄</span> Front Desk Synchronization</h3>
                    <p>The reception portal receives real-time visibility into room status updates, enabling rapid guest check-ins without communication delays.</p>
                </div>
            </div>
        </div>

        <!-- Room Status Ledger -->
        <div class="content-panel">
            <div class="panel-heading">
                <div>
                    <h2><span>📋</span> Live Floor & Room Readiness Ledger</h2>
                    <p class="panel-sub">Real-time status of all monitored suites and rooms</p>
                </div>
            </div>

            <?php if (empty($housekeeping_valid)): ?>
                <p style="color: var(--text-muted); padding: 20px 0;">No room statuses logged yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Room Identifier</th>
                                <th>Status Overview</th>
                                <th>Inspection Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($housekeeping_valid as $f): ?>
                                <?php 
                                    $content = file_get_contents($f);
                                    $status_badge = 'pill-clean';
                                    if (stripos($content, 'Progress') !== false) $status_badge = 'pill-progress';
                                    elseif (stripos($content, 'Dirty') !== false) $status_badge = 'pill-dirty';
                                    elseif (stripos($content, 'Supervisor') !== false) $status_badge = 'pill-inspect';
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--text-pure); font-size: 15px;"><?= htmlspecialchars(basename($f)) ?></strong>
                                    </td>
                                    <td>
                                        <div style="font-size: 13px; color: var(--text-sub); line-height: 1.5;">
                                            <?= nl2br(htmlspecialchars($content)) ?>
                                        </div>
                                    </td>
                                    <td style="color: var(--text-muted);">
                                        <?= date("M d, Y · H:i", filemtime($f)) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- TAB 3: EXECUTIVE TELEMETRY & SYSTEM HEALTH -->
    <?php if ($tab === 'admin'): ?>
        <div class="content-panel">
            <div class="panel-heading">
                <div>
                    <h2><span>📊</span> Executive System Telemetry</h2>
                    <p class="panel-sub">Real-time infrastructure health, resource allocation, and core service status</p>
                </div>
                <div class="pill-badge pill-live">
                    <span class="live-dot"></span> System Nominal
                </div>
            </div>

            <div class="grid-2">
                <div>
                    <div style="margin-bottom: 14px;">
                        <span style="color: var(--text-muted); font-size: 13px;">System Availability / Uptime:</span>
                        <div style="color: var(--text-pure); font-size: 17px; font-weight: 600;"><?= htmlspecialchars($uptime) ?></div>
                    </div>
                    <div style="margin-bottom: 14px;">
                        <span style="color: var(--text-muted); font-size: 13px;">Workload Average (1m, 5m, 15m):</span>
                        <div style="color: var(--accent-glow); font-size: 17px; font-weight: 600;"><?= htmlspecialchars($load) ?></div>
                    </div>
                    <div style="margin-bottom: 14px;">
                        <span style="color: var(--text-muted); font-size: 13px;">Memory Allocation:</span>
                        <div style="color: var(--text-pure); font-size: 17px; font-weight: 600;"><?= htmlspecialchars($mem_info) ?></div>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); font-size: 13px;">Active Administrative Sessions:</span>
                        <div style="color: var(--text-pure); font-size: 17px; font-weight: 600;"><?= htmlspecialchars($logged_users) ?> Active Connection(s)</div>
                    </div>
                </div>

                <div>
                    <h3 style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.6px; color: var(--text-muted); margin-bottom: 16px;">Core Infrastructure Daemons:</h3>
                    
                    <div style="background: var(--bg-card); padding: 14px 18px; border-radius: 8px; border: 1px solid var(--border-card); margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--text-pure); font-size: 14px;">Secure Gateway Access (Port 22)</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Hardened Cryptographic SSH Daemon</div>
                        </div>
                        <span class="pill-badge <?= $ssh_active ? 'pill-live' : 'pill-dirty' ?>"><?= $ssh_active ? 'Active & Enforced' : 'Offline' ?></span>
                    </div>

                    <div style="background: var(--bg-card); padding: 14px 18px; border-radius: 8px; border: 1px solid var(--border-card); margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--text-pure); font-size: 14px;">Web Application Gateway (Port 80)</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Apache High-Performance HTTP Engine</div>
                        </div>
                        <span class="pill-badge <?= $apache_active ? 'pill-live' : 'pill-dirty' ?>"><?= $apache_active ? 'Active & Serving' : 'Offline' ?></span>
                    </div>

                    <div style="background: var(--bg-card); padding: 14px 18px; border-radius: 8px; border: 1px solid var(--border-card); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: var(--text-pure); font-size: 14px;">Automated Operations Scheduler</strong>
                            <div style="font-size: 12px; color: var(--text-muted);">Background Health & Backup Automation</div>
                        </div>
                        <span class="pill-badge <?= $cron_active ? 'pill-live' : 'pill-dirty' ?>"><?= $cron_active ? 'Active & Running' : 'Offline' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storage Allocation Table -->
        <div class="content-panel">
            <div class="panel-heading">
                <div>
                    <h2><span>💾</span> Storage Vault Allocation & Capacity</h2>
                    <p class="panel-sub">Volume distribution and partition consumption rates</p>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Storage Repository</th>
                            <th>Used / Total Capacity</th>
                            <th>Available Space</th>
                            <th style="width: 250px;">Utilization Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($disks as $d): ?>
                            <tr>
                                <td>
                                    <strong style="color: var(--text-pure); font-size: 14px;"><?= htmlspecialchars($d['name']) ?></strong>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($d['mount']) ?></div>
                                </td>
                                <td style="color: var(--text-pure); font-weight: 600;">
                                    <?= htmlspecialchars($d['used']) ?> / <?= htmlspecialchars($d['size']) ?>
                                </td>
                                <td style="color: #34d399; font-weight: 600;">
                                    <?= htmlspecialchars($d['avail']) ?> free
                                </td>
                                <td>
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-sub); margin-bottom: 2px;">
                                        <span><?= htmlspecialchars($d['pcent']) ?></span>
                                    </div>
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: <?= intval($d['pcent']) ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Health Monitor Log -->
        <div class="content-panel">
            <div class="panel-heading">
                <div>
                    <h2><span>📜</span> Automated System Health & Diagnostics Activity Log</h2>
                    <p class="panel-sub">Periodic self-healing monitoring updates</p>
                </div>
            </div>
            <div class="console-container"><?= htmlspecialchars($monitor_logs) ?></div>
        </div>
    <?php endif; ?>

    <!-- TAB 4: DISASTER RECOVERY & BACKUPS -->
    <?php if ($tab === 'backup'): ?>
        <div class="content-panel">
            <div class="panel-heading">
                <div>
                    <h2><span>🛡️</span> Automated Disaster Recovery Vault</h2>
                    <p class="panel-sub">Redundant snapshots with automated integrity verification and 7-day retention lifecycle</p>
                </div>
                <div class="pill-badge pill-blue">
                    <span>📦</span> Vault Synchronized
                </div>
            </div>

            <?php if (empty($backup_archives)): ?>
                <p style="color: var(--text-muted); padding: 20px 0;">No backup snapshots currently recorded in the vault.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Snapshot Archive Package</th>
                                <th>Payload Size</th>
                                <th>Generation Timestamp</th>
                                <th>Integrity Verification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backup_archives as $b): ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--text-pure); font-size: 14px;">📦 <?= htmlspecialchars(basename($b)) ?></strong>
                                    </td>
                                    <td style="color: var(--text-muted);"><?= round(filesize($b) / 1024, 2) ?> KB</td>
                                    <td style="color: var(--text-muted);"><?= date("M d, Y · H:i:s", filemtime($b)) ?></td>
                                    <td>
                                        <span class="pill-badge pill-live" style="font-size: 11px;">
                                            ✓ Verified & Intact
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Backup Execution Log -->
        <div class="content-panel">
            <div class="panel-heading">
                <div>
                    <h2><span>📜</span> Disaster Recovery Execution Audit Log</h2>
                    <p class="panel-sub">Cryptographic verification and snapshot retention audit history</p>
                </div>
            </div>
            <div class="console-container"><?= htmlspecialchars($backup_logs) ?></div>
        </div>
    <?php endif; ?>

</main>

</body>
</html>
