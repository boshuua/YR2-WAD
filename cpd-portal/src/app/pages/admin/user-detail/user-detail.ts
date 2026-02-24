import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { UserService } from '../../../core/services/user.service';
import { ToastService } from '../../../core/services/toast.service';
import { LoadingService } from '../../../core/services/loading.service';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../../environments/environment';
import { User } from '../../../core/models/user.model';
import { UserDashboard, UserCourse, Attachment } from '../../../core/models/dashboard.model';

@Component({
  selector: 'app-user-detail',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './user-detail.html',
  styleUrls: ['./user-detail.css'],
})
export class UserDetailComponent implements OnInit {
  userId: number | null = null;
  user: User | null = null;
  activeCourses: UserCourse[] = [];
  completedCourses: UserCourse[] = [];
  examHistory: UserCourse[] = [];
  attachments: Attachment[] = [];
  trainingSummary: UserDashboard['training_summary'] | null = null;

  isLoading = true;
  errorMessage = '';
  isUploading = false;
  private apiUrl = environment.apiUrl;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private userService: UserService,
    private toastService: ToastService,
    private loadingService: LoadingService,
  ) {}

  ngOnInit(): void {
    this.route.paramMap.subscribe((params) => {
      const id = params.get('id');
      if (id) {
        this.userId = +id;
        this.loadDashboard();
      } else {
        this.router.navigate(['/admin/users']);
      }
    });
  }

  loadDashboard(): void {
    if (!this.userId) return;

    this.loadingService.show();
    this.isLoading = true;

    this.userService.getUserDashboard(this.userId).subscribe({
      next: (data: UserDashboard) => {
        this.user = data.user;
        this.activeCourses = data.active_courses || [];
        this.completedCourses = data.completed_courses || [];
        this.examHistory = data.exam_history || [];
        this.attachments = data.attachments || [];
        this.trainingSummary = data.training_summary || null;
        this.isLoading = false;
        this.loadingService.hide();
      },
      error: (err) => {
        this.loadingService.hide();
        this.isLoading = false;
        this.errorMessage = 'Failed to load user dashboard.';
        this.toastService.error(this.errorMessage);
        console.error(err);
      },
    });
  }

  getProgressPercentage(course: UserCourse): number {
    if (!course.total_lessons || course.total_lessons === 0) return 0;
    return Math.round(((course.completed_lessons ?? 0) / course.total_lessons) * 100);
  }

  getLessonProgressText(course: UserCourse): string {
    return `Lesson ${course.completed_lessons ?? 0} of ${course.total_lessons ?? 0}`;
  }

  editUser(): void {
    this.router.navigate(['/admin/users/edit', this.userId]);
  }

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) {
      this.uploadFile(file);
    }
  }

  uploadFile(file: File): void {
    if (!this.userId) return;

    this.isUploading = true;
    this.userService.uploadUserAttachment(this.userId, file).subscribe({
      next: (res) => {
        this.toastService.success('File uploaded successfully');
        const attachment = (res as Record<string, Attachment>)['attachment'];
        if (attachment) this.attachments.unshift(attachment);
        this.isUploading = false;
      },
      error: (err) => {
        this.toastService.error('Upload failed: ' + (err.error?.message || err.message));
        this.isUploading = false;
      },
    });
  }

  deleteAttachment(id: number): void {
    if (!confirm('Are you sure you want to delete this file?')) return;

    this.userService.deleteUserAttachment(id).subscribe({
      next: () => {
        this.toastService.success('Attachment deleted');
        this.attachments = this.attachments.filter((a) => a.id !== id);
      },
      error: (err) => {
        this.toastService.error('Delete failed: ' + (err.error?.message || err.message));
      },
    });
  }

  getAttachmentUrl(attachmentId: number): string {
    return `${this.apiUrl}/view_attachment.php?id=${attachmentId}`;
  }

  goBack(): void {
    this.router.navigate(['/admin/users']);
  }
}
