import { Routes } from '@angular/router';
import { LoginComponent } from './pages/login/login.component';
import { AdminDashboardComponent } from './pages/admin-dashboard/admin-dashboard.component';
import { UserDashboardComponent } from './pages/user-dashboard/user-dashboard.component';
import { adminGuard } from './core/guards/admin-guard';

// Import your admin page components
import { OverviewComponent } from './pages/admin/overview/overview.component';
import { UserListComponent } from './pages/admin/user-list/user-list.component';
import { UserCreateComponent } from './pages/admin/user-create/user-create.component';
import { UserEditComponent } from './pages/admin/user-edit/user-edit.component';
import { UserDetailComponent } from './pages/admin/user-detail/user-detail';
import { CourseListComponent } from './pages/admin/course-list/course-list.component';
import { CourseFormComponent } from './pages/admin/course-form/course-form.component';
import { CourseQuestionsComponent } from './pages/admin/course-questions/course-questions.component';
import { ActivityLogComponent } from './pages/admin/activity-log/activity-log.component';
import { CalendarComponent } from './pages/admin/calendar/calendar';
import { SettingsComponent as AdminSettingsComponent } from './pages/admin/settings/settings.component';
import { CourseContentComponent } from './pages/course-content/course-content.component';
import { MyCoursesComponent } from './pages/user/my-courses/my-courses.component';
import { UserCalendarComponent } from './pages/user/calendar/user-calendar.component';
import { UserSettingsComponent } from './pages/user/settings/user-settings.component';

export const routes: Routes = [
  { path: 'login', component: LoginComponent },

  // Admin Layout Route
  {
    path: 'admin',
    component: AdminDashboardComponent,
    canActivate: [adminGuard],
    children: [
      { path: 'overview', component: OverviewComponent, data: { breadcrumb: 'Overview' } },
      { path: 'users', component: UserListComponent, data: { breadcrumb: 'User Management' } },
      { path: 'users/new', component: UserCreateComponent, data: { breadcrumb: 'Create User' } },
      { path: 'users/:id', component: UserDetailComponent, data: { breadcrumb: 'User Dashboard' } },
      { path: 'users/edit/:id', component: UserEditComponent, data: { breadcrumb: 'Edit User' } },
      { path: 'courses', component: CourseListComponent, data: { breadcrumb: 'Course Management' } },
      { path: 'courses/new', component: CourseFormComponent, data: { breadcrumb: 'Create Course' } },
      { path: 'courses/edit/:id', component: CourseFormComponent, data: { breadcrumb: 'Edit Course' } },
      { path: 'courses/:id/questions', component: CourseQuestionsComponent, data: { breadcrumb: 'Manage Questions' } },
      { path: 'calendar', component: CalendarComponent, data: { breadcrumb: 'Training Schedule' } }, // New Route
      { path: 'activity', component: ActivityLogComponent, data: { breadcrumb: 'Activity Log' } },
      { path: 'settings', component: AdminSettingsComponent, data: { breadcrumb: 'Settings' } },
      { path: '', redirectTo: 'overview', pathMatch: 'full' } // Default admin page
    ]
  },

  // User Dashboard Layout Route
  {
    path: 'dashboard',
    component: UserDashboardComponent,
    children: [
      { path: 'my-courses', component: MyCoursesComponent, data: { breadcrumb: 'My Courses' } },
      { path: 'calendar', component: UserCalendarComponent, data: { breadcrumb: 'Calendar' } },
      { path: 'settings', component: UserSettingsComponent, data: { breadcrumb: 'Settings' } },
      { path: '', redirectTo: 'my-courses', pathMatch: 'full' }
    ]
  },

  { path: 'courses/:id', component: CourseContentComponent, data: { breadcrumb: 'Course Content' } },
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: '**', redirectTo: '/login' }
];