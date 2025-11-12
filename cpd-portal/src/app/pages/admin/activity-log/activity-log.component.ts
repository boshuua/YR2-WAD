import { Component, OnInit } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { AuthService } from '../../../service/auth.service';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-activity-log',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './activity-log.component.html',
  styleUrls: ['./activity-log.component.css'],
  providers: [DatePipe]
})
export class ActivityLogComponent implements OnInit {
  activityLog: any[] = [];
  filteredLog: any[] = [];
  isLoading = true;
  loadError = '';

  // Make Math available in template
  Math = Math;

  // Pagination
  currentPage = 1;
  itemsPerPage = 20;

  // Filters
  searchTerm = '';
  filterAction = '';
  filterUser = '';

  // Unique values for filters
  uniqueActions: string[] = [];
  uniqueUsers: string[] = [];

  constructor(
    private authService: AuthService,
    public datePipe: DatePipe
  ) {}

  ngOnInit(): void {
    this.loadActivityLog();
  }

  loadActivityLog(): void {
    this.isLoading = true;
    this.loadError = '';
    // Load a larger dataset for the full log page
    this.authService.getActivityLog(200).subscribe({
      next: (logs) => {
        this.activityLog = logs;
        this.filteredLog = logs;
        this.extractUniqueValues();
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Failed to load activity log', err);
        this.loadError = 'Could not load activity log. Please try again later.';
        this.isLoading = false;
      }
    });
  }

  extractUniqueValues(): void {
    const actions = new Set<string>();
    const users = new Set<string>();

    this.activityLog.forEach(log => {
      if (log.action) actions.add(log.action);
      if (log.user_email) users.add(log.user_email);
    });

    this.uniqueActions = Array.from(actions).sort();
    this.uniqueUsers = Array.from(users).sort();
  }

  applyFilters(): void {
    this.filteredLog = this.activityLog.filter(log => {
      const matchesSearch = !this.searchTerm ||
        (log.action && log.action.toLowerCase().includes(this.searchTerm.toLowerCase())) ||
        (log.details && log.details.toLowerCase().includes(this.searchTerm.toLowerCase())) ||
        (log.user_email && log.user_email.toLowerCase().includes(this.searchTerm.toLowerCase()));

      const matchesAction = !this.filterAction || log.action === this.filterAction;
      const matchesUser = !this.filterUser || log.user_email === this.filterUser;

      return matchesSearch && matchesAction && matchesUser;
    });

    this.currentPage = 1; // Reset to first page when filters change
  }

  clearFilters(): void {
    this.searchTerm = '';
    this.filterAction = '';
    this.filterUser = '';
    this.applyFilters();
  }

  get paginatedLogs(): any[] {
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;
    return this.filteredLog.slice(startIndex, endIndex);
  }

  get totalPages(): number {
    return Math.ceil(this.filteredLog.length / this.itemsPerPage);
  }

  get totalLogs(): number {
    return this.filteredLog.length;
  }

  nextPage(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }

  previousPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }

  goToPage(page: number): void {
    if (page >= 1 && page <= this.totalPages) {
      this.currentPage = page;
    }
  }

  get visiblePageNumbers(): number[] {
    const total = this.totalPages;
    const current = this.currentPage;
    const delta = 2;

    const range = [];
    const rangeWithDots = [];

    for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
      range.push(i);
    }

    if (current - delta > 2) {
      rangeWithDots.push(1, -1); // -1 represents ellipsis
    } else {
      rangeWithDots.push(1);
    }

    rangeWithDots.push(...range);

    if (current + delta < total - 1) {
      rangeWithDots.push(-1, total);
    } else if (total > 1) {
      rangeWithDots.push(total);
    }

    return rangeWithDots;
  }

  getActionBadgeClass(action: string): string {
    if (!action) return 'badge-default';

    const actionLower = action.toLowerCase();

    if (actionLower.includes('login') || actionLower.includes('logout')) {
      return 'badge-info';
    } else if (actionLower.includes('create') || actionLower.includes('add')) {
      return 'badge-success';
    } else if (actionLower.includes('delete') || actionLower.includes('remove')) {
      return 'badge-danger';
    } else if (actionLower.includes('update') || actionLower.includes('edit')) {
      return 'badge-warning';
    }

    return 'badge-default';
  }
}
