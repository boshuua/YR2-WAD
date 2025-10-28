import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../../service/auth.service';
import { ToastService } from '../../../service/toast.service';
import { switchMap } from 'rxjs/operators';

@Component({
  selector: 'app-course-edit',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './course-edit.component.html',
  styleUrls: ['./course-edit.component.css']
})
export class CourseEditComponent implements OnInit {
  courseId: number | null = null;
  courseForm!: FormGroup;
  isLoading = true;
  errorMessage = '';

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
      code: [''],
      duration: [null, [Validators.min(0)]],
      category: ['', Validators.required],
      instructor_id: [null],
      status: ['draft', Validators.required]
    });

    this.route.paramMap.pipe(
      switchMap(params => {
        const id = params.get('id');
        if (id) {
          this.courseId = +id;
          return this.authService.getCourseById(this.courseId);
        } else {
          this.router.navigate(['/admin/courses']);
          throw new Error('Course ID not found in route');
        }
      })
    ).subscribe({
      next: (course) => {
        if (course && course.id) {
          this.courseForm.patchValue(course);
        } else {
          this.errorMessage = 'Course not found.';
        }
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Failed to load course data', err);
        this.errorMessage = 'Error loading course data: ' + (err.error?.message || err.message);
        this.toastService.error(this.errorMessage);
        this.isLoading = false;
      }
    });
  }

  onSubmit(): void {
    if (!this.courseId) {
      this.toastService.error('Cannot update course without a valid ID.');
      return;
    }

    if (this.courseForm.valid) {
      const updateData = { ...this.courseForm.value };
      delete updateData.id; // ID is in URL, not body

      this.authService.adminUpdateCourse(this.courseId, updateData).subscribe({
        next: () => {
          this.toastService.success('Course updated successfully!');
          this.router.navigate(['/admin/courses']);
        },
        error: (err: any) => {
          console.error('Failed to update course', err);
          this.toastService.error('Error updating course: ' + (err.error?.message || err.message));
        }
      });
    } else {
      this.toastService.info('Please fill in all required fields and ensure they are valid.');
      this.courseForm.markAllAsTouched();
    }
  }

  cancel(): void {
    this.router.navigate(['/admin/courses']);
  }
}