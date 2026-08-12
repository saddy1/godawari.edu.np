<?php

namespace App\Support;

class AdminPermissionMatrix
{
    public static function modules(): array
    {
        return [
            'website' => [
                'label' => 'Website',
                'components' => [
                    'dashboard' => [
                        'label' => 'Dashboard',
                        'permissions' => [
                            'view' => 'dashboard.admin',
                            'general' => 'dashboard.view',
                            'financial' => 'dashboard.financial',
                        ],
                    ],
                    'staff' => [
                        'label' => 'Staff & Roles',
                        'permissions' => [
                            'view' => 'users.view',
                            'create' => 'users.create',
                            'edit' => 'users.edit',
                            'delete' => 'users.delete',
                        ],
                    ],
                    'admissions' => [
                        'label' => 'Admissions',
                        'permissions' => [
                            'view' => 'students.admission',
                            'edit' => 'students.edit',
                            'delete' => 'students.delete',
                        ],
                    ],
                    'announcements' => [
                        'label' => 'Notices & Events',
                        'permissions' => [
                            'view' => 'announcements.view',
                            'create' => 'announcements.create',
                            'edit' => 'announcements.edit',
                            'delete' => 'announcements.delete',
                        ],
                    ],
                    'faculty' => [
                        'label' => 'Faculty Directory',
                        'permissions' => [
                            'view' => 'faculty.view',
                            'create' => 'faculty.create',
                            'edit' => 'faculty.edit',
                            'delete' => 'faculty.delete',
                        ],
                    ],
                    'gallery' => [
                        'label' => 'Gallery',
                        'permissions' => [
                            'view' => 'media.view',
                            'create' => 'media.create',
                            'edit' => 'media.edit',
                            'delete' => 'media.delete',
                        ],
                    ],
                    'popups' => [
                        'label' => 'Popup Notices',
                        'permissions' => [
                            'view' => 'popups.view',
                            'create' => 'popups.create',
                            'edit' => 'popups.edit',
                            'delete' => 'popups.delete',
                        ],
                    ],
                    'testimonials' => [
                        'label' => 'Testimonials',
                        'permissions' => [
                            'view' => 'testimonials.view',
                            'create' => 'testimonials.create',
                            'edit' => 'testimonials.edit',
                            'delete' => 'testimonials.delete',
                        ],
                    ],
                    'vacancies' => [
                        'label' => 'Vacancies',
                        'permissions' => [
                            'view' => 'vacancies.view',
                            'create' => 'vacancies.create',
                            'edit' => 'vacancies.edit',
                            'delete' => 'vacancies.delete',
                            'applications' => 'vacancies.applications',
                        ],
                    ],
                    'academics' => [
                        'label' => 'Academics',
                        'permissions' => [
                            'view' => 'academics.view',
                            'create' => 'academics.create',
                            'edit' => 'academics.edit',
                        ],
                    ],
                    'reports' => [
                        'label' => 'Reports',
                        'permissions' => [
                            'view' => 'reports.view',
                            'export' => 'reports.export',
                            'schedule' => 'reports.schedule',
                        ],
                    ],
                    'payroll' => [
                        'label' => 'Payroll',
                        'permissions' => [
                            'view' => 'payroll.view',
                            'create' => 'payroll.create',
                            'process' => 'payroll.process',
                            'approve' => 'payroll.approve',
                            'report' => 'payroll.report',
                        ],
                    ],
                    'contacts' => [
                        'label' => 'Contact Messages',
                        'permissions' => [
                            'view' => 'contacts.view',
                            'edit' => 'contacts.edit',
                            'delete' => 'contacts.delete',
                        ],
                    ],
                    'settings' => [
                        'label' => 'Settings',
                        'permissions' => [
                            'view' => 'settings.view',
                            'edit' => 'settings.edit',
                        ],
                    ],
                ],
            ],
            'billing' => [
                'label' => 'Billing',
                'components' => [
                    'bills' => [
                        'label' => 'Receipts & Bills',
                        'permissions' => [
                            'view' => 'billing.view',
                            'create' => 'billing.create',
                            'delete' => 'billing.delete',
                        ],
                    ],
                ],
            ],
            'store' => [
                'label' => 'Store Management',
                'components' => [
                    'inventory' => [
                        'label' => 'Inventory Master',
                        'permissions' => [
                            'view' => 'store.view',
                            'create' => 'store.create',
                            'edit' => 'store.edit',
                            'delete' => 'store.delete',
                        ],
                    ],
                    'transactions' => [
                        'label' => 'Demand, Purchase & Issue',
                        'permissions' => [
                            'view' => 'store.view',
                            'create' => 'store.create',
                            'approve' => 'store.approve',
                        ],
                    ],
                    'reports' => [
                        'label' => 'Government Forms & Ledgers',
                        'permissions' => [
                            'view' => 'store.reports',
                        ],
                    ],
                ],
            ],
            'library' => [
                'label' => 'Library',
                'components' => [
                    'catalog' => [
                        'label' => 'Catalog & Copies',
                        'permissions' => [
                            'view' => 'library.view',
                            'create' => 'library.create',
                            'edit' => 'library.edit',
                        ],
                    ],
                    'circulation' => [
                        'label' => 'Issue, Return & Fines',
                        'permissions' => [
                            'view' => 'library.view',
                            'issue' => 'library.issue',
                            'reports' => 'library.reports',
                        ],
                    ],
                ],
            ],
            'hr' => [
                'label' => 'HR',
                'components' => [
                    'members' => [
                        'label' => 'People Master',
                        'permissions' => [
                            'view' => 'hr.members.view',
                            'create' => 'hr.members.create',
                            'edit' => 'hr.members.edit',
                            'delete' => 'hr.members.delete',
                        ],
                    ],
                    'designations' => [
                        'label' => 'Designations',
                        'permissions' => [
                            'view' => 'settings.view',
                            'edit' => 'settings.edit',
                        ],
                    ],
                ],
            ],
            'id-card' => [
                'label' => 'Student Management',
                'components' => [
                    'members' => [
                        'label' => 'Members',
                        'permissions' => [
                            'view' => 'students.view',
                            'create' => 'students.create',
                            'edit' => 'students.edit',
                            'delete' => 'students.delete',
                        ],
                    ],
                    'import' => [
                        'label' => 'Import',
                        'permissions' => [
                            'view' => 'students.view',
                            'create' => 'users.bulk-import',
                        ],
                    ],
                    'cards' => [
                        'label' => 'Cards',
                        'permissions' => [
                            'view' => 'cards.view',
                            'create' => 'cards.print',
                            'edit' => 'cards.print',
                        ],
                    ],
                    'student-requests' => [
                        'label' => 'Student Requests',
                        'permissions' => [
                            'view' => 'students.card-request',
                            'edit' => 'students.card-request',
                        ],
                    ],
                    'certificates' => [
                        'label' => 'Certificates',
                        'permissions' => [
                            'view' => 'hr.certificates.view',
                            'create' => 'hr.certificates.create',
                            'delete' => 'hr.certificates.delete',
                        ],
                    ],
                    'card-settings' => [
                        'label' => 'Settings',
                        'permissions' => [
                            'view' => 'card-settings.view',
                            'create' => 'card-settings.create',
                            'edit' => 'card-settings.edit',
                            'delete' => 'card-settings.delete',
                        ],
                    ],
                ],
            ],
            'hajiri' => [
                'label' => 'Hajiri',
                'components' => [
                    'attendance' => [
                        'label' => 'Attendance',
                        'permissions' => [
                            'view' => 'attendance.view',
                            'create' => 'attendance.create',
                            'edit' => 'attendance.edit',
                        ],
                    ],
                    'reports' => [
                        'label' => 'Reports',
                        'permissions' => [
                            'view' => 'attendance.report',
                            'create' => 'attendance.export',
                        ],
                    ],
                    'employees' => [
                        'label' => 'Employees',
                        'permissions' => [
                            'view' => 'users.view',
                            'create' => 'users.create',
                            'edit' => 'users.edit',
                            'delete' => 'users.delete',
                        ],
                    ],
                    'leaves' => [
                        'label' => 'Leaves',
                        'permissions' => [
                            'view' => 'leaves.view',
                            'create' => 'leaves.create',
                            'edit' => 'leaves.approve',
                            'delete' => 'leaves.reject',
                            'cancel' => 'leaves.cancel',
                        ],
                    ],
                    'hajiri-settings' => [
                        'label' => 'Settings',
                        'permissions' => [
                            'view' => 'settings.view',
                            'create' => 'settings.edit',
                            'edit' => 'settings.edit',
                            'delete' => 'settings.system',
                        ],
                    ],
                ],
            ],
            'learning' => [
                'label' => 'E-Learning',
                'components' => [
                    'courses' => [
                        'label' => 'Courses',
                        'permissions' => [
                            'view' => 'learning.courses.view',
                            'create' => 'learning.courses.create',
                            'edit' => 'learning.courses.edit',
                            'delete' => 'learning.courses.delete',
                        ],
                    ],
                    'student-accounts' => [
                        'label' => 'Student Accounts',
                        'permissions' => [
                            'view' => 'learning.students.view',
                            'create' => 'learning.students.create',
                            'edit' => 'learning.students.edit',
                            'delete' => 'learning.students.delete',
                        ],
                    ],
                    'lessons' => [
                        'label' => 'Lessons',
                        'permissions' => [
                            'view' => 'learning.lessons.view',
                            'create' => 'learning.lessons.create',
                            'edit' => 'learning.lessons.edit',
                            'delete' => 'learning.lessons.delete',
                        ],
                    ],
                    'resources' => [
                        'label' => 'Notes & Question Bank',
                        'permissions' => [
                            'view' => 'learning.resources.view',
                            'create' => 'learning.resources.create',
                            'edit' => 'learning.resources.edit',
                            'delete' => 'learning.resources.delete',
                        ],
                    ],
                    'teacher-mapping' => [
                        'label' => 'Teacher Class Mapping',
                        'permissions' => [
                            'view' => 'learning.teacher.assign',
                            'edit' => 'learning.teacher.assign',
                        ],
                    ],
                    'quizzes' => [
                        'label' => 'Mock Tests',
                        'permissions' => [
                            'view' => 'learning.quizzes.view',
                            'create' => 'learning.quizzes.create',
                            'edit' => 'learning.quizzes.edit',
                            'delete' => 'learning.quizzes.delete',
                        ],
                    ],
                    'reports' => [
                        'label' => 'Reports',
                        'permissions' => [
                            'view' => 'learning.reports.view',
                        ],
                    ],
                ],
            ],
            'work-tasks' => [
                'label' => 'Work Tasks',
                'components' => [
                    'tasks' => [
                        'label' => 'Task Assignment',
                        'permissions' => [
                            'view' => 'work-tasks.view',
                            'create' => 'work-tasks.create',
                            'submit' => 'work-tasks.submit',
                            'review' => 'work-tasks.review',
                        ],
                    ],
                    'groups' => [
                        'label' => 'Groups & Committees',
                        'permissions' => [
                            'manage' => 'work-groups.manage',
                        ],
                    ],
                    'checklists' => [
                        'label' => 'Task Checklists',
                        'permissions' => [
                            'manage' => 'work-checklists.manage',
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function names(): array
    {
        $names = [];

        foreach (self::modules() as $module) {
            foreach ($module['components'] as $component) {
                foreach ($component['permissions'] as $permission) {
                    $names[] = $permission;
                }
            }
        }

        return array_values(array_unique($names));
    }
}
