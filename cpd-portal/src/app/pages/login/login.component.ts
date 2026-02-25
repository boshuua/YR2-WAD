import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  ReactiveFormsModule,
  FormBuilder,
  FormGroup,
  Validators,
  FormControl,
} from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { AuthResponse } from '../../core/models/api-response.model';
import { LoadingService } from '../../core/services/loading.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css'],
})
export class LoginComponent implements OnInit {
  loginForm!: FormGroup;
  errorMessage = '';

  // Forgot Password feature
  showForgotModal = false;
  forgotEmailControl = new FormControl('', [Validators.required, Validators.email]);
  forgotSuccessMessage = '';
  forgotErrorMessage = '';
  isSubmittingForgot = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private loadingService: LoadingService,
  ) {}

  ngOnInit(): void {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required],
    });
  }

  onLogin() {
    if (this.loginForm.invalid) {
      this.loginForm.markAllAsTouched();
      return;
    }

    this.loadingService.show();
    const credentials = this.loginForm.value;

    this.authService.loginUser(credentials).subscribe({
      next: (response: AuthResponse) => {
        if (!response.user) {
          this.loadingService.hide();
          this.errorMessage = 'Login failed: No user data received.';
          return;
        }

        sessionStorage.setItem('currentUser', JSON.stringify(response.user));

        // Use loading service to keep spinner up while getting CSRF
        this.authService.getCsrfToken().subscribe({
          next: (csrf) => {
            sessionStorage.setItem('csrfToken', csrf.csrfToken);
            this.loadingService.hide(); // Hide before navigating

            if (response.user.access_level === 'admin') {
              this.router.navigate(['/admin/overview']);
            } else {
              this.router.navigate(['/dashboard']);
            }
          },
          error: (error: any) => {
            this.loadingService.hide();
            console.error('CSRF bootstrap failed', error);
            this.errorMessage =
              error?.error?.message || 'Unable to start secure session. Please try again.';
          },
        });
      },
      error: (error: any) => {
        this.loadingService.hide();
        console.error('Login failed', error);
        this.errorMessage = error.error?.message || 'An unknown error occurred.';
      },
    });
  }

  // --- Forgot Password Methods ---

  openForgotPassword() {
    this.showForgotModal = true;
    this.forgotEmailControl.reset();
    this.forgotSuccessMessage = '';
    this.forgotErrorMessage = '';
  }

  closeForgotPassword() {
    this.showForgotModal = false;
  }

  submitForgotPassword() {
    if (this.forgotEmailControl.invalid) {
      this.forgotEmailControl.markAsTouched();
      return;
    }

    this.isSubmittingForgot = true;
    this.forgotSuccessMessage = '';
    this.forgotErrorMessage = '';

    const email = this.forgotEmailControl.value;

    if (!email) {
      this.isSubmittingForgot = false;
      return;
    }

    this.authService.forgotPassword(email).subscribe({
      next: (res) => {
        this.isSubmittingForgot = false;
        this.forgotSuccessMessage =
          res.message ||
          'If your email is registered in our system, a password reset request has been sent to the administrator.';
        this.forgotEmailControl.reset();
      },
      error: (err) => {
        this.isSubmittingForgot = false;
        console.error('Forgot Password API Error:', err);
        this.forgotErrorMessage =
          err.error?.message ||
          'An error occurred while processing your request. Please try again.';
      },
    });
  }
}
