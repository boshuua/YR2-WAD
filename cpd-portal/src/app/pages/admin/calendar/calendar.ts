import { Component, OnInit } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CourseService } from '../../../core/services/course.service';
import { UserService } from '../../../core/services/user.service';
import { ToastService } from '../../../core/services/toast.service';
import { LoadingService } from '../../../core/services/loading.service';
import { Course } from '../../../core/models/course.model';
import { User } from '../../../core/models/user.model';
import { AssignCoursePayload } from '../../../core/models/dashboard.model';

interface CalendarDay {
  date: Date;
  isCurrentMonth: boolean;
  isToday: boolean;
  courses: { id: number; course_id: number; course_title: string }[];
}

@Component({
  selector: 'app-admin-calendar',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './calendar.html',
  styleUrls: ['./calendar.css'],
  providers: [DatePipe],
})
export class CalendarComponent implements OnInit {
  currentDate = new Date();
  daysInMonth: CalendarDay[] = [];
  weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  showModal = false;

  users: User[] = [];
  courses: Course[] = [];

  assignmentData: AssignCoursePayload & { date: string } = {
    user_id: 0,
    course_id: 0,
    date: '',
  };

  isLoading = false;
  scheduledCourses: Course[] = [];

  constructor(
    private courseService: CourseService,
    private userService: UserService,
    private toastService: ToastService,
    private loadingService: LoadingService,
    private datePipe: DatePipe,
  ) {}

  ngOnInit(): void {
    this.generateCalendar();
    this.loadResources();
  }

  loadResources(): void {
    this.loadingService.show();

    this.userService.getUsers().subscribe({
      next: (data) => {
        this.users = data;
      },
      error: (err) => console.error('Failed to load users', err),
    });

    this.courseService.getCourses('locked').subscribe({
      next: (data) => {
        this.courses = data;
      },
      error: (err) => console.error('Failed to load locked courses', err),
    });

    this.courseService.getCourses('active').subscribe({
      next: (data) => {
        this.scheduledCourses = data;
        this.mapCoursesToCalendar();
        this.loadingService.hide();
      },
      error: (err) => {
        console.error('Failed to load active courses', err);
        this.loadingService.hide();
      },
    });
  }

  generateCalendar(): void {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    let startDayOfWeek = firstDay.getDay() - 1;
    if (startDayOfWeek === -1) startDayOfWeek = 6;

    const days: CalendarDay[] = [];

    for (let i = startDayOfWeek; i > 0; i--) {
      days.push({
        date: new Date(year, month, 1 - i),
        isCurrentMonth: false,
        isToday: false,
        courses: [],
      });
    }

    for (let i = 1; i <= lastDay.getDate(); i++) {
      const date = new Date(year, month, i);
      days.push({
        date,
        isCurrentMonth: true,
        isToday: this.isSameDate(date, new Date()),
        courses: [],
      });
    }

    const totalCells = 42;
    const remaining = totalCells - days.length;
    for (let i = 1; i <= remaining; i++) {
      days.push({
        date: new Date(year, month + 1, i),
        isCurrentMonth: false,
        isToday: false,
        courses: [],
      });
    }

    this.daysInMonth = days;
    this.mapCoursesToCalendar();
  }

  prevMonth(): void {
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

    this.daysInMonth.forEach((day) => {
      day.courses = [];
      const dayTime = day.date.getTime();

      const coursesActive = this.scheduledCourses.filter((c) => {
        if (!c.start_date || !c.end_date) return false;
        const start = new Date(c.start_date).setHours(0, 0, 0, 0);
        const end = new Date(c.end_date).setHours(23, 59, 59, 999);
        return dayTime >= start && dayTime <= end;
      });

      coursesActive.forEach((c) => {
        day.courses.push({ id: c.id, course_id: c.id, course_title: c.title });
      });
    });
  }

  onDayClick(day: CalendarDay): void {
    if (day.isCurrentMonth) {
      this.assignmentData.date = this.formatDateForInput(day.date);
    }
  }

  openModal(): void {
    this.assignmentData.date = this.formatDateForInput(new Date());
    this.assignmentData.user_id = 0;
    this.assignmentData.course_id = 0;
    this.showModal = true;
  }

  closeModal(): void {
    this.showModal = false;
  }

  assignTraining(): void {
    if (
      !this.assignmentData.user_id ||
      !this.assignmentData.course_id ||
      !this.assignmentData.date
    ) {
      this.toastService.error('Please fill all fields');
      return;
    }

    this.loadingService.show();
    const payload: AssignCoursePayload = {
      user_id: this.assignmentData.user_id,
      course_id: this.assignmentData.course_id,
      start_date: this.assignmentData.date,
    };

    this.userService.assignCourse(payload).subscribe({
      next: () => {
        this.toastService.success('Training Assigned Successfully');
        this.loadingService.hide();
        this.closeModal();
      },
      error: (err) => {
        console.error(err);
        this.loadingService.hide();
        this.toastService.error(err.error?.message || 'Assignment Failed');
      },
    });
  }

  private isSameDate(d1: Date, d2: Date): boolean {
    return (
      d1.getDate() === d2.getDate() &&
      d1.getMonth() === d2.getMonth() &&
      d1.getFullYear() === d2.getFullYear()
    );
  }

  private formatDateForInput(date: Date): string {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
  }
}
