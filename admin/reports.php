<?php
require_once '../config.php';
checkUserType('admin');

$conn = getDBConnection();

// Handle report generation
$report_data = null;
$report_type = '';
$report_summary = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = sanitize($_POST['report_type']);
    $date_range = sanitize($_POST['date_range']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $filter_type = sanitize($_POST['filter_type']);
    $filter_value = sanitize($_POST['filter_value']);
    
    // Set date range
    if ($date_range === 'custom') {
        $from_date = $start_date;
        $to_date = $end_date;
    } else {
        $to_date = date('Y-m-d');
        switch($date_range) {
            case 'today':
                $from_date = $to_date;
                break;
            case 'week':
                $from_date = date('Y-m-d', strtotime('-7 days'));
                break;
            case 'month':
                $from_date = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'year':
                $from_date = date('Y-m-d', strtotime('-1 year'));
                break;
            default:
                $from_date = date('Y-m-01');
        }
    }
    
    // Generate report based on type
    switch($report_type) {
        case 'attendance':
            $query = "SELECT a.*, s.roll_number, s.first_name, s.last_name, 
                     c.class_name, c.section, a.attendance_date, a.status
                     FROM attendance a
                     JOIN students s ON a.student_id = s.id
                     LEFT JOIN classes c ON s.class_id = c.id
                     WHERE a.attendance_date BETWEEN '$from_date' AND '$to_date'";
            
            if ($filter_type === 'class' && $filter_value) {
                $query .= " AND s.class_id = " . intval($filter_value);
            } elseif ($filter_type === 'student' && $filter_value) {
                $query .= " AND s.id = " . intval($filter_value);
            }
            
            $query .= " ORDER BY a.attendance_date DESC, s.roll_number";
            $report_data = $conn->query($query);
            
            // Calculate summary
            $summary_query = "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as total_absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as total_late
                FROM attendance 
                WHERE attendance_date BETWEEN '$from_date' AND '$to_date'";
            $report_summary = $conn->query($summary_query)->fetch_assoc();
            $report_summary['attendance_rate'] = $report_summary['total_records'] > 0 
                ? round(($report_summary['total_present'] / $report_summary['total_records']) * 100, 2) 
                : 0;
            break;
            
        case 'fees':
            $query = "SELECT f.*, s.roll_number, s.first_name, s.last_name, 
                     c.class_name, c.section, f.fee_type, f.amount, 
                     f.due_date, f.status, f.payment_date, f.payment_method
                     FROM fees f
                     JOIN students s ON f.student_id = s.id
                     LEFT JOIN classes c ON s.class_id = c.id
                     WHERE f.created_at BETWEEN '$from_date' AND '$to_date'";
            
            if ($filter_type === 'class' && $filter_value) {
                $query .= " AND s.class_id = " . intval($filter_value);
            } elseif ($filter_type === 'student' && $filter_value) {
                $query .= " AND s.id = " . intval($filter_value);
            }
            
            $query .= " ORDER BY f.due_date DESC";
            $report_data = $conn->query($query);
            
            // Calculate summary
            $summary_query = "SELECT 
                COUNT(*) as total_records,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
                SUM(CASE WHEN status = 'overdue' THEN amount ELSE 0 END) as total_overdue
                FROM fees 
                WHERE created_at BETWEEN '$from_date' AND '$to_date'";
            $report_summary = $conn->query($summary_query)->fetch_assoc();
            $report_summary['collection_rate'] = $report_summary['total_amount'] > 0 
                ? round(($report_summary['total_paid'] / $report_summary['total_amount']) * 100, 2) 
                : 0;
            break;
            
        case 'students':
            $query = "SELECT s.*, c.class_name, c.section, u.status as account_status,
                     (SELECT COUNT(*) FROM fees WHERE student_id = s.id AND status = 'pending') as pending_fees_count,
                     (SELECT COUNT(*) FROM attendance WHERE student_id = s.id AND status = 'present' 
                      AND attendance_date BETWEEN '$from_date' AND '$to_date') as days_present
                     FROM students s
                     LEFT JOIN classes c ON s.class_id = c.id
                     LEFT JOIN users u ON s.user_id = u.id
                     WHERE s.created_at BETWEEN '$from_date' AND '$to_date'";
            
            if ($filter_type === 'class' && $filter_value) {
                $query .= " AND s.class_id = " . intval($filter_value);
            }
            
            $query .= " ORDER BY s.roll_number";
            $report_data = $conn->query($query);
            
            $report_summary['total_students'] = $report_data->num_rows;
            break;
            
        case 'teachers':
            $query = "SELECT t.*, u.status as account_status,
                     (SELECT COUNT(*) FROM classes WHERE teacher_id = t.id) as total_classes
                     FROM teachers t
                     LEFT JOIN users u ON t.user_id = u.id
                     WHERE t.created_at BETWEEN '$from_date' AND '$to_date'
                     ORDER BY t.employee_id";
            $report_data = $conn->query($query);
            
            $report_summary['total_teachers'] = $report_data->num_rows;
            break;
    }
}

