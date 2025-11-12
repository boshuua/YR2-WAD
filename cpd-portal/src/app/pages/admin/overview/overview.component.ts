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
  // Properties for activity log
  activityLog: any[] = [];
  isLoadingLog = true;
  logLoadError = '';
  currentPage = 1;
  itemsPerPage = 5;
  totalLogs = 0;

  // Calendar
  currentDate = new Date();
  calendarDays: any[] = [];
  currentMonth: string = '';
  currentYear: number = 0;
  courses: any[] = [];

  // Inject AuthService and DatePipe
  constructor(private authService: AuthService, public datePipe: DatePipe) {} // Make datePipe public

  ngOnInit(): void {
    this.loadActivityLog();
    this.loadCourses();
  }

  loadCourses(): void {
    this.authService.getCourses().subscribe({
      next: (courses) => {
        this.courses = courses;
        this.generateCalendar();
      },
      error: (err) => {
        console.error('Failed to load courses', err);
        this.generateCalendar();
      }
    });
  }

  generateCalendar(): void {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    this.currentMonth = this.currentDate.toLocaleDateString('en-US', { month: 'long' });
    this.currentYear = year;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = new Date();

    this.calendarDays = [];

    // Add empty cells for days before the first day of month
    for (let i = 0; i < firstDay; i++) {
      this.calendarDays.push({ day: null, isToday: false, isCurrentMonth: false, courses: [] });
    }

    // Add days of the month
    for (let day = 1; day <= daysInMonth; day++) {
      const isToday = day === today.getDate() &&
                      month === today.getMonth() &&
                      year === today.getFullYear();

      const currentDayDate = new Date(year, month, day);
      const coursesOnDay = this.getCoursesForDay(currentDayDate);

      this.calendarDays.push({
        day,
        isToday,
        isCurrentMonth: true,
        courses: coursesOnDay
      });
    }
  }

  getCoursesForDay(date: Date): any[] {
    return this.courses.filter(course => {
      if (!course.start_date || !course.end_date) return false;

      const courseStart = new Date(course.start_date);
      const courseEnd = new Date(course.end_date);

      // Check if the date falls within the course date range
      const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
      const startOnly = new Date(courseStart.getFullYear(), courseStart.getMonth(), courseStart.getDate());
      const endOnly = new Date(courseEnd.getFullYear(), courseEnd.getMonth(), courseEnd.getDate());

      return dateOnly >= startOnly && dateOnly <= endOnly;
    });
  }

  previousMonth(): void {
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
    this.generateCalendar();
  }

  nextMonth(): void {
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
    this.generateCalendar();
  }

  // Method to load activity logs with pagination
  loadActivityLog(): void {
    this.isLoadingLog = true;
    this.logLoadError = '';
    // Load more logs for pagination purposes
    this.authService.getActivityLog(50).subscribe({
      next: (logs) => {
        this.totalLogs = logs.length;
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

  get paginatedLogs(): any[] {
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;
    return this.activityLog.slice(startIndex, endIndex);
  }

  get totalPages(): number {
    return Math.ceil(this.totalLogs / this.itemsPerPage);
  }

  nextPage(): void {
    if (this.currentPage < this.totalPages) {
      this.currentPage++;
    }
  }

  previousPage(): void {
    if (this.currentPage > 1) {
      this.currentPage--;
    }
  }

  goToPage(page: number): void {
    this.currentPage = page;
  }

  get pageNumbers(): number[] {
    return Array.from({ length: this.totalPages }, (_, i) => i + 1);
  }
}