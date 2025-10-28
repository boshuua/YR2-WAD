import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../service/auth.service';
import { ToastService } from '../../../service/toast.service';

@Component({
  selector: 'app-course-create',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './course-create.component.html',
  styleUrls: ['./course-create.component.css']
})
export class CourseCreateComponent implements OnInit {
  courseForm!: FormGroup;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private toastService: ToastService
  ) { }

  ngOnInit(): void {
    this.courseForm = this.fb.group({
      title: ['', Validators.required],
      description: ['', Validators.required],
      code: [''], // Optional
      duration: [null, [Validators.min(0)]], // Optional, but must be positive if provided
      category: ['', Validators.required],
      instructor_id: [null], // Optional
      status: ['draft', Validators.required] // Default status
    });
  }

  onSubmit(): void {
    if (this.courseForm.valid) {
      this.authService.adminCreateCourse(this.courseForm.value).subscribe({
        next: () => {
          this.toastService.success('Course created successfully!');
          this.router.navigate(['/admin/courses']); // Navigate back to course list (will be created later)
        },
        error: (err: any) => {
          console.error('Failed to create course', err);
          this.toastService.error('Error creating course: ' + (err.error?.message || err.message));
        }
      });
    } else {
      this.toastService.info('Please fill in all required fields and ensure they are valid.');
      this.courseForm.markAllAsTouched();
    }
  }

  cancel(): void {
    this.router.navigate(['/admin/courses']); // Navigate back without saving
  }
}