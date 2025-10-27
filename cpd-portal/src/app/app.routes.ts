import { Routes } from '@angular/router';
import { LoginComponent } from './pages/login/login.component';
import { AdminDashboardComponent } from './pages/admin-dashboard/admin-dashboard.component';
import { UserDashboardComponent } from './pages/user-dashboard/user-dashboard.component';
import { adminGuard } from './auth/admin-guard';

// Import your admin page components
import { OverviewComponent } from './pages/admin/overview/overview.component';
import { UserListComponent } from './pages/admin/user-list/user-list.component';
import { UserCreateComponent } from './pages/admin/user-create/user-create.component';
// *** Import the UserEditComponent ***
import { UserEditComponent } from './pages/admin/user-edit/user-edit.component'; // Make sure this path is correct

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
      // *** Add the edit route definition ***
      { path: 'users/edit/:id', component: UserEditComponent, data: { breadcrumb: 'Edit User' } },
      { path: '', redirectTo: 'overview', pathMatch: 'full' } // Default admin page
    ]
  },

  { path: 'dashboard', component: UserDashboardComponent },
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: '**', redirectTo: '/login' }
];