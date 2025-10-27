import { Component, OnInit } from '@angular/core'; // Import OnInit
import { CommonModule, DatePipe } from '@angular/common'; // Import DatePipe
import { RouterLink } from '@angular/router'; // Import RouterLink
import { AuthService } from '../../../service/auth.service'; // Adjust path if needed

@Component({
  selector: 'app-overview',
  standalone: true,
  imports: [CommonModule, RouterLink], // Add RouterLink
  templateUrl: './overview.component.html',
  styleUrls: ['./overview.component.css'], // Reference the CSS file
  providers: [DatePipe] // Add DatePipe to providers
})
export class OverviewComponent implements OnInit { // Implement OnInit
  recentUsers: any[] = [];
  isLoadingUsers = true;
  userLoadError = '';

  // Properties for activity log
  activityLog: any[] = [];
  isLoadingLog = true;
  logLoadError = '';

  // Inject AuthService and DatePipe
  constructor(private authService: AuthService, public datePipe: DatePipe) {} // Make datePipe public

  ngOnInit(): void {
    this.loadRecentUsers();
    this.loadActivityLog(); // Load activity logs on init
  }

  loadRecentUsers(): void {
    this.isLoadingUsers = true;
    this.userLoadError = '';
    this.authService.getUsers().subscribe({ // Use the getUsers method
      next: (allUsers) => {
        // Get the first 5 users (or sort by creation date if available)
        this.recentUsers = allUsers.slice(0, 5);
        this.isLoadingUsers = false;
      },
      error: (err) => {
        console.error('Failed to load recent users', err);
        this.userLoadError = 'Could not load recent users.';
        this.isLoadingUsers = false;
      }
    });
  }

  // Method to load activity logs
  loadActivityLog(): void {
    this.isLoadingLog = true;
    this.logLoadError = '';
    this.authService.getActivityLog(10).subscribe({ // Fetch last 10 entries for overview
      next: (logs) => {
        this.activityLog = logs;
        this.isLoadingLog = false;
      },
      error: (err) => {
        console.error('Failed to load activity log', err);
        this.logLoadError = 'Could not load activity log.';
        this.isLoadingLog = false;
      }
    });
  }
}