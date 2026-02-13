import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { switchMap } from 'rxjs/operators';
import { of } from 'rxjs';
import { Course } from '../../../core/models/course.model';
import { ApiResponse } from '../../../core/models/api-response.model';

@Component({
  selector: 'app-course-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './course-form.component.html',
  styleUrls: ['./course-form.component.css']
})
export class CourseFormComponent implements OnInit {
  courseForm!: FormGroup;
  isEditMode = false;
  courseId: number | null = null;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private route: ActivatedRoute,
    private toastService: ToastService
  ) { }

  ngOnInit(): void {
    this.courseForm = this.fb.group({
      title: ['', Validators.required],
      description: ['', Validators.required],
      required_hours: [3.00, [Validators.required, Validators.min(0)]],
      category: [''],
      start_date: ['', Validators.required],
      end_date: ['', Validators.required],
      status: ['published']
    });

    this.route.paramMap.pipe(
      switchMap(params => {
        const id = params.get('id');
        if (id) {
          this.isEditMode = true;
          this.courseId = +id;
          return this.authService.getCourseById(this.courseId);
        }
        return of(null);
      })
    ).subscribe((course: Course | null) => {
      if (course) {
        this.courseForm.patchValue(course);
      }
    });
  }

  onSubmit(): void {
    if (this.courseForm.invalid) {
      this.toastService.error('Please fill in all required fields.');
      return;
    }

    const courseData = this.courseForm.value;

    if (this.isEditMode && this.courseId) {
      this.authService.adminUpdateCourse(this.courseId, courseData).subscribe({
        next: (response: ApiResponse) => {
          this.toastService.success('Course updated successfully!');
          this.router.navigate(['/admin/courses']);
        },
        error: (err: any) => this.toastService.error('Failed to update course: ' + err.error?.message)
      });
    } else {
      this.authService.adminCreateCourse(courseData).subscribe({
        next: (response: ApiResponse) => {
          this.toastService.success('Course created successfully!');
          this.router.navigate(['/admin/courses']);
        },
        error: (err: any) => this.toastService.error('Failed to create course: ' + err.error?.message)
      });
    }
  }

  cancel(): void {
    this.router.navigate(['/admin/courses']);
  }
}
