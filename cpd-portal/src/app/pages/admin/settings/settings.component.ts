import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule, AbstractControl, ValidatorFn } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';

// Custom validator for password matching
export function passwordMatchValidator(controlName: string, checkControlName: string): ValidatorFn {
  return (control: AbstractControl): { [key: string]: any } | null => {
    const controlValue = control.get(controlName);
    const checkControlValue = control.get(checkControlName);

    if (!controlValue || !checkControlValue) {
      return null; // Return if controls are not found
    }

    if (checkControlValue.errors && !checkControlValue.errors['passwordMatch']) {
      return null; // Return if another validator has already found an error on the matching control
    }

    if (controlValue.value !== checkControlValue.value) {
      checkControlValue.setErrors({ passwordMatch: true });
      return { passwordMatch: true };
    } else {
      checkControlValue.setErrors(null);
      return null;
    }
  };
}

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './settings.component.html',
  styleUrls: ['./settings.component.css']
})
export class SettingsComponent implements OnInit {
  passwordForm!: FormGroup;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private toastService: ToastService
  ) { }

  ngOnInit(): void {
    this.passwordForm = this.fb.group({
      current_password: ['', Validators.required],
      new_password: ['', [Validators.required, Validators.minLength(6)]],
      confirm_password: ['', Validators.required]
    }, { validators: passwordMatchValidator('new_password', 'confirm_password') });
  }

  onSubmitPasswordChange(): void {
    if (this.passwordForm.valid) {
      const { current_password, new_password } = this.passwordForm.value;
      this.authService.adminUpdatePassword({ current_password, new_password }).subscribe({
        next: () => {
          this.toastService.success('Password updated successfully!');
          this.passwordForm.reset(); // Clear form after successful update
        },
        error: (err: any) => {
          console.error('Failed to update password', err);
          this.toastService.error('Error updating password: ' + (err.error?.message || err.message));
        }
      });
    } else {
      this.toastService.info('Please correct the form errors.');
      this.passwordForm.markAllAsTouched();
    }
  }

  cancel(): void {
    this.router.navigate(['/admin/overview']); // Navigate back to admin overview
  }
}