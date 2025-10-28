import { Component, OnInit, Input, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../service/auth.service';
import { ToastService } from '../../../service/toast.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { FormsModule } from '@angular/forms'; // For inline form

@Component({
  selector: 'app-lesson-management',
  standalone: true,
  imports: [CommonModule, RouterLink, ConfirmModalComponent, FormsModule],
  templateUrl: './lesson-management.component.html',
  styleUrls: ['./lesson-management.component.css']
})
export class LessonManagementComponent implements OnInit, OnChanges {
  @Input() courseId: number | null = null;
  lessons: any[] = [];
  isLoading = true;
  errorMessage = '';

  showAddEditLessonModal = false;
  currentLesson: any | null = null; // For editing
  lessonFormTitle: string = '';
  lessonFormDescription: string = '';
  lessonFormOrderIndex: number = 0;

  showDeleteConfirmModal = false;
  lessonToDeleteId: number | null = null;
  lessonToDeleteTitle: string = '';

  constructor(
    private authService: AuthService,
    private toastService: ToastService
  ) { }

  ngOnInit(): void {
    // Initial load if courseId is already available (e.g., from parent component)
    if (this.courseId !== null) {
      this.loadLessons();
    }
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['courseId'] && changes['courseId'].currentValue !== changes['courseId'].previousValue) {
      if (this.courseId !== null) {
        this.loadLessons();
      } else {
        this.lessons = [];
        this.isLoading = false;
        this.errorMessage = '';
      }
    }
  }

  loadLessons(): void {
    if (this.courseId === null) return;

    this.isLoading = true;
    this.errorMessage = '';
    this.authService.getLessonsByCourse(this.courseId).subscribe({
      next: (data) => {
        this.lessons = data;
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Failed to load lessons', err);
        if (err.status === 404 && err.error?.message === 'No lessons found for this course.') {
          this.lessons = []; // Ensure lessons array is empty
          this.errorMessage = ''; // Clear error message for this specific case
        } else {
          this.errorMessage = 'Error loading lessons: ' + (err.error?.message || err.message);
          this.toastService.error(this.errorMessage);
        }
        this.isLoading = false;
      }
    });
  }

  openAddLessonModal(): void {
    this.currentLesson = null;
    this.lessonFormTitle = '';
    this.lessonFormDescription = '';
    this.lessonFormOrderIndex = this.lessons.length > 0 ? Math.max(...this.lessons.map(l => l.order_index)) + 1 : 0;
    this.showAddEditLessonModal = true;
  }

  openEditLessonModal(lesson: any): void {
    this.currentLesson = { ...lesson }; // Clone to avoid direct mutation
    this.lessonFormTitle = lesson.title;
    this.lessonFormDescription = lesson.description;
    this.lessonFormOrderIndex = lesson.order_index;
    this.showAddEditLessonModal = true;
  }

  closeAddEditLessonModal(): void {
    this.showAddEditLessonModal = false;
    this.currentLesson = null;
    this.lessonFormTitle = '';
    this.lessonFormDescription = '';
    this.lessonFormOrderIndex = 0;
  }

  saveLesson(): void {
    if (this.courseId === null) return;

    const lessonData = {
      course_id: this.courseId,
      title: this.lessonFormTitle,
      description: this.lessonFormDescription,
      order_index: this.lessonFormOrderIndex
    };

    if (this.currentLesson) {
      // Update existing lesson
      this.authService.adminUpdateLesson(this.currentLesson.id, lessonData).subscribe({
        next: () => {
          this.toastService.success('Lesson updated successfully!');
          this.loadLessons();
          this.closeAddEditLessonModal();
        },
        error: (err) => {
          console.error('Failed to update lesson', err);
          this.toastService.error('Error updating lesson: ' + (err.error?.message || err.message));
        }
      });
    } else {
      // Create new lesson
      this.authService.adminCreateLesson(lessonData).subscribe({
        next: () => {
          this.toastService.success('Lesson created successfully!');
          this.loadLessons();
          this.closeAddEditLessonModal();
        },
        error: (err) => {
          console.error('Failed to create lesson', err);
          this.toastService.error('Error creating lesson: ' + (err.error?.message || err.message));
        }
      });
    }
  }

  promptDeleteLesson(lessonId: number, lessonTitle: string): void {
    this.lessonToDeleteId = lessonId;
    this.lessonToDeleteTitle = lessonTitle;
    this.showDeleteConfirmModal = true;
  }

  confirmDeleteLesson(): void {
    if (this.lessonToDeleteId !== null) {
      this.authService.adminDeleteLesson(this.lessonToDeleteId).subscribe({
        next: (response) => {
          this.toastService.success(response.message || 'Lesson deleted successfully!');
          this.loadLessons();
          this.closeDeleteModal();
        },
        error: (err) => {
          console.error('Failed to delete lesson', err);
          this.toastService.error('Error deleting lesson: ' + (err.error?.message || err.message));
          this.closeDeleteModal();
        }
      });
    } else {
      console.error("Lesson ID to delete is null");
      this.closeDeleteModal();
    }
  }

  cancelDeleteLesson(): void {
    this.closeDeleteModal();
  }

  private closeDeleteModal(): void {
    this.showDeleteConfirmModal = false;
    this.lessonToDeleteId = null;
    this.lessonToDeleteTitle = '';
  }
}