import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../../core/services/auth.service';
import { RouterLink } from '@angular/router';
import { ConfirmModalComponent } from '../../../shared/components/confirm-modal/confirm-modal.component';
import { ToastService } from '../../../core/services/toast.service';
import { LoadingService } from '../../../core/services/loading.service';
import { User } from '../../../core/models/user.model';

@Component({
  selector: 'app-user-list',
  standalone: true,
  // Add ConfirmModalComponent to imports
  imports: [CommonModule, RouterLink, ConfirmModalComponent],
  templateUrl: './user-list.component.html',
  styleUrls: ['./user-list.component.css']
})
export class UserListComponent implements OnInit {
  users: User[] = [];
  showDeleteConfirmModal = false;
  userToDeleteId: number | null = null;
  userToDeleteName: string = '';

  constructor(private authService: AuthService, private toastService: ToastService, private loadingService: LoadingService) { }

  ngOnInit(): void {
    this.loadUsers();
  }

  loadUsers(): void {
    this.loadingService.show();
    // *** Corrected: Call getUsers ***
    this.authService.getUsers().subscribe({
      next: (data) => {
        this.users = data;
        this.loadingService.hide();
      },
      error: (err) => {
        this.loadingService.hide();
        console.error('Failed to load users', err);
        this.toastService.error('Error loading users: ' + (err.error?.message || err.message));
      }
    });
  }
  promptDeleteUser(userId: number, userName: string): void {
    console.log('Prompting delete for user ID:', userId);
    this.userToDeleteId = userId;
    this.userToDeleteName = userName;
    // Try this:
    setTimeout(() => {
      this.showDeleteConfirmModal = true;
    }, 0);
  }
  confirmDelete(): void {
    if (this.userToDeleteId !== null) {
      this.authService.adminDeleteUser(this.userToDeleteId).subscribe({
        next: (response) => {
          this.toastService.success(response.message || 'User deleted successfully!');
          this.users = this.users.filter(user => user.id !== this.userToDeleteId);
          this.closeDeleteModal();
        },
        error: (err) => {
          console.error('Failed to delete user', err);
          this.toastService.error('Error deleting user: ' + (err.error?.message || err.message));
          this.closeDeleteModal();
        }
      });
    } else {
      console.error("User ID to delete is null");
      this.closeDeleteModal();
    }
  }

  cancelDelete(): void {
    this.closeDeleteModal();
  }

  /** Helper to reset modal state */
  private closeDeleteModal(): void {
    this.showDeleteConfirmModal = false;
    this.userToDeleteId = null;
    this.userToDeleteName = '';
  }
}