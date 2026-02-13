import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.css']
})
export class LoginComponent {
  credentials = { email: '', password: '' };
  errorMessage = '';

  constructor(private authService: AuthService, private router: Router) {}

  onLogin() {
    this.authService.loginUser(this.credentials).subscribe({
      next: (response: any) => {
        console.log('Login successful', response);

        // Keep for UI convenience only; authorization is enforced via /me.php + backend
        sessionStorage.setItem('currentUser', JSON.stringify(response.user));

        this.authService.getCsrfToken().subscribe({
          next: (csrf) => {
            sessionStorage.setItem('csrfToken', csrf.csrfToken);

            if (response.user.access_level === 'admin') {
              this.router.navigate(['/admin/overview']);
            } else {
              this.router.navigate(['/dashboard']);
            }
          },
          error: () => {
            this.errorMessage = 'Unable to initialize session security. Please try again.';
          }
        });
      },
      error: (error: any) => {
        console.error('Login failed', error);
        this.errorMessage = error.error.message || 'An unknown error occurred.';
      }
    });
  }
}
