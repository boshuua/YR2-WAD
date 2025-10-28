import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { AuthService } from '../../../service/auth.service';
import { ToastService } from '../../../service/toast.service';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-my-courses',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './my-courses.component.html',
  styleUrls: ['./my-courses.component.css']
})
export class MyCoursesComponent implements OnInit {
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
        if (err.status === 404) {
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

  updateCourseStatus(courseId: number, status: string): void {
    const progressData = { course_id: courseId, status };
    this.authService.updateCourseProgress(progressData).subscribe({
      next: () => {
        this.toastService.success(`Course status updated to ${status}`);
        this.loadUserCourses();
      },
      error: (err) => {
        this.toastService.error('Failed to update course status: ' + err.error?.message);
      }
    });
  }
}
