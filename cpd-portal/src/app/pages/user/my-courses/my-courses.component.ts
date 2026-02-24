import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { CourseService } from '../../../core/services/course.service';
import { ToastService } from '../../../core/services/toast.service';
import { HttpErrorResponse } from '@angular/common/http';
import { Course } from '../../../core/models/course.model';
import { UserCourse } from '../../../core/models/dashboard.model';

@Component({
  selector: 'app-my-courses',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './my-courses.component.html',
  styleUrls: ['./my-courses.component.css'],
})
export class MyCoursesComponent implements OnInit {
  activeCourses: UserCourse[] = [];
  historyCourses: UserCourse[] = [];
  availableCourses: Course[] = [];
  examHistory: (UserCourse & { passed: boolean })[] = [];

  filteredActive: UserCourse[] = [];
  filteredHistory: UserCourse[] = [];
  filteredAvailable: Course[] = [];

  isLoading = true;
  errorMessage = '';
  searchTerm = '';

  constructor(
    private courseService: CourseService,
    private toastService: ToastService,
    private router: Router,
  ) {}

  ngOnInit(): void {
    this.loadData();
  }

  loadData(): void {
    this.isLoading = true;
    this.errorMessage = '';

    this.courseService.getUserCourses().subscribe({
      next: (userCourses: UserCourse[]) => {
        this.activeCourses = userCourses.filter((c) => c.user_progress_status !== 'completed');

        const completed = userCourses.filter((c) => c.user_progress_status === 'completed');

        this.historyCourses = completed.filter(
          (c) => c.category !== 'Assessment' && !c.title.includes('Assessment'),
        );

        this.examHistory = completed
          .filter((c) => c.category === 'Assessment' || c.title.includes('Assessment'))
          .map((c) => ({ ...c, passed: (c.score ?? 0) >= 80 }));

        this.courseService.getUpcomingCourses().subscribe({
          next: (allUpcoming: Course[]) => {
            const enrolledIds = new Set(userCourses.map((c) => c.id));
            this.availableCourses = allUpcoming.filter((c) => !enrolledIds.has(c.id));
            this.applyFilters();
            this.isLoading = false;
          },
          error: (err) => {
            console.error('Failed to load upcoming', err);
            this.availableCourses = [];
            this.applyFilters();
            this.isLoading = false;
          },
        });
      },
      error: (err: HttpErrorResponse) => {
        this.errorMessage = 'Error loading your courses.';
        this.isLoading = false;
      },
    });
  }

  onSearchChange(term: string): void {
    this.searchTerm = term;
    this.applyFilters();
  }

  applyFilters(): void {
    const term = this.searchTerm.toLowerCase().trim();

    const filterUserCourse = (course: UserCourse): boolean => {
      if (!term) return true;
      return (
        course.title.toLowerCase().includes(term) ||
        (course.description?.toLowerCase().includes(term) ?? false)
      );
    };

    const filterCourse = (course: Course): boolean => {
      if (!term) return true;
      return (
        course.title.toLowerCase().includes(term) ||
        course.description?.toLowerCase().includes(term)
      );
    };

    this.filteredActive = this.activeCourses.filter(filterUserCourse);
    this.filteredHistory = this.historyCourses.filter(filterUserCourse);
    this.filteredAvailable = this.availableCourses.filter(filterCourse);
  }

  viewCourse(courseId: number): void {
    this.router.navigate(['/courses', courseId]);
  }

  enrollCourse(courseId: number): void {
    this.courseService.enrollCourse(courseId).subscribe({
      next: () => {
        this.toastService.success('Successfully enrolled!');
        this.loadData();
      },
      error: (err) => {
        this.toastService.error('Failed to enroll: ' + (err.error?.message || err.message));
      },
    });
  }

  isFull(course: Course): boolean {
    if (!course.max_attendees) return false;
    return (course.spaces_booked ?? 0) >= course.max_attendees;
  }

  getResumeLink(course: UserCourse): (string | number)[] {
    if (course.last_accessed_lesson_id) {
      return ['/courses', course.id, 'lesson', course.last_accessed_lesson_id];
    }
    return ['/courses', course.id];
  }
}
