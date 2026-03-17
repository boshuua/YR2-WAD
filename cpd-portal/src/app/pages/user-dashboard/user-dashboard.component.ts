import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterOutlet, RouterLink, RouterLinkActive, NavigationEnd } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { ToastService } from '../../core/services/toast.service';
import { Subscription, filter } from 'rxjs';

@Component({
  selector: 'app-user-dashboard',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './user-dashboard.component.html',
  styleUrls: ['./user-dashboard.component.css'],
})
export class UserDashboardComponent implements OnInit, OnDestroy {
  isSidebarCollapsed = false;
  userName: string = 'User';
  userRole: string = 'User';
  userInitials: string = 'U';
  private routerSubscription!: Subscription;

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private router: Router,
  ) {}

  ngOnInit(): void {
    this.loadUserInfo();

    // Start with sidebar collapsed on mobile devices
    if (typeof window !== 'undefined') {
      this.isSidebarCollapsed = window.innerWidth <= 1024;
    }

    // Auto-close sidebar on navigation (Mobile)
    this.routerSubscription = this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe(() => {
        if (typeof window !== 'undefined' && window.innerWidth <= 1024) {
          this.isSidebarCollapsed = true;
        }
      });
  }

  ngOnDestroy(): void {
    if (this.routerSubscription) {
      this.routerSubscription.unsubscribe();
    }
  }


  loadUserInfo(): void {
    if (typeof sessionStorage !== 'undefined') {
      const userJson = sessionStorage.getItem('currentUser');
      if (userJson) {
        const user = JSON.parse(userJson);
        const firstName = user.first_name || '';
        const lastName = user.last_name || '';

        // Build full name
        this.userName = `${firstName} ${lastName}`.trim() || 'User';

        // Set role based on access level
        this.userRole = user?.access_level === 'admin' ? 'Administrator' : 'Student';

        // Generate initials
        this.userInitials = this.getInitials(this.userName);
      }
    }
  }

  getInitials(name: string): string {
    return name
      .split(' ')
      .map((n) => n[0])
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
}
