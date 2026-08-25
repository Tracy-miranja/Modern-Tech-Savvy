<x-auth-layout>
@php
    $moduleContent = [
        'core-hr-management' => [
            'tagline' => 'Employee records, departments, and organization structure.',
            'features' => ['Central employee database', 'Departments & organization structure', 'Job categories & roles', 'Document storage per employee'],
            'demo' => [
                'stats' => [
                    ['label' => 'Employees', 'value' => '128', 'icon' => 'bi-people-fill'],
                    ['label' => 'Departments', 'value' => '9', 'icon' => 'bi-diagram-3-fill'],
                    ['label' => 'On Probation', 'value' => '6', 'icon' => 'bi-hourglass-split'],
                    ['label' => 'New This Month', 'value' => '4', 'icon' => 'bi-person-plus-fill'],
                ],
                'tabs' => [
                    'Employees' => ['type' => 'table', 'columns' => ['Employee', 'Department', 'Job Title', 'Status', 'Joined'], 'rows' => [
                        ['Amina Chebet', 'Engineering', 'Senior Developer', ['badge' => 'success', 'text' => 'Active'], '12 Jan 2023'],
                        ['Brian Otieno', 'Finance', 'Accountant', ['badge' => 'success', 'text' => 'Active'], '03 Mar 2022'],
                        ['Grace Wambui', 'Human Resources', 'HR Officer', ['badge' => 'warning', 'text' => 'On Leave'], '19 Jul 2021'],
                        ['Kevin Mwangi', 'Engineering', 'QA Engineer', ['badge' => 'info', 'text' => 'Probation'], '02 Aug 2026'],
                        ['Faith Nyambura', 'Sales', 'Account Manager', ['badge' => 'success', 'text' => 'Active'], '15 Nov 2020'],
                    ]],
                    'Departments' => ['type' => 'table', 'columns' => ['Department', 'Head', 'Employees', 'Open Roles'], 'rows' => [
                        ['Engineering', 'Amina Chebet', '42', '3'],
                        ['Finance', 'Brian Otieno', '11', '0'],
                        ['Human Resources', 'Grace Wambui', '6', '1'],
                        ['Sales', 'Faith Nyambura', '23', '2'],
                    ]],
                ],
            ],
        ],
        'leave-management' => [
            'tagline' => 'Leave requests, multi-level approvals, entitlements, and balances - synced straight into attendance.',
            'features' => ['Multi-level approval workflows', 'Leave types & entitlement policies', 'Automatic attendance sync', 'Leave calendar & encashment'],
            'demo' => [
                'stats' => [
                    ['label' => 'Pending Requests', 'value' => '5', 'icon' => 'bi-hourglass-split'],
                    ['label' => 'Approved This Month', 'value' => '31', 'icon' => 'bi-check2-square'],
                    ['label' => 'On Leave Today', 'value' => '6', 'icon' => 'bi-airplane-fill'],
                    ['label' => 'Avg Balance', 'value' => '12.4 days', 'icon' => 'bi-calendar2-week-fill'],
                ],
                'tabs' => [
                    'Requests' => ['type' => 'table', 'columns' => ['Employee', 'Type', 'Dates', 'Days', 'Status'], 'rows' => [
                        ['Grace Wambui', 'Annual Leave', '02 - 06 Sep 2026', '5', ['badge' => 'warning', 'text' => 'Pending']],
                        ['Kevin Mwangi', 'Sick Leave', '14 Aug 2026', '1', ['badge' => 'success', 'text' => 'Approved']],
                        ['Faith Nyambura', 'Annual Leave', '20 - 24 Jul 2026', '5', ['badge' => 'success', 'text' => 'Approved']],
                        ['Brian Otieno', 'Compassionate Leave', '18 Aug 2026', '2', ['badge' => 'warning', 'text' => 'Pending']],
                    ]],
                    'Balances' => ['type' => 'table', 'columns' => ['Employee', 'Leave Type', 'Entitled', 'Used', 'Remaining'], 'rows' => [
                        ['Amina Chebet', 'Annual Leave', '21', '9', '12'],
                        ['Brian Otieno', 'Annual Leave', '21', '14', '7'],
                        ['Grace Wambui', 'Annual Leave', '21', '5', '16'],
                        ['Kevin Mwangi', 'Sick Leave', '14', '2', '12'],
                    ]],
                ],
            ],
        ],
        'payroll-management' => [
            'tagline' => 'Run payroll, handle statutory deductions, and pay your team accurately.',
            'features' => ['Automated payroll runs', 'Statutory deductions (PAYE, NSSF, SHIF, etc.)', 'Payslips & bank advice exports', 'Multi-currency support'],
            'demo' => [
                'stats' => [
                    ['label' => 'Net Payroll', 'value' => 'KES 4.2M', 'icon' => 'bi-cash-stack'],
                    ['label' => 'Employees Paid', 'value' => '128', 'icon' => 'bi-people-fill'],
                    ['label' => 'Avg Net Pay', 'value' => 'KES 32,850', 'icon' => 'bi-graph-up'],
                    ['label' => 'Pending Approval', 'value' => '3', 'icon' => 'bi-hourglass-split'],
                ],
                'tabs' => [
                    'Payroll Run - August 2026' => ['type' => 'table', 'columns' => ['Employee', 'Gross Pay', 'Deductions', 'Net Pay', 'Status'], 'rows' => [
                        ['Amina Chebet', 'KES 95,000', 'KES 21,300', 'KES 73,700', ['badge' => 'success', 'text' => 'Paid']],
                        ['Brian Otieno', 'KES 62,000', 'KES 13,900', 'KES 48,100', ['badge' => 'success', 'text' => 'Paid']],
                        ['Grace Wambui', 'KES 58,500', 'KES 12,750', 'KES 45,750', ['badge' => 'warning', 'text' => 'Pending']],
                        ['Kevin Mwangi', 'KES 41,000', 'KES 8,200', 'KES 32,800', ['badge' => 'success', 'text' => 'Paid']],
                    ]],
                    'Payslip Preview' => ['type' => 'payslip', 'employee' => 'Amina Chebet', 'role' => 'Senior Developer', 'period' => 'August 2026', 'rows' => [
                        ['Basic Salary', 'KES 85,000'], ['House Allowance', 'KES 10,000'], ['PAYE', '- KES 15,200'],
                        ['NSSF', '- KES 2,160'], ['SHIF', '- KES 3,940'], ['Net Pay', 'KES 73,700'],
                    ]],
                ],
            ],
        ],
        'recruitment-onboarding' => [
            'tagline' => 'Post jobs, track applicants, and onboard new hires.',
            'features' => ['Public job postings', 'Applicant tracking', 'Interview scheduling', 'Recruitment reports'],
            'demo' => [
                'stats' => [
                    ['label' => 'Open Positions', 'value' => '5', 'icon' => 'bi-briefcase-fill'],
                    ['label' => 'Applicants', 'value' => '87', 'icon' => 'bi-people-fill'],
                    ['label' => 'Interviews Scheduled', 'value' => '9', 'icon' => 'bi-calendar-event-fill'],
                    ['label' => 'Hired This Month', 'value' => '2', 'icon' => 'bi-person-check-fill'],
                ],
                'tabs' => [
                    'Job Postings' => ['type' => 'table', 'columns' => ['Job Title', 'Department', 'Applicants', 'Stage', 'Posted'], 'rows' => [
                        ['Product Designer', 'Engineering', '24', ['badge' => 'info', 'text' => 'Screening'], '10 Aug 2026'],
                        ['Payroll Officer', 'Finance', '18', ['badge' => 'primary', 'text' => 'Interviewing'], '05 Aug 2026'],
                        ['Sales Executive', 'Sales', '31', ['badge' => 'info', 'text' => 'Screening'], '14 Aug 2026'],
                        ['Backend Engineer', 'Engineering', '14', ['badge' => 'success', 'text' => 'Offer Sent'], '01 Aug 2026'],
                    ]],
                    'Applicant Pipeline' => ['type' => 'kanban', 'columns' => [
                        'Applied' => ['Cynthia Achieng - Product Designer', 'Dennis Kiptoo - Sales Executive', 'Esther Wanjiru - Backend Engineer'],
                        'Screening' => ['Felix Omondi - Product Designer', 'George Mutua - Payroll Officer'],
                        'Interview' => ['Hannah Njoki - Payroll Officer'],
                        'Offer' => ['Ian Karanja - Backend Engineer'],
                    ]],
                ],
            ],
        ],
        'performance-management' => [
            'tagline' => 'Set objectives, run appraisal cycles, and track KPIs.',
            'features' => ['Appraisal cycles', 'Objectives & key results', 'KPI tracking', 'Performance reviews & feedback'],
            'demo' => [
                'stats' => [
                    ['label' => 'Active Cycles', 'value' => '1', 'icon' => 'bi-arrow-repeat'],
                    ['label' => 'Objectives Set', 'value' => '64', 'icon' => 'bi-bullseye'],
                    ['label' => 'Reviews Completed', 'value' => '41', 'icon' => 'bi-check2-square'],
                    ['label' => 'Avg Score', 'value' => '4.2 / 5', 'icon' => 'bi-star-fill'],
                ],
                'tabs' => [
                    'Objectives' => ['type' => 'progress', 'rows' => [
                        ['Amina Chebet', 'Ship v2 of the mobile app', 82],
                        ['Brian Otieno', 'Close August books by day 3', 60],
                        ['Grace Wambui', 'Reduce time-to-hire to 21 days', 45],
                        ['Kevin Mwangi', 'Automate the QA regression suite', 95],
                    ]],
                    'Reviews' => ['type' => 'table', 'columns' => ['Employee', 'Cycle', 'Reviewer', 'Score', 'Status'], 'rows' => [
                        ['Amina Chebet', 'Q3 2026', 'Faith Nyambura', '4.6 / 5', ['badge' => 'success', 'text' => 'Completed']],
                        ['Brian Otieno', 'Q3 2026', 'Amina Chebet', '4.1 / 5', ['badge' => 'success', 'text' => 'Completed']],
                        ['Grace Wambui', 'Q3 2026', 'Amina Chebet', '-', ['badge' => 'warning', 'text' => 'In Progress']],
                    ]],
                ],
            ],
        ],
        'learning-management' => [
            'tagline' => 'Give your team courses and track their development.',
            'features' => ['Course catalog', 'Employee enrollments', 'Course categories', 'Learning reports'],
            'demo' => [
                'stats' => [
                    ['label' => 'Courses', 'value' => '22', 'icon' => 'bi-journal-bookmark-fill'],
                    ['label' => 'Enrollments', 'value' => '340', 'icon' => 'bi-person-video3'],
                    ['label' => 'Completed', 'value' => '211', 'icon' => 'bi-check2-square'],
                    ['label' => 'Avg Completion', 'value' => '78%', 'icon' => 'bi-graph-up'],
                ],
                'tabs' => [
                    'Course Catalog' => ['type' => 'table', 'columns' => ['Course', 'Category', 'Enrolled', 'Completion'], 'rows' => [
                        ['Workplace Safety Basics', 'Compliance', '128', '92%'],
                        ['Effective People Management', 'Leadership', '46', '64%'],
                        ['Excel for Finance Teams', 'Skills', '31', '71%'],
                        ['Customer Service Excellence', 'Sales', '58', '80%'],
                    ]],
                    'My Learning' => ['type' => 'progress', 'rows' => [
                        ['Amina Chebet', 'Effective People Management', 70],
                        ['Brian Otieno', 'Excel for Finance Teams', 100],
                        ['Grace Wambui', 'Workplace Safety Basics', 45],
                    ]],
                ],
            ],
        ],
        'time-attendance' => [
            'tagline' => 'Clock in/out via geofencing or biometric devices, with shift scheduling.',
            'features' => ['Geofenced self clock-in/out', 'Biometric device integration', 'Shift & roster scheduling', 'Overtime tracking'],
            'demo' => [
                'stats' => [
                    ['label' => 'Present Today', 'value' => '112', 'icon' => 'bi-person-check-fill'],
                    ['label' => 'On Leave', 'value' => '6', 'icon' => 'bi-airplane-fill'],
                    ['label' => 'Late Arrivals', 'value' => '4', 'icon' => 'bi-alarm-fill'],
                    ['label' => 'Absent', 'value' => '2', 'icon' => 'bi-person-x-fill'],
                ],
                'tabs' => [
                    "Today's Attendance" => ['type' => 'table', 'columns' => ['Employee', 'Clock In', 'Clock Out', 'Status'], 'rows' => [
                        ['Amina Chebet', '08:02', '17:05', ['badge' => 'success', 'text' => 'On Time']],
                        ['Brian Otieno', '08:41', '—', ['badge' => 'warning', 'text' => 'Late']],
                        ['Grace Wambui', '—', '—', ['badge' => 'info', 'text' => 'On Leave']],
                        ['Kevin Mwangi', '07:58', '17:02', ['badge' => 'success', 'text' => 'On Time']],
                        ['Faith Nyambura', '—', '—', ['badge' => 'danger', 'text' => 'Absent']],
                    ]],
                    'Devices' => ['type' => 'table', 'columns' => ['Device', 'Vendor', 'Location', 'Status'], 'rows' => [
                        ['Main Gate Terminal', 'ZKTeco', 'Nairobi HQ', ['badge' => 'success', 'text' => 'Active']],
                        ['Warehouse Scanner', 'Hikvision', 'Mombasa Depot', ['badge' => 'success', 'text' => 'Active']],
                        ['Reception Kiosk', 'ZKTeco', 'Nairobi HQ', ['badge' => 'secondary', 'text' => 'Inactive']],
                    ]],
                ],
            ],
        ],
        'asset-management' => [
            'tagline' => 'Track company assets and who they are assigned to.',
            'features' => ['Asset register', 'Assignment history', 'Condition tracking', 'Asset reports'],
            'demo' => [
                'stats' => [
                    ['label' => 'Total Assets', 'value' => '312', 'icon' => 'bi-box-seam-fill'],
                    ['label' => 'Assigned', 'value' => '271', 'icon' => 'bi-person-badge-fill'],
                    ['label' => 'Available', 'value' => '38', 'icon' => 'bi-check-circle-fill'],
                    ['label' => 'Under Repair', 'value' => '3', 'icon' => 'bi-tools'],
                ],
                'tabs' => [
                    'Assets' => ['type' => 'table', 'columns' => ['Asset', 'Category', 'Assigned To', 'Status'], 'rows' => [
                        ['MacBook Pro 14" - #A1042', 'Laptop', 'Amina Chebet', ['badge' => 'success', 'text' => 'Assigned']],
                        ['Dell Latitude 5420 - #A1108', 'Laptop', 'Brian Otieno', ['badge' => 'success', 'text' => 'Assigned']],
                        ['Toyota Hiace - KDA 214B', 'Vehicle', '—', ['badge' => 'secondary', 'text' => 'Available']],
                        ['HP LaserJet M404 - #A0921', 'Printer', 'Nairobi HQ', ['badge' => 'warning', 'text' => 'Under Repair']],
                    ]],
                ],
            ],
        ],
        'employee-self-service' => [
            'tagline' => 'Employees manage their own profile, leave, and payslips.',
            'features' => ['Self-service portal', 'Leave requests', 'Payslip access', 'Career history'],
            'demo' => [
                'stats' => [
                    ['label' => 'Leave Balance', 'value' => '14 days', 'icon' => 'bi-airplane-fill'],
                    ['label' => 'Payslips Available', 'value' => '8', 'icon' => 'bi-receipt'],
                    ['label' => 'Pending Requests', 'value' => '1', 'icon' => 'bi-hourglass-split'],
                    ['label' => 'Open Tasks', 'value' => '3', 'icon' => 'bi-check2-square'],
                ],
                'tabs' => [
                    'My Leave' => ['type' => 'table', 'columns' => ['Type', 'Dates', 'Days', 'Status'], 'rows' => [
                        ['Annual Leave', '02 - 06 Sep 2026', '5', ['badge' => 'warning', 'text' => 'Pending']],
                        ['Sick Leave', '14 Jul 2026', '1', ['badge' => 'success', 'text' => 'Approved']],
                        ['Annual Leave', '20 - 24 Apr 2026', '5', ['badge' => 'success', 'text' => 'Approved']],
                    ]],
                    'My Payslips' => ['type' => 'table', 'columns' => ['Period', 'Net Pay', 'Status'], 'rows' => [
                        ['August 2026', 'KES 73,700', ['badge' => 'success', 'text' => 'Available']],
                        ['July 2026', 'KES 73,700', ['badge' => 'success', 'text' => 'Available']],
                        ['June 2026', 'KES 71,200', ['badge' => 'success', 'text' => 'Available']],
                    ]],
                ],
            ],
        ],
        'crm-integration' => [
            'tagline' => 'Manage leads, contacts, and campaigns.',
            'features' => ['Contact submissions', 'Lead tracking', 'Campaign management', 'CRM reports'],
            'demo' => [
                'stats' => [
                    ['label' => 'Leads', 'value' => '156', 'icon' => 'bi-person-lines-fill'],
                    ['label' => 'Contacts', 'value' => '892', 'icon' => 'bi-people-fill'],
                    ['label' => 'Active Campaigns', 'value' => '4', 'icon' => 'bi-megaphone-fill'],
                    ['label' => 'Conversion Rate', 'value' => '18%', 'icon' => 'bi-graph-up-arrow'],
                ],
                'tabs' => [
                    'Contacts' => ['type' => 'table', 'columns' => ['Name', 'Company', 'Status', 'Last Contact'], 'rows' => [
                        ['Peter Kamau', 'Zenith Traders', ['badge' => 'success', 'text' => 'Customer'], '2 days ago'],
                        ['Susan Achieng', 'Highland Foods', ['badge' => 'info', 'text' => 'Lead'], '5 days ago'],
                        ['Tom Wafula', 'Riverside Motors', ['badge' => 'warning', 'text' => 'Negotiation'], 'Today'],
                    ]],
                    'Campaigns' => ['type' => 'table', 'columns' => ['Campaign', 'Reach', 'Conversions', 'Status'], 'rows' => [
                        ['September Referral Push', '4,200', '112', ['badge' => 'success', 'text' => 'Active']],
                        ['Product Launch Webinar', '1,850', '64', ['badge' => 'success', 'text' => 'Active']],
                        ['Q2 Retargeting', '3,100', '201', ['badge' => 'secondary', 'text' => 'Ended']],
                    ]],
                ],
            ],
        ],
        'project-management' => [
            'tagline' => 'Projects, tasks, and Kanban boards for team collaboration.',
            'features' => ['Project & task tracking', 'Kanban boards', 'Time logging', 'Project reports'],
            'demo' => [
                'stats' => [
                    ['label' => 'Active Projects', 'value' => '7', 'icon' => 'bi-kanban-fill'],
                    ['label' => 'Open Tasks', 'value' => '54', 'icon' => 'bi-list-task'],
                    ['label' => 'Completed This Week', 'value' => '19', 'icon' => 'bi-check2-square'],
                    ['label' => 'Team Members', 'value' => '23', 'icon' => 'bi-people-fill'],
                ],
                'tabs' => [
                    'Board' => ['type' => 'kanban', 'columns' => [
                        'To Do' => ['Design new payslip layout (Amina)', 'Draft Q4 hiring plan (Grace)'],
                        'In Progress' => ['Biometric device integration (Kevin)', 'August payroll reconciliation (Brian)'],
                        'Review' => ['CRM campaign report (Faith)'],
                        'Done' => ['Leave/attendance sync (Kevin)', 'Public landing page (Amina)'],
                    ]],
                    'Projects' => ['type' => 'table', 'columns' => ['Project', 'Owner', 'Progress', 'Due'], 'rows' => [
                        ['Q4 Product Roadmap', 'Amina Chebet', '62%', '30 Sep 2026'],
                        ['Payroll Automation', 'Brian Otieno', '88%', '05 Sep 2026'],
                        ['Office Relocation', 'Grace Wambui', '34%', '15 Oct 2026'],
                    ]],
                ],
            ],
        ],
    ];
    $moduleLogos = [
        'core-hr-management' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="M17 18c0-4 3-7 7-7s7 3 7 7-3 7-7 7-7-3-7-7Zm16 5c0-5 4-9 9-9s9 4 9 9-4 9-9 9-9-4-9-9ZM11 48c1-10 7-16 14-16 5 0 9 2 12 6-5 2-9 7-10 13H11v-3Zm20 3c1-9 6-15 13-15s12 6 13 15H31Z"/><circle class="kw-logo-accent" cx="31" cy="31" r="4"/></svg>',
        'leave-management' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="M16 15h32a5 5 0 0 1 5 5v28a5 5 0 0 1-5 5H16a5 5 0 0 1-5-5V20a5 5 0 0 1 5-5Zm0 10v23h32V25H16Z"/><path class="kw-logo-accent" d="M21 10h5v10h-5V10Zm17 0h5v10h-5V10Zm-16 25c6-8 13-7 20-1-4 0-8 2-11 6-2-3-5-5-9-5Z"/></svg>',
        'payroll-management' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="M14 15h36v34H14V15Zm6 7v20h24V22H20Z"/><path class="kw-logo-accent" d="M26 27h12v4H26v-4Zm-3 8h18v4H23v-4Zm9-19 8 5-8 5-8-5 8-5Z"/></svg>',
        'recruitment-onboarding' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="M12 16h40v9l-15 13v12H27V38L12 25v-9Zm7 6 13 11 13-11H19Z"/><path class="kw-logo-accent" d="M45 37h5v6h6v5h-6v6h-5v-6h-6v-5h6v-6Z"/></svg>',
        'performance-management' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="M12 49h40v5H12v-5Zm5-8 9-11 8 6 12-16 4 3-15 21-8-6-6 7-4-4Z"/><path class="kw-logo-accent" d="M40 15h12v12h-5v-7h-7v-5Z"/></svg>',
        'learning-management' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="M10 18c8-4 15-3 22 2 7-5 14-6 22-2v31c-8-4-15-3-22 2-7-5-14-6-22-2V18Zm6 7v17c5-1 10 0 16 3V27c-5-4-10-5-16-2Zm32 0c-6-3-11-2-16 2v18c6-3 11-4 16-3V25Z"/><path class="kw-logo-accent" d="M29 14h6v10h-6V14Z"/></svg>',
        'time-attendance' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="M32 10a22 22 0 1 1-15.6 6.4l3.6 3.6A17 17 0 1 0 32 15v-5Z"/><path class="kw-logo-accent" d="M29 20h6v13l9 6-3 5-12-8V20Z"/></svg>',
        'asset-management' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="m32 9 21 12v24L32 57 11 45V21L32 9Zm0 7-13 7 13 7 13-7-13-7Zm-15 12v13l12 7V35l-12-7Zm18 20 12-7V28l-12 7v13Z"/><path class="kw-logo-accent" d="m23 20 9-5 9 5-9 5-9-5Z"/></svg>',
        'employee-self-service' => '<svg viewBox="0 0 64 64" aria-hidden="true"><circle class="kw-logo-dark" cx="32" cy="20" r="8"/><path class="kw-logo-dark" d="M16 51c2-12 8-18 16-18s14 6 16 18H16Z"/><path class="kw-logo-accent" d="M49 13h5v7h7v5h-7v7h-5v-7h-7v-5h7v-7Z"/></svg>',
        'crm-integration' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="M17 18h13v13H17V18Zm17 15h13v13H34V33Zm-20 8h13v13H14V41Zm11-13 12 9-3 4-12-9 3-4Zm2 16h8v5h-8v-5Z"/><circle class="kw-logo-accent" cx="47" cy="18" r="7"/></svg>',
        'project-management' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path class="kw-logo-dark" d="M10 13h20v17H10V13Zm24 0h20v10H34V13ZM10 34h20v17H10V34Zm24-7h20v24H34V27Z"/><path class="kw-logo-accent" d="M15 18h10v7H15v-7Zm24 14h10v14H39V32Z"/></svg>',
    ];

    $moduleNames = [
        'core-hr-management' => 'Core HR Management',
        'leave-management' => 'Leave Management',
        'payroll-management' => 'Payroll Management',
        'recruitment-onboarding' => 'Recruitment & Onboarding',
        'performance-management' => 'Performance Management',
        'learning-management' => 'Learning Management',
        'time-attendance' => 'Time & Attendance',
        'asset-management' => 'Asset Management',
        'employee-self-service' => 'Employee Self Service',
        'crm-integration' => 'CRM Integration',
        'project-management' => 'Project Management',
    ];
