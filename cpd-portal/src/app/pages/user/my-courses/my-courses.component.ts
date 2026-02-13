import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-my-courses',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './my-courses.component.html',
  styleUrls: ['./my-courses.component.css']
})
export class MyCoursesComponent implements OnInit {
  // Lists
  activeCourses: any[] = [];
  historyCourses: any[] = [];
  availableCourses: any[] = [];
  examHistory: any[] = [];

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

        // Filter completed courses
        const completed = userCourses.filter(c => c.user_progress_status === 'completed');

        this.historyCourses = completed.filter(c => c.category !== 'Assessment' && !c.title.includes('Assessment'));

        this.examHistory = completed.filter(c => c.category === 'Assessment' || c.title.includes('Assessment'))
          .map(c => ({ ...c, passed: (c.score >= 80) })); // Add passed flag if not from API (API usually sends score)

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

  getResumeLink(course: any): any[] {
    if (course.last_accessed_lesson_id) {
      // If we know the exact lesson, go there (assuming route is /courses/:id/lessons/:lessonId or similar)
      // BUT, the current router seems to be just /courses/:id.
      // If the course viewer handles lesson navigation internally via URL, we might need a specific route.
      // Let's assume standard route is /courses/:id and we pass the lesson as query param or fragment if supported.
      // OR if the route is /courses/:id/lesson/:lessonId
      return ['/courses', course.id, 'lesson', course.last_accessed_lesson_id];
    }
    // Default to course overview or first lesson
    return ['/courses', course.id];
  }

}
