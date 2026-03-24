import { Component, OnInit } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { CourseService } from '../../../core/services/course.service';
import { FormsModule } from '@angular/forms';
import { ActivityLog } from '../../../core/models/dashboard.model';

@Component({
  selector: 'app-activity-log',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './activity-log.component.html',
  styleUrls: ['./activity-log.component.css'],
  providers: [DatePipe],
})
export class ActivityLogComponent implements OnInit {
  activityLog: ActivityLog[] = [];
  isLoading = true;
  loadError = '';

  currentPage = 1;
  itemsPerPage = 20;
  totalItems = 0;
  lastPage = 1;

  searchTerm = '';
  filterAction = '';
  filterUser = '';

  uniqueActions: string[] = [];
  uniqueUsers: string[] = [];

  constructor(
    private courseService: CourseService,
    public datePipe: DatePipe,
  ) {}

  ngOnInit(): void {
    this.loadActivityLog();
  }

  loadActivityLog(): void {
    this.isLoading = true;
    this.loadError = '';
    this.courseService.getActivityLog(this.currentPage, this.itemsPerPage).subscribe({
      next: (response) => {
        this.activityLog = response.data;
        this.totalItems = response.meta.total;
        this.lastPage = response.meta.last_page;
        this.isLoading = false;
        // For simplicity, we're not doing server-side filtering yet as per the prompt's focus on pagination
      },
      error: (err) => {
        console.error('Failed to load activity log', err);
        this.loadError = 'Could not load activity log. Please try again later.';
        this.isLoading = false;
      },
    });
  }

  goToPage(page: number): void {
    if (page >= 1 && page <= this.lastPage && page !== this.currentPage) {
      this.currentPage = page;
      this.loadActivityLog();
    }
  }

  nextPage(): void {
    if (this.currentPage < this.lastPage) {
      this.currentPage++;
      this.loadActivityLog();
    }
  }

  previousPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
      this.loadActivityLog();
    }
  }

  get totalLogs(): number {
    return this.totalItems;
  }

  get totalPages(): number {
    return this.lastPage;
  }

  get Math() {
    return Math;
  }

  applyFilters(): void {
    this.currentPage = 1;
    this.loadActivityLog();
  }

  clearFilters(): void {
    this.searchTerm = '';
    this.filterAction = '';
    this.filterUser = '';
    this.applyFilters();
  }

  get paginatedLogs(): ActivityLog[] {
    return this.activityLog;
  }

  get visiblePageNumbers(): number[] {
    const total = this.totalPages;
    const current = this.currentPage;
    const delta = 2;

    const range: number[] = [];
    const rangeWithDots: number[] = [];

    for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
      range.push(i);
    }

    if (current - delta > 2) {
      rangeWithDots.push(1, -1);
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
    if (actionLower.includes('login') || actionLower.includes('logout')) return 'badge-info';
    if (actionLower.includes('create') || actionLower.includes('add')) return 'badge-success';
    if (actionLower.includes('delete') || actionLower.includes('remove')) return 'badge-danger';
    if (actionLower.includes('update') || actionLower.includes('edit')) return 'badge-warning';
    return 'badge-default';
  }
}
