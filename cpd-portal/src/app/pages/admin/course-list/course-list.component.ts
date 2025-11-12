import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '../../../service/auth.service';
import { ToastService } from '../../../service/toast.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';

@Component({
  selector: 'app-course-list',
  standalone: true,
  imports: [CommonModule, ConfirmModalComponent],
  templateUrl: './course-list.component.html',
  styleUrls: ['./course-list.component.css']
})
export class CourseListComponent implements OnInit {
  courses: any[] = [];
  isLoading = true;
  errorMessage = '';
  noCoursesFound: boolean = false;

  showDeleteConfirmModal = false;
  courseToDeleteId: number | null = null;
  courseToDeleteTitle: string = '';

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.loadCourses();
  }

  loadCourses(): void {
    this.isLoading = true;
    this.errorMessage = '';
    this.noCoursesFound = false;
    this.authService.getCourses().subscribe({
      next: (data) => {
        this.courses = data;
        this.isLoading = false;
        if (this.courses.length === 0) {
          this.noCoursesFound = true;
        }
      },
      error: (err) => {
        if (err.status === 404) {
          this.noCoursesFound = true;
          this.courses = [];
        } else {
          this.errorMessage = 'Error loading courses: ' + (err.error?.message || err.message);
          this.toastService.error(this.errorMessage);
        }
        this.isLoading = false;
      }
    });
  }

  addCourse(): void {
    this.router.navigate(['/admin/courses/new']);
  }

  editCourse(courseId: number): void {
    this.router.navigate(['/admin/courses/edit', courseId]);
  }

  manageQuestions(courseId: number): void {
    this.router.navigate(['/admin/courses', courseId, 'questions']);
  }

  promptDeleteCourse(courseId: number, courseTitle: string): void {
    this.courseToDeleteId = courseId;
    this.courseToDeleteTitle = courseTitle;
    this.showDeleteConfirmModal = true;
  }

  confirmDelete(): void {
    if (this.courseToDeleteId !== null) {
      this.authService.adminDeleteCourse(this.courseToDeleteId).subscribe({
        next: (response) => {
          this.toastService.success(response.message || 'Course deleted successfully!');
          this.loadCourses(); // Reload the list after deletion
          this.closeDeleteModal();
        },
        error: (err) => {
          console.error('Failed to delete course', err);
          this.toastService.error('Error deleting course: ' + (err.error?.message || err.message));
          this.closeDeleteModal();
        }
      });
    } else {
      console.error("Course ID to delete is null");
      this.closeDeleteModal();
    }
  }

  cancelDelete(): void {
    this.closeDeleteModal();
  }

  private closeDeleteModal(): void {
    this.showDeleteConfirmModal = false;
    this.courseToDeleteId = null;
    this.courseToDeleteTitle = '';
  }
}