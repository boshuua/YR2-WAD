import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '../../service/auth.service';
import { ToastService } from '../../service/toast.service';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-user-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './user-dashboard.component.html',
  styleUrls: ['./user-dashboard.component.css']
})
export class UserDashboardComponent implements OnInit {
  courses: any[] = [];
  isLoading = true;
  errorMessage = '';

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.loadUserCourses();
  }

  loadUserCourses(): void {
    this.isLoading = true;
    this.errorMessage = '';
    this.authService.getUserCourses().subscribe({
      next: (data: any) => {
        this.courses = data;
        this.isLoading = false;
      },
      error: (err: HttpErrorResponse) => {
        console.error('Failed to load user courses', err);
        if (err.status === 404 && err.error?.message === 'No published courses found.') {
          this.courses = [];
          this.errorMessage = '';
        } else {
          this.errorMessage = 'Error loading courses: ' + (err.error?.message || err.message);
          this.toastService.error(this.errorMessage);
        }
        this.isLoading = false;
      }
    });
  }

  viewCourse(courseId: number): void {
    this.router.navigate(['/courses', courseId]);
  }
}
