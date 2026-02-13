import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common'; // Covers TitleCasePipe, DatePipe, UpperCasePipe, NgIf, NgFor
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { LoadingService } from '../../../core/services/loading.service';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../../environments/environment';

@Component({
  selector: 'app-user-detail',
  standalone: true,
  imports: [CommonModule, FormsModule], // Import Modules here
  templateUrl: './user-detail.html',
  styleUrls: ['./user-detail.css']
})
export class UserDetailComponent implements OnInit {
  userId: number | null = null;
  user: any = null;
  activeCourses: any[] = []; // Renamed from enrolments
  completedCourses: any[] = []; // New - completed training courses
  examHistory: any[] = []; // Reserved for future assessments
  attachments: any[] = [];
  trainingSummary: any = null;

  isLoading = true;
  errorMessage = '';

  isUploading = false;
  private apiUrl = environment.apiUrl;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService,
    private toastService: ToastService,
    private loadingService: LoadingService
  ) { }

  ngOnInit(): void {
    this.route.paramMap.subscribe(params => {
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

    this.authService.getUserDashboard(this.userId).subscribe({
      next: (data) => {
        this.user = data.user;
        this.activeCourses = data.active_courses || [];
        this.completedCourses = data.completed_courses || [];
        this.examHistory = data.exam_history || [];
        this.attachments = data.attachments || [];
        this.trainingSummary = data.training_summary || {};
        this.isLoading = false;
        this.loadingService.hide();
      },
      error: (err) => {
        this.loadingService.hide();
        this.isLoading = false;
        this.errorMessage = 'Failed to load user dashboard.';
        this.toastService.error(this.errorMessage);
        console.error(err);
      }
    });
  }

  // Helper methods for progress display
  getProgressPercentage(course: any): number {
    if (!course.total_lessons || course.total_lessons === 0) return 0;
    return Math.round((course.completed_lessons / course.total_lessons) * 100);
  }

  getLessonProgressText(course: any): string {
    return `Lesson ${course.completed_lessons || 0} of ${course.total_lessons || 0}`;
  }

  editUser(): void {
    this.router.navigate(['/admin/users/edit', this.userId]);
  }

  onFileSelected(event: any): void {
    const file: File = event.target.files[0];
    if (file) {
      this.uploadFile(file);
    }
  }

  uploadFile(file: File): void {
    if (!this.userId) return;

    this.isUploading = true;
    this.authService.uploadUserAttachment(this.userId, file).subscribe({
      next: (res) => {
        this.toastService.success('File uploaded successfully');
        this.attachments.unshift(res['attachment']);
        this.isUploading = false;
      },
      error: (err) => {
        this.toastService.error('Upload failed: ' + (err.error?.message || err.message));
        this.isUploading = false;
      }
    });
  }

  deleteAttachment(id: number): void {
    if (!confirm('Are you sure you want to delete this file?')) return;

    this.authService.deleteUserAttachment(id).subscribe({
      next: () => {
        this.toastService.success('Attachment deleted');
        this.attachments = this.attachments.filter(a => a.id !== id);
      },
      error: (err) => {
        this.toastService.error('Delete failed: ' + (err.error?.message || err.message));
      }
    });
  }

  getAttachmentUrl(attachmentId: number): string {
    return `${this.apiUrl}/view_attachment.php?id=${attachmentId}`;
  }

  goBack(): void {
    this.router.navigate(['/admin/users']);
  }
}
