import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../service/auth.service';
import { ToastService } from '../../../service/toast.service';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-my-courses',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './my-courses.component.html',
  styleUrls: ['./my-courses.component.css']
})
export class MyCoursesComponent implements OnInit {
  courses: any[] = [];
  filteredCourses: any[] = [];
  isLoading = true;
  errorMessage = '';
  searchTerm: string = '';
  statusFilter: string = 'all';

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.loadUserCourses();
  }

  loadUserCourses(): void {
    this.isLoading = true;
    this.errorMessage = '';
    this.authService.getUserCourses().subscribe({
      next: (data: any) => {
        this.courses = data;
        this.filteredCourses = data;
        this.applyFilters();
        this.isLoading = false;
      },
      error: (err: HttpErrorResponse) => {
        if (err.status === 404) {
          this.courses = [];
          this.filteredCourses = [];
          this.errorMessage = '';
        } else {
          this.errorMessage = 'Error loading courses: ' + (err.error?.message || err.message);
          this.toastService.error(this.errorMessage);
        }
        this.isLoading = false;
      }
    });
  }

  onSearchChange(term: string): void {
    this.searchTerm = term;
    this.applyFilters();
  }

  onStatusFilterChange(status: string): void {
    this.statusFilter = status;
    this.applyFilters();
  }

  applyFilters(): void {
    let filtered = [...this.courses];

    // Apply status filter
    if (this.statusFilter !== 'all') {
      filtered = filtered.filter(course => course.user_progress_status === this.statusFilter);
    }

    // Apply search filter
    if (this.searchTerm.trim()) {
      const term = this.searchTerm.toLowerCase();
      filtered = filtered.filter(course =>
        course.title.toLowerCase().includes(term) ||
        course.description?.toLowerCase().includes(term) ||
        course.code?.toLowerCase().includes(term)
      );
    }

    this.filteredCourses = filtered;
  }

  viewCourse(courseId: number): void {
    this.router.navigate(['/courses', courseId]);
  }

  enrollCourse(courseId: number): void {
    this.authService.enrollCourse(courseId).subscribe({
      next: () => {
        this.toastService.success('Successfully enrolled in the course!');
        this.loadUserCourses();
      },
      error: (err) => {
        this.toastService.error('Failed to enroll: ' + (err.error?.message || err.message));
      }
    });
  }

  updateCourseStatus(courseId: number, status: string): void {
    const progressData = { course_id: courseId, status };
    this.authService.updateCourseProgress(progressData).subscribe({
      next: () => {
        this.toastService.success(`Course status updated to ${status}`);
        this.loadUserCourses();
      },
      error: (err) => {
        this.toastService.error('Failed to update course status: ' + err.error?.message);
      }
    });
  }

  isEnrolled(course: any): boolean {
    return course.user_progress_status !== undefined && course.user_progress_status !== null;
  }
}
