import { Routes } from '@angular/router';
import { LoginComponent } from './pages/login/login.component';
import { AdminDashboardComponent } from './pages/admin-dashboard/admin-dashboard.component';
import { UserDashboardComponent } from './pages/user-dashboard/user-dashboard.component';
import { adminGuard } from './auth/admin-guard';

// Import your admin page components
import { OverviewComponent } from './pages/admin/overview/overview.component';
import { UserListComponent } from './pages/admin/user-list/user-list.component';
import { UserCreateComponent } from './pages/admin/user-create/user-create.component';
import { UserEditComponent } from './pages/admin/user-edit/user-edit.component'; 
import { CourseCreateComponent } from './pages/admin/course-create/course-create.component';
import { CourseListComponent } from './pages/admin/course-list/course-list.component';
import { CourseEditComponent } from './pages/admin/course-edit/course-edit.component';
import { SettingsComponent } from './pages/admin/settings/settings.component';

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
      { path: 'users/edit/:id', component: UserEditComponent, data: { breadcrumb: 'Edit User' } },
      { path: 'courses', component: CourseListComponent, data: { breadcrumb: 'Course Management' } },
      { path: 'courses/new', component: CourseCreateComponent, data: { breadcrumb: 'Create Course' } },
      { path: 'courses/edit/:id', component: CourseEditComponent, data: { breadcrumb: 'Edit Course' } },
      { path: 'settings', component: SettingsComponent, data: { breadcrumb: 'Settings' } },
      { path: '', redirectTo: 'overview', pathMatch: 'full' } // Default admin page
    ]
  },

  { path: 'dashboard', component: UserDashboardComponent },
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: '**', redirectTo: '/login' }
];