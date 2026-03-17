import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { UserService } from '../../../core/services/user.service';
import { ToastService } from '../../../core/services/toast.service';
import { LoadingService } from '../../../core/services/loading.service';
import { User } from '../../../core/models/user.model';

@Component({
  selector: 'app-user-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './user-settings.component.html',
  styleUrls: ['./user-settings.component.css'],
})
export class UserSettingsComponent implements OnInit {
  user: Partial<User> = {
    first_name: '',
    last_name: '',
    email: ''
  };

  passwordData = {
    current_password: '',
    new_password: '',
    confirm_password: ''
  };

  isUpdatingProfile = false;
  isUpdatingPassword = false;

  constructor(
    private userService: UserService,
    private toastService: ToastService,
    private loadingService: LoadingService
  ) {}

  ngOnInit(): void {
    this.loadProfile();
  }

  loadUserInfo(): void {
    if (typeof sessionStorage !== 'undefined') {
      const userJson = sessionStorage.getItem('currentUser');
      if (userJson) {
        const storedUser = JSON.parse(userJson);
        this.user = {
          first_name: storedUser.first_name,
          last_name: storedUser.last_name,
          email: storedUser.email
        };
      }
    }
  }

  loadProfile(): void {
    this.loadUserInfo();
  }

  updateProfile(): void {
    if (!this.user.first_name || !this.user.last_name || !this.user.email) {
      this.toastService.error('Please fill in all required fields.');
      return;
    }

    this.isUpdatingProfile = true;
    this.userService.updateSelf(this.user).subscribe({
      next: (res) => {
        this.toastService.success('Profile updated successfully!');
        // Update session storage
        if (typeof sessionStorage !== 'undefined') {
          const userJson = sessionStorage.getItem('currentUser');
          if (userJson) {
            const storedUser = JSON.parse(userJson);
            const updatedUser = { ...storedUser, ...this.user };
            sessionStorage.setItem('currentUser', JSON.stringify(updatedUser));
            // Trigger a page reload or event to update sidebar?
            // For now, just notifying the user is fine.
          }
        }
        this.isUpdatingProfile = false;
      },
      error: (err) => {
        this.toastService.error(err.error?.message || 'Failed to update profile.');
        this.isUpdatingProfile = false;
      }
    });
  }

  updatePassword(): void {
    if (!this.passwordData.current_password || !this.passwordData.new_password) {
      this.toastService.error('Please fill in all password fields.');
      return;
    }

    if (this.passwordData.new_password !== this.passwordData.confirm_password) {
      this.toastService.error('New passwords do not match.');
      return;
    }

    if (this.passwordData.new_password.length < 6) {
      this.toastService.error('New password must be at least 6 characters.');
      return;
    }

    this.isUpdatingPassword = true;
    this.userService.updateSelfPassword({
      current_password: this.passwordData.current_password,
      new_password: this.passwordData.new_password
    }).subscribe({
      next: () => {
        this.toastService.success('Password changed successfully!');
        this.passwordData = {
          current_password: '',
          new_password: '',
          confirm_password: ''
        };
        this.isUpdatingPassword = false;
      },
      error: (err) => {
        this.toastService.error(err.error?.message || 'Failed to change password.');
        this.isUpdatingPassword = false;
      }
    });
  }
}
