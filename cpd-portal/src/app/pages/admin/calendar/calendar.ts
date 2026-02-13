import { Component, OnInit } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { LoadingService } from '../../../core/services/loading.service';

interface Assignment {
  id: number;
  user_id: number;
  course_id: number;
  user_name?: string;
  course_title?: string;
}

interface Day {
  date: Date;
  isCurrentMonth: boolean;
  isToday: boolean;
  assignments: Assignment[];
}

@Component({
  selector: 'app-admin-calendar', // Changed selector
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './calendar.html',
  styleUrls: ['./calendar.css'],
  providers: [DatePipe]
})
export class CalendarComponent implements OnInit {
  currentDate = new Date();
  daysInMonth: Day[] = [];
  weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  // Assignment Modal State
  showModal = false;

  users: any[] = [];
  courses: any[] = []; // Only Locked Courses

  // Form Model
  assignmentData = {
    // We need to type these properly or handle the nulls in template
    userId: null as number | null,
    courseId: null as number | null,
    date: ''
  };

  isLoading = false;

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private loadingService: LoadingService,
    private datePipe: DatePipe
  ) { }

  ngOnInit(): void {
    this.generateCalendar();
    this.loadResources();
  }

  scheduledCourses: any[] = [];

  loadResources(): void {
    this.loadingService.show();

    // 1. Load Users (for assignment modal)
    this.authService.getUsers().subscribe({
      next: (data) => {
        this.users = data;
      },
      error: (err) => console.error('Failed to load users', err)
    });

    // 2. Load Locked Courses (Templates for assignment modal)
    this.authService.getCourses('locked').subscribe({
      next: (data) => {
        this.courses = data;
      },
      error: (err) => console.error('Failed to load locked courses', err)
    });

    // 3. Load Active/Scheduled Courses (For Calendar Display)
    this.authService.getCourses('active').subscribe({
      next: (data) => {
        this.scheduledCourses = data;
        this.mapCoursesToCalendar();
        this.loadingService.hide();
      },
      error: (err) => {
        console.error('Failed to load active courses', err);
        this.loadingService.hide();
      }
    });
  }

  generateCalendar(): void {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    // Set to first day of month
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    // Adjust for Monday start
    // If firstDay.getDay() is 1 (Mon), padding is 0.
    // If firstDay.getDay() is 0 (Sun), padding is 6.
    let startDayOfWeek = firstDay.getDay() - 1;
    if (startDayOfWeek === -1) startDayOfWeek = 6;

    // Clear days
    const days: Day[] = [];

    // Previous Month padding
    for (let i = startDayOfWeek; i > 0; i--) {
      // Create new date object to avoid reference issues
      const date = new Date(year, month, 1 - i);
      days.push({ date: date, isCurrentMonth: false, isToday: false, assignments: [] });
    }

    // Current Month
    for (let i = 1; i <= lastDay.getDate(); i++) {
      const date = new Date(year, month, i);
      const isToday = this.isSameDate(date, new Date());
      days.push({ date: date, isCurrentMonth: true, isToday: isToday, assignments: [] });
    }

    // Next Month padding (fill up to 42 cells for 6 rows grid)
    const totalCells = 42;
    const remaining = totalCells - days.length;
    for (let i = 1; i <= remaining; i++) {
      const date = new Date(year, month + 1, i);
      days.push({ date: date, isCurrentMonth: false, isToday: false, assignments: [] });
    }

    this.daysInMonth = days;
    this.mapCoursesToCalendar(); // Remap when view changes
  }

  prevMonth(): void {
    // Ensure we update the object reference
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
    this.generateCalendar();
  }

  nextMonth(): void {
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
    this.generateCalendar();
  }

  today(): void {
    this.currentDate = new Date();
    this.generateCalendar();
  }

  mapCoursesToCalendar(): void {
    if (!this.daysInMonth.length || !this.scheduledCourses.length) return;

    this.daysInMonth.forEach(day => {
      day.assignments = []; // Clear previous
      const dayDateStr = this.formatDateForInput(day.date);

      // Find courses starting on this day
      // Note: This logic places the "Course Start" on the calendar.
      // If we want to show duration, we'd check if date is within range.
      // For now, let's show Start Date.

      const coursesStarting = this.scheduledCourses.filter(c => {
        if (!c.start_date) return false;
        const startDate = c.start_date.split(' ')[0].split('T')[0];
        return startDate === dayDateStr;
      });

      coursesStarting.forEach(c => {
        day.assignments.push({
          id: c.id,
          user_id: 0, // Not relevant for course instance view
          course_id: c.id,
          user_name: '', // Not relevant
          course_title: c.title
        });
      });
    });
  }

  onDayClick(day: Day): void {
    // Interaction disabled as per requirement
    // this.assignmentData.date = this.formatDateForInput(day.date);
    // this.assignmentData.userId = null;
    // this.assignmentData.courseId = null;
    // this.showModal = true;
  }

  openModal(): void {
    this.assignmentData.date = this.formatDateForInput(new Date());
    this.assignmentData.userId = null;
    this.assignmentData.courseId = null;
    this.showModal = true;
  }

  closeModal(): void {
    this.showModal = false;
  }

  assignTraining(): void {
    if (!this.assignmentData.userId || !this.assignmentData.courseId || !this.assignmentData.date) {
      this.toastService.error('Please fill all fields');
      return;
    }

    this.loadingService.show();
    const payload = {
      user_id: this.assignmentData.userId,
      course_id: this.assignmentData.courseId,
      start_date: this.assignmentData.date
    };

    console.log("Assigning Payload:", payload);

    this.authService.assignCourse(payload).subscribe({
      next: (res) => {
        this.toastService.success('Training Assigned Successfully');
        this.loadingService.hide();
        this.closeModal();
      },
      error: (err) => {
        console.error(err);
        this.loadingService.hide();
        this.toastService.error(err.error?.message || 'Assignment Failed');
      }
    });
  }

  private isSameDate(d1: Date, d2: Date): boolean {
    return d1.getDate() === d2.getDate() &&
      d1.getMonth() === d2.getMonth() &&
      d1.getFullYear() === d2.getFullYear();
  }

  private formatDateForInput(date: Date): string {
    // Return YYYY-MM-DD
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
  }
}
