import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../service/auth.service';
import { ToastService } from '../../../service/toast.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { LoadingService } from '../../../service/loading.service';

@Component({
  selector: 'app-course-list',
  standalone: true,
  imports: [CommonModule, FormsModule, ConfirmModalComponent],
  templateUrl: './course-list.component.html',
  styleUrls: ['./course-list.component.css']
})
export class CourseListComponent implements OnInit {
  courses: any[] = [];
  templates: any[] = [];
  isLoading = true;
  errorMessage = '';
  noCoursesFound: boolean = false;

  currentTab: 'active' | 'library' = 'active';

  // Delete Modal
  showDeleteConfirmModal = false;
  courseToDeleteId: number | null = null;
  courseToDeleteTitle: string = '';

  // Schedule Modal
  showScheduleModal = false;
  scheduleData = {
    templateId: null,
    startDate: '',
    endDate: '',
    title: '' // Optional override
  };
  isScheduling = false;

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private router: Router,
    private loadingService: LoadingService
  ) { }

  ngOnInit(): void {
    this.loadCourses();
  }

  // Updated to 'library'
  switchTab(tab: 'active' | 'library'): void {
    this.currentTab = tab;
    this.loadCourses();
  }

  loadCourses(): void {
    this.loadingService.show();
    this.isLoading = true;
    this.errorMessage = '';
    this.noCoursesFound = false;
    this.courses = [];

    // Map 'library' tab to API type 'library'
    // Map 'active' to 'active'
    const apiType = this.currentTab === 'library' ? 'library' : 'active';

    this.authService.getCourses(apiType as any).subscribe({
      next: (data) => {
        this.courses = data;
        this.isLoading = false;
        if (this.courses.length === 0) {
          this.noCoursesFound = true;
        }
        this.loadingService.hide();
      },
      error: (err) => {
        this.loadingService.hide();
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
    // If in Active tab, open Schedule Modal
    if (this.currentTab === 'active') {
      this.openScheduleModal();
    } else {
      // If in Template tab, go to Create Template page
      // We need to pass a query param to tell the form it's a template
      this.router.navigate(['/admin/courses/new'], { queryParams: { isTemplate: true } });
    }
  }

  openScheduleModal(): void {
    this.loadingService.show();
    // Fetch templates for the dropdown
    this.authService.getCourses('template').subscribe({
      next: (data) => {
        this.templates = data;
        this.showScheduleModal = true;
        this.loadingService.hide();

        // Reset form
        this.scheduleData = {
          templateId: null,
          startDate: '',
          endDate: '',
          title: ''
        };
      },
      error: (err) => {
        this.loadingService.hide();
        this.toastService.error('Failed to load templates for scheduling.');
      }
    });
  }

  closeScheduleModal(): void {
    this.showScheduleModal = false;
  }

  scheduleCourse(): void {
    if (!this.scheduleData.templateId || !this.scheduleData.startDate || !this.scheduleData.endDate) {
      this.toastService.error('Please fill in all required fields.');
      return;
    }

    this.isScheduling = true;
    this.loadingService.show();

    const payload = {
      template_id: this.scheduleData.templateId,
      start_date: this.scheduleData.startDate,
      end_date: this.scheduleData.endDate,
      title: this.scheduleData.title || undefined
    };

    this.authService.adminCreateCourseFromTemplate(payload).subscribe({
      next: (res) => {
        this.toastService.success('Course scheduled successfully!');
        this.isScheduling = false;
        this.showScheduleModal = false;
        this.loadingService.hide();
        this.loadCourses(); // Reload active list
      },
      error: (err) => {
        this.isScheduling = false;
        this.loadingService.hide();
        this.toastService.error('Failed to schedule course: ' + (err.error?.message || err.message));
      }
    });
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