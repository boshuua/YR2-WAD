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

  // Auto-save state
  isSaving = false;
  saveStatus: 'idle' | 'saving' | 'saved' | 'error' = 'idle';

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService,
    private toastService: ToastService
  ) { }

  ngOnInit(): void {
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      const lessonId = params.get('lessonId');
      if (id) {
        this.checkEnrollment(+id, lessonId ? +lessonId : null);
      } else {
        this.toastService.error('Course ID not found.');
        this.router.navigate(['/dashboard']);
      }
    });
  }

  checkEnrollment(courseId: number, targetLessonId: number | null = null): void {
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
        this.loadCourseData(courseId, targetLessonId);
      },
      error: () => {
        this.toastService.error('Failed to verify enrollment status.');
        this.router.navigate(['/dashboard/my-courses']);
      }
    });
  }

  loadCourseData(courseId: number, targetLessonId: number | null = null): void {
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
            } else if (targetLessonId) {
              const index = this.lessons.findIndex(l => l.id === targetLessonId);
              if (index !== -1) {
                this.currentLessonIndex = index;
              }
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
    // Save current lesson progress before moving
    this.saveProgress();

    if (this.currentLessonIndex < this.lessons.length - 1) {
      this.currentLessonIndex++;
    }
  }

  previousLesson(): void {
    // Save current lesson progress before moving
    this.saveProgress();

    if (this.currentLessonIndex > 0) {
      this.currentLessonIndex--;
    }
  }

  // Auto-save lesson progress
  saveProgress(): void {
    if (!this.course || !this.currentLesson) return;

    this.isSaving = true;
    this.saveStatus = 'saving';

    this.authService.saveLessonProgress(this.course.id, this.currentLesson.id).subscribe({
      next: (response) => {
        this.isSaving = false;
        this.saveStatus = 'saved';

        // Reset status after 2 seconds
        setTimeout(() => {
          if (this.saveStatus === 'saved') {
            this.saveStatus = 'idle';
          }
        }, 2000);
      },
      error: (err) => {
        this.isSaving = false;
        this.saveStatus = 'error';
        console.error('Failed to save progress:', err);
      }
    });
  }

  completeTraining(): void {
    if (!this.course) return;

    // Save final lesson before completing
    this.saveProgress();

    this.isCompleting = true;

    // Simple hours calculation (could be customizable)
    const hoursCompleted = 3;

    this.authService.completeCourse(this.course.id, hoursCompleted).subscribe({
      next: (response: any) => {
        this.toastService.success('Training completed successfully!');

        if (response.assigned_course_id) {
          const startNow = confirm(`Training Completed.\n\nYou have been assigned "${response.assigned_course_title}".\n\nDo you want to start the assessment now?`);
          if (startNow) {
            this.router.navigate(['/dashboard/course-content', response.assigned_course_id]);
          } else {
            this.router.navigate(['/dashboard/my-courses']);
          }
        } else {
          this.router.navigate(['/dashboard/my-courses']);
        }
      },
      error: (err) => {
        this.toastService.error('Failed to complete training.');
        console.error(err);
        this.isCompleting = false;
      }
    });
  }

  goBack(): void {
    this.router.navigate(['/dashboard/my-courses']);
  }
}