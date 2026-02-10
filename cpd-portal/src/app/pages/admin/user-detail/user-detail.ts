import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common'; // Covers TitleCasePipe, DatePipe, UpperCasePipe, NgIf, NgFor
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../../service/auth.service';
import { ToastService } from '../../../service/toast.service';
import { LoadingService } from '../../../service/loading.service';
import { FormsModule } from '@angular/forms';

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
  enrolments: any[] = [];
  examHistory: any[] = [];
  attachments: any[] = [];
  trainingSummary: any = null;

  isLoading = true;
  errorMessage = '';

  selectedPeriod = '2025-2026';
  periods = ['2025-2026', '2024-2025'];
  isUploading = false;

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
        this.enrolments = data.enrolments;
        this.examHistory = data.exam_history;
        this.attachments = data.attachments;
        this.trainingSummary = data.training_summary;
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

  onPeriodChange(): void {
    this.toastService.info(`Period changed to ${this.selectedPeriod}`);
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
        this.attachments.unshift(res.attachment);
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

  goBack(): void {
    this.router.navigate(['/admin/users']);
  }
}
