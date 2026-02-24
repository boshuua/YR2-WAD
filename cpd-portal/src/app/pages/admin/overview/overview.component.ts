import { Component, OnInit } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { CourseService } from '../../../core/services/course.service';
import { Course } from '../../../core/models/course.model';
import { ActivityLog } from '../../../core/models/dashboard.model';

interface CalendarDay {
  day: number | null;
  isToday: boolean;
  isCurrentMonth: boolean;
  courses: Course[];
}

@Component({
  selector: 'app-overview',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './overview.component.html',
  styleUrls: ['./overview.component.css'],
  providers: [DatePipe],
})
export class OverviewComponent implements OnInit {
  activityLog: ActivityLog[] = [];
  isLoadingLog = true;
  logLoadError = '';
  currentPage = 1;
  itemsPerPage = 5;
  totalLogs = 0;

  currentDate = new Date();
  calendarDays: CalendarDay[] = [];
  currentMonth = '';
  currentYear = 0;
  courses: Course[] = [];

  constructor(
    private courseService: CourseService,
    public datePipe: DatePipe,
  ) {}

  ngOnInit(): void {
    this.loadActivityLog();
    this.loadCourses();
  }

  loadCourses(): void {
    this.courseService.getCourses().subscribe({
      next: (courses) => {
        this.courses = courses;
        this.generateCalendar();
      },
      error: (err) => {
        console.error('Failed to load courses', err);
        this.generateCalendar();
      },
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

    for (let i = 0; i < firstDay; i++) {
      this.calendarDays.push({ day: null, isToday: false, isCurrentMonth: false, courses: [] });
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const isToday =
        day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
      const currentDayDate = new Date(year, month, day);
      const coursesOnDay = this.getCoursesForDay(currentDayDate);
      this.calendarDays.push({ day, isToday, isCurrentMonth: true, courses: coursesOnDay });
    }
  }

  getCoursesForDay(date: Date): Course[] {
    return this.courses.filter((course) => {
      if (!course.start_date || !course.end_date) return false;

      const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
      const startOnly = new Date(
        new Date(course.start_date).getFullYear(),
        new Date(course.start_date).getMonth(),
        new Date(course.start_date).getDate(),
      );
      const endOnly = new Date(
        new Date(course.end_date).getFullYear(),
        new Date(course.end_date).getMonth(),
        new Date(course.end_date).getDate(),
      );

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

  loadActivityLog(): void {
    this.isLoadingLog = true;
    this.logLoadError = '';
    this.courseService.getActivityLog(50).subscribe({
      next: (logs) => {
        this.totalLogs = logs.length;
        this.activityLog = logs;
        this.isLoadingLog = false;
      },
      error: (err) => {
        console.error('Failed to load activity log', err);
        this.logLoadError = 'Could not load activity log.';
        this.isLoadingLog = false;
      },
    });
  }

  get paginatedLogs(): ActivityLog[] {
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    return this.activityLog.slice(startIndex, startIndex + this.itemsPerPage);
  }

  get totalPages(): number {
    return Math.ceil(this.totalLogs / this.itemsPerPage);
  }

  nextPage(): void {
    if (this.currentPage < this.totalPages) this.currentPage++;
  }

  previousPage(): void {
    if (this.currentPage > 1) this.currentPage--;
  }

  goToPage(page: number): void {
    this.currentPage = page;
  }

  get pageNumbers(): number[] {
    return Array.from({ length: this.totalPages }, (_, i) => i + 1);
  }
}
