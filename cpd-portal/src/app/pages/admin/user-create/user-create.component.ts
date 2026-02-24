import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { UserService } from '../../../core/services/user.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-user-create',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './user-create.component.html',
  styleUrls: ['./user-create.component.css'],
})
export class UserCreateComponent implements OnInit {
  userForm!: FormGroup;

  constructor(
    private fb: FormBuilder,
    private userService: UserService,
    private router: Router,
    private toastService: ToastService,
  ) {}

  ngOnInit(): void {
    this.userForm = this.fb.group({
      first_name: ['', Validators.required],
      last_name: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      job_title: [''],
      access_level: ['user', Validators.required],
    });
  }

  onSubmit(): void {
    if (this.userForm.valid) {
      this.userService.adminCreateUser(this.userForm.value).subscribe({
        next: () => {
          this.toastService.success('User created successfully!');
          this.router.navigate(['/admin/users']);
        },
        error: (err: any) => {
          console.error('Failed to create user', err);
          this.toastService.error('Error creating user: ' + (err.error?.message || err.message));
        },
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
