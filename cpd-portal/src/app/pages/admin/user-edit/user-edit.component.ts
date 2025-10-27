import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
// Ensure this path is correct for your project structure
import { AuthService } from '../../../service/auth.service';
import { switchMap } from 'rxjs/operators';

@Component({
  selector: 'app-user-edit',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './user-edit.component.html',
  styleUrls: ['./user-edit.component.css']
})
export class UserEditComponent implements OnInit {
  userId: number | null = null;
  // Initialize userData structure
  userData: any = {
    id: null,
    first_name: '',
    last_name: '',
    email: '',
    job_title: '',
    access_level: 'user' // Default access level
  };
  isLoading = true; // Flag for loading state
  errorMessage = ''; // To display errors

  constructor(
    private route: ActivatedRoute, // To get route parameters (like the user ID)
    private router: Router,       // To navigate after saving or cancelling
    private authService: AuthService // The service to interact with the backend API
  ) {}

  ngOnInit(): void {
    // This runs when the component loads
    this.route.paramMap.pipe( // Get the route parameters as an observable
      switchMap(params => {    // Use switchMap to handle the async call based on params
        const id = params.get('id'); // Get the 'id' parameter from the URL (e.g., /admin/users/edit/123)
        if (id) {
          this.userId = +id; // Convert the string ID from URL to a number
          // *** Call the correct service method to fetch user data ***
          return this.authService.getUserById(this.userId);
        } else {
          // If no ID is found in the URL, redirect back to the user list
          this.router.navigate(['/admin/users']);
          // Throw an error to stop further execution in this observable chain
          throw new Error('User ID not found in route');
        }
      })
    ).subscribe({
      // This runs when the getUserById call is successful
      next: (user) => {
        // The backend `get_users.php?id=...` should return a single user object
        if (user && user.id) { // Check if a valid user object was returned
           // Copy the fetched user data into the component's userData property
           this.userData = { ...user };
           // Remove password from the form data (don't display or handle it here)
           delete this.userData.password;
        } else {
            // If the API didn't return a valid user for the ID
            this.errorMessage = 'User not found.';
            // Optionally, you could redirect back:
            // this.router.navigate(['/admin/users']);
        }
        this.isLoading = false; // Data loaded (or not found), stop showing loading indicator
      },
      // This runs if the getUserById call fails
      error: (err) => {
        console.error('Failed to load user data', err);
        this.errorMessage = 'Error loading user data: ' + (err.error?.message || err.message);
        this.isLoading = false; // Stop loading indicator even on error
         // Optionally, redirect back on error:
         // this.router.navigate(['/admin/users']);
      }
    });
  }

  // This method is called when the form is submitted
  onSubmit(): void {
    // Double-check if userId is available
    if (!this.userId) {
      alert('Cannot update user without a valid ID.');
      return;
    }

    // Create a copy of the form data to send to the API
    const updateData = { ...this.userData };
    // The backend `admin_update_user.php` expects the ID in the URL, not the body
    delete updateData.id;

    // *** Call the correct service method to update the user ***
    this.authService.adminUpdateUser(this.userId, updateData).subscribe({
      // This runs if the update is successful
      next: () => {
        alert('User updated successfully');
        this.router.navigate(['/admin/users']); // Navigate back to the user list
      },
      // This runs if the update fails
      error: (err: any) => {
        console.error('Failed to update user', err);
        alert('Error updating user: ' + (err.error?.message || 'Unknown error'));
      }
    });
  }

  // This method is called when the "Cancel" button is clicked
  cancel(): void {
    this.router.navigate(['/admin/users']); // Navigate back to the user list without saving
  }
}