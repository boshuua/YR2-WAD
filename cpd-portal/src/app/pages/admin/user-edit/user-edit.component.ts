import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { switchMap } from 'rxjs/operators';

@Component({
  selector: 'app-user-edit',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './user-edit.component.html',
  styleUrls: ['./user-edit.component.css']
})
export class UserEditComponent implements OnInit {
  userId: number | null = null;
  userForm!: FormGroup;
  isLoading = true;
  errorMessage = '';

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService,
    private fb: FormBuilder,
    private toastService: ToastService
  ) {}

  ngOnInit(): void {
    this.userForm = this.fb.group({
      first_name: ['', Validators.required],
      last_name: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      job_title: [''],
      access_level: ['user', Validators.required]
    });

    this.route.paramMap.pipe(
      switchMap(params => {
        const id = params.get('id');
        if (id) {
          this.userId = +id;
          return this.authService.getUserById(this.userId);
        } else {
          this.router.navigate(['/admin/users']);
          throw new Error('User ID not found in route');
        }
      })
    ).subscribe({
      next: (user) => {
        if (user && user.id) {
           this.userForm.patchValue(user);
        } else {
            this.errorMessage = 'User not found.';
        }
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Failed to load user data', err);
        this.errorMessage = 'Error loading user data: ' + (err.error?.message || err.message);
        this.isLoading = false;
      }
    });
  }

  onSubmit(): void {
    if (!this.userId) {
      this.toastService.error('Cannot update user without a valid ID.');
      return;
    }

    if (this.userForm.valid) {
      const updateData = { ...this.userForm.value };
      delete updateData.id;

      this.authService.adminUpdateUser(this.userId, updateData).subscribe({
        next: () => {
          this.toastService.success('User updated successfully');
          this.router.navigate(['/admin/users']);
        },
        error: (err: any) => {
          console.error('Failed to update user', err);
          this.toastService.error('Error updating user: ' + (err.error?.message || 'Unknown error'));
        }
      });
    } else {
      this.toastService.info('Please fill in all required fields and ensure they are valid.');
      this.userForm.markAllAsTouched();
    }
  }

  cancel(): void {
    this.router.navigate(['/admin/users']);
  }
}