// Get filter options
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name");
$students = $conn->query("SELECT id, roll_number, first_name, last_name FROM students ORDER BY roll_number");
$teachers = $conn->query("SELECT id, employee_id, first_name, last_name FROM teachers ORDER BY employee_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        /* Top Navigation */
        .top-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .top-nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }
        
        .nav-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        /* Report Generator Card */
        .generator-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .generator-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .generator-header h2 {
            font-size: 24px;
            color: #333;
        }
        
        .generator-icon {
            font-size: 36px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group select, .form-group input {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-generate {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }
        
        .summary-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .summary-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .summary-card .subtext {
            font-size: 13px;
            color: #999;
        }
        
        /* Report Preview */
        .report-preview {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .report-header h2 {
            font-size: 22px;
            color: #333;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-export {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-pdf {
            background: #e74c3c;
            color: white;
        }
        
        .btn-excel {
            background: #27ae60;
            color: white;
        }
        
        .btn-csv {
            background: #3498db;
            color: white;
        }
        
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .report-table thead {
            background: #f8f9fa;
        }
        
        .report-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .report-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }
        
        .report-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-present {
            background: #d4edda;
            color: #155724;
        }
        
        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-late {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .no-data-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .export-buttons {
                flex-direction: column;
            }
            
            .report-table {
                font-size: 12px;
            }
            
            .report-table th, .report-table td {
                padding: 8px;
            }
        }
        
        @media print {
            .top-nav, .generator-card, .export-buttons {
                display: none !important;
            }
            
            body {
                background: white;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="top-nav-content">
            <div class="nav-left">
                <a href="dashboard.php" class="back-btn">
                    <span>←</span> Dashboard
                </a>
                <h1 class="page-title">📊 Reports & Analytics</h1>
            </div>
            <a href="../logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Report Generator -->
        <div class="generator-card">
            <div class="generator-header">
                <span class="generator-icon">📈</span>
                <h2>Generate Report</h2>
            </div>
            
            <form method="POST" id="reportForm">
                <input type="hidden" name="generate_report" value="1">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Report Type *</label>
                        <select name="report_type" id="reportType" required onchange="updateFilters()">
                            <option value="">Select Report Type</option>
                            <option value="attendance" <?php echo $report_type === 'attendance' ? 'selected' : ''; ?>>📋 Attendance Report</option>
                            <option value="fees" <?php echo $report_type === 'fees' ? 'selected' : ''; ?>>💰 Fees Report</option>
                            <option value="students" <?php echo $report_type === 'students' ? 'selected' : ''; ?>>👨‍🎓 Students Report</option>
                            <option value="teachers" <?php echo $report_type === 'teachers' ? 'selected' : ''; ?>>👨‍🏫 Teachers Report</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Date Range *</label>
                        <select name="date_range" id="dateRange" required onchange="toggleCustomDates()">
                            <option value="today">Today</option>
                            <option value="week">Last 7 Days</option>
                            <option value="month" selected>Last 30 Days</option>
                            <option value="year">Last Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="startDateGroup" style="display: none;">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="startDate">
                    </div>
                    
                    <div class="form-group" id="endDateGroup" style="display: none;">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="endDate" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Filter By</label>
                        <select name="filter_type" id="filterType" onchange="updateFilterValue()">
                            <option value="">All Records</option>
                            <option value="class">By Class</option>
                            <option value="student">By Student</option>
                            <option value="teacher">By Teacher</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="filterValueGroup" style="display: none;">
                        <label id="filterValueLabel">Select Value</label>
                        <select name="filter_value" id="filterValue">
                            <option value="">Select...</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn-generate">
                    <span>📊</span> Generate Report
                </button>
            </form>
        </div>
        
        <?php if ($report_data && $report_data->num_rows > 0): ?>
            <!-- Summary Cards -->
            <?php if (!empty($report_summary)): ?>
            <div class="summary-grid">
                <?php if ($report_type === 'attendance'): ?>
                    <div class="summary-card">
                        <h3>Total Records</h3>
                        <div class="value"><?php echo $report_summary['total_records']; ?></div>
                        <div class="subtext">Attendance entries</div>
                    </div>
                    <div class="summary-card">
                        <h3>Present</h3>
                        <div class="value"><?php echo $report_summary['total_present']; ?></div>
                        <div class="subtext"><?php echo $report_summary['attendance_rate']; ?>% attendance rate</div>
                    </div>
                    <div class="summary-card">
                        <h3>Absent</h3>
                        <div class="value"><?php echo $report_summary['total_absent']; ?></div>
                        <div class="subtext">Students missed</div>
                    </div>
                    <div class="summary-card">
                        <h3>Late Arrivals</h3>
                        <div class="value"><?php echo $report_summary['total_late']; ?></div>
                        <div class="subtext">Late entries</div>
                    </div>
                <?php elseif ($report_type === 'fees'): ?>
                    <div class="summary-card">
                        <h3>Total Amount</h3>
                        <div class="value">$<?php echo number_format($report_summary['total_amount'], 2); ?></div>
                        <div class="subtext"><?php echo $report_summary['total_records']; ?> fee records</div>
                    </div>
                    <div class="summary-card">
                        <h3>Collected</h3>
                        <div class="value">$<?php echo number_format($report_summary['total_paid'], 2); ?></div>
                        <div class="subtext"><?php echo $report_summary['collection_rate']; ?>% collection rate</div>
                    </div>
                    <div class="summary-card">
                        <h3>Pending</h3>
                        <div class="value">$<?php echo number_format($report_summary['total_pending'], 2); ?></div>
                        <div class="subtext">Awaiting payment</div>
                    </div>
                    <div class="summary-card">
                        <h3>Overdue</h3>
                        <div class="value">$<?php echo number_format($report_summary['total_overdue'], 2); ?></div>
                        <div class="subtext">Past due date</div>
                    </div>
                <?php elseif ($report_type === 'students'): ?>
                    <div class="summary-card">
                        <h3>Total Students</h3>
                        <div class="value"><?php echo $report_summary['total_students']; ?></div>
                        <div class="subtext">In selected period</div>
                    </div>
                <?php elseif ($report_type === 'teachers'): ?>
                    <div class="summary-card">
                        <h3>Total Teachers</h3>
                        <div class="value"><?php echo $report_summary['total_teachers']; ?></div>
                        <div class="subtext">In selected period</div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Report Preview -->
            <div class="report-preview">
                <div class="report-header">
                    <h2><?php echo ucfirst($report_type); ?> Report</h2>
                    <div class="export-buttons">
                        <button class="btn-export btn-pdf" onclick="exportReport('pdf')">
                            <span>📄</span> Export PDF
                        </button>
                        <button class="btn-export btn-excel" onclick="exportReport('excel')">
                            <span>📊</span> Export Excel
                        </button>
                        <button class="btn-export btn-csv" onclick="exportReport('csv')">
                            <span>📋</span> Export CSV
                        </button>
                        <button class="btn-export" onclick="window.print()" style="background: #95a5a6;">
                            <span>🖨️</span> Print
                        </button>
                    </div>
                </div>
                
                <table class="report-table" id="reportTable">
                    <thead>
                        <tr>
                            <?php if ($report_type === 'attendance'): ?>
                                <th>Date</th>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Status</th>
                            <?php elseif ($report_type === 'fees'): ?>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                            <?php elseif ($report_type === 'students'): ?>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Class</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Pending Fees</th>
                                <th>Days Present</th>
                            <?php elseif ($report_type === 'teachers'): ?>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Classes</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $report_data->fetch_assoc()): ?>
                            <tr>
                                <?php if ($report_type === 'attendance'): ?>
                                    <td><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                                    <td><?php echo $row['roll_number']; ?></td>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['class_name'] . ' - ' . $row['section']; ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <?php elseif ($report_type === 'fees'): ?>
                                    <td><?php echo $row['roll_number']; ?></td>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['class_name'] . ' - ' . $row['section']; ?></td>
                                    <td><?php echo $row['fee_type']; ?></td>
                                    <td>$<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><?php echo $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '-'; ?></td>
                                <?php elseif ($report_type === 'students'): ?>
                                    <td><?php echo $row['roll_number']; ?></td>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['class_name'] . ' - ' . $row['section']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><span class="status-badge status-<?php echo $row['account_status']; ?>"><?php echo ucfirst($row['account_status']); ?></span></td>
                                    <td><?php echo $row['pending_fees_count']; ?></td>
                                    <td><?php echo $row['days_present']; ?></td>
                                <?php elseif ($report_type === 'teachers'): ?>
                                    <td><?php echo $row['employee_id']; ?></td>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['subject']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['phone']; ?></td>
                                    <td><span class="status-badge status-<?php echo $row['account_status']; ?>"><?php echo ucfirst($row['account_status']); ?></span></td>
                                    <td><?php echo $row['total_classes']; ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="report-preview">
                <div class="no-data">
                    <div class="no-data-icon">📭</div>
                    <h3>No Data Found</h3>
                    <p>No records found for the selected criteria. Try adjusting your filters.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="report-preview">
                <div class="no-data">
                    <div class="no-data-icon">📊</div>
                    <h3>Ready to Generate Reports</h3>
                    <p>Select report type and filters above, then click "Generate Report" to view data.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Toggle custom date fields
        function toggleCustomDates() {
            const dateRange = document.getElementById('dateRange').value;
            const startDateGroup = document.getElementById('startDateGroup');
            const endDateGroup = document.getElementById('endDateGroup');
            
            if (dateRange === 'custom') {
                startDateGroup.style.display = 'block';
                endDateGroup.style.display = 'block';
                document.getElementById('startDate').required = true;
                document.getElementById('endDate').required = true;
            } else {
                startDateGroup.style.display = 'none';
                endDateGroup.style.display = 'none';
                document.getElementById('startDate').required = false;
                document.getElementById('endDate').required = false;
            }
        }
        
        // Update filter options based on report type
        function updateFilters() {
            const reportType = document.getElementById('reportType').value;
            const filterType = document.getElementById('filterType');
            
            // Reset filter
            filterType.selectedIndex = 0;
            updateFilterValue();
            
            // Update available filters based on report type
            const classOption = filterType.querySelector('option[value="class"]');
            const studentOption = filterType.querySelector('option[value="student"]');
            const teacherOption = filterType.querySelector('option[value="teacher"]');
            
            if (reportType === 'teachers') {
                classOption.style.display = 'none';
                studentOption.style.display = 'none';
                teacherOption.style.display = 'none';
            } else if (reportType === 'students') {
                classOption.style.display = 'block';
                studentOption.style.display = 'none';
                teacherOption.style.display = 'none';
            } else {
                classOption.style.display = 'block';
                studentOption.style.display = 'block';
                teacherOption.style.display = 'none';
            }
        }
        
        // Update filter value dropdown based on filter type
        function updateFilterValue() {
            const filterType = document.getElementById('filterType').value;
            const filterValueGroup = document.getElementById('filterValueGroup');
            const filterValue = document.getElementById('filterValue');
            const filterValueLabel = document.getElementById('filterValueLabel');
            
            if (!filterType) {
                filterValueGroup.style.display = 'none';
                return;
            }
            
            filterValueGroup.style.display = 'block';
            filterValue.innerHTML = '<option value="">Select...</option>';
            
            if (filterType === 'class') {
                filterValueLabel.textContent = 'Select Class';
                <?php 
                $classes->data_seek(0);
                while ($class = $classes->fetch_assoc()): 
                ?>
                filterValue.innerHTML += '<option value="<?php echo $class['id']; ?>"><?php echo $class['class_name'] . ' - ' . $class['section']; ?></option>';
                <?php endwhile; ?>
            } else if (filterType === 'student') {
                filterValueLabel.textContent = 'Select Student';
                <?php 
                $students->data_seek(0);
                while ($student = $students->fetch_assoc()): 
                ?>
                filterValue.innerHTML += '<option value="<?php echo $student['id']; ?>"><?php echo $student['roll_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']; ?></option>';
                <?php endwhile; ?>
            } else if (filterType === 'teacher') {
                filterValueLabel.textContent = 'Select Teacher';
                <?php 
                $teachers->data_seek(0);
                while ($teacher = $teachers->fetch_assoc()): 
                ?>
                filterValue.innerHTML += '<option value="<?php echo $teacher['id']; ?>"><?php echo $teacher['employee_id'] . ' - ' . $teacher['first_name'] . ' ' . $teacher['last_name']; ?></option>';
                <?php endwhile; ?>
            }
        }
        
        // Export functions
        function exportReport(format) {
            const table = document.getElementById('reportTable');
            const reportType = document.getElementById('reportType').value;
            
            if (format === 'csv') {
                exportToCSV(table, reportType);
            } else if (format === 'excel') {
                exportToExcel(table, reportType);
            } else if (format === 'pdf') {
                alert('PDF export functionality requires a backend library like TCPDF or mPDF. For now, please use the Print option.');
                // In production, you would call a PHP script that generates PDF
                // window.location.href = 'export_pdf.php?type=' + reportType;
            }
        }
        
        // Export to CSV
        function exportToCSV(table, filename) {
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                
                csv.push(row.join(','));
            }
            
            downloadCSV(csv.join('\n'), filename + '_report.csv');
        }
        
        function downloadCSV(csv, filename) {
            let csvFile;
            let downloadLink;
            
            csvFile = new Blob([csv], {type: 'text/csv'});
            downloadLink = document.createElement('a');
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        // Export to Excel (HTML table format)
        function exportToExcel(table, filename) {
            const uri = 'data:application/vnd.ms-excel;base64,';
            const template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--><meta http-equiv="content-type" content="text/plain; charset=UTF-8"/></head><body><table>{table}</table></body></html>';
            
            const base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) };
            const format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) };
            
            const ctx = {
                worksheet: filename || 'Worksheet',
                table: table.innerHTML
            };
            
            const link = document.createElement('a');
            link.download = filename + '_report.xls';
            link.href = uri + base64(format(template, ctx));
            link.click();
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleCustomDates();
            updateFilters();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>