import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { BreadcrumbComponent } from '../../shared/components/breadcrumb/breadcrumb.component';
import { ToastNotificationComponent } from '../../shared/components/toast-notification/toast-notification.component';
import { ToastService, ToastConfig } from '../../core/services/toast.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive, BreadcrumbComponent, ToastNotificationComponent],
  templateUrl: './admin-dashboard.component.html',
  styleUrls: ['./admin-dashboard.component.css']
})
export class AdminDashboardComponent implements OnInit, OnDestroy {
  isSidebarCollapsed = false;
  userName: string = 'Admin User';
  userRole: string = 'Administrator';
  userInitials: string = 'AU';

  currentToast: ToastConfig | null = null;
  private toastSubscription!: Subscription;

  constructor(private router: Router, private toastService: ToastService) {}

  ngOnInit(): void {
    this.loadUserInfo();

    // Start with sidebar collapsed on mobile devices
    if (typeof window !== 'undefined') {
      this.isSidebarCollapsed = window.innerWidth <= 768;
    }

    this.toastSubscription = this.toastService.getToastEvents().subscribe(config => {
      this.currentToast = config;
    });
  }

  loadUserInfo(): void {
    if (typeof sessionStorage !== 'undefined') {
      const userJson = sessionStorage.getItem('currentUser');
      if (userJson) {
        const user = JSON.parse(userJson);
        const firstName = user.first_name || '';
        const lastName = user.last_name || '';

        // Build full name
        this.userName = `${firstName} ${lastName}`.trim() || 'Admin User';

        // Set role based on access level
        this.userRole = user.access_level === 'admin' ? 'Administrator' : 'User';

        // Generate initials
        this.userInitials = this.getInitials(this.userName);
      }
    }
  }

  ngOnDestroy(): void {
    if (this.toastSubscription) {
      this.toastSubscription.unsubscribe();
    }
  }

  getInitials(name: string): string {
    return name
      .split(' ')
      .map(n => n[0])
      .join('');
  }

  toggleSidebar(): void {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  logout(): void {
    // Clear all storage
    if (typeof sessionStorage !== 'undefined') {
      sessionStorage.clear();
    }
    if (typeof localStorage !== 'undefined') {
      localStorage.clear();
    }
    this.router.navigate(['/login']);
    this.toastService.info('You have been logged out.');
  }

  onToastClosed(): void {
    this.currentToast = null;
  }
}