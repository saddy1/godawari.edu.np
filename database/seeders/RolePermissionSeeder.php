<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear all existing roles and permissions for fresh start
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Permission::truncate();
        Role::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Define all permissions
        $permissions = [
            // User Management
            'users.view' => 'View users',
            'users.create' => 'Create users',
            'users.edit' => 'Edit users',
            'users.delete' => 'Delete users',
            'users.bulk-import' => 'Bulk import users',

            // Attendance/Hajiri Module
            'attendance.view' => 'View attendance records',
            'attendance.create' => 'Mark attendance',
            'attendance.edit' => 'Edit attendance',
            'attendance.report' => 'View attendance reports',
            'attendance.export' => 'Export attendance data',

            // Payroll Management
            'payroll.view' => 'View payroll',
            'payroll.create' => 'Create payroll',
            'payroll.process' => 'Process payroll',
            'payroll.approve' => 'Approve payroll',
            'payroll.report' => 'View payroll reports',
            'billing.view' => 'View billing records',
            'billing.create' => 'Create billing records',
            'billing.delete' => 'Delete billing records',
            'library.view' => 'View library',
            'library.create' => 'Create library records',
            'library.edit' => 'Edit library records',
            'library.issue' => 'Issue and return books',
            'library.reports' => 'View library reports',

            // Leave Management
            'leaves.view' => 'View leave requests',
            'leaves.create' => 'Create leave request',
            'leaves.approve' => 'Approve leave requests',
            'leaves.reject' => 'Reject leave requests',
            'leaves.cancel' => 'Cancel leave requests',

            // Student Management
            'students.view' => 'View students',
            'students.create' => 'Create student record',
            'students.edit' => 'Edit student record',
            'students.delete' => 'Delete student record',
            'students.admission' => 'Manage admissions',
            'students.card-request' => 'Process card requests',

            // Faculty Management
            'faculty.view' => 'View faculty',
            'faculty.create' => 'Create faculty',
            'faculty.edit' => 'Edit faculty',
            'faculty.delete' => 'Delete faculty',

            // Announcements
            'announcements.view' => 'View announcements',
            'announcements.create' => 'Create announcement',
            'announcements.edit' => 'Edit announcement',
            'announcements.delete' => 'Delete announcement',

            // Reports
            'reports.view' => 'View reports',
            'reports.export' => 'Export reports',
            'reports.schedule' => 'Schedule reports',

            // Settings & Configuration
            'settings.view' => 'View settings',
            'settings.edit' => 'Edit settings',
            'settings.system' => 'System administration',

            // Vacancies & Recruitment
            'vacancies.view' => 'View vacancies',
            'vacancies.create' => 'Create vacancy',
            'vacancies.edit' => 'Edit vacancy',
            'vacancies.applications' => 'Manage applications',

            // Academic Management
            'academics.view' => 'View academic data',
            'academics.create' => 'Create academic records',
            'academics.edit' => 'Edit academic records',

            // Dashboard
            'dashboard.view' => 'View dashboard',
            'dashboard.admin' => 'View admin dashboard',
            'dashboard.financial' => 'View financial dashboard',

            // HR
            'hr.members.view' => 'View HR members',
            'hr.members.create' => 'Create HR members',
            'hr.members.edit' => 'Edit HR members',
            'hr.members.delete' => 'Delete HR members',
            'card-settings.view' => 'View card and HR organization settings',
            'card-settings.create' => 'Create card and HR organization settings',
            'card-settings.edit' => 'Edit card and HR organization settings',
            'card-settings.delete' => 'Delete card and HR organization settings',

            // E-Learning
            'learning.courses.view' => 'View learning courses',
            'learning.courses.create' => 'Create learning courses',
            'learning.courses.edit' => 'Edit learning courses',
            'learning.courses.delete' => 'Delete learning courses',
            'learning.students.view' => 'View student learning accounts',
            'learning.students.create' => 'Create student learning accounts',
            'learning.students.edit' => 'Edit student learning accounts',
            'learning.students.delete' => 'Delete student learning accounts',
            'learning.lessons.view' => 'View learning lessons',
            'learning.lessons.create' => 'Create learning lessons',
            'learning.lessons.edit' => 'Edit learning lessons',
            'learning.lessons.delete' => 'Delete learning lessons',
            'learning.resources.view' => 'View learning resources',
            'learning.resources.create' => 'Create learning resources',
            'learning.resources.edit' => 'Edit learning resources',
            'learning.resources.delete' => 'Delete learning resources',
            'learning.teacher.assign' => 'Assign teachers to learning classes',
            'learning.quizzes.view' => 'View mock tests',
            'learning.quizzes.create' => 'Create mock tests',
            'learning.quizzes.edit' => 'Edit mock tests',
            'learning.quizzes.delete' => 'Delete mock tests',
            'learning.reports.view' => 'View learning reports',

            // Examinations
            'examinations.view' => 'View examinations',
            'examinations.manage' => 'Configure examinations',
            'examinations.marks.enter' => 'Enter assigned subject marks',
            'examinations.marks.verify' => 'Review all examination marks',
            'examinations.reports' => 'View examination analytics and reports',
            'teaching-learning.routine.view' => 'View routine builder and final routines',
            'teaching-learning.routine.manage' => 'Build and edit class routines',
            'teaching-learning.routine.publish' => 'Publish and lock class routines',

            // Work Task Management
            'work-tasks.view' => 'View assigned work tasks',
            'work-tasks.create' => 'Assign work tasks',
            'work-tasks.submit' => 'Submit assigned work tasks',
            'work-tasks.review' => 'Review work task submissions',
            'work-groups.manage' => 'Manage work groups and committees',
            'work-checklists.manage' => 'Manage reusable work checklists',
        ];

        // Create all permissions
        $permissionModels = [];
        foreach ($permissions as $key => $name) {
            $permissionModels[$key] = Permission::create([
                'name' => $key,
                'guard_name' => 'web',
            ]);
        }

        // Define roles with their permissions
        $roles = [
            'super-admin' => [
                // Super admin gets ALL permissions
                ...$permissions,
            ],
            'principal' => [
                'users.view' => 'View users',
                'users.create' => 'Create users',
                'users.edit' => 'Edit users',
                'students.view' => 'View students',
                'students.create' => 'Create student record',
                'students.edit' => 'Edit student record',
                'faculty.view' => 'View faculty',
                'faculty.create' => 'Create faculty',
                'faculty.edit' => 'Edit faculty',
                'attendance.view' => 'View attendance records',
                'attendance.report' => 'View attendance reports',
                'leaves.view' => 'View leave requests',
                'leaves.approve' => 'Approve leave requests',
                'announcements.view' => 'View announcements',
                'announcements.create' => 'Create announcement',
                'reports.view' => 'View reports',
                'reports.export' => 'Export reports',
                'dashboard.admin' => 'View admin dashboard',
                'vacancies.view' => 'View vacancies',
                'payroll.view' => 'View payroll',
                'billing.view' => 'View billing records',
                'billing.create' => 'Create billing records',
                'billing.delete' => 'Delete billing records',
                'library.view' => 'View library',
                'library.create' => 'Create library records',
                'library.edit' => 'Edit library records',
                'library.issue' => 'Issue and return books',
                'library.reports' => 'View library reports',
                'settings.view' => 'View settings',
                'hr.members.view' => 'View HR members',
                'hr.members.create' => 'Create HR members',
                'hr.members.edit' => 'Edit HR members',
                'hr.members.delete' => 'Delete HR members',
                'card-settings.view' => 'View card and HR organization settings',
                'card-settings.create' => 'Create card and HR organization settings',
                'card-settings.edit' => 'Edit card and HR organization settings',
                'card-settings.delete' => 'Delete card and HR organization settings',
                'learning.courses.view' => 'View learning courses',
                'learning.courses.create' => 'Create learning courses',
                'learning.courses.edit' => 'Edit learning courses',
                'learning.students.view' => 'View student learning accounts',
                'learning.students.create' => 'Create student learning accounts',
                'learning.students.edit' => 'Edit student learning accounts',
                'learning.lessons.view' => 'View learning lessons',
                'learning.lessons.create' => 'Create learning lessons',
                'learning.lessons.edit' => 'Edit learning lessons',
                'learning.resources.view' => 'View learning resources',
                'learning.resources.create' => 'Create learning resources',
                'learning.resources.edit' => 'Edit learning resources',
                'learning.resources.delete' => 'Delete learning resources',
                'learning.teacher.assign' => 'Assign teachers to learning classes',
                'learning.quizzes.view' => 'View mock tests',
                'learning.reports.view' => 'View learning reports',
                'examinations.view' => 'View examinations',
                'examinations.manage' => 'Configure examinations',
                'examinations.marks.enter' => 'Enter assigned subject marks',
                'examinations.marks.verify' => 'Review all examination marks',
                'examinations.reports' => 'View examination analytics and reports',
                'teaching-learning.routine.view' => 'View routine builder and final routines',
                'teaching-learning.routine.manage' => 'Build and edit class routines',
                'teaching-learning.routine.publish' => 'Publish and lock class routines',
                'work-tasks.view' => 'View assigned work tasks',
                'work-tasks.create' => 'Assign work tasks',
                'work-tasks.submit' => 'Submit assigned work tasks',
                'work-tasks.review' => 'Review work task submissions',
                'work-groups.manage' => 'Manage work groups and committees',
                'work-checklists.manage' => 'Manage reusable work checklists',
            ],
            'accountant' => [
                'users.view' => 'View users',
                'payroll.view' => 'View payroll',
                'payroll.create' => 'Create payroll',
                'payroll.process' => 'Process payroll',
                'payroll.approve' => 'Approve payroll',
                'payroll.report' => 'View payroll reports',
                'billing.view' => 'View billing records',
                'billing.create' => 'Create billing records',
                'billing.delete' => 'Delete billing records',
                'attendance.view' => 'View attendance records',
                'attendance.report' => 'View attendance reports',
                'reports.view' => 'View reports',
                'reports.export' => 'Export reports',
                'dashboard.financial' => 'View financial dashboard',
                'students.view' => 'View students',
                'vacancies.view' => 'View vacancies',
            ],
            'administrator' => [
                'users.view' => 'View users',
                'users.create' => 'Create users',
                'users.edit' => 'Edit users',
                'users.delete' => 'Delete users',
                'users.bulk-import' => 'Bulk import users',
                'students.view' => 'View students',
                'students.create' => 'Create student record',
                'students.edit' => 'Edit student record',
                'students.delete' => 'Delete student record',
                'students.admission' => 'Manage admissions',
                'students.card-request' => 'Process card requests',
                'faculty.view' => 'View faculty',
                'faculty.create' => 'Create faculty',
                'faculty.edit' => 'Edit faculty',
                'faculty.delete' => 'Delete faculty',
                'announcements.view' => 'View announcements',
                'announcements.create' => 'Create announcement',
                'announcements.edit' => 'Edit announcement',
                'announcements.delete' => 'Delete announcement',
                'reports.view' => 'View reports',
                'reports.export' => 'Export reports',
                'settings.view' => 'View settings',
                'settings.edit' => 'Edit settings',
                'dashboard.admin' => 'View admin dashboard',
                'billing.view' => 'View billing records',
                'billing.create' => 'Create billing records',
                'billing.delete' => 'Delete billing records',
                'library.view' => 'View library',
                'library.create' => 'Create library records',
                'library.edit' => 'Edit library records',
                'library.issue' => 'Issue and return books',
                'library.reports' => 'View library reports',
                'hr.members.view' => 'View HR members',
                'hr.members.create' => 'Create HR members',
                'hr.members.edit' => 'Edit HR members',
                'hr.members.delete' => 'Delete HR members',
                'card-settings.view' => 'View card and HR organization settings',
                'card-settings.create' => 'Create card and HR organization settings',
                'card-settings.edit' => 'Edit card and HR organization settings',
                'card-settings.delete' => 'Delete card and HR organization settings',
                'academics.view' => 'View academic data',
                'academics.create' => 'Create academic records',
                'academics.edit' => 'Edit academic records',
                'learning.courses.view' => 'View learning courses',
                'learning.courses.create' => 'Create learning courses',
                'learning.courses.edit' => 'Edit learning courses',
                'learning.courses.delete' => 'Delete learning courses',
                'learning.students.view' => 'View student learning accounts',
                'learning.students.create' => 'Create student learning accounts',
                'learning.students.edit' => 'Edit student learning accounts',
                'learning.students.delete' => 'Delete student learning accounts',
                'learning.lessons.view' => 'View learning lessons',
                'learning.lessons.create' => 'Create learning lessons',
                'learning.lessons.edit' => 'Edit learning lessons',
                'learning.lessons.delete' => 'Delete learning lessons',
                'learning.resources.view' => 'View learning resources',
                'learning.resources.create' => 'Create learning resources',
                'learning.resources.edit' => 'Edit learning resources',
                'learning.resources.delete' => 'Delete learning resources',
                'learning.teacher.assign' => 'Assign teachers to learning classes',
                'learning.quizzes.view' => 'View mock tests',
                'learning.quizzes.create' => 'Create mock tests',
                'learning.quizzes.edit' => 'Edit mock tests',
                'learning.quizzes.delete' => 'Delete mock tests',
                'learning.reports.view' => 'View learning reports',
                'examinations.view' => 'View examinations',
                'examinations.manage' => 'Configure examinations',
                'examinations.marks.enter' => 'Enter assigned subject marks',
                'examinations.marks.verify' => 'Review all examination marks',
                'examinations.reports' => 'View examination analytics and reports',
                'teaching-learning.routine.view' => 'View routine builder and final routines',
                'teaching-learning.routine.manage' => 'Build and edit class routines',
                'teaching-learning.routine.publish' => 'Publish and lock class routines',
                'work-tasks.view' => 'View assigned work tasks',
                'work-tasks.create' => 'Assign work tasks',
                'work-tasks.submit' => 'Submit assigned work tasks',
                'work-tasks.review' => 'Review work task submissions',
                'work-groups.manage' => 'Manage work groups and committees',
                'work-checklists.manage' => 'Manage reusable work checklists',
            ],
            'store-keeper' => [
                'dashboard.admin' => 'View admin dashboard',
                'store.view' => 'View store',
                'store.create' => 'Create store records',
                'store.edit' => 'Edit store records',
                'store.approve' => 'Approve store records',
                'store.reports' => 'View store reports',
            ],
            'librarian' => [
                'dashboard.admin' => 'View admin dashboard',
                'library.view' => 'View library',
                'library.create' => 'Create library records',
                'library.edit' => 'Edit library records',
                'library.issue' => 'Issue and return books',
                'library.reports' => 'View library reports',
            ],
            'technical' => [
                'dashboard.admin' => 'View admin dashboard',
            ],
            'teacher' => [
                'dashboard.view' => 'View dashboard',
                'attendance.view' => 'View attendance records',
                'attendance.create' => 'Mark attendance',
                'attendance.edit' => 'Edit attendance',
                'students.view' => 'View students',
                'announcements.view' => 'View announcements',
                'leaves.view' => 'View leave requests',
                'leaves.create' => 'Create leave request',
                'leaves.cancel' => 'Cancel leave requests',
                'academics.view' => 'View academic data',
                'academics.create' => 'Create academic records',
                'academics.edit' => 'Edit academic records',
                'reports.view' => 'View reports',
                'learning.courses.view' => 'View learning courses',
                'learning.courses.create' => 'Create learning courses',
                'learning.courses.edit' => 'Edit learning courses',
                'learning.courses.delete' => 'Delete learning courses',
                'learning.lessons.view' => 'View learning lessons',
                'learning.lessons.create' => 'Create learning lessons',
                'learning.lessons.edit' => 'Edit learning lessons',
                'learning.lessons.delete' => 'Delete learning lessons',
                'learning.resources.view' => 'View learning resources',
                'learning.resources.create' => 'Create learning resources',
                'learning.resources.edit' => 'Edit learning resources',
                'learning.resources.delete' => 'Delete learning resources',
                'learning.quizzes.view' => 'View mock tests',
                'learning.quizzes.create' => 'Create mock tests',
                'learning.quizzes.edit' => 'Edit mock tests',
                'learning.quizzes.delete' => 'Delete mock tests',
                'examinations.view' => 'View examinations',
                'examinations.marks.enter' => 'Enter assigned subject marks',
                'work-tasks.view' => 'View assigned work tasks',
                'work-tasks.submit' => 'Submit assigned work tasks',
            ],
            'staff' => [
                'dashboard.view' => 'View dashboard',
                'announcements.view' => 'View announcements',
                'leaves.view' => 'View leave requests',
                'leaves.create' => 'Create leave request',
                'leaves.cancel' => 'Cancel leave requests',
                'attendance.view' => 'View attendance records',
            ],
            'student' => [
                'dashboard.view' => 'View dashboard',
                'announcements.view' => 'View announcements',
                'students.view' => 'View students',
                'learning.courses.view' => 'View learning courses',
                'learning.lessons.view' => 'View learning lessons',
                'learning.resources.view' => 'View learning resources',
                'learning.quizzes.view' => 'View mock tests',
                'library.view' => 'View library',
            ],
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::create([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            // Assign permissions to role
            foreach ($rolePermissions as $permissionKey => $permissionName) {
                if (isset($permissionModels[$permissionKey])) {
                    $role->givePermissionTo($permissionModels[$permissionKey]);
                }
            }
        }
    }
}
