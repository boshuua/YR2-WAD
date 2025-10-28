import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterOutlet, RouterLink, RouterLinkActive } from '@angular/router';
import { BreadcrumbComponent } from '../../components/breadcrumb/breadcrumb.component';
import { ToastNotificationComponent } from '../../components/toast-notification/toast-notification.component';
import { ToastService, ToastConfig } from '../../service/toast.service';
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
    if (typeof localStorage !== 'undefined') {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      this.userName = user.name || 'Admin User';
      this.userRole = user.role || 'Administrator';
      this.userInitials = this.getInitials(this.userName);
    }

    this.toastSubscription = this.toastService.getToastEvents().subscribe(config => {
      this.currentToast = config;
    });
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
    sessionStorage.clear();
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    this.router.navigate(['/login']);
    this.toastService.info('You have been logged out.');
  }

  onToastClosed(): void {
    this.currentToast = null;
  }
}