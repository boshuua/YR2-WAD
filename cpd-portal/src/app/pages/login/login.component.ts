import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../service/auth.service';
import { LoadingService } from '../../service/loading.service';

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

  constructor(private authService: AuthService, private router: Router, private loadingService: LoadingService) { }

  onLogin() {
    this.loadingService.show();
    this.authService.loginUser(this.credentials).subscribe({
      next: (response: any) => {
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
            this.errorMessage = error?.error?.message || 'Unable to start secure session. Please try again.';
          }
        });
      },
      error: (error: any) => {
        this.loadingService.hide();
        console.error('Login failed', error);
        this.errorMessage = error.error?.message || 'An unknown error occurred.';
      }
    });
  }
}
