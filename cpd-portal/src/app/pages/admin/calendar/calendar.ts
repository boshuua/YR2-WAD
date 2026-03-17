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

interface CourseSegment {
  id: number;
  title: string;
  gridRow: number;
  gridColumnStart: number;
  gridColumnEnd: number;
  lane: number;
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
  Math = Math;
  currentDate = new Date();
  daysInMonth: CalendarDay[] = [];
  courseSegments: CourseSegment[] = [];
  weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  // ... existing members ...
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

    this.courseSegments = [];
    const laneMap = new Map<string, number>(); // key: "row-col", value: lane mask or similar

    // We'll track which lanes are occupied in each row/col
    const rowLanes: boolean[][][] = Array.from(
      { length: 7 },
      () => Array.from({ length: 8 }, () => []), // 7 rows (max), 8 cols (1-indexed), list of occupied lanes
    );

    this.scheduledCourses.forEach((course) => {
      if (!course.start_date || !course.end_date) return;

      const start = new Date(course.start_date);
      start.setHours(0, 0, 0, 0);
      const end = new Date(course.end_date);
      end.setHours(23, 59, 59, 999);

      // Find indices in daysInMonth
      const indices = this.daysInMonth
        .map((day, idx) => ({ day, idx }))
        .filter((item) => item.day.date >= start && item.day.date <= end)
        .map((item) => item.idx);

      if (indices.length === 0) return;

      // Group into continuous segments per row
      let currentSegment: { row: number; startCol: number; endCol: number } | null = null;
      const segments: { row: number; startCol: number; endCol: number }[] = [];

      indices.forEach((idx) => {
        const row = Math.floor(idx / 7) + 1;
        const col = (idx % 7) + 1;

        if (!currentSegment || currentSegment.row !== row || currentSegment.endCol !== col - 1) {
          if (currentSegment) segments.push(currentSegment);
          currentSegment = { row, startCol: col, endCol: col };
        } else {
          currentSegment.endCol = col;
        }
      });
      if (currentSegment) segments.push(currentSegment);

      // Assign lane for the whole course (simplification: same lane across all its segments if possible)
      let lane = 0;
      let laneFound = false;
      while (!laneFound) {
        let conflict = false;
        segments.forEach((seg) => {
          for (let c = seg.startCol; c <= seg.endCol; c++) {
            if (rowLanes[seg.row] && rowLanes[seg.row][c] && rowLanes[seg.row][c][lane]) {
              conflict = true;
              break;
            }
          }
          if (conflict) return;
        });

        if (!conflict) {
          laneFound = true;
        } else {
          lane++;
        }
      }

      // Mark lanes as occupied and add segments
      segments.forEach((seg) => {
        for (let c = seg.startCol; c <= seg.endCol; c++) {
          if (!rowLanes[seg.row]) rowLanes[seg.row] = [];
          if (!rowLanes[seg.row][c]) rowLanes[seg.row][c] = [];
          rowLanes[seg.row][c][lane] = true;
        }

        this.courseSegments.push({
          id: course.id,
          title: course.title,
          gridRow: seg.row,
          gridColumnStart: seg.startCol,
          gridColumnEnd: seg.endCol + 1,
          lane: lane,
        });
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
