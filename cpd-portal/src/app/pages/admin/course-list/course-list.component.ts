import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { FormsModule } from '@angular/forms';
import { CourseService } from '../../../core/services/course.service';
import { UserService } from '../../../core/services/user.service';
import { ToastService } from '../../../core/services/toast.service';
import { ConfirmModalComponent } from '../../../shared/components/confirm-modal/confirm-modal.component';
import { LoadingService } from '../../../core/services/loading.service';
import { Course } from '../../../core/models/course.model';
import { User } from '../../../core/models/user.model';

@Component({
  selector: 'app-course-list',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule, ConfirmModalComponent],
  templateUrl: './course-list.component.html',
  styleUrls: ['./course-list.component.css'],
})
export class CourseListComponent implements OnInit {
  upcomingCourses: Course[] = [];
  pastCourses: Course[] = [];
  libraryCourses: Course[] = [];
  courses: Course[] = [];
  templates: Course[] = [];
  isLoading = true;
  errorMessage = '';
  noCoursesFound = false;

  currentTab: 'active' | 'library' = 'active';

  // Delete Modal
  showDeleteConfirmModal = false;
  courseToDeleteId: number | null = null;
  courseToDeleteTitle = '';

  // Schedule Modal
  showScheduleModal = false;
  scheduleForm!: FormGroup;
  isScheduling = false;

  // Enroll Modal
  showEnrollModal = false;
  enrollCourseId: number | null = null;
  enrollUserId: number | null = null;
  users: User[] = [];
  searchTerm = '';
  isEnrolling = false;

  constructor(
    private courseService: CourseService,
    private userService: UserService,
    private toastService: ToastService,
    private router: Router,
    private loadingService: LoadingService,
    private fb: FormBuilder,
  ) {}

  ngOnInit(): void {
    this.scheduleForm = this.fb.group({
      templateId: [null, Validators.required],
      startDate: ['', Validators.required],
      endDate: ['', Validators.required],
      title: [''],
      userIds: [[]],
    });
    this.loadCourses();
  }

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

    const apiType = this.currentTab === 'library' ? 'library' : 'active';

    this.courseService.getCourses(apiType).subscribe({
      next: (data) => {
        this.courses = data;

        if (this.currentTab === 'active') {
          const now = new Date();
          this.upcomingCourses = data.filter(
            (c) => new Date(c.end_date) >= now || new Date(c.start_date) >= now,
          );
          this.pastCourses = data.filter((c) => new Date(c.end_date) < now);
        } else {
          this.libraryCourses = data;
        }

        this.isLoading = false;
        this.noCoursesFound = data.length === 0;
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
      },
    });
  }

  addCourse(): void {
    if (this.currentTab === 'active') {
      this.openScheduleModal();
    } else {
      this.router.navigate(['/admin/courses/new'], { queryParams: { isTemplate: true } });
    }
  }

  openScheduleModal(): void {
    this.loadingService.show();
    this.courseService.getCourses('template').subscribe({
      next: (data) => {
        this.templates = data;
        this.showScheduleModal = true;
        this.loadingService.hide();
        if (this.users.length === 0) {
          this.loadUsers();
        }
        this.scheduleForm.reset({
          templateId: null,
          startDate: '',
          endDate: '',
          title: '',
          userIds: [],
        });
      },
      error: () => {
        this.loadingService.hide();
        this.toastService.error('Failed to load templates for scheduling.');
      },
    });
  }

  closeScheduleModal(): void {
    this.showScheduleModal = false;
  }

  toggleUserSelection(userId: number, event: Event): void {
    const checked = (event.target as HTMLInputElement).checked;
    const currentIds: number[] = this.scheduleForm.get('userIds')?.value ?? [];
    if (checked) {
      this.scheduleForm.patchValue({ userIds: [...currentIds, userId] });
    } else {
      this.scheduleForm.patchValue({ userIds: currentIds.filter((id) => id !== userId) });
    }
  }

  scheduleCourse(): void {
    if (this.scheduleForm.invalid) {
      this.toastService.error('Please fill in all required fields.');
      return;
    }

    this.isScheduling = true;
    this.loadingService.show();

    const { templateId, startDate, endDate, title, userIds } = this.scheduleForm.value;
    const payload = {
      template_id: templateId,
      start_date: startDate,
      end_date: endDate,
      title: title || undefined,
      user_ids: userIds,
    };

    this.courseService.adminCreateCourseFromTemplate(payload).subscribe({
      next: () => {
        this.toastService.success('Course scheduled successfully!');
        this.isScheduling = false;
        this.showScheduleModal = false;
        this.loadingService.hide();
        this.loadCourses();
      },
      error: (err) => {
        this.isScheduling = false;
        this.loadingService.hide();
        this.toastService.error(
          'Failed to schedule course: ' + (err.error?.message || err.message),
        );
      },
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
      this.courseService.adminDeleteCourse(this.courseToDeleteId).subscribe({
        next: (response) => {
          this.toastService.success(response.message || 'Course deleted successfully!');
          this.loadCourses();
          this.closeDeleteModal();
        },
        error: (err) => {
          console.error('Failed to delete course', err);
          this.toastService.error('Error deleting course: ' + (err.error?.message || err.message));
          this.closeDeleteModal();
        },
      });
    } else {
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

  get filteredUsers(): User[] {
    if (!this.searchTerm) return this.users;
    const lowerTerm = this.searchTerm.toLowerCase();
    return this.users.filter(
      (user) =>
        user.first_name.toLowerCase().includes(lowerTerm) ||
        user.last_name.toLowerCase().includes(lowerTerm) ||
        user.email.toLowerCase().includes(lowerTerm),
    );
  }

  openEnrollModal(courseId: number): void {
    this.enrollCourseId = courseId;
    this.showEnrollModal = true;
    this.searchTerm = '';
    if (this.users.length === 0) {
      this.loadUsers();
    }
  }

  closeEnrollModal(): void {
    this.showEnrollModal = false;
    this.enrollCourseId = null;
    this.enrollUserId = null;
  }

  loadUsers(): void {
    this.loadingService.show();
    this.userService.getUsers().subscribe({
      next: (data) => {
        this.users = data;
        this.loadingService.hide();
      },
      error: () => {
        this.loadingService.hide();
        this.toastService.error('Failed to load users');
      },
    });
  }

  enrollUser(): void {
    if (!this.enrollUserId || !this.enrollCourseId) {
      this.toastService.error('Please select a user');
      return;
    }

    this.isEnrolling = true;
    this.loadingService.show();

    const course = this.courses.find((c) => c.id === this.enrollCourseId);
    const startDate = course ? course.start_date : new Date().toISOString().split('T')[0];

    this.userService
      .assignCourse({
        user_id: this.enrollUserId,
        course_id: this.enrollCourseId,
        start_date: startDate,
      })
      .subscribe({
        next: () => {
          this.toastService.success('User enrolled successfully');
          this.isEnrolling = false;
          this.closeEnrollModal();
          this.loadingService.hide();
        },
        error: (err) => {
          this.isEnrolling = false;
          this.loadingService.hide();
          this.toastService.error('Enrollment failed: ' + (err.error?.message || err.message));
        },
      });
  }
}
