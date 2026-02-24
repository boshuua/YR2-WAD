import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  FormBuilder,
  FormGroup,
  Validators,
  ReactiveFormsModule,
  AbstractControl,
  ValidatorFn,
} from '@angular/forms';
import { Router } from '@angular/router';
import { UserService } from '../../../core/services/user.service';
import { ToastService } from '../../../core/services/toast.service';
import { passwordMatchValidator } from '../../../shared/utils/validators';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './settings.component.html',
  styleUrls: ['./settings.component.css'],
})
export class SettingsComponent implements OnInit {
  passwordForm!: FormGroup;

  constructor(
    private fb: FormBuilder,
    private userService: UserService,
    private router: Router,
    private toastService: ToastService,
  ) {}

  ngOnInit(): void {
    this.passwordForm = this.fb.group(
      {
        current_password: ['', Validators.required],
        new_password: ['', [Validators.required, Validators.minLength(6)]],
        confirm_password: ['', Validators.required],
      },
      { validators: passwordMatchValidator('new_password', 'confirm_password') },
    );
  }

  onSubmitPasswordChange(): void {
    if (this.passwordForm.valid) {
      const { current_password, new_password } = this.passwordForm.value;
      this.userService.adminUpdatePassword({ current_password, new_password }).subscribe({
        next: () => {
          this.toastService.success('Password updated successfully!');
          this.passwordForm.reset();
        },
        error: (err: unknown) => {
          const message =
            err instanceof Error
              ? err.message
              : ((err as { error?: { message?: string } })?.error?.message ?? 'Unknown error');
          console.error('Failed to update password', err);
          this.toastService.error('Error updating password: ' + message);
        },
      });
    } else {
      this.toastService.info('Please correct the form errors.');
      this.passwordForm.markAllAsTouched();
    }
  }

  cancel(): void {
    this.router.navigate(['/admin/overview']);
  }
}
