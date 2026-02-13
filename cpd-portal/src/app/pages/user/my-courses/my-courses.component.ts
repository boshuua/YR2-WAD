import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-my-courses',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './my-courses.component.html',
  styleUrls: ['./my-courses.component.css']
})
export class MyCoursesComponent implements OnInit {
  // Lists
  activeCourses: any[] = [];
  historyCourses: any[] = [];
  availableCourses: any[] = [];

  // filtered lists (if we keep search)
  filteredActive: any[] = [];
  filteredHistory: any[] = [];
  filteredAvailable: any[] = [];

  isLoading = true;
  errorMessage = '';
  searchTerm: string = '';

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.loadData();
  }

  loadData(): void {
    this.isLoading = true;
    this.errorMessage = '';

    // ForkJoin would be better, but let's do sequential for simplicity or simple parallel
    // 1. Get User Courses (Enrolled & History)
    this.authService.getUserCourses().subscribe({
      next: (userCourses: any[]) => {
        // Split into Active and History
        this.activeCourses = userCourses.filter(c => c.user_progress_status !== 'completed');
        this.historyCourses = userCourses.filter(c => c.user_progress_status === 'completed');

        // 2. Get Upcoming Courses
        this.authService.getUpcomingCourses().subscribe({
          next: (allUpcoming: any[]) => {
            // Filter out courses user is already enrolled in
            const enrolledIds = new Set(userCourses.map(c => c.id));
            this.availableCourses = allUpcoming.filter(c => !enrolledIds.has(c.id));

            this.applyFilters();
            this.isLoading = false;
          },
          error: (err) => {
            // If upcoming fails, maybe just show enrolled?
            console.error('Failed to load upcoming', err);
            this.availableCourses = [];
            this.applyFilters();
            this.isLoading = false;
          }
        });
      },
      error: (err: HttpErrorResponse) => {
        this.errorMessage = 'Error loading your courses.';
        this.isLoading = false;
      }
    });
  }

  onSearchChange(term: string): void {
    this.searchTerm = term;
    this.applyFilters();
  }

  applyFilters(): void {
    const term = this.searchTerm.toLowerCase().trim();

    const filterFn = (course: any) => {
      if (!term) return true;
      return course.title.toLowerCase().includes(term) ||
        course.description?.toLowerCase().includes(term);
    };

    this.filteredActive = this.activeCourses.filter(filterFn);
    this.filteredHistory = this.historyCourses.filter(filterFn);
    this.filteredAvailable = this.availableCourses.filter(filterFn);
  }

  viewCourse(courseId: number): void {
    this.router.navigate(['/courses', courseId]);
  }

  enrollCourse(courseId: number): void {
    this.authService.enrollCourse(courseId).subscribe({
      next: () => {
        this.toastService.success('Successfully enrolled!');
        this.loadData(); // Reload all
      },
      error: (err) => {
        this.toastService.error('Failed to enroll: ' + (err.error?.message || err.message));
      }
    });
  }

  isFull(course: any): boolean {
    if (!course.max_attendees) return false;
    return (course.enrolled_count || 0) >= course.max_attendees;
  }

}
