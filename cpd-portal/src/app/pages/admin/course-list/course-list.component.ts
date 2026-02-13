import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { ConfirmModalComponent } from '../../../shared/components/confirm-modal/confirm-modal.component';
import { LoadingService } from '../../../core/services/loading.service';

@Component({
  selector: 'app-course-list',
  standalone: true,
  imports: [CommonModule, FormsModule, ConfirmModalComponent],
  templateUrl: './course-list.component.html',
  styleUrls: ['./course-list.component.css']
})
export class CourseListComponent implements OnInit {
  // Lists
  upcomingCourses: any[] = [];
  pastCourses: any[] = [];

  // Library/Templates list (when tab is library)
  libraryCourses: any[] = [];

  // Original properties (Restored)
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
    title: '', // Optional override
    userIds: [] as number[] // Multiple assignment
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
    this.upcomingCourses = [];
    this.pastCourses = [];
    this.libraryCourses = [];

    // Map 'library' tab to API type 'library'
    // Map 'active' to 'active'
    const apiType = this.currentTab === 'library' ? 'library' : 'active';

    this.authService.getCourses(apiType as any).subscribe({
      next: (data) => {
        this.courses = data; // Keep raw for reference if needed

        if (this.currentTab === 'active') {
          const now = new Date();
          // Split into Upcoming and Past
          // Upcoming: Start Date >= Today OR End Date >= Today (Active)
          // Past: End Date < Today
          this.upcomingCourses = data.filter((c: any) => new Date(c.end_date) >= now || new Date(c.start_date) >= now);
          this.pastCourses = data.filter((c: any) => new Date(c.end_date) < now);
        } else {
          this.libraryCourses = data;
        }

        this.isLoading = false;
        if (data.length === 0) {
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

        // Load users if not already loaded
        if (this.users.length === 0) {
          this.loadUsers();
        }

        // Reset form
        this.scheduleData = {
          templateId: null,
          startDate: '',
          endDate: '',
          title: '',
          userIds: []
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

  toggleUserSelection(userId: number, event: any): void {
    if (event.target.checked) {
      this.scheduleData.userIds.push(userId);
    } else {
      this.scheduleData.userIds = this.scheduleData.userIds.filter(id => id !== userId);
    }
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
      title: this.scheduleData.title || undefined,
      user_ids: this.scheduleData.userIds
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

  // --- Enroll Modal Logic ---
  showEnrollModal = false;
  enrollData = {
    courseId: null as number | null,
    userId: null as number | null
  };
  users: any[] = [];
  searchTerm: string = ''; // Search term for user filtering
  isEnrolling = false;

  openEnrollModal(courseId: number): void {
    this.enrollData.courseId = courseId;
    this.showEnrollModal = true;
    this.searchTerm = ''; // Reset search
    if (this.users.length === 0) {
      this.loadUsers();
    }
  }

  // Filtered users for both Schedule and Enroll modals
  get filteredUsers(): any[] {
    if (!this.searchTerm) {
      return this.users;
    }
    const lowerTerm = this.searchTerm.toLowerCase();
    return this.users.filter(user =>
      user.first_name.toLowerCase().includes(lowerTerm) ||
      user.last_name.toLowerCase().includes(lowerTerm) ||
      user.email.toLowerCase().includes(lowerTerm)
    );
  }

  closeEnrollModal(): void {
    this.showEnrollModal = false;
    this.enrollData = { courseId: null, userId: null };
  }

  loadUsers(): void {
    this.loadingService.show();
    this.authService.getUsers().subscribe({
      next: (data) => {
        this.users = data;
        this.loadingService.hide();
      },
      error: (err) => {
        console.error('Failed to load users', err);
        this.loadingService.hide();
        this.toastService.error('Failed to load users');
      }
    });
  }

  enrollUser(): void {
    if (!this.enrollData.userId || !this.enrollData.courseId) {
      this.toastService.error('Please select a user');
      return;
    }

    this.isEnrolling = true;
    this.loadingService.show();

    // Find course to get start date
    const course = this.courses.find(c => c.id === this.enrollData.courseId);
    // Default to today if not found, but scheduled courses usually have start_date
    const startDate = course ? course.start_date : new Date().toISOString().split('T')[0];

    const payload = {
      user_id: this.enrollData.userId,
      course_id: this.enrollData.courseId,
      start_date: startDate
    };

    // Assuming assignCourse exists in AuthService as seen in CalendarComponent
    this.authService.assignCourse(payload).subscribe({
      next: (res) => {
        this.toastService.success('User enrolled successfully');
        this.isEnrolling = false;
        this.closeEnrollModal();
        this.loadingService.hide();
      },
      error: (err) => {
        this.isEnrolling = false;
        this.loadingService.hide();
        this.toastService.error('Enrollment failed: ' + (err.error?.message || err.message));
      }
    });
  }
}