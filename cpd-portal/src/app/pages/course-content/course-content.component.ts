import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { ToastService } from '../../core/services/toast.service';

@Component({
  selector: 'app-course-content',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './course-content.component.html',
  styleUrls: ['./course-content.component.css']
})
export class CourseContentComponent implements OnInit {
  course: any | null = null;
  lessons: any[] = [];

  isLoading = true;
  errorMessage = '';
  isEnrolled = false;

  // Navigation State
  currentLessonIndex = 0;
  isCompleting = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService,
    private toastService: ToastService
  ) { }

  ngOnInit(): void {
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.checkEnrollment(+id);
      } else {
        this.toastService.error('Course ID not found.');
        this.router.navigate(['/dashboard']);
      }
    });
  }

  checkEnrollment(courseId: number): void {
    this.authService.getUserCourses().subscribe({
      next: (courses: any[]) => {
        const course = courses.find(c => c.id === courseId || c.course_id === courseId);

        if (!course) {
          this.toastService.error('You must enroll in this course first.');
          this.router.navigate(['/dashboard/my-courses']);
          return;
        }

        if (course.user_progress_status === 'completed') {
          this.toastService.info('You have already completed this course.');
          this.router.navigate(['/dashboard/my-courses']);
          return;
        }

        this.isEnrolled = true;
        this.loadCourseData(courseId);
      },
      error: () => {
        this.toastService.error('Failed to verify enrollment status.');
        this.router.navigate(['/dashboard/my-courses']);
      }
    });
  }

  loadCourseData(courseId: number): void {
    this.isLoading = true;

    // Load Course Basic Info
    this.authService.getCourseById(courseId).subscribe({
      next: (data) => {
        this.course = data;

        // Load Lessons (Training Content)
        this.authService.getCourseLessons(courseId).subscribe({
          next: (lessons) => {
            this.lessons = lessons.sort((a: any, b: any) => a.order_index - b.order_index);
            this.isLoading = false;

            if (this.lessons.length === 0) {
              this.errorMessage = 'No training content available for this course.';
            }
          },
          error: () => {
            this.errorMessage = 'Failed to load training content.';
            this.isLoading = false;
          }
        });
      },
      error: () => {
        this.errorMessage = 'Failed to load course details.';
        this.isLoading = false;
      }
    });
  }

  get currentLesson() {
    return this.lessons[this.currentLessonIndex];
  }

  get isLastLesson(): boolean {
    return this.currentLessonIndex === this.lessons.length - 1;
  }

  nextLesson(): void {
    if (this.currentLessonIndex < this.lessons.length - 1) {
      this.currentLessonIndex++;
    }
  }

  previousLesson(): void {
    if (this.currentLessonIndex > 0) {
      this.currentLessonIndex--;
    }
  }

  completeCourse(): void {
    this.isCompleting = true;

    this.authService.completeCourse(this.course.id).subscribe({
      next: () => {
        this.toastService.success('Congratulations! You have completed this training course.');
        this.router.navigate(['/dashboard/my-courses']);
      },
      error: (err) => {
        this.isCompleting = false;
        this.toastService.error('Failed to complete course: ' + (err.error?.message || err.message));
      }
    });
  }

  goBack(): void {
    this.router.navigate(['/dashboard/my-courses']);
  }
}