@endphp

<div class="authentication-wrapper kw-landing" style="display:block; min-height:100vh;">

    <!-- Top bar -->
    <div class="d-flex align-items-center justify-content-between px-4 py-3 kw-topbar">
        <a href="{{ route('welcome') }}" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="{{ asset('media/krstlogo.png') }}" alt="Krestworks Solutions" style="height:36px; width:auto;">
            <span class="kw-brand-name">Krestworks Solutions</span>
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('login') }}" class="btn kw-btn-outline">Sign In</a>
            <a href="{{ route('register') }}" class="btn kw-btn-primary">Create account</a>
        </div>
    </div>

    <!-- Hero -->
    <div class="kw-hero text-center px-3">
        <span class="kw-hero-eyebrow">HR and workforce software</span>
        <h1 class="mb-3">HR tools that work together</h1>
        <p class="mx-auto mb-4">
            Manage employee records, leave, attendance, payroll, recruitment and performance in one system.
            Browse the modules below using sample data; you do not need an account to look around.
        </p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ route('register') }}" class="btn kw-btn-primary btn-lg">Create account</a>
            <a href="{{ route('login') }}" class="btn kw-btn-outline-light btn-lg">Sign In</a>
        </div>
    </div>

    <!-- Trust strip -->
    <div class="container kw-trust-wrap" style="max-width:1040px;">
        <div class="kw-trust-strip">
            <div><strong>{{ count($moduleContent) }}</strong><span>Modules</span></div>
            <div class="kw-trust-divider"></div>
            <div><strong>Connected workflow</strong><span>From payroll to performance</span></div>
            <div class="kw-trust-divider"></div>
            <div><strong>5 Countries</strong><span>Kenya, Tanzania, Uganda, Ghana & Rwanda</span></div>
        </div>
    </div>

    <!-- Module grid -->
    <div class="container pb-5" style="max-width:1040px;">
        <div class="kw-section-heading">
            <h2 class="mb-2">Modules</h2>
            <p class="text-muted">Open a module to see how the screen works with sample data.</p>
        </div>
        <div class="row g-3 justify-content-center">
            @foreach ($moduleContent as $slug => $content)
                @php
                    $moduleName = $moduleNames[$slug] ?? Str::headline($slug);
                    $logoSvg = $moduleLogos[$slug] ?? $moduleLogos['core-hr-management'];
                    $modalId = 'moduleModal-' . $slug;
                    $demoId = 'demo-' . $slug;
                @endphp
                <div class="col-6 col-md-4 col-lg-3">
                    <button type="button" class="btn w-100 h-100 text-start p-3 kw-module-card"
                        data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                        <span class="kw-module-logo mb-3" aria-hidden="true">
                            {!! $logoSvg !!}
                        </span>
                        <div class="kw-module-name">{{ $moduleName }}</div>
                        <div class="text-muted kw-module-card-tagline">{{ Str::limit($content['tagline'], 58) }}</div>
                        <div class="kw-module-card-cta">View module <i class="bi bi-arrow-right"></i></div>
                    </button>
                </div>

                <!-- Module demo modal -->
                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-fullscreen">
                        <div class="modal-content kw-demo-modal">
                            <div class="modal-header kw-demo-header flex-wrap row-gap-2">
                                <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width:0;">
                                    <span class="kw-module-logo flex-shrink-0" aria-hidden="true">
                                        {!! $logoSvg !!}
                                    </span>
                                    <div style="min-width:0;">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h5 class="modal-title mb-0">{{ $moduleName }}</h5>
                                            <span class="badge kw-demo-badge">Sample data</span>
                                        </div>
                                        <small class="text-muted d-none d-md-block">{{ $content['tagline'] }}</small>
                                    </div>
                                </div>
                                <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body kw-demo-body">
                                @if ($content['demo'])

                                    <div class="row g-3 mb-4">
                                        @foreach ($content['demo']['stats'] as $stat)
                                            <div class="col-6 col-lg-3">
                                                <div class="kw-stat-card">
                                                    <span class="kw-stat-icon"><i class="bi {{ $stat['icon'] }}"></i></span>
                                                    <div>
                                                        <div class="kw-stat-value">{{ $stat['value'] }}</div>
                                                        <div class="kw-stat-label">{{ $stat['label'] }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <ul class="nav nav-tabs kw-demo-tabs" role="tablist">
                                        @foreach ($content['demo']['tabs'] as $tabName => $tabData)
                                            @php $tabId = $demoId . '-tab-' . $loop->index; @endphp
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link @if ($loop->first) active @endif" data-bs-toggle="tab"
                                                    data-bs-target="#{{ $tabId }}" type="button" role="tab">{{ $tabName }}</button>
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div class="tab-content kw-demo-tab-content">
                                        @foreach ($content['demo']['tabs'] as $tabName => $tabData)
                                            @php $tabId = $demoId . '-tab-' . $loop->index; @endphp
                                            <div class="tab-pane fade @if ($loop->first) show active @endif" id="{{ $tabId }}" role="tabpanel">

                                                @if ($tabData['type'] === 'table')
                                                    <input type="text" class="form-control form-control-sm kw-demo-search mb-2" placeholder="Search {{ strtolower($tabName) }}...">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover align-middle mb-0">
                                                            <thead>
                                                                <tr>
                                                                    @foreach ($tabData['columns'] as $col)
                                                                        <th>{{ $col }}</th>
                                                                    @endforeach
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($tabData['rows'] as $row)
                                                                    <tr class="kw-demo-row">
                                                                        @foreach ($row as $cell)
                                                                            <td>
                                                                                @if (is_array($cell))
                                                                                    <span class="badge bg-{{ $cell['badge'] }}">{{ $cell['text'] }}</span>
                                                                                @else
                                                                                    {{ $cell }}
                                                                                @endif
                                                                            </td>
                                                                        @endforeach
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                @elseif ($tabData['type'] === 'progress')
                                                    @foreach ($tabData['rows'] as $row)
                                                        <div class="kw-demo-progress-row">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span><strong>{{ $row[0] }}</strong> &mdash; {{ $row[1] }}</span>
                                                                <span class="text-muted">{{ $row[2] }}%</span>
                                                            </div>
                                                            <div class="progress" style="height:8px;">
                                                                <div class="progress-bar" style="width:{{ $row[2] }}%; background:var(--clr-theme-primary,#f5c115);"></div>
                                                            </div>
                                                        </div>
                                                    @endforeach

                                                @elseif ($tabData['type'] === 'kanban')
                                                    <div class="row g-3 kw-kanban">
                                                        @foreach ($tabData['columns'] as $colName => $cards)
                                                            <div class="col-md-3">
                                                                <div class="kw-kanban-col">
                                                                    <div class="kw-kanban-col-title">{{ $colName }} <span class="text-muted">({{ count($cards) }})</span></div>
                                                                    @foreach ($cards as $card)
                                                                        <div class="kw-kanban-card kw-demo-row">{{ $card }}</div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                @elseif ($tabData['type'] === 'payslip')
                                                    <div class="kw-payslip">
                                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                                            <div>
                                                                <div class="fw-bold">{{ $tabData['employee'] }}</div>
                                                                <div class="text-muted small">{{ $tabData['role'] }}</div>
                                                            </div>
                                                            <div class="text-end">
                                                                <div class="fw-bold">Payslip</div>
                                                                <div class="text-muted small">{{ $tabData['period'] }}</div>
                                                            </div>
                                                        </div>
                                                        <table class="table table-sm mb-0">
                                                            <tbody>
                                                                @foreach ($tabData['rows'] as $row)
                                                                    <tr class="@if ($loop->last) fw-bold border-top @endif">
                                                                        <td>{{ $row[0] }}</td>
                                                                        <td class="text-end">{{ $row[1] }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                        <button type="button" class="btn btn-sm kw-btn-outline mt-3 kw-demo-action">
                                                            <i class="bi bi-download me-1"></i> Download Payslip
                                                        </button>
                                                    </div>
                                                @endif

                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted">{{ $content['tagline'] }}</p>
                                @endif

                                @if (!empty($content['features']))
                                    <div class="kw-demo-features mt-4">
                                        <div class="kw-feature-heading mb-2">What's included</div>
                                        <div class="row g-2">
                                            @foreach ($content['features'] as $feature)
                                                <div class="col-md-6">
                                                    <div class="d-flex align-items-center gap-2 text-muted">
                                                        <i class="bi bi-check-circle-fill" style="color:var(--clr-theme-primary,#f5c115);"></i>
                                                        {{ $feature }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="modal-footer kw-demo-footer">
                                <span class="text-muted small me-auto">Sample data is shown here. Your account will display your own business information.</span>
                                <a href="{{ route('register') }}" class="btn kw-btn-primary px-4">Create account</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Closing CTA -->
    <div class="kw-cta text-center px-3">
        <h2 class="mb-2">Ready to use Krestworks with your team?</h2>
        <p class="mx-auto mb-4">Create your account, add your business details and start with the modules you need.</p>
        <a href="{{ route('register') }}" class="btn kw-btn-primary btn-lg">Create account</a>
    </div>

    <!-- Footer -->
    <div class="kw-footer">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 text-center text-md-start">
            <div class="d-flex align-items-center gap-2">
                <img src="{{ asset('media/krstlogo.png') }}" alt="Krestworks Solutions" style="height:24px; width:auto;">
                <span class="kw-footer-brand">Krestworks Solutions</span>
            </div>
            <div class="d-flex gap-3">
                <a href="{{ route('login') }}" class="text-muted text-decoration-none small">Sign In</a>
                <a href="{{ route('register') }}" class="text-muted text-decoration-none small">Get Started</a>
            </div>
            <div class="text-muted small">&copy; {{ now()->year }} Krestworks Solutions. All rights reserved.</div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /*
     * Deliberately restrained landing-page styling.
     * The product UI should feel like software first, marketing second:
     * flat surfaces, modest radii, minimal motion and a small brand palette.
     */
    .kw-landing {
        --kw-ink: #20242a;
        --kw-muted: #6b7280;
        --kw-line: #e2e5e9;
        --kw-soft: #f6f7f8;
        --kw-soft-2: #fafafa;
        --kw-brand: var(--clr-bg-primary, #f89616);
        --kw-brand-dark: #d97706;
        --kw-brand-soft: #fff7ed;
        background: #fff;
        color: var(--kw-ink);
    }

    .kw-topbar {
        background: #fff;
        border-bottom: 1px solid var(--kw-line);
        position: sticky;
        top: 0;
        z-index: 1030;
    }

    .kw-brand-name {
        color: var(--kw-ink);
        font-size: 1.06rem;
        font-weight: 600;
        letter-spacing: 0;
    }

    .kw-btn-outline,
    .kw-btn-outline-light,
    .kw-btn-primary {
        border-radius: 5px;
        font-weight: 500;
        box-shadow: none;
        transition: background-color .12s ease, border-color .12s ease, color .12s ease;
    }

    .kw-btn-outline {
        border: 1px solid #cfd4da;
        color: var(--kw-ink);
        background: #fff;
    }
    .kw-btn-outline:hover {
        background: var(--kw-soft);
        border-color: #b8bec6;
        color: var(--kw-ink);
    }

    .kw-btn-primary {
        background: var(--kw-brand);
        border: 1px solid var(--kw-brand);
        color: #fff;
    }
    .kw-btn-primary:hover {
        background: var(--kw-brand-dark);
        border-color: var(--kw-brand-dark);
        color: #fff;
    }

    .kw-btn-outline-light {
        border: 1px solid #cfd4da;
        color: var(--kw-ink);
        background: transparent;
    }
    .kw-btn-outline-light:hover {
        background: var(--kw-soft);
        color: var(--kw-ink);
    }

    .kw-hero {
        background: #fff;
        border-bottom: 1px solid var(--kw-line);
        padding: 68px 16px 64px;
    }
    .kw-hero-eyebrow {
        display: inline-block;
        color: var(--kw-brand-dark);
        font-size: .84rem;
        font-weight: 500;
        margin-bottom: 12px;
    }
    .kw-hero h1 {
        color: var(--kw-ink);
        font-size: 2.25rem;
        font-weight: 600;
        letter-spacing: -.02em;
    }
    .kw-hero p {
        color: var(--kw-muted);
        max-width: 650px;
        font-size: 1rem;
        line-height: 1.7;
    }

    .kw-trust-wrap {
        margin-top: 0;
    }
    .kw-trust-strip {
        background: #fff;
        border-bottom: 1px solid var(--kw-line);
        padding: 20px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 30px;
        flex-wrap: wrap;
    }
    .kw-trust-strip > div:not(.kw-trust-divider) { text-align: center; }
    .kw-trust-strip strong {
        display: block;
        color: var(--kw-ink);
        font-size: .96rem;
        font-weight: 600;
    }
    .kw-trust-strip span {
        display: block;
        color: var(--kw-muted);
        font-size: .78rem;
        margin-top: 2px;
    }
    .kw-trust-divider {
        width: 1px;
        height: 28px;
        background: var(--kw-line);
    }

    .kw-section-heading {
        margin-top: 44px;
        margin-bottom: 22px;
    }
    .kw-section-heading h2 {
        color: var(--kw-ink);
        font-size: 1.5rem;
        font-weight: 600;
        letter-spacing: -.01em;
    }
    .kw-section-heading p {
        max-width: 560px;
        margin-bottom: 0;
    }

    .kw-module-card {
        min-height: 178px;
        background: #fff;
        border: 1px solid var(--kw-line);
        border-radius: 6px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-start;
        box-shadow: none;
        transition: border-color .12s ease, background-color .12s ease;
    }
    .kw-module-card:hover {
        transform: none;
        box-shadow: none;
        background: var(--kw-soft-2);
        border-color: #c8cdd3;
    }
    .kw-module-card:active {
        background: var(--kw-soft);
    }
    .kw-module-name {
        color: var(--kw-ink);
        font-size: .92rem;
        font-weight: 600;
        line-height: 1.35;
    }
    .kw-module-card-tagline {
        color: var(--kw-muted) !important;
        font-size: .78rem;
        margin-top: 4px;
        line-height: 1.45;
    }
    .kw-module-card-cta {
        color: var(--kw-brand-dark);
        font-size: .76rem;
        font-weight: 500;
        margin-top: 12px;
        opacity: 1;
        transform: none;
        transition: none;
    }

    /*
     * Module logo system: bespoke vector marks, not interface icons.
     * The marks share proportions and two brand colours, while each module
     * has its own silhouette so it can work as a recognizable product logo.
     */
    .kw-module-logo {
        width: 48px;
        height: 48px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .kw-module-logo svg {
        width: 48px;
        height: 48px;
        display: block;
        overflow: visible;
        transition: transform .14s ease;
    }

    .kw-module-logo .kw-logo-dark { fill: #20242a; }
    .kw-module-logo .kw-logo-accent { fill: var(--kw-brand); }

    .kw-module-card:hover .kw-module-logo svg {
        transform: scale(1.04);
    }

    .kw-demo-modal { background: #fff; }
    .kw-demo-header {
        background: #fff;
        border-bottom: 1px solid var(--kw-line);
        padding: 1rem 1.5rem;
    }
    .kw-demo-header .modal-title {
        color: var(--kw-ink);
        font-weight: 600;
    }
    .kw-demo-badge {
        background: var(--kw-soft);
        border: 1px solid var(--kw-line);
        color: var(--kw-muted);
        font-weight: 500;
        padding: .38em .65em;
        border-radius: 4px;
    }
    .kw-demo-body {
        padding: 1.5rem;
        max-width: 1100px;
        margin: 0 auto;
        width: 100%;
        background: #fff;
    }
    .kw-demo-footer {
        background: #fff;
        border-top: 1px solid var(--kw-line);
        padding: 1rem 1.5rem;
    }

    .kw-stat-card {
        background: #fff;
        border: 1px solid var(--kw-line);
        border-radius: 6px;
        padding: 13px 14px;
        display: flex;
        align-items: center;
        gap: 11px;
        height: 100%;
        box-shadow: none;
    }
    .kw-stat-icon {
        width: 34px;
        height: 34px;
        border-radius: 5px;
        background: var(--kw-brand-soft);
        color: var(--kw-brand-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .kw-stat-value {
        color: var(--kw-ink);
        font-size: 1.05rem;
        font-weight: 600;
        line-height: 1.15;
    }
    .kw-stat-label {
        color: var(--kw-muted);
        font-size: .77rem;
        margin-top: 2px;
    }

    .kw-demo-tabs {
        border-bottom: 1px solid var(--kw-line);
        gap: 4px;
    }
    .kw-demo-tabs .nav-link {
        color: var(--kw-muted);
        border: 0;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        font-weight: 500;
        padding-left: .75rem;
        padding-right: .75rem;
    }
    .kw-demo-tabs .nav-link:hover {
        color: var(--kw-ink);
        border-bottom-color: #d7dbe0;
    }
    .kw-demo-tabs .nav-link.active {
        color: var(--kw-ink);
        border-bottom-color: var(--kw-brand);
        background: transparent;
    }
    .kw-demo-tab-content {
        background: #fff;
        border: 1px solid var(--kw-line);
        border-top: 0;
        border-radius: 0 0 6px 6px;
        padding: 16px;
    }

    .kw-demo-row { cursor: pointer; }
    .kw-demo-row:hover { background: var(--kw-soft-2); }
    .table-hover > tbody > tr:hover > * { --bs-table-accent-bg: var(--kw-soft-2); }

    .kw-demo-search {
        max-width: 280px;
        border-color: #d5d9de;
        border-radius: 5px;
        box-shadow: none;
    }
    .kw-demo-search:focus {
        border-color: #b8bec6;
        box-shadow: 0 0 0 2px rgba(32,36,42,.06);
    }

    .kw-demo-progress-row { margin-bottom: 16px; }
    .kw-demo-progress-row:last-child { margin-bottom: 0; }
    .kw-demo-progress-row strong { font-weight: 600; }

    .kw-kanban-col {
        background: var(--kw-soft);
        border: 1px solid var(--kw-line);
        border-radius: 6px;
        padding: 10px;
        min-height: 120px;
    }
    .kw-kanban-col-title {
        color: var(--kw-ink);
        font-size: .83rem;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .kw-kanban-card {
        background: #fff;
        border: 1px solid #dfe3e7;
        border-radius: 5px;
        padding: 8px 10px;
        margin-bottom: 8px;
        font-size: .82rem;
        box-shadow: none;
    }
    .kw-kanban-card:last-child { margin-bottom: 0; }

    .kw-payslip {
        background: #fff;
        border: 1px solid #d7dbe0;
        border-radius: 6px;
        padding: 18px;
        max-width: 420px;
    }
    .kw-payslip .fw-bold { font-weight: 600 !important; }

    .kw-feature-heading {
        color: var(--kw-ink);
        font-weight: 600;
        font-size: .9rem;
    }
    .kw-demo-features {
        border-top: 1px solid var(--kw-line);
        padding-top: 16px;
    }

    .kw-cta {
        background: var(--kw-soft);
        border-top: 1px solid var(--kw-line);
        padding: 52px 16px;
        margin-top: 24px;
    }
    .kw-cta h2 {
        color: var(--kw-ink);
        font-size: 1.55rem;
        font-weight: 600;
        letter-spacing: -.01em;
    }
    .kw-cta p {
        color: var(--kw-muted);
        max-width: 520px;
        line-height: 1.6;
    }

    .kw-footer {
        border-top: 1px solid var(--kw-line);
        background: #fff;
        padding: 20px 0;
    }
    .kw-footer-brand {
        color: var(--kw-ink);
        font-weight: 600;
    }

    #toast-container .toast-info {
        background-color: #3f4650 !important;
    }

    .kw-landing .btn:focus,
    .kw-landing .btn:focus-visible,
    .kw-landing .kw-module-card:focus,
    .kw-landing .kw-module-card:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(32,36,42,.12);
    }

    @media (max-width: 575px) {
        .kw-trust-divider { display: none; }
        .kw-trust-strip { gap: 18px 28px; }
    }

    @media (max-width: 767px) {
        .kw-hero { padding: 52px 16px 48px; }
        .kw-hero h1 { font-size: 1.75rem; }
        .kw-cta { padding: 44px 16px; }
        .kw-section-heading { margin-top: 36px; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('kw-demo-search')) return;
        const term = e.target.value.trim().toLowerCase();
        const tabPane = e.target.closest('.tab-pane');
        if (!tabPane) return;
        tabPane.querySelectorAll('tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });

    function demoToast(msg) {
        if (window.toastr) {
            toastr.info(msg || 'This demo uses sample data. Create an account to use this feature with your own records.');
        }
    }

    document.addEventListener('click', function (e) {
        const row = e.target.closest('.kw-demo-row');
        if (row) {
            row.classList.toggle('table-active');
            demoToast();
            return;
        }
        if (e.target.closest('.kw-demo-action')) {
            demoToast();
        }
    });
})();
</script>
@endpush
</x-auth-layout>