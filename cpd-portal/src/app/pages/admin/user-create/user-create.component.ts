import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../service/auth.service';

@Component({
  selector: 'app-user-create',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './user-create.component.html',
  styleUrls: ['./user-create.component.css']
})
export class UserCreateComponent {
  userData = {
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    job_title: '',
    access_level: 'user' // Default to 'user'
  };

  constructor(private authService: AuthService, private router: Router) {}

  onSubmit(): void {
    this.authService.adminCreateUser(this.userData).subscribe({
      next: () => {
        alert('User created successfully!');
        this.router.navigate(['/admin/users']); // Navigate back to the user list
      },
      error: (err: any) => {
        console.error('Failed to create user', err);
        alert('Error: ' + err.error.message);
      }
    });
  }
  
  cancel(): void {
    this.router.navigate(['/admin/users']); // Navigate back without saving
  }